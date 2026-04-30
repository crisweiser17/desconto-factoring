<?php
// envia_alertas.php
// Este script deve ser executado diariamente via Cron Job

// --- Configurações Essenciais ---
// Defina o fuso horário correto para suas datas
date_default_timezone_set('America/Sao_Paulo'); // Ajuste para seu fuso horário

// Inclui a conexão com o banco (ajuste o caminho se necessário)
require_once 'db_connection.php'; // Garante que $pdo está disponível

// --- Configurações da API WhatsApp (NÃO OFICIAL - USE COM CAUTELA) ---
define('WHATSAPP_WEBHOOK_URL', 'https://webhook.site/1f6a9d64-646e-4913-aac6-e162aed7d2f4'); // <<< COLOQUE A URL DO SEU WEBHOOK AQUI
//define('WHATSAPP_WEBHOOK_URL', 'envia_email.php'); // <<< COLOQUE A URL DO SEU WEBHOOK AQUI
define('WHATSAPP_API_KEY', '<SUA_API_KEY_SE_NECESSARIO>'); // <<< Deixe vazio '' se não precisar de chave/token

// --- Dados do Dono do Sistema (Para receber os alertas) ---
define('DONO_NOME', 'Cristian Weiser'); // Seu nome para referência
define('DONO_TELEFONE_WHATSAPP', '+5519998989999'); // <<< SEU NÚMERO AQUI (formato internacional)

// --- Função para Enviar Mensagem via Webhook ---
/**
 * Envia uma mensagem via POST para o webhook da API WhatsApp.
 * Adapte o formato do $postData conforme a API específica que você está usando.
 *
 * @param string $numero Destinatário no formato internacional (ex: +5519...)
 * @param string $mensagem Texto da mensagem
 * @return bool True se sucesso (envio HTTP ok), False se falha.
 */
function enviarMensagemWhatsApp($numero, $mensagem) {
    // Formato comum: JSON com 'phoneNumber' e 'message'. VERIFIQUE A DOCUMENTAÇÃO DA SUA API!
    $postData = json_encode([
        'phoneNumber' => $numero,
        'message' => $mensagem
        // Outros campos que sua API possa exigir...
        // 'apiKey' => WHATSAPP_API_KEY // Exemplo se a API key for no corpo
    ]);

    $curl = curl_init(WHATSAPP_WEBHOOK_URL);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json', // Mude se sua API esperar x-www-form-urlencoded
        'Content-Length: ' . strlen($postData)
        // Adicione outros headers se necessário (ex: 'Authorization: Bearer ' . WHATSAPP_API_KEY)
    ]);
    // Descomente se precisar ignorar verificação SSL (não recomendado em produção)
    // curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    // curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curlError = curl_error($curl);
    curl_close($curl);

    if ($curlError) {
        error_log("Erro cURL ao enviar WhatsApp para $numero: " . $curlError);
        return false;
    }

    if ($httpCode >= 200 && $httpCode < 300) {
        // Requisição HTTP foi bem sucedida (não garante entrega da msg pela API)
        error_log("Webhook WhatsApp enviado para $numero. Resposta HTTP: $httpCode. Resposta: $response");
        return true;
    } else {
        error_log("Erro HTTP ao enviar WhatsApp para $numero. Código: $httpCode. Resposta: $response");
        return false;
    }
}

// --- Lógica Principal do Cron Job ---

echo "--- Iniciando verificação de recebíveis vencendo hoje (" . date('d/m/Y') . ") ---\n";
error_log("Cron Job envia_alertas.php iniciado em " . date('Y-m-d H:i:s'));

$hoje = date('Y-m-d');
$recebiveisVencendo = [];

try {
    // Busca recebíveis com vencimento hoje e status "Em Aberto" (ou outro status relevante)
    // Junta com operacoes e sacados para obter os detalhes
    $sql = "SELECT r.id as recebivel_id, r.operacao_id, r.valor_original,
                   o.data_operacao,
                   s.empresa as cedente_nome, s.telefone as sacado_telefone
            FROM recebiveis r
            JOIN operacoes o ON r.operacao_id = o.id
            JOIN clientes s ON o.cedente_id = s.id
            WHERE r.data_vencimento = :hoje AND r.status = 'Em Aberto'"; // <<< Verifique se 'Em Aberto' é o status correto

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':hoje', $hoje, PDO::PARAM_STR);
    $stmt->execute();
    $recebiveisVencendo = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $msgErro = "Erro no banco de dados ao buscar recebíveis: " . $e->getMessage();
    echo $msgErro . "\n";
    error_log($msgErro);
    // Considerar enviar um alerta de erro para o dono aqui também
    // enviarMensagemWhatsApp(DONO_TELEFONE_WHATSAPP, "Erro CRÍTICO no Cron Job de Alertas: Não foi possível buscar recebíveis. Verifique os logs.");
    exit(1); // Termina o script com erro
}

if (empty($recebiveisVencendo)) {
    $msg = "Nenhum recebível 'Em Aberto' vencendo hoje (" . date('d/m/Y', strtotime($hoje)) . ").";
    echo $msg . "\n";
    error_log($msg);
} else {
    $totalValor = 0;
    $listaMensagem = "";
    $count = 0;

    foreach ($recebiveisVencendo as $r) {
        $count++;
        $valorFmt = number_format($r['valor_original'], 2, ',', '.');
        $listaMensagem .= "\n- Cedente: " . $r['cedente_nome'] . " (Op: " . $r['operacao_id'] . ", Rec: " . $r['recebivel_id'] . ") - R$ " . $valorFmt;
        $totalValor += $r['valor_original'];
    }

    $totalValorFmt = number_format($totalValor, 2, ',', '.');
    $mensagemFinal = "*ALERTA DE VENCIMENTOS* (". date('d/m/Y', strtotime($hoje)) .")\n\n";
    $mensagemFinal .= "Olá " . DONO_NOME . ", você possui *" . $count . "* recebíve(is) vencendo hoje:";
    $mensagemFinal .= $listaMensagem;
    $mensagemFinal .= "\n\n*Valor Total Vencendo Hoje: R$ " . $totalValorFmt . "*";
    $mensagemFinal .= "\n\n_Sistema Factoring_";

    echo "Enviando alerta para " . DONO_NOME . " (" . DONO_TELEFONE_WHATSAPP . ")...\n";
    error_log("Preparando para enviar alerta de vencimento para " . DONO_TELEFONE_WHATSAPP . " com " . $count . " recebíveis.");

    if (enviarMensagemWhatsApp(DONO_TELEFONE_WHATSAPP, $mensagemFinal)) {
        echo "Alerta enviado com sucesso.\n";
        error_log("Alerta de vencimento enviado com sucesso para " . DONO_TELEFONE_WHATSAPP);
    } else {
        echo "Falha ao enviar alerta.\n";
        error_log("Falha ao enviar alerta de vencimento para " . DONO_TELEFONE_WHATSAPP);
    }
}

echo "--- Verificação concluída ---\n";
error_log("Cron Job envia_alertas.php concluído em " . date('Y-m-d H:i:s'));
exit(0); // Termina com sucesso

?>
