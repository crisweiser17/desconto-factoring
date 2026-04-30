<?php
require_once 'auth_check.php';
require_once 'db_connection.php';

$message = '';
$messageType = '';

if (isset($_GET['status'])) {
    if ($_GET['status'] === 'success') {
        $message = $_GET['msg'] ?? 'Operação realizada com sucesso!';
        $messageType = 'success';
    } elseif ($_GET['status'] === 'error') {
        $message = $_GET['msg'] ?? 'Ocorreu um erro na operação.';
        $messageType = 'danger';
    }
}

// Buscar usuários
try {
    $stmt = $pdo->query("SELECT id, email, criado_em FROM usuarios ORDER BY id ASC");
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = "Erro ao carregar usuários: " . $e->getMessage();
    $messageType = "danger";
    $usuarios = [];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Usuários - Sistema</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
    <?php require_once 'menu.php'; ?>

    <div class="container-fluid px-3 px-md-4 mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h1>Gerenciar Usuários</h1>
            <a href="form_usuario.php" class="btn btn-primary"><i class="bi bi-person-plus-fill"></i> Novo Usuário</a>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Usuário / Email</th>
                                <th>Data de Criação</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($usuarios) > 0): ?>
                                <?php foreach ($usuarios as $u): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($u['id']); ?></td>
                                        <td><strong><?php echo htmlspecialchars($u['email']); ?></strong></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($u['criado_em'])); ?></td>
                                        <td class="text-end">
                                            <a href="form_usuario.php?id=<?php echo $u['id']; ?>" class="btn btn-sm btn-outline-secondary" title="Editar Senha">
                                                <i class="bi bi-key-fill"></i> Alterar Senha
                                            </a>
                                            <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                            <a href="excluir_usuario.php?id=<?php echo $u['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Tem certeza que deseja excluir o usuário <?php echo htmlspecialchars($u['email']); ?>?');" title="Excluir Usuário">
                                                <i class="bi bi-trash-fill"></i>
                                            </a>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-outline-danger" disabled title="Você não pode excluir a si mesmo"><i class="bi bi-trash-fill"></i></button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4">Nenhum usuário encontrado.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
