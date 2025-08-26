<?php
// login.php
// Inicia a sessão para poder exibir mensagens de erro, se houver
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Se o usuário já está logado, redireciona para a página principal
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header('Location: index.php'); // Ou relatorio.php, ou a página que você preferir
    exit;
}

// Verifica se há erro (vindo do processa_login.php)
$error_message = '';
if (isset($_GET['error']) && $_GET['error'] == 1) {
    $error_message = "Senha incorreta. Tente novamente.";
}

// Pega a URL de redirecionamento, se houver
$redirect_url = isset($_GET['redirect']) ? $_GET['redirect'] : 'index.php';

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Calculadora Desconto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background-color: #f8f9fa;
        }
        .login-card {
            max-width: 400px;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="card login-card shadow">
        <div class="card-body p-4">
            <h3 class="card-title text-center mb-4">Acesso Restrito</h3>
            <form action="processa_login.php" method="post">
                <?php if ($error_message): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>
                <div class="mb-3">
                    <label for="password" class="form-label">Senha</label>
                    <input type="password" class="form-control" id="password" name="password" required autofocus>
                </div>
                <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect_url); ?>">
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Entrar</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
