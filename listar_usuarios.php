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

// KPIs
$totalUsuarios = count($usuarios);
$novosMes = 0;
foreach ($usuarios as $u) {
    if (!empty($u['criado_em']) && strtotime($u['criado_em']) >= strtotime('-30 days')) {
        $novosMes++;
    }
}

function iniciaisEmail($email) {
    $local = strtolower(trim((string)$email));
    if ($local === '') return '?';
    $local = explode('@', $local)[0];
    $partes = preg_split('/[._\-]+/', $local);
    $r = '';
    foreach ($partes as $p) {
        if ($p === '') continue;
        if (strlen($r) >= 2) break;
        $r .= mb_strtoupper(mb_substr($p, 0, 1));
    }
    return $r ?: mb_strtoupper(mb_substr($local, 0, 2));
}

function avatarColor($id) {
    $cls = ['b1', 'b2', 'b3', 'b4'];
    return $cls[((int)$id) % count($cls)];
}

function dataHumana($dataStr) {
    if (!$dataStr) return '<span class="text-muted">—</span>';
    try {
        $dt = new DateTime($dataStr);
        $hoje = new DateTime();
        $dias = (int) $hoje->diff($dt)->days;
        if ($dias === 0) return 'hoje, ' . $dt->format('H:i');
        if ($dias === 1) return 'ontem, ' . $dt->format('H:i');
        if ($dias < 30) return "há $dias dias";
        return $dt->format('d/m/Y');
    } catch (Exception $e) { return '—'; }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuários do Sistema</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --profit: #198754; --profit-soft: #d1f0dc;
            --warn: #b76b00; --warn-soft: #fff3d6;
            --danger: #b02a37;
            --info: #0a4ea8; --info-soft: #eef4ff;
            --neutral: #6c757d;
            --surface: #ffffff; --surface-2: #f6f8fb;
            --border: #e3e8ef;
        }
        body { background: #eef2f7; font-size: 0.95rem; }

        .page-toolbar {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: 12px; padding: 14px 18px; margin-bottom: 18px;
            display: flex; justify-content: space-between; align-items: center;
            flex-wrap: wrap; gap: 12px;
        }
        .page-toolbar h1 { font-size: 1.35rem; margin: 0; font-weight: 600; }
        .id-pill {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 4px 10px; border-radius: 999px;
            background: var(--info-soft); color: var(--info);
            font-size: 0.78rem; font-weight: 700; margin-left: 6px;
        }

        .kpi-strip {
            display: grid; grid-template-columns: repeat(3, 1fr);
            gap: 12px; margin-bottom: 18px;
        }
        @media (max-width: 768px) { .kpi-strip { grid-template-columns: 1fr; } }
        .kpi-card {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: 12px; padding: 14px 16px;
        }
        .kpi-card .k-icon {
            width: 36px; height: 36px; border-radius: 10px;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 1.1rem; margin-bottom: 6px;
        }
        .k-icon.b-blue   { background: var(--info-soft); color: var(--info); }
        .k-icon.b-green  { background: var(--profit-soft); color: var(--profit); }
        .k-icon.b-purple { background: #efe8fa; color: #6f42c1; }
        .kpi-card .k-label {
            font-size: 0.72rem; color: var(--neutral);
            text-transform: uppercase; letter-spacing: 0.04em; font-weight: 600;
        }
        .kpi-card .k-value { font-size: 1.4rem; font-weight: 700; line-height: 1.1; margin-top: 2px; }
        .kpi-card .k-trend { font-size: 0.78rem; color: var(--neutral); margin-top: 4px; }

        .data-table-wrap {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: 14px; overflow: hidden;
        }
        .data-table-head {
            display: flex; justify-content: space-between; align-items: center;
            padding: 14px 18px;
            border-bottom: 1px solid var(--border);
            background: var(--surface-2);
        }
        .data-table-head h3 { margin: 0; font-size: 0.95rem; font-weight: 600; }
        .data-table-head .meta { font-size: 0.78rem; color: var(--neutral); }
        .data-table { width: 100%; margin-bottom: 0; font-size: 0.9rem; }
        .data-table thead th {
            background: var(--surface-2);
            font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.04em;
            color: var(--neutral); font-weight: 700;
            border-bottom: 1px solid var(--border); border-top: none;
            padding: 10px 12px; white-space: nowrap;
        }
        .data-table tbody td {
            padding: 12px; vertical-align: middle;
            border-top: 1px solid var(--border);
        }
        .data-table tbody tr:hover { background: #fafbfd; }

        .user-cell { display: flex; align-items: center; gap: 10px; }
        .user-cell .avatar {
            width: 36px; height: 36px; border-radius: 50%;
            color: #fff; display: inline-flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 0.82rem; flex-shrink: 0;
        }
        .user-cell .avatar.b1 { background: linear-gradient(135deg, #0d6efd, #15b079); }
        .user-cell .avatar.b2 { background: linear-gradient(135deg, #fd7e14, #b76b00); }
        .user-cell .avatar.b3 { background: linear-gradient(135deg, #0a8754, #15b079); }
        .user-cell .avatar.b4 { background: linear-gradient(135deg, #d63384, #b02a37); }
        .user-cell .name { font-weight: 600; font-size: 0.92rem; line-height: 1.2; }
        .user-cell .meta { font-size: 0.75rem; color: var(--neutral); }

        .self-chip {
            display: inline-block; font-size: 0.68rem; font-weight: 700;
            padding: 2px 8px; border-radius: 999px;
            background: var(--profit-soft); color: var(--profit);
            text-transform: uppercase; letter-spacing: 0.04em;
            margin-left: 6px; vertical-align: middle;
        }

        .row-actions { display: inline-flex; gap: 4px; }
        .row-actions .btn-ico {
            width: 32px; height: 32px;
            display: inline-flex; align-items: center; justify-content: center;
            border-radius: 6px; color: var(--neutral); background: transparent;
            border: 1px solid transparent; text-decoration: none; transition: background 0.15s;
        }
        .row-actions .btn-ico:hover { background: var(--surface-2); border-color: var(--border); color: var(--info); }
        .row-actions .btn-ico.danger:hover { color: var(--danger); border-color: #f1c8cd; background: #fbecee; }
        .row-actions .btn-ico:disabled,
        .row-actions .btn-ico[disabled] { opacity: 0.35; cursor: not-allowed; }

        .empty-state {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: 14px; padding: 60px 20px;
            text-align: center; color: var(--neutral);
        }
        .empty-state i { font-size: 3.5rem; opacity: 0.4; }
    </style>
</head>
<body>
    <?php require_once 'menu.php'; ?>

    <div class="container-fluid px-3 px-md-4 mt-4" style="max-width: 1100px;">

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                <i class="bi bi-<?php echo $messageType === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill'; ?>"></i>
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Toolbar -->
        <div class="page-toolbar">
            <div>
                <h1>
                    <i class="bi bi-shield-lock-fill text-info"></i>
                    Usuários do Sistema
                    <span class="id-pill"><i class="bi bi-hash"></i><?php echo $totalUsuarios; ?> ativos</span>
                </h1>
                <div class="text-muted small mt-1">Quem tem acesso ao painel · gerencie senhas e permissões</div>
            </div>
            <div>
                <a href="form_usuario.php" class="btn btn-success">
                    <i class="bi bi-person-plus-fill"></i> Novo Usuário
                </a>
            </div>
        </div>

        <!-- KPIs -->
        <div class="kpi-strip">
            <div class="kpi-card">
                <div class="k-icon b-blue"><i class="bi bi-people-fill"></i></div>
                <div class="k-label">Total de usuários</div>
                <div class="k-value"><?php echo $totalUsuarios; ?></div>
                <div class="k-trend">com acesso ativo</div>
            </div>
            <div class="kpi-card">
                <div class="k-icon b-green"><i class="bi bi-person-plus-fill"></i></div>
                <div class="k-label">Novos no mês</div>
                <div class="k-value"><?php echo $novosMes; ?></div>
                <div class="k-trend">criados nos últimos 30 dias</div>
            </div>
            <div class="kpi-card">
                <div class="k-icon b-purple"><i class="bi bi-person-badge-fill"></i></div>
                <div class="k-label">Sua sessão</div>
                <div class="k-value" style="font-size:0.95rem;line-height:1.4;word-break:break-word;">
                    <?php echo htmlspecialchars($_SESSION['user_email'] ?? 'desconhecido'); ?>
                </div>
                <div class="k-trend">você está logado</div>
            </div>
        </div>

        <!-- Tabela -->
        <?php if (empty($usuarios)): ?>
            <div class="empty-state">
                <i class="bi bi-shield-lock"></i>
                <h4 class="mt-3 text-muted">Nenhum usuário cadastrado</h4>
                <p class="mb-3">Comece adicionando o primeiro usuário do sistema.</p>
                <a href="form_usuario.php" class="btn btn-primary"><i class="bi bi-person-plus-fill"></i> Novo Usuário</a>
            </div>
        <?php else: ?>
            <div class="data-table-wrap">
                <div class="data-table-head">
                    <h3><?php echo $totalUsuarios; ?> usuário<?php echo $totalUsuarios === 1 ? '' : 's'; ?></h3>
                    <div class="meta">Ordenados por data de criação</div>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width:60px;">ID</th>
                                <th>Usuário</th>
                                <th>Criado em</th>
                                <th class="text-center" style="width:140px;">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $u):
                                $isMe = (int)$u['id'] === (int)($_SESSION['user_id'] ?? 0);
                            ?>
                                <tr>
                                    <td class="text-muted">#<?php echo (int)$u['id']; ?></td>
                                    <td>
                                        <div class="user-cell">
                                            <div class="avatar <?php echo avatarColor($u['id']); ?>"><?php echo iniciaisEmail($u['email']); ?></div>
                                            <div>
                                                <div class="name">
                                                    <?php echo htmlspecialchars($u['email']); ?>
                                                    <?php if ($isMe): ?>
                                                        <span class="self-chip"><i class="bi bi-person-check-fill"></i> Você</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="meta">login por e-mail</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-muted small"><?php echo dataHumana($u['criado_em']); ?></td>
                                    <td class="text-center">
                                        <div class="row-actions">
                                            <a href="form_usuario.php?id=<?php echo (int)$u['id']; ?>" class="btn-ico" title="Alterar senha">
                                                <i class="bi bi-key-fill"></i>
                                            </a>
                                            <?php if (!$isMe): ?>
                                                <a href="excluir_usuario.php?id=<?php echo (int)$u['id']; ?>" class="btn-ico danger" title="Excluir usuário"
                                                   onclick="return confirm('Tem certeza que deseja excluir o usuário <?php echo htmlspecialchars($u['email'], ENT_QUOTES); ?>?');">
                                                    <i class="bi bi-trash3-fill"></i>
                                                </a>
                                            <?php else: ?>
                                                <button class="btn-ico" disabled title="Você não pode excluir a si mesmo">
                                                    <i class="bi bi-trash3-fill"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
