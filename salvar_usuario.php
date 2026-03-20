<?php
require_once 'auth_check.php';
require_once 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? filter_var($_POST['id'], FILTER_VALIDATE_INT) : null;
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $senha = $_POST['senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';

    // Validações básicas
    if (empty($senha) || strlen($senha) < 6) {
        header("Location: listar_usuarios.php?status=error&msg=" . urlencode("A senha deve ter pelo menos 6 caracteres."));
        exit;
    }

    if ($senha !== $confirmar_senha) {
        header("Location: listar_usuarios.php?status=error&msg=" . urlencode("As senhas não coincidem."));
        exit;
    }

    // Cria o Hash seguro da senha
    $hash = password_hash($senha, PASSWORD_DEFAULT);

    try {
        if ($id) {
            // Edição: Apenas atualiza a senha (não permite mudar email por segurança básica)
            $stmt = $pdo->prepare("UPDATE usuarios SET senha_hash = :hash WHERE id = :id");
            $stmt->bindParam(':hash', $hash);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            header("Location: listar_usuarios.php?status=success&msg=" . urlencode("Senha do usuário atualizada com sucesso."));
        } else {
            // Criação de novo usuário
            if (empty($email)) {
                header("Location: listar_usuarios.php?status=error&msg=" . urlencode("O e-mail/usuário é obrigatório."));
                exit;
            }

            // Verifica se o email já existe
            $checkStmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = :email");
            $checkStmt->bindParam(':email', $email);
            $checkStmt->execute();
            if ($checkStmt->fetch()) {
                header("Location: listar_usuarios.php?status=error&msg=" . urlencode("Este e-mail/usuário já está cadastrado."));
                exit;
            }

            $stmt = $pdo->prepare("INSERT INTO usuarios (email, senha_hash) VALUES (:email, :hash)");
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':hash', $hash);
            $stmt->execute();

            header("Location: listar_usuarios.php?status=success&msg=" . urlencode("Usuário criado com sucesso."));
        }
    } catch (PDOException $e) {
        error_log("Erro ao salvar usuário: " . $e->getMessage());
        header("Location: listar_usuarios.php?status=error&msg=" . urlencode("Erro no banco de dados ao salvar o usuário."));
    }
} else {
    header("Location: listar_usuarios.php");
}
exit;
?>
