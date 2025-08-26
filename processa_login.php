<?php
// processa_login.php - CORRIGIDO PARA CLOUDWAYS/NGINX
if (session_status() === PHP_SESSION_NONE) {
    // Configurações específicas para Cloudways ANTES de session_start
    ini_set('session.gc_maxlifetime', 86400); // 24 horas
    ini_set('session.gc_probability', 0); // Desabilitar GC automático
    ini_set('session.cookie_lifetime', 86400); // 24 horas
    ini_set('session.cookie_secure', 0);
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    
    session_start();
}

// --- SENHA RAW (NÃO RECOMENDADO POR SEGURANÇA!) ---
$senha_correta_raw = 'Qazwsx123@';
// Se precisar consultar a senha, ela está aqui. Mas isso é inseguro!
// ---------------------------------------------------

// URL padrão para redirecionar após login
$redirect_url_default = 'index.php'; // Ou relatorio.php
$redirect_url = isset($_POST['redirect']) && !empty($_POST['redirect']) ? $_POST['redirect'] : $redirect_url_default;

// Verifica se a senha foi enviada via POST
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['password'])) {
    $senha_digitada = $_POST['password'];

    // --- Verificação com Senha Raw (Comparação direta - NÃO RECOMENDADO) ---
    if ($senha_digitada === $senha_correta_raw) {
        // Senha correta! Regenera o ID da sessão por segurança
        session_regenerate_id(true);

        // Define as variáveis de sessão
        $_SESSION['loggedin'] = true;
        $_SESSION['login_timestamp'] = time();
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // FORÇA a gravação da sessão
        session_write_close();
        session_start();

        // Redireciona para a página de onde o usuário veio ou para a padrão
        header("Location: " . $redirect_url);
        exit;
    } else {
        // Senha incorreta - redireciona de volta para login com erro
        // Passa o redirect original de volta para não perdê-lo
        $redirect_param = isset($_POST['redirect']) ? '?redirect=' . urlencode($_POST['redirect']) . '&error=1' : '?error=1';
        header("Location: login.php" . $redirect_param);
        exit;
    }
} else {
    // Se não foi POST ou senha não foi enviada, redireciona para login
    header("Location: login.php");
    exit;
}
?>
