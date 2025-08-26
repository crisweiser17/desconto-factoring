<?php
// envia_email.php
// Recebe dados via POST e envia por e-mail para depuração.

// --- Configurações de E-mail ---
define('EMAIL_DESTINATARIO', 'cristianweiser@gmail.com'); // Seu e-mail
define('EMAIL_REMETENTE', 'factoring@crisweiser.com'); // Use um e-mail válido do seu domínio se possível
define('EMAIL_ASSUNTO', 'Alerta Recebido do Sistema Factoring');

// --- Configuração de Envio (true para usar SMTP no futuro, false para usar mail() agora) ---
$usarSmtp = false; // Mude para true quando for configurar SMTP

// --- Configurações SMTP (PREENCHER QUANDO $usarSmtp = true) ---
// Estas são ignoradas se $usarSmtp for false
define('SMTP_HOST', 'smtp.example.com');        // Ex: smtp.gmail.com, smtp.seudominio.com
define('SMTP_PORT', 587);                       // Porta comum: 587 (TLS), 465 (SSL), 25 (sem criptografia)
define('SMTP_USERNAME', 'seu_email@example.com'); // Seu usuário SMTP (geralmente seu e-mail)
define('SMTP_PASSWORD', 'sua_senha_smtp');       // Sua senha SMTP ou senha de aplicativo
define('SMTP_ENCRYPTION', 'tls');               // 'tls', 'ssl' ou '' (sem criptografia)
define('SMTP_AUTH', true);                      // Geralmente true

// --- Fim das Configurações ---

// Verifica se a requisição é POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Pega todos os dados POST
    $postData = $_POST;

    // Prepara o corpo do e-mail
    $corpoEmail = "Dados recebidos via POST em: " . date('d/m/Y H:i:s') . "\n";
    $corpoEmail .= "============================================\n\n";

    if (empty($postData)) {
        $corpoEmail .= "Nenhum dado foi recebido no corpo do POST.\n";
    } else {
        // Formata os dados recebidos
        foreach ($postData as $chave => $valor) {
            // Se o valor for um array (pouco provável aqui, mas por segurança)
            if (is_array($valor)) {
                $valorFormatado = print_r($valor, true);
            } else {
                $valorFormatado = $valor;
            }
            $corpoEmail .= htmlspecialchars($chave) . ": " . htmlspecialchars($valorFormatado) . "\n";
        }
    }

    $corpoEmail .= "\n============================================\n";
    $corpoEmail .= "IP Remoto: " . ($_SERVER['REMOTE_ADDR'] ?? 'N/A') . "\n";

    // Prepara os cabeçalhos do e-mail
    $headers = "From: Sistema Factoring <" . EMAIL_REMETENTE . ">\r\n";
    $headers .= "Reply-To: " . EMAIL_REMETENTE . "\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    $envioOk = false;
    $metodoEnvio = "";

    // Envia o e-mail
    if ($usarSmtp) {
        // --- LÓGICA SMTP (Requer Biblioteca Externa) ---
        $metodoEnvio = "SMTP (Não implementado neste script base)";
        error_log("Tentativa de envio via SMTP (requer biblioteca PHPMailer/Symfony Mailer). Dados: " . $corpoEmail);
        // Para usar SMTP, você precisará instalar uma biblioteca como PHPMailer:
        // Exemplo básico com PHPMailer (PRECISA INSTALAR: composer require phpmailer/phpmailer):
        /*
        require 'vendor/autoload.php'; // Se usar Composer
        // Ou inclua os arquivos do PHPMailer manualmente
        // require 'path/to/PHPMailer/src/Exception.php';
        // require 'path/to/PHPMailer/src/PHPMailer.php';
        // require 'path/to/PHPMailer/src/SMTP.php';

        // use PHPMailer\PHPMailer\PHPMailer;
        // use PHPMailer\PHPMailer\Exception;

        $mail = new PHPMailer(true);
        try {
            //Server settings
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = SMTP_AUTH;
            $mail->Username   = SMTP_USERNAME;
            $mail->Password   = SMTP_PASSWORD;
            $mail->SMTPSecure = SMTP_ENCRYPTION; // PHPMailer::ENCRYPTION_STARTTLS ou PHPMailer::ENCRYPTION_SMTPS
            $mail->Port       = SMTP_PORT;
            $mail->CharSet    = 'UTF-8';

            //Recipients
            $mail->setFrom(EMAIL_REMETENTE, 'Sistema Factoring');
            $mail->addAddress(EMAIL_DESTINATARIO); // Adiciona o destinatário
            $mail->addReplyTo(EMAIL_REMETENTE, 'Sistema Factoring');

            // Content
            $mail->isHTML(false); // Define e-mail como texto plano
            $mail->Subject = EMAIL_ASSUNTO;
            $mail->Body    = $corpoEmail;

            $mail->send();
            $envioOk = true;
        } catch (Exception $e) {
            error_log("Erro ao enviar e-mail via SMTP: {$mail->ErrorInfo}");
            $envioOk = false;
        }
        */
        echo "Configurado para SMTP, mas requer biblioteca PHPMailer/Symfony Mailer. Verifique os logs.\n"; // Mensagem para teste direto

    } else {
        // --- LÓGICA mail() PADRÃO DO PHP ---
        $metodoEnvio = "PHP mail()";
        // ATENÇÃO: mail() depende da configuração do servidor e pode falhar ou cair em spam.
        if (mail(EMAIL_DESTINATARIO, EMAIL_ASSUNTO, $corpoEmail, $headers)) {
            $envioOk = true;
        } else {
            error_log("Falha ao enviar e-mail usando a função mail() do PHP.");
            $envioOk = false;
        }
    }

    // Resposta e Log
    if ($envioOk) {
        $msg = "E-mail de depuração enviado com sucesso para " . EMAIL_DESTINATARIO . " usando " . $metodoEnvio . ".";
        echo $msg . "\n";
        error_log($msg . " Conteúdo: " . $corpoEmail);
    } else {
        $msg = "Falha ao enviar e-mail de depuração para " . EMAIL_DESTINATARIO . " usando " . $metodoEnvio . ".";
        echo $msg . "\n";
        // O erro específico já foi logado dentro da lógica de envio
    }

} else {
    // Responde se não for POST
    header("HTTP/1.1 405 Method Not Allowed");
    echo "Método não permitido. Use POST.";
    exit;
}
?>
