<?php require_once 'auth_check.php'; ?>
<?php
// --- APENAS PARA DEBUG - REMOVER/COMENTAR EM PRODUÇÃO ---
ini_set('display_errors', 1); // Descomente para ver erros no navegador
ini_set('display_startup_errors', 1); // Descomente para ver erros de inicialização
error_reporting(E_ALL); // Descomente para reportar todos os tipos de erros
// --- FIM DEBUG ---

require_once 'db_connection.php'; // Conexão $pdo
require_once 'funcoes_compensacao.php';
require_once 'functions.php'; // Funções auxiliares

// --- Configurações e Parâmetros ---
$items_per_page_options = [25, 50, 100];
$items_per_page = isset($_GET['per_page']) && in_array((int)$_GET['per_page'], $items_per_page_options) ? (int)$_GET['per_page'] : 25;
$results_per_page = $items_per_page;
setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'portuguese');

// Parâmetros GET (Paginação, Ordenação, Filtros Existentes, Busca)
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'data_vencimento'; // Padrão
$dir = isset($_GET['dir']) && in_array(strtolower($_GET['dir']), ['asc', 'desc']) ? strtolower($_GET['dir']) : 'asc'; // Padrão ASC para data

// Filtros existentes
// Filtro de status agora aceita múltiplos valores
$filtro_status = isset($_GET['status']) && is_array($_GET['status']) ? $_GET['status'] : [];
$filtro_data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : '';
$filtro_data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : '';
// Filtro por tipo de pagamento (múltipla seleção)
$filtro_tipo_pagamento = isset($_GET['tipo_pagamento']) && is_array($_GET['tipo_pagamento']) ? $_GET['tipo_pagamento'] : [];
// Busca
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Colunas permitidas para ordenação (Adicionado cedente_nome e tipo_pagamento)
$allowed_sort_columns = [
    'operacao_id' => 'r.operacao_id',
    'id' => 'r.id',
    'cedente_nome' => 's.empresa',
    'sacado_nome' => 'sac.empresa',
    'data_operacao' => 'o.data_operacao',
    'tipo_pagamento' => 'o.tipo_pagamento',
    'data_vencimento' => 'r.data_vencimento',
    'valor_original' => 'r.valor_original',
    'status' => 'r.status'
    // 'dias_p_vencimento' não é adicionado aqui porque é calculado no PHP e não diretamente no SQL para ordenação.
];

// Validar coluna de ordenação
if (!array_key_exists($sort, $allowed_sort_columns)) {
    $sort = 'data_vencimento';
}
$sort_column_sql = $allowed_sort_columns[$sort];

// Calcular offset
$offset = ($page - 1) * $results_per_page;
if ($offset < 0) $offset = 0;

// --- Construção da Query Dinâmica ---
$params_count = [];
$params_data = [];
$whereClauses = [];

// Filtro de status (múltiplos valores)
if (!empty($filtro_status)) {
    $placeholders = [];
    for ($i = 0; $i < count($filtro_status); $i++) {
        $placeholders[] = ":status_$i";
        $params_count[":status_$i"] = $filtro_status[$i];
        $params_data[":status_$i"] = $filtro_status[$i];
    }
    $whereClauses[] = "r.status IN (" . implode(',', $placeholders) . ")";
}

// Filtro de tipo de pagamento (múltiplos valores)
if (!empty($filtro_tipo_pagamento)) {
    $placeholders = [];
    for ($i = 0; $i < count($filtro_tipo_pagamento); $i++) {
        $placeholders[] = ":tipo_pagamento_$i";
        $params_count[":tipo_pagamento_$i"] = $filtro_tipo_pagamento[$i];
        $params_data[":tipo_pagamento_$i"] = $filtro_tipo_pagamento[$i];
    }
    $whereClauses[] = "o.tipo_pagamento IN (" . implode(',', $placeholders) . ")";
}

// Filtro de data de início
if ($filtro_data_inicio && DateTime::createFromFormat('Y-m-d', $filtro_data_inicio)) {
    $whereClauses[] = "r.data_vencimento >= :data_inicio";
    $params_count[':data_inicio'] = $filtro_data_inicio;
    $params_data[':data_inicio'] = $filtro_data_inicio;
} else {
    $filtro_data_inicio = '';
}

// Filtro de data de fim
if ($filtro_data_fim && DateTime::createFromFormat('Y-m-d', $filtro_data_fim)) {
    $whereClauses[] = "r.data_vencimento <= :data_fim";
    $params_count[':data_fim'] = $filtro_data_fim;
    $params_data[':data_fim'] = $filtro_data_fim;
} else {
    $filtro_data_fim = '';
}

// Filtro de Busca - Inclui busca pelo nome do cedente e sacado
if (!empty($search)) {
     $whereClauses[] = "(CAST(r.id AS CHAR) LIKE :search_rid OR CAST(r.operacao_id AS CHAR) LIKE :search_oid OR CAST(r.valor_original AS CHAR) LIKE :search_valor OR s.empresa LIKE :search_cedente OR sac.empresa LIKE :search_sacado)";
     $search_param = "%" . $search . "%";
     $search_valor_param = "%" . str_replace(',', '.', $search) . "%";

     $params_count[':search_rid'] = $search_param;
     $params_count[':search_oid'] = $search_param;
     $params_count[':search_valor'] = $search_valor_param;
     $params_count[':search_cedente'] = $search_param;
     $params_count[':search_sacado'] = $search_param;

     $params_data[':search_rid'] = $search_param;
     $params_data[':search_oid'] = $search_param;
     $params_data[':search_valor'] = $search_valor_param;
     $params_data[':search_cedente'] = $search_param;
     $params_data[':search_sacado'] = $search_param;
}

$whereSql = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

// --- Query para Contar o Total ---
$total_results = 0;
try {
    $countSql = "SELECT COUNT(r.id)
                 FROM recebiveis r
                 LEFT JOIN operacoes o ON r.operacao_id = o.id
                 LEFT JOIN cedentes s ON o.cedente_id = s.id
                 LEFT JOIN sacados sac ON r.sacado_id = sac.id
                 $whereSql";
    $stmtCount = $pdo->prepare($countSql);
    $stmtCount->execute($params_count);
    $total_results = (int)$stmtCount->fetchColumn();
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Erro Crítico [Count Recebíveis]: " . htmlspecialchars($e->getMessage()) . "</div>";
    $recebiveis = []; // Definir valores padrão para evitar erros no HTML
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


// --- Query para Buscar os Dados da Página Atual ---
$recebiveis = []; // Inicializa
try {
    $sql = "SELECT r.*, o.data_operacao, o.tipo_pagamento, r.tipo_recebivel, s.empresa AS cedente_nome,
                   sac.empresa AS sacado_nome,
                   COALESCE(SUM(c.valor_compensado), 0) as total_compensado
            FROM recebiveis r
            LEFT JOIN operacoes o ON r.operacao_id = o.id
            LEFT JOIN cedentes s ON o.cedente_id = s.id
            LEFT JOIN sacados sac ON r.sacado_id = sac.id
            LEFT JOIN compensacoes c ON r.id = c.recebivel_compensado_id
            $whereSql
            GROUP BY r.id, r.operacao_id, r.valor_original, r.data_vencimento, r.status,
                     o.data_operacao, o.tipo_pagamento, o.cedente_id, s.empresa, r.sacado_id, sac.empresa
            ORDER BY $sort_column_sql $dir
            LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);

    // Bind dos parâmetros de filtros e busca
    if (!empty($search)) {
         $stmt->bindParam(':search_rid', $params_data[':search_rid'], PDO::PARAM_STR);
         $stmt->bindParam(':search_oid', $params_data[':search_oid'], PDO::PARAM_STR);
         $stmt->bindParam(':search_valor', $params_data[':search_valor'], PDO::PARAM_STR);
         $stmt->bindParam(':search_cedente', $params_data[':search_cedente'], PDO::PARAM_STR);
         $stmt->bindParam(':search_sacado', $params_data[':search_sacado'], PDO::PARAM_STR);
    }
    if (!empty($filtro_status)) {
        for ($i = 0; $i < count($filtro_status); $i++) {
            $stmt->bindParam(":status_$i", $params_data[":status_$i"], PDO::PARAM_STR);
        }
    }
    if (!empty($filtro_tipo_pagamento)) {
        for ($i = 0; $i < count($filtro_tipo_pagamento); $i++) {
            $stmt->bindParam(":tipo_pagamento_$i", $params_data[":tipo_pagamento_$i"], PDO::PARAM_STR);
        }
    }
    if ($filtro_data_inicio) {
        $stmt->bindParam(':data_inicio', $params_data[':data_inicio'], PDO::PARAM_STR);
    }
    if ($filtro_data_fim) {
        $stmt->bindParam(':data_fim', $params_data[':data_fim'], PDO::PARAM_STR);
    }

    // Bind de paginação
    $stmt->bindParam(':limit', $results_per_page, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);

    $stmt->execute();
    $recebiveis = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Enriquecer dados com informações de compensação
    foreach ($recebiveis as &$recebivel) {
        $statusInfo = verificarStatusRecebivel($recebivel['id'], $pdo);
        $recebivel['status_real'] = $statusInfo['status'];
        $recebivel['saldo_disponivel'] = $statusInfo['saldo_disponivel'];
        $recebivel['percentual_compensado'] = $statusInfo['percentual_compensado'];
        $recebivel['disponivel_para_compensacao'] = $statusInfo['disponivel_para_compensacao'];
    }

} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Erro Crítico [Data Recebíveis]: " . htmlspecialchars($e->getMessage()) . "</div>";
}

// --- Helpers de Formatação HTML ---
function formatHtmlCurrency($value) { return 'R$ ' . number_format($value ?? 0, 2, ',', '.'); }

function formatHtmlDate($value) {
  if(!$value) return '-';
  try {
    return (new DateTime($value))->format('d/m/Y');
  } catch(Exception $e){
    return '-';
  }
}

function formatHtmlStatus($recebivel, $data_recebimento = null) {
    $status = $recebivel['status_real'] ?? $recebivel['status'] ?? 'Em Aberto';
    $percentual = $recebivel['percentual_compensado'] ?? 0;
    
    $badgeClass = 'bg-secondary'; $tooltip = '';
    switch ($status) {
        case 'Em Aberto': $badgeClass = 'bg-info text-dark'; $tooltip = 'Aguardando ação ou recebimento'; break;
        case 'Recebido': 
            $badgeClass = 'bg-success'; 
            $tooltip = 'Recebimento confirmado';
            // Se tiver data de recebimento, incluir no tooltip
            if (!empty($data_recebimento)) {
                $dataFormatada = formatHtmlDate($data_recebimento);
                $tooltip .= ' em ' . $dataFormatada;
            }
            break;
        case 'Problema': $badgeClass = 'bg-danger'; $tooltip = 'Problema no recebimento'; break;
        case 'Compensado':
        case 'Totalmente Compensado': $badgeClass = 'bg-info'; $tooltip = 'Valor totalmente compensado em encontro de contas'; break;
        case 'Parcialmente Compensado': $badgeClass = 'bg-primary'; $tooltip = "Valor parcialmente compensado ({$percentual}%)"; break;
    }
    
    $statusText = $status;
    if ($status === 'Parcialmente Compensado') {
        $statusText .= " ({$percentual}%)";
    }
    
    // Se for "Recebido" e tiver data, usar tooltip customizado
    if ($status === 'Recebido' && !empty($data_recebimento)) {
        return '<div class="tooltip-wrapper">
                    <span class="badge ' . $badgeClass . '">' . htmlspecialchars($statusText) . '</span>
                    <span class="tooltip-text">' . htmlspecialchars($tooltip) . '</span>
                </div>';
    }
    
    // Para outros casos, usar tooltip padrão
    return '<span class="badge ' . $badgeClass . '" title="' . htmlspecialchars($tooltip) . '">' . htmlspecialchars($statusText) . '</span>';
}

function getTableRowClass($status) {
    switch ($status) {
        case 'Recebido': return 'table-light text-muted opacity-75';
        case 'Problema': return 'table-danger fw-bold';
        case 'Compensado': return 'table-warning text-muted opacity-75';
        case 'Totalmente Compensado': return 'table-warning text-muted opacity-75';
        case 'Parcialmente Compensado': return 'table-primary';
        case 'Em Aberto': default: return '';
    }
}

// Helper function para links de ordenação
function getRecebivelSortLink($column, $text, $currentSort, $currentDir, $currentFilters) {
    $newDir = ($currentSort === $column && $currentDir === 'asc') ? 'desc' : 'asc';
    $icon = '';
    if ($currentSort === $column) {
        $icon = $currentDir === 'asc' ? ' ▲' : ' ▼';
    }
    $filterParams = http_build_query($currentFilters);
    return "<a href=\"?sort=$column&dir=$newDir&$filterParams\">" . htmlspecialchars($text) . $icon . "</a>";
}

// Monta array com filtros atuais para usar nos links (excluindo page)
$current_filters_for_links = [
    'status' => $filtro_status,
    'tipo_pagamento' => $filtro_tipo_pagamento,
    'data_inicio' => $filtro_data_inicio,
    'data_fim' => $filtro_data_fim,
    'search' => $search,
    'per_page' => $items_per_page
];
// Monta array com filtros + sort/dir para usar nos links de paginação
$current_filters_for_pagination = $current_filters_for_links + ['sort' => $sort, 'dir' => $dir];

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Recebíveis</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        th, td { vertical-align: middle; }
        .action-btn { margin: 0 2px; padding: 0.15rem 0.4rem; font-size: 0.8em; }
        tr.table-light.text-muted.opacity-75 td { /* Estilos para recebido */ }
        th a { text-decoration: none; color: inherit; }
        th a:hover { color: #0056b3; }
        .pagination .page-link { color: #007bff; }
        .pagination .page-item.active .page-link { z-index: 3; color: #fff; background-color: #007bff; border-color: #007bff;}
        .pagination .page-item.disabled .page-link { color: #6c757d; pointer-events: none; background-color: #fff; border-color: #dee2e6; }
        
        /* Tooltip customizado */
        .tooltip-wrapper {
            position: relative;
            display: inline-block;
        }
        .tooltip-wrapper .tooltip-text {
            visibility: hidden;
            width: auto;
            min-width: 220px;
            max-width: 300px;
            background-color: #000 !important;
            color: #fff !important;
            text-align: center;
            border-radius: 6px;
            padding: 10px 15px;
            position: absolute;
            z-index: 1000;
            bottom: 125%;
            left: 50%;
            transform: translateX(-50%);
            opacity: 0;
            transition: opacity 0.2s;
            font-size: 0.75rem;
            white-space: normal;
            box-shadow: 0 2px 8px rgba(0,0,0,0.5);
            border: 1px solid #333;
        }
        .tooltip-wrapper .tooltip-text::after {
            content: "";
            position: absolute;
            top: 100%;
            left: 50%;
            margin-left: -5px;
            border-width: 5px;
            border-style: solid;
            border-color: #000 transparent transparent transparent;
        }
        .tooltip-wrapper:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
        }
        
        /* Ações Fixas na direita */
        .table-responsive {
            overflow-x: auto;
        }
        .acoes-col {
            position: sticky;
            right: 0;
            background-color: #fff;
            z-index: 1;
            box-shadow: -2px 0 5px rgba(0,0,0,0.05);
        }
        thead .acoes-col {
            background-color: #f8f9fa; /* igual ao .table-light */
            z-index: 2;
        }
        tfoot .acoes-col {
            background-color: #e2e3e5; /* igual ao .table-secondary */
            z-index: 2;
        }
        .table-striped tbody tr:nth-of-type(odd) td.acoes-col {
            background-color: rgba(0, 0, 0, 0.05);
        }
        .table-hover tbody tr:hover td.acoes-col {
            background-color: rgba(0, 0, 0, 0.075);
        }
        
        /* Animação suave para o ícone do collapse */
        .card-header i.bi-chevron-down {
            transition: transform 0.3s ease;
        }
    </style>
</head>
<body>
    <?php require_once 'menu.php'; ?>

    <div class="container-fluid px-3 px-md-4 mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
            <h1>Lista de Recebíveis</h1>
             <div>
                <a href="exportar_ics.php?<?php echo http_build_query($current_filters_for_links);?>" class="btn btn-sm btn-outline-primary me-2" target="_blank" title="Exportar Lembretes (.ics) dos resultados filtrados">
                    <i class="bi bi-calendar-plus"></i> .ics
                </a>
                <a href="exportar_csv.php?<?php echo http_build_query($current_filters_for_links);?>" class="btn btn-sm btn-outline-success" target="_blank" title="Exportar Lista (.csv) dos resultados filtrados">
                    <i class="bi bi-file-earmark-spreadsheet"></i> .csv
                </a>
            </div>
        </div>

        <!-- Formulário de Filtros -->
        <div class="card mb-4">
            <div class="card-header" style="cursor: pointer;" data-bs-toggle="collapse" data-bs-target="#filtrosCollapse" aria-expanded="false" aria-controls="filtrosCollapse">
                <h5 class="mb-0">
                    <i class="bi bi-funnel"></i> Filtros
                    <i class="bi bi-chevron-down float-end"></i>
                </h5>
            </div>
            <div class="collapse" id="filtrosCollapse">
                <div class="card-body">
                    <form method="GET" action="listar_recebiveis.php">
                        <!-- Preservar parâmetros de ordenação -->
                        <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort); ?>">
                        <input type="hidden" name="dir" value="<?php echo htmlspecialchars($dir); ?>">
                        
                        <div class="row g-3">
                            <!-- Filtro de Busca -->
                            <div class="col-md-3">
                                <label for="search" class="form-label">Buscar ID/Op./Valor/Cedente/Sacado</label>
                                <input type="search" name="search" id="search" class="form-control" value="<?php echo htmlspecialchars($search); ?>" placeholder="Digite para buscar...">
                            </div>
                            
                            <!-- Filtro por Status (Múltipla Seleção) -->
                            <div class="col-md-3">
                                <label class="form-label">Status (múltipla seleção)</label>
                                <div class="border rounded p-2" style="max-height: 120px; overflow-y: auto;">
                                    <?php
                                    $status_options = ['Em Aberto', 'Recebido', 'Problema', 'Parcialmente Compensado', 'Totalmente Compensado'];
                                    foreach ($status_options as $status_option):
                                        $checked = in_array($status_option, $filtro_status) ? 'checked' : '';
                                    ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="status[]"
                                                   value="<?php echo htmlspecialchars($status_option); ?>"
                                                   id="status_<?php echo str_replace([' ', 'ç'], ['_', 'c'], strtolower($status_option)); ?>" <?php echo $checked; ?>>
                                            <label class="form-check-label" for="status_<?php echo str_replace([' ', 'ç'], ['_', 'c'], strtolower($status_option)); ?>">
                                                <?php echo htmlspecialchars($status_option); ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <!-- Filtro por Tipo de Pagamento (Múltipla Seleção) -->
                            <div class="col-md-3">
                                <label class="form-label">Tipo de Pagamento</label>
                                <div class="border rounded p-2" style="max-height: 120px; overflow-y: auto;">
                                    <?php
                                    $tipo_pagamento_options = ['direto', 'escrow', 'indireto'];
                                    foreach ($tipo_pagamento_options as $tipo_option):
                                        $checked = in_array($tipo_option, $filtro_tipo_pagamento) ? 'checked' : '';
                                    ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="tipo_pagamento[]"
                                                   value="<?php echo htmlspecialchars($tipo_option); ?>"
                                                   id="tipo_<?php echo htmlspecialchars($tipo_option); ?>" <?php echo $checked; ?>>
                                            <label class="form-check-label" for="tipo_<?php echo htmlspecialchars($tipo_option); ?>">
                                                <?php echo ucfirst(htmlspecialchars($tipo_option)); ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <!-- Filtro por Data de Vencimento -->
                            <div class="col-md-3">
                                <label for="data_inicio" class="form-label">Vencimento De</label>
                                <input type="date" name="data_inicio" id="data_inicio" class="form-control" value="<?php echo htmlspecialchars($filtro_data_inicio); ?>">
                            </div>
                            
                            <div class="col-md-3">
                                <label for="data_fim" class="form-label">Vencimento Até</label>
                                <input type="date" name="data_fim" id="data_fim" class="form-control" value="<?php echo htmlspecialchars($filtro_data_fim); ?>">
                            </div>
                            
                            <!-- Items por página -->
                            <div class="col-md-3">
                                <label for="per_page" class="form-label">Itens por página</label>
                                <select name="per_page" id="per_page" class="form-select">
                                    <?php foreach ($items_per_page_options as $option): ?>
                                        <option value="<?php echo $option; ?>" <?php echo ($items_per_page == $option) ? 'selected' : ''; ?>><?php echo $option; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="bi bi-search"></i> Aplicar Filtros
                                </button>
                                <a href="?sort=<?php echo htmlspecialchars($sort); ?>&dir=<?php echo htmlspecialchars($dir); ?>"
                                   class="btn btn-outline-secondary">
                                    <i class="bi bi-x-circle"></i> Limpar Filtros
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div id="status-feedback" class="mt-2 mb-3" style="min-height: 1.5em;"></div>
        <?php if (empty($recebiveis) && $total_results == 0 && empty($filtro_status) && empty($filtro_tipo_pagamento) && empty($filtro_data_inicio) && empty($filtro_data_fim) && empty($search)): ?>
            <div class="alert alert-info">Nenhum recebível registrado ainda.</div>
        <?php elseif (empty($recebiveis) && $total_results > 0): ?>
             <p class="alert alert-warning">Nenhum recebível encontrado para os filtros/busca selecionados nesta página.</p>
             <?php // Inclui paginação mesmo se página vazia ?>
               <?php // include 'includes/pagination_recebiveis.php'; // Ou coloque o código da paginação aqui ?>
        <?php elseif (empty($recebiveis) && $total_results == 0): ?>
            <div class="alert alert-warning">Nenhum recebível encontrado para os filtros/busca selecionados.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-bordered table-hover mb-3">
                  <thead class="table-light">
                          <tr>
                              <th class="text-center"><?php echo getRecebivelSortLink('operacao_id', 'ID Op.', $sort, $dir, $current_filters_for_links); ?></th>
                              <th class="text-center"><?php echo getRecebivelSortLink('id', 'Id Rec.', $sort, $dir, $current_filters_for_links); ?></th>
                              <th><?php echo getRecebivelSortLink('cedente_nome', 'Cedente', $sort, $dir, $current_filters_for_links); ?></th>
                              <th><?php echo getRecebivelSortLink('sacado_nome', 'Sacado', $sort, $dir, $current_filters_for_links); ?></th>
                              <th class="text-center"><?php echo getRecebivelSortLink('data_operacao', 'Data Operacao', $sort, $dir, $current_filters_for_links); ?></th>
                              <th class="text-center"><?php echo getRecebivelSortLink('tipo_pagamento', 'Tipo Pagamento', $sort, $dir, $current_filters_for_links); ?></th>
                              <th class="text-center">Tipo Recebível</th>
                              <th class="text-center"><?php echo getRecebivelSortLink('data_vencimento', 'Vencimento', $sort, $dir, $current_filters_for_links); ?></th>
                              <th class="text-center">Dias p/ Vencimento</th> <th class="text-end"><?php echo getRecebivelSortLink('valor_original', 'Valor Original', $sort, $dir, $current_filters_for_links); ?></th>
                              <th class="text-center"><?php echo getRecebivelSortLink('status', 'Status', $sort, $dir, $current_filters_for_links); ?></th>
                              <th class="text-center acoes-col" style="width: 110px;">Ações</th>
                          </tr>
                      </thead>
                    <tbody>
                        <?php
                        foreach ($recebiveis as $r):
                        ?>
                            <?php
                            $rowClass = getTableRowClass($r['status_real']);

                            // Usar a nova função que considera apenas datas (sem horário)
                            $dias_p_vencimento = calcularDiasParaVencimento($r['data_vencimento']);
                            ?>
                            <tr id="recebivel-row-<?php echo $r['id']; ?>" class="<?php echo $rowClass; ?>">
                                <td class="text-center"><a href="detalhes_operacao.php?id=<?php echo (int)$r['operacao_id']; ?>" title="Ver detalhes da Operação <?php echo (int)$r['operacao_id']; ?>">
                                  <?php echo htmlspecialchars($r['operacao_id']); ?></a></td>

                                <td class="text-center"><small><?php echo htmlspecialchars($r['id']); ?></small></td>
                                <td><?php echo htmlspecialchars($r['cedente_nome'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($r['sacado_nome'] ?? 'N/A'); ?></td>
                                <td class="text-center"><?php echo formatHtmlDate($r['data_operacao']); ?></td>
                                <td class="text-center"><?php echo htmlspecialchars($r['tipo_pagamento'] ?? 'N/A'); ?></td>
                                <td class="text-center"><?php echo htmlspecialchars($r['tipo_recebivel'] ?? 'N/A'); ?></td>
                                <td class="text-center"><?php echo formatHtmlDate($r['data_vencimento']); ?></td>
                                <td class="text-center"><?php echo htmlspecialchars($dias_p_vencimento); ?></td> <td class="text-end">
                                    <div><?php echo formatHtmlCurrency($r['valor_original']); ?></div>
                                    <?php if ($r['total_compensado'] > 0): ?>
                                        <small class="text-muted">Saldo: <?php echo formatHtmlCurrency($r['saldo_disponivel']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center status-cell"><?php echo formatHtmlStatus($r, $r['data_recebimento'] ?? null); ?></td>
                                <td class="text-center actions-cell acoes-col">
                                    <?php if ($r['status_real'] === 'Em Aberto'): ?>
                                        <button class="btn btn-success action-btn update-status-btn" data-id="<?php echo $r['id']; ?>" data-status="Recebido" title="Marcar como Recebido"><i class="bi bi-check-lg"></i></button>
                                        <button class="btn btn-danger action-btn update-status-btn" data-id="<?php echo $r['id']; ?>" data-status="Problema" title="Marcar com Problema"><i class="bi bi-exclamation-triangle-fill"></i></button>
                                    <?php elseif ($r['status_real'] === 'Problema'): ?>
                                        <button class="btn btn-success action-btn update-status-btn" data-id="<?php echo $r['id']; ?>" data-status="Recebido" title="Marcar como Recebido"><i class="bi bi-check-lg"></i></button>
                                        <button class="btn btn-secondary action-btn update-status-btn" data-id="<?php echo $r['id']; ?>" data-status="Em Aberto" title="Reverter para Em Aberto"><i class="bi bi-arrow-counterclockwise"></i></button>
                                    <?php elseif ($r['status_real'] === 'Recebido'): ?>
                                        <button class="btn btn-secondary action-btn update-status-btn" data-id="<?php echo $r['id']; ?>" data-status="Em Aberto" title="Reverter para Em Aberto"><i class="bi bi-arrow-counterclockwise"></i></button>
                                    <?php elseif ($r['status_real'] === 'Parcialmente Compensado'): ?>
                                        <button class="btn btn-success action-btn update-status-btn" data-id="<?php echo $r['id']; ?>" data-status="Recebido" title="Marcar como Recebido"><i class="bi bi-check-lg"></i></button>
                                        <button class="btn btn-danger action-btn update-status-btn" data-id="<?php echo $r['id']; ?>" data-status="Problema" title="Marcar com Problema"><i class="bi bi-exclamation-triangle-fill"></i></button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <?php if (!empty($recebiveis)): ?>
                    <tfoot class="table-secondary">
                        <tr>
                            <th colspan="7" class="text-end"><strong>TOTAIS:</strong></th>
                            <th class="text-end">
                                <?php
                                // Calcular total dos valores originais dos recebíveis filtrados
                                $total_valor_original = 0;
                                $total_saldo_disponivel = 0;
                                foreach ($recebiveis as $r) {
                                    $total_valor_original += $r['valor_original'] ?? 0;
                                    $total_saldo_disponivel += $r['saldo_disponivel'] ?? $r['valor_original'] ?? 0;
                                }
                                ?>
                                <div><strong><?php echo formatHtmlCurrency($total_valor_original); ?></strong></div>
                                <small class="text-muted">Saldo: <?php echo formatHtmlCurrency($total_saldo_disponivel); ?></small>
                            </th>
                            <th colspan="2" class="text-center acoes-col">
                                <small><?php echo count($recebiveis); ?> recebíveis na página</small>
                                <?php if ($total_results > count($recebiveis)): ?>
                                    <br><small class="text-muted">Total geral: <?php echo $total_results; ?> recebíveis</small>
                                <?php endif; ?>
                            </th>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>

            <?php if ($total_pages > 1): ?>
                 <nav aria-label="Paginação Recebíveis">
                    <ul class="pagination justify-content-center">
                        <?php $baseUrl = "?" . http_build_query($current_filters_for_pagination); ?>
                        <li class="page-item <?php echo ((int)$page <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo $baseUrl . '&page=' . ((int)$page - 1); ?>">&laquo;</a>
                        </li>
                        <?php
                            $start_page = max(1, (int)$page - 2);
                            $end_page = min((int)$total_pages, (int)$page + 2);
                            if ((int)$page <= 3) $end_page = min((int)$total_pages, 5);
                            if ((int)$page >= ((int)$total_pages - 2)) $start_page = max(1, ((int)$total_pages - 4));

                            if ($start_page > 1) {
                                echo '<li class="page-item"><a class="page-link" href="' . $baseUrl . '&page=1">1</a></li>';
                                if ($start_page > 2) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                            for ($i = (int)$start_page; $i <= (int)$end_page; $i++):
                        ?>
                            <li class="page-item <?php echo ((int)$i == (int)$page) ? 'active' : ''; ?>">
                                <a class="page-link" href="<?php echo $baseUrl . '&page=' . (int)$i; ?>"><?php echo (int)$i; ?></a>
                            </li>
                        <?php endfor;
                            if ((int)$end_page < (int)$total_pages) {
                                if ((int)$end_page < ((int)$total_pages - 1)) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                echo '<li class="page-item"><a class="page-link" href="' . $baseUrl . '&page=' . (int)$total_pages . '">' . (int)$total_pages . '</a></li>';
                             }
                        ?>
                        <li class="page-item <?php echo ((int)$page >= (int)$total_pages) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo $baseUrl . '&page=' . ((int)$page + 1); ?>">&raquo;</a>
                        </li>
                    </ul>
                </nav>
                <p class="text-center text-muted">Página <?php echo (int)$page; ?> de <?php echo (int)$total_pages; ?> (Total: <?php echo (int)$total_results; ?> recebíve<?php echo (int)$total_results == 1 ? 'l' : 'is'; ?>)</p>
            <?php elseif($total_results > 0): ?>
                <p class="text-center text-muted">Total: <?php echo (int)$total_results; ?> recebíve<?php echo (int)$total_results == 1 ? 'l' : 'is'; ?></p>
            <?php endif; ?>

        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
document.addEventListener('DOMContentLoaded', function () {
    const feedbackDiv = document.getElementById('status-feedback');
    const tableBody = document.querySelector('.table tbody');

    if (tableBody) {
        tableBody.addEventListener('click', function(event) {
            const button = event.target.closest('.update-status-btn');
            if (!button) return; // Ignora cliques fora dos botões

            const recebivelId = button.dataset.id;
            const newStatus = button.dataset.status;
            const row = document.getElementById('recebivel-row-' + recebivelId);
            if (!row) {
                console.error('Elemento da linha não encontrado:', 'recebivel-row-' + recebivelId);
                return;
            }

            const statusCell = row.querySelector('.status-cell');
            const actionsCell = row.querySelector('.actions-cell');
            if (!statusCell || !actionsCell) {
                 console.error('Célula de status ou ações não encontrada na linha:', row);
                return;
            }

            // Mostra feedback inicial
            feedbackDiv.innerHTML = '<div class="spinner-border spinner-border-sm text-primary" role="status"><span class="visually-hidden">Atualizando...</span></div> Atualizando status...';
            feedbackDiv.className = 'alert alert-info';

            fetch('atualizar_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'id=' + encodeURIComponent(recebivelId) + '&status=' + encodeURIComponent(newStatus)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Erro HTTP ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                const closeButtonHtml = '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';

                 if (data.success && typeof data.newStatusHtml !== 'undefined' && typeof data.newActionsHtml !== 'undefined' && typeof data.newRowClass !== 'undefined') {
                    statusCell.innerHTML = data.newStatusHtml;
                    actionsCell.innerHTML = data.newActionsHtml;
                    row.className = data.newRowClass;
                    feedbackDiv.innerHTML = `Status do recebível ${recebivelId} atualizado para ${newStatus}. ${closeButtonHtml}`;
                    feedbackDiv.className = 'alert alert-success alert-dismissible fade show';
                } else {
                    feedbackDiv.innerHTML = `Erro ao atualizar status: ${data.message || 'Dados de resposta incompletos ou falha no servidor.'} ${closeButtonHtml}`;
                    feedbackDiv.className = 'alert alert-danger alert-dismissible fade show';
                }
            })
            .catch(error => {
                console.error('Erro no catch:', error);
                const closeButtonHtml = '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                feedbackDiv.innerHTML = `Erro na comunicação ou processamento da resposta. Ver Console (F12). [${error.message}] ${closeButtonHtml}`;
                feedbackDiv.className = 'alert alert-danger alert-dismissible fade show';
            });
        });
    }
});
    </script>
</body>
</html>
