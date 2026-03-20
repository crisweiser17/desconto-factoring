<?php
require_once 'auth_check.php';
require_once 'db_connection.php';

$id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : null;

if (!$id) {
    header("Location: listar_usuarios.php?status=error&msg=" . urlencode("ID de usuário inválido."));
    exit;
}

// Previne que o usuário exclua a si mesmo
if ($id == $_SESSION['user_id']) {
    header("Location: listar_usuarios.php?status=error&msg=" . urlencode("Você não pode excluir o seu próprio usuário enquanto estiver logado."));
    exit;
}

try {
    // Verifica se é o último usuário no sistema (para não trancar fora)
    $countStmt = $pdo->query("SELECT COUNT(*) FROM usuarios");
    $totalUsuarios = $countStmt->fetchColumn();

    if ($totalUsuarios <= 1) {
        header("Location: listar_usuarios.php?status=error&msg=" . urlencode("Você não pode excluir o único usuário do sistema."));
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    
    if ($stmt->execute() && $stmt->rowCount() > 0) {
        header("Location: listar_usuarios.php?status=success&msg=" . urlencode("Usuário excluído com sucesso."));
    } else {
        header("Location: listar_usuarios.php?status=error&msg=" . urlencode("Usuário não encontrado."));
    }
} catch (PDOException $e) {
    error_log("Erro ao excluir usuário: " . $e->getMessage());
    header("Location: listar_usuarios.php?status=error&msg=" . urlencode("Erro no banco de dados ao tentar excluir o usuário."));
}
exit;
?>
