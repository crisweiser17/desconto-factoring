<?php
// logout.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Remove todas as variáveis de sessão
$_SESSION = array();

// Destrói a sessão
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// Redireciona para a página de login
header("Location: login.php?logout=1"); // Adiciona ?logout=1 se quiser mostrar msg na pág de login
exit;
?>
