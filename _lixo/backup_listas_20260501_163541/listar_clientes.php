<?php require_once 'auth_check.php'; ?><?php
// --- APENAS PARA DEBUG - REMOVER/COMENTAR EM PRODUÇÃO ---
// ini_set('display_errors', 1); // Descomente para ver erros no navegador
// ini_set('display_startup_errors', 1); // Descomente para ver erros de inicialização
// error_reporting(E_ALL); // Descomente para reportar todos os tipos de erros
// --- FIM DEBUG ---

require_once 'db_connection.php'; // Conexão $pdo

// --- Configurações de Paginação, Ordenação e Busca ---
$results_per_page = 15;

// Parâmetros da URL
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'nome'; // Padrão
$dir = isset($_GET['dir']) && in_array(strtolower($_GET['dir']), ['asc', 'desc']) ? strtolower($_GET['dir']) : 'asc'; // Padrão

// Colunas permitidas para ordenação
$allowed_sort_columns = [
    'id' => 'id',
    'nome' => 'nome',
    'empresa' => 'empresa',
    'email' => 'email',
    'telefone' => 'telefone',
    'documento_principal' => 'documento_principal'
];

// Validar coluna de ordenação
if (!array_key_exists($sort, $allowed_sort_columns)) {
    $sort = 'nome';
}
$sort_column_sql = $allowed_sort_columns[$sort];

// Calcular offset
$offset = ($page - 1) * $results_per_page;
if ($offset < 0) $offset = 0;

// --- Construção da Query ---
$params_count = [];
$params_data = [];
$whereClauses = [];

// Adicionar cláusula de busca
if (!empty($search)) {
    $whereClauses[] = "(nome LIKE :search_nome OR empresa LIKE :search_empresa OR email LIKE :search_email OR telefone LIKE :search_tel OR documento_principal LIKE :search_doc)";
    $search_param = "%" . $search . "%";

    // Parâmetros para a contagem
    $params_count[':search_nome'] = $search_param;
    $params_count[':search_empresa'] = $search_param;
    $params_count[':search_email'] = $search_param;
    $params_count[':search_tel'] = $search_param;
    $params_count[':search_doc'] = $search_param;
    $params_count[':search_tipo'] = $search_param;

    // Parâmetros para os dados
    $params_data[':search_nome'] = $search_param;
    $params_data[':search_empresa'] = $search_param;
    $params_data[':search_email'] = $search_param;
    $params_data[':search_tel'] = $search_param;
    $params_data[':search_doc'] = $search_param;
    $params_data[':search_tipo'] = $search_param;
}

$whereSql = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

// --- Query para Contar o Total ---
$total_results = 0;
try {
    $countSql = "SELECT COUNT(id) FROM clientes $whereSql";
    $stmtCount = $pdo->prepare($countSql);
    $stmtCount->execute($params_count);
    $total_results = (int)$stmtCount->fetchColumn();
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Erro Crítico [Count Clientes]: " . htmlspecialchars($e->getMessage()) . "</div>";
    $clientes = [];
    $total_pages = 0;
}

$total_pages = ($results_per_page > 0) ? ceil($total_results / $results_per_page) : 0;
if ($total_pages == 0) $total_pages = 1;

// Ajustar a página atual se for inválida
if ($page > $total_pages) {
    $page = $total_pages;
    $offset = ($page - 1) * $results_per_page;
    if ($offset < 0) $offset = 0;
} elseif ($page < 1) {
    $page = 1;
    $offset = 0;
}

// --- Query para Buscar os Dados ---
$clientes = []; // Inicializa
try {
    $sql = "SELECT id, empresa as nome, email, telefone, empresa, documento_principal FROM clientes
            $whereSql
            ORDER BY $sort_column_sql $dir
            LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);

    // Bind busca
    if (!empty($search)) {
        $stmt->bindParam(':search_nome', $params_data[':search_nome'], PDO::PARAM_STR);
        $stmt->bindParam(':search_empresa', $params_data[':search_empresa'], PDO::PARAM_STR);
        $stmt->bindParam(':search_email', $params_data[':search_email'], PDO::PARAM_STR);
        $stmt->bindParam(':search_tel', $params_data[':search_tel'], PDO::PARAM_STR);
        $stmt->bindParam(':search_doc', $params_data[':search_doc'], PDO::PARAM_STR);
        $stmt->bindParam(':search_tipo', $params_data[':search_tipo'], PDO::PARAM_STR);
    }
    // Bind paginação
    $stmt->bindParam(':limit', $results_per_page, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);

    $stmt->execute();
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Erro Crítico [Data Clientes]: " . htmlspecialchars($e->getMessage()) . "</div>";
    // $clientes já está vazio
}

// Helper function para links de ordenação
function getSortLink($column, $text, $currentSort, $currentDir, $currentSearch) {
    $newDir = ($currentSort === $column && $currentDir === 'asc') ? 'desc' : 'asc';
    $icon = '';
    if ($currentSort === $column) {
        $icon = $currentDir === 'asc' ? ' ▲' : ' ▼';
    }
    $searchParam = $currentSearch ? '&search=' . urlencode($currentSearch) : '';
    $pageParam = ''; // Reset page on sort

    return "<a href=\"?sort=$column&dir=$newDir$searchParam$pageParam\">" . htmlspecialchars($text) . $icon . "</a>";
}

// Função auxiliar para formatar CNPJ para exibição
function formatDocumento($documento) {
    $docLimpo = preg_replace('/\D/', '', $documento); // Remove não-dígitos
    if (empty($docLimpo)) {
        return '-';
    }
    // Formato CNPJ: 99.999.999/9999-99
    if (strlen($docLimpo) == 14) {
        return substr($docLimpo, 0, 2) . '.' .
               substr($docLimpo, 2, 3) . '.' .
               substr($docLimpo, 5, 3) . '/' .
               substr($docLimpo, 8, 4) . '-' .
               substr($docLimpo, 12, 2);
    }
    return $documento; // Retorna original se não puder formatar
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Clientes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
     <style>
        th, td { vertical-align: middle; }
        .action-icon { margin: 0 5px; }
        th a { text-decoration: none; color: inherit; }
        th a:hover { color: #0056b3; }
        .pagination .page-link { color: #007bff; }
        .pagination .page-item.active .page-link { z-index: 3; color: #fff; background-color: #007bff; border-color: #007bff;}
        .pagination .page-item.disabled .page-link { color: #6c757d; pointer-events: none; background-color: #fff; border-color: #dee2e6; }
    </style>
</head>
<body>
    <?php require_once 'menu.php'; ?>

    <div class="container-fluid px-3 px-md-4 mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h1>Gerenciar Clientes</h1>
             <form method="GET" action="listar_clientes.php" class="d-flex ms-auto me-3" style="max-width: 300px;">
                <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort); ?>">
                <input type="hidden" name="dir" value="<?php echo htmlspecialchars($dir); ?>">
                <input class="form-control me-2" type="search" name="search" placeholder="Buscar cliente..." aria-label="Buscar" value="<?php echo htmlspecialchars($search); ?>">
                <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
                 <?php if (!empty($search)): ?>
                    <a href="?sort=<?php echo htmlspecialchars($sort); ?>&dir=<?php echo htmlspecialchars($dir); ?>" class="btn btn-outline-danger ms-2" title="Limpar Busca"><i class="bi bi-x-lg"></i></a>
                <?php endif; ?>
            </form>
            <a href="form_cliente.php" class="btn btn-success">
                <i class="bi bi-plus-lg"></i> Novo Cliente
            </a>
        </div>

        <?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">Cliente salvo com sucesso!<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
        <?php elseif (isset($_GET['status']) && $_GET['status'] == 'deleted'): ?>
             <div class="alert alert-success alert-dismissible fade show" role="alert">Cliente excluído com sucesso!<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
         <?php elseif (isset($_GET['status']) && $_GET['status'] == 'error'): ?>
             <div class="alert alert-danger alert-dismissible fade show" role="alert">Ocorreu um erro: <?php echo htmlspecialchars(isset($_GET['msg']) ? $_GET['msg'] : 'Erro desconhecido.'); ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
        <?php endif; ?>

        <?php if (empty($clientes) && $total_results == 0 && empty($search)): ?>
            <div class="alert alert-info">Nenhum cliente cadastrado ainda. <a href="form_cliente.php">Clique aqui para adicionar</a>.</div>
        <?php elseif (empty($clientes) && $total_results > 0): ?>
             <p class="alert alert-warning">Nenhum cliente encontrado para a busca: "<?php echo htmlspecialchars($search); ?>" nesta página.</p>
        <?php elseif (empty($clientes) && $total_results == 0): ?>
            <div class="alert alert-warning">Nenhum cliente encontrado para a busca: "<?php echo htmlspecialchars($search); ?>"</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 80px;"><?php echo getSortLink('id', 'ID', $sort, $dir, $search); ?></th>
                            <th><?php echo getSortLink('empresa', 'Empresa', $sort, $dir, $search); ?></th>
                            <th><?php echo getSortLink('email', 'Email', $sort, $dir, $search); ?></th>
                            <th><?php echo getSortLink('telefone', 'Telefone', $sort, $dir, $search); ?></th>
                            <th><?php echo getSortLink('documento_principal', 'CNPJ', $sort, $dir, $search); ?></th>
                            <th class="text-center" style="width: 120px;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clientes as $cliente): ?>
                            <tr>
                                <td><span class="badge bg-secondary"><?php echo $cliente['id']; ?></span></td>
                                <td><?php echo htmlspecialchars($cliente['empresa'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($cliente['email'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($cliente['telefone'] ?? '-'); ?></td>
                                <td><?php
                                    // Exibe o documento principal formatado (CNPJ)
                                    echo formatDocumento($cliente['documento_principal'] ?? '');
                                ?></td>
                                <td class="text-center">
                                    <a href="visualizar_cliente.php?id=<?php echo $cliente['id']; ?>" class="text-info action-icon" title="Visualizar Cliente"><i class="bi bi-eye-fill"></i></a>
                                    <a href="form_cliente.php?id=<?php echo $cliente['id']; ?>" class="text-primary action-icon" title="Editar Cliente"><i class="bi bi-pencil-square"></i></a>
                                    <a href="excluir_cliente.php?id=<?php echo $cliente['id']; ?>" class="text-danger action-icon delete-btn" title="Excluir Cliente" onclick="return confirm('Tem certeza que deseja excluir este cliente? Operações associadas a ele podem ficar sem referência.');"><i class="bi bi-trash3-fill"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

             <?php if ($total_pages > 1): ?>
                 <?php // --- Início Código Paginação --- ?>
                <nav aria-label="Paginação Clientes">
                    <ul class="pagination justify-content-center">
                         <?php $baseUrl = "?sort=" . urlencode($sort) . "&dir=" . urlencode($dir) . "&search=" . urlencode($search); ?>
                        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo $baseUrl . '&page=' . ($page - 1); ?>">&laquo;</a>
                        </li>
                        <?php
                            $start_page = max(1, $page - 2); $end_page = min($total_pages, $page + 2);
                            if ($page <= 3) $end_page = min($total_pages, 5);
                            if ($page >= $total_pages - 2) $start_page = max(1, $total_pages - 4);
                            if ($start_page > 1) {
                                echo '<li class="page-item"><a class="page-link" href="' . $baseUrl . '&page=1">1</a></li>';
                                if ($start_page > 2) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                            for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                            <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                <a class="page-link" href="<?php echo $baseUrl . '&page=' . $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor;
                            if ($end_page < $total_pages) {
                                if ($end_page < $total_pages - 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                echo '<li class="page-item"><a class="page-link" href="' . $baseUrl . '&page=' . $total_pages . '">' . $total_pages . '</a></li>';
                             }
                        ?>
                        <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo $baseUrl . '&page=' . ($page + 1); ?>">&raquo;</a>
                        </li>
                    </ul>
                </nav>
                 <p class="text-center text-muted">Página <?php echo $page; ?> de <?php echo $total_pages; ?> (Total: <?php echo $total_results; ?> clientes)</p>
                 <?php // --- Fim Código Paginação --- ?>
            <?php elseif($total_results > 0): ?>
                 <p class="text-center text-muted">Total: <?php echo $total_results; ?> cliente<?php echo $total_results == 1 ? '' : 's'; ?></p>
            <?php endif; ?>

        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // O onclick="return confirm(...)" já existe para exclusão.
    </script>
</body>
</html>