<?php require_once 'auth_check.php'; ?><?php
// --- APENAS PARA DEBUG - REMOVER/COMENTAR EM PRODUÇÃO ---
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
// --- FIM DEBUG ---

require_once 'db_connection.php'; // Conexão $pdo

// --- Configurações de Paginação, Ordenação e Busca (Mantidas) ---
$results_per_page = 15;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'id';
$dir = isset($_GET['dir']) && in_array(strtolower($_GET['dir']), ['asc', 'desc']) ? strtolower($_GET['dir']) : 'desc';

// --- Novos Filtros ---
$filter_cedente = isset($_GET['filter_cedente']) ? trim($_GET['filter_cedente']) : '';
// Filtro de status agora aceita múltiplos valores
$filter_status = isset($_GET['filter_status']) && is_array($_GET['filter_status']) ? $_GET['filter_status'] : [];
$filter_valor_min = isset($_GET['filter_valor_min']) ? floatval($_GET['filter_valor_min']) : 0;
$filter_valor_max = isset($_GET['filter_valor_max']) ? floatval($_GET['filter_valor_max']) : 0;
$filter_data = isset($_GET['filter_data']) ? trim($_GET['filter_data']) : '';
$filter_data_inicio = isset($_GET['filter_data_inicio']) ? trim($_GET['filter_data_inicio']) : '';
$filter_data_fim = isset($_GET['filter_data_fim']) ? trim($_GET['filter_data_fim']) : '';

$allowed_sort_columns = [ /* ... colunas permitidas ... */
    'id' => 'o.id',
    'cedente_nome' => 's.empresa',
    'taxa_mensal' => 'o.taxa_mensal',
    'total_original_calc' => 'o.total_original_calc',
    'total_liquido_pago_calc' => 'o.total_liquido_pago_calc',
    'total_lucro_liquido_calc' => 'o.total_lucro_liquido_calc',
    'data_operacao' => 'o.data_operacao',
    'data_base_calculo' => 'o.data_operacao',
    'media_dias_operacao' => 'media_dias_operacao',
    'status_operacao' => 'status_operacao',
    'num_recebiveis' => 'num_recebiveis',
    'saldo_em_aberto' => 'saldo_em_aberto' // Adicionado para ordenação
];
if (!array_key_exists($sort, $allowed_sort_columns)) { $sort = 'data_operacao'; }
$sort_column_sql = $allowed_sort_columns[$sort];

// --- Processamento dos Filtros de Data ---
$data_inicio = null; $data_fim = null;
if (!empty($filter_data)) {
    $hoje = date('Y-m-d');
    switch ($filter_data) {
        case 'hoje':
            $data_inicio = $data_fim = $hoje;
            break;
        case 'ontem':
            $data_inicio = $data_fim = date('Y-m-d', strtotime('-1 day'));
            break;
        case 'ultimos_7_dias':
            $data_inicio = date('Y-m-d', strtotime('-7 days'));
            $data_fim = $hoje;
            break;
        case 'mes_atual':
            $data_inicio = date('Y-m-01');
            $data_fim = date('Y-m-t');
            break;
        case 'mes_passado':
            $data_inicio = date('Y-m-01', strtotime('first day of last month'));
            $data_fim = date('Y-m-t', strtotime('last day of last month'));
            break;
        case 'custom':
            $data_inicio = !empty($filter_data_inicio) ? $filter_data_inicio : null;
            $data_fim = !empty($filter_data_fim) ? $filter_data_fim : null;
            break;
    }
}

$offset = ($page - 1) * $results_per_page;
if ($offset < 0) $offset = 0;

// --- Construção da Query com Filtros ---
$params_count = []; $params_data = []; $whereClauses = [];

// Filtro de busca (mantido)
if (!empty($search)) {
    $whereClauses[] = "(CAST(o.id AS CHAR) LIKE :search_id OR s.empresa LIKE :search_nome)";
    $search_param_value = "%" . $search . "%";
    $params_count[':search_id'] = $search_param_value; $params_count[':search_nome'] = $search_param_value;
    $params_data[':search_id'] = $search_param_value; $params_data[':search_nome'] = $search_param_value;
}

// Filtro por sacado
if (!empty($filter_cedente)) {
    $whereClauses[] = "o.cedente_id = :filter_cedente";
    $params_count[':filter_cedente'] = $filter_cedente;
    $params_data[':filter_cedente'] = $filter_cedente;
}

// Filtro por valor
if ($filter_valor_min > 0) {
    $whereClauses[] = "o.total_liquido_pago_calc >= :filter_valor_min";
    $params_count[':filter_valor_min'] = $filter_valor_min;
    $params_data[':filter_valor_min'] = $filter_valor_min;
}
if ($filter_valor_max > 0) {
    $whereClauses[] = "o.total_liquido_pago_calc <= :filter_valor_max";
    $params_count[':filter_valor_max'] = $filter_valor_max;
    $params_data[':filter_valor_max'] = $filter_valor_max;
}

// Filtro por data
if ($data_inicio && $data_fim) {
    $whereClauses[] = "DATE(o.data_operacao) BETWEEN :data_inicio AND :data_fim";
    $params_count[':data_inicio'] = $data_inicio;
    $params_count[':data_fim'] = $data_fim;
    $params_data[':data_inicio'] = $data_inicio;
    $params_data[':data_fim'] = $data_fim;
} elseif ($data_inicio) {
    $whereClauses[] = "DATE(o.data_operacao) >= :data_inicio";
    $params_count[':data_inicio'] = $data_inicio;
    $params_data[':data_inicio'] = $data_inicio;
} elseif ($data_fim) {
    $whereClauses[] = "DATE(o.data_operacao) <= :data_fim";
    $params_count[':data_fim'] = $data_fim;
    $params_data[':data_fim'] = $data_fim;
}

$whereSql = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

// --- Query Contagem (Mantida) ---
$total_results = 0;
try {
    $countSql = "SELECT COUNT(DISTINCT o.id)
                 FROM operacoes o
                 LEFT JOIN cedentes s ON o.cedente_id = s.id
                 $whereSql";
    $stmtCount = $pdo->prepare($countSql);
    $stmtCount->execute($params_count);
    $total_results = (int)$stmtCount->fetchColumn();
} catch (PDOException $e) { $error_message_count = "Erro Crítico [Count]: " . htmlspecialchars($e->getMessage()); }

$total_pages = ($results_per_page > 0) ? ceil($total_results / $results_per_page) : 0;
if ($total_pages == 0) $total_pages = 1;

if ($page > $total_pages) { $page = $total_pages; $offset = max(0, ((int)$page - 1) * $results_per_page); }
elseif ($page < 1) { $page = 1; $offset = 0; }

// --- Query Busca Dados (MODIFICADA para incluir status da operação e contagem de recebíveis e saldo em aberto) ---
$operacoes = [];
if (!isset($error_message_count)) { // Só busca dados se contagem funcionou
    try {
        $sql = "SELECT
                    o.id,
                    o.cedente_id,
                    o.data_operacao,
                    o.taxa_mensal,
                    o.total_original_calc,
                    o.total_liquido_pago_calc,
                    o.total_lucro_liquido_calc,
                    s.empresa AS cedente_nome,
                    AVG(DATEDIFF(r.data_vencimento, o.data_operacao)) AS media_dias_operacao,
                    -- Lógica para determinar o status da operação
                    CASE
                        WHEN SUM(CASE WHEN r.status = 'Problema' THEN 1 ELSE 0 END) > 0 THEN 'Com Problema'
                        WHEN SUM(CASE WHEN r.status = 'Em Aberto' THEN 1 ELSE 0 END) > 0 THEN 'Em Aberto'
                        WHEN SUM(CASE WHEN r.status = 'Parcialmente Compensado' THEN 1 ELSE 0 END) > 0 THEN 'Parcialmente Compensada'
                        WHEN SUM(CASE WHEN r.status IN ('Recebido', 'Compensado') THEN 1 ELSE 0 END) = COUNT(r.id) AND COUNT(r.id) > 0 THEN 'Concluída'
                        ELSE 'Em Aberto'
                    END AS status_operacao,
                    COUNT(r.id) AS num_recebiveis,
                    -- NOVA COLUNA: Saldo (CORRIGIDO para considerar compensações parciais)
                    SUM(CASE
                        WHEN r.status = 'Recebido' THEN 0
                        WHEN r.status = 'Parcialmente Compensado' THEN
                            COALESCE((SELECT c.saldo_restante
                                     FROM compensacoes c
                                     WHERE c.recebivel_compensado_id = r.id
                                     ORDER BY c.data_compensacao DESC
                                     LIMIT 1), r.valor_original)
                        ELSE r.valor_original
                    END) AS saldo_em_aberto,
                    -- Coluna para data base de cálculo (mesma que data_operacao)
                    o.data_operacao AS data_base_calculo
                FROM
                    operacoes o
                LEFT JOIN
                    cedentes s ON o.cedente_id = s.id
                LEFT JOIN
                    recebiveis r ON o.id = r.operacao_id
                $whereSql
                GROUP BY
                    o.id,
                    o.cedente_id,
                    o.data_operacao,
                    o.taxa_mensal,
                    o.total_original_calc,
                    o.total_liquido_pago_calc,
                    o.total_lucro_liquido_calc,
                    s.empresa,
                    o.data_operacao";
        
        // Adicionar filtro por status após GROUP BY (múltiplos valores)
        if (!empty($filter_status)) {
            $placeholders = [];
            for ($i = 0; $i < count($filter_status); $i++) {
                $placeholders[] = ":filter_status_$i";
            }
            $sql .= " HAVING status_operacao IN (" . implode(',', $placeholders) . ")";
        }
        
        $sql .= " ORDER BY $sort_column_sql $dir
                LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($sql);
        // Bind busca
        if (!empty($search)) {
            $stmt->bindParam(':search_id', $params_data[':search_id'], PDO::PARAM_STR);
            $stmt->bindParam(':search_nome', $params_data[':search_nome'], PDO::PARAM_STR);
        }
        
        // Bind dos parâmetros de filtro
        if (!empty($filter_cedente)) {
            $stmt->bindParam(':filter_cedente', $filter_cedente, PDO::PARAM_INT);
        }
        if (!empty($filter_valor_min)) {
            $stmt->bindParam(':filter_valor_min', $filter_valor_min);
        }
        if (!empty($filter_valor_max)) {
            $stmt->bindParam(':filter_valor_max', $filter_valor_max);
        }
        if (!empty($filter_data_inicio)) {
            $stmt->bindParam(':filter_data_inicio', $filter_data_inicio);
        }
        if (!empty($filter_data_fim)) {
            $stmt->bindParam(':filter_data_fim', $filter_data_fim);
        }
        if (!empty($filter_status)) {
            for ($i = 0; $i < count($filter_status); $i++) {
                $stmt->bindParam(":filter_status_$i", $filter_status[$i]);
            }
        }
        
        // Bind paginação (sempre)
        $stmt->bindParam(':limit', $results_per_page, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $operacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { $error_message_data = "Erro Crítico [Data]: " . htmlspecialchars($e->getMessage()); }
}

// --- Função Helper Ordenação (Atualizada para preservar filtros) ---
function getSortLink($column, $text, $currentSort, $currentDir, $currentSearch, $filters = []) {
    $newDir = ($currentSort === $column && $currentDir === 'asc') ? 'desc' : 'asc';
    $icon = ($currentSort === $column) ? ($currentDir === 'asc' ? ' ▲' : ' ▼') : '';
    
    $params = [];
    $params['sort'] = $column;
    $params['dir'] = $newDir;
    
    if ($currentSearch) {
        $params['search'] = $currentSearch;
    }
    
    // Adicionar filtros ativos
    foreach ($filters as $key => $value) {
        if (!empty($value)) {
            $params[$key] = $value;
        }
    }
    
    $queryString = http_build_query($params);
    return "<a href=\"?$queryString\">" . htmlspecialchars($text) . $icon . "</a>";
}

// --- Função Helper para formatar o badge de status da operação (Atualizada) ---
function formatOperacaoStatusBadge($status_operacao) {
    $badgeClass = 'bg-secondary';
    switch ($status_operacao) {
        case 'Concluída': $badgeClass = 'bg-success'; break;
        case 'Em Aberto': $badgeClass = 'bg-info text-dark'; break;
        case 'Parcialmente Compensada': $badgeClass = 'bg-warning text-dark'; break;
        case 'Com Problema': $badgeClass = 'bg-danger'; break;
    }
    return '<span class="badge ' . $badgeClass . '">' . htmlspecialchars($status_operacao) . '</span>';
}

// Helper para formatar moeda (copiado de outro lugar para garantir disponibilidade)
function formatHtmlCurrency($value) {
    return 'R$ ' . number_format($value ?? 0, 2, ',', '.');
}

// --- Buscar lista de cedentes para o filtro ---
$cedentes_list = [];
try {
    $stmt_sacados = $pdo->query("SELECT id, empresa as nome FROM cedentes ORDER BY empresa");
    $cedentes_list = $stmt_sacados->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Em caso de erro, continua sem os sacados
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Operações</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        th, td { vertical-align: middle; }
        /* Layout final otimizado */
        .table {
            table-layout: fixed;
        }
        .table th {
            min-width: auto;
            white-space: normal;
            font-size: 0.9em;
            padding: 0.5rem;
        }
        .table td {
            word-break: break-word;
            font-size: 0.85em;
            line-height: 1.2;
            padding: 0.4rem;
        }
        @media (max-width: 768px) {
            .table th, .table td {
                font-size: 0.75em;
                padding: 0.3rem;
            }
        }
        /* Definir larguras específicas para cada coluna - ajustadas para melhor distribuição */
        th:nth-child(1), td:nth-child(1) { width: 60px; min-width: 60px; } /* ID */
        th:nth-child(2), td:nth-child(2) { width: 154px; min-width: 154px; max-width: 210px; } /* Sacado */
        th:nth-child(3), td:nth-child(3) { width: 90px; min-width: 90px; } /* Taxa */
        th:nth-child(4), td:nth-child(4) { width: 130px; min-width: 130px; } /* T. Original */
        th:nth-child(5), td:nth-child(5) { width: 130px; min-width: 130px; } /* T. Líquido */
        th:nth-child(6), td:nth-child(6) { width: 130px; min-width: 130px; } /* Lucro */
        th:nth-child(7), td:nth-child(7) { width: 110px; min-width: 110px; } /* Dias Médios */
        th:nth-child(8), td:nth-child(8) { width: 130px; min-width: 130px; } /* Status */
        th:nth-child(9), td:nth-child(9) { width: 90px; min-width: 90px; } /* # Rec. */
        th:nth-child(10), td:nth-child(10) { width: 140px; min-width: 140px; } /* Saldo */
        th:nth-child(11), td:nth-child(11) { width: 140px; min-width: 140px; } /* Data Base Cálculo */
        th:nth-child(12), td:nth-child(12) { width: 150px; min-width: 150px; } /* Data Operação */
        th:nth-child(13), td:nth-child(13) { width: 140px; min-width: 140px; } /* Ações */
        
        /* Ajustar o espaçamento do action-icon e garantir que fiquem na linha */
        .action-icon {
            margin: 0 1px; /* Reduza o espaçamento horizontal para caber mais */
            padding: 0.15rem 0.3rem; /* Reduza o padding para diminuir o tamanho do botão */
            font-size: 0.8em; /* Pode reduzir um pouco a fonte */
            display: inline-block; /* Garante que se comportem como blocos em linha */
            white-space: nowrap; /* Impede a quebra de linha dentro dos botões */
        }
        /* Aumentar a largura da coluna de ações para caber os 4 botões */
        .acoes-col {
            width: 120px; /* Ajuste este valor conforme necessário */
            min-width: 120px; /* Garante que a coluna não encolha demais */
        }
        th a { text-decoration: none; color: inherit; }
        th a:hover { color: #0056b3; }
        /* Estilos Paginação (Mantidos) */
        .pagination .page-link { color: #007bff; }
        .pagination .page-item.active .page-link { z-index: 3; color: #fff; background-color: #007bff; border-color: #007bff;}
        .pagination .page-item.disabled .page-link { color: #6c757d; pointer-events: none; background-color: #fff; border-color: #dee2e6; }
        
        /* Evitar quebra de texto nas células */
        th, td {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        /* Permitir quebra apenas na coluna do sacado que pode ter nomes longos */
        th:nth-child(2), td:nth-child(2) {
            white-space: normal;
            word-wrap: break-word;
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
            background-color: rgba(0, 0, 0, 0.05); /* Aproximação do bg listrado do Bootstrap */
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
            <h1>Gerenciar Operações</h1>
            <form method="GET" action="listar_operacoes.php" class="d-flex ms-auto" style="max-width: 300px;">
                <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort); ?>">
                <input type="hidden" name="dir" value="<?php echo htmlspecialchars($dir); ?>">
                <input class="form-control me-2" type="search" name="search" placeholder="Buscar por ID ou Sacado" aria-label="Buscar" value="<?php echo htmlspecialchars($search); ?>">
                <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
                <?php if (!empty($search)): ?>
                    <a href="?sort=<?php echo htmlspecialchars($sort); ?>&dir=<?php echo htmlspecialchars($dir); ?>" class="btn btn-outline-danger ms-2" title="Limpar Busca"><i class="bi bi-x-lg"></i></a>
                <?php endif; ?>
            </form>
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
                    <form method="GET" action="listar_operacoes.php">
                        <!-- Preservar parâmetros de ordenação e busca -->
                        <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort); ?>">
                        <input type="hidden" name="dir" value="<?php echo htmlspecialchars($dir); ?>">
                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                        
                        <div class="row g-3">
                            <!-- Filtro por Sacado -->
                            <div class="col-md-3">
                                <label for="filter_cedente" class="form-label">Cedente</label>
                                <select class="form-select" id="filter_cedente" name="filter_cedente">
                                    <option value="">Todos os sacados</option>
                                    <?php foreach ($cedentes_list as $cedente): ?>
                                        <option value="<?php echo $cedente['id']; ?>" <?php echo ($filter_cedente == $cedente['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cedente['nome']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Filtro por Status (Múltipla Seleção) -->
                            <div class="col-md-3">
                                <label class="form-label">Status (múltipla seleção)</label>
                                <div class="border rounded p-2" style="max-height: 120px; overflow-y: auto;">
                                    <?php
                                    $status_options = ['Em Aberto', 'Parcialmente Compensada', 'Concluída', 'Com Problema'];
                                    foreach ($status_options as $status_option):
                                        $checked = in_array($status_option, $filter_status) ? 'checked' : '';
                                    ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="filter_status[]"
                                                   value="<?php echo htmlspecialchars($status_option); ?>"
                                                   id="status_<?php echo str_replace(' ', '_', $status_option); ?>" <?php echo $checked; ?>>
                                            <label class="form-check-label" for="status_<?php echo str_replace(' ', '_', $status_option); ?>">
                                                <?php echo htmlspecialchars($status_option); ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <!-- Filtro por Valor Mínimo -->
                            <div class="col-md-3">
                                <label for="filter_valor_min" class="form-label">Valor Mínimo</label>
                                <input type="number" class="form-control" id="filter_valor_min" name="filter_valor_min"
                                       step="0.01" min="0" placeholder="0,00"
                                       value="<?php echo ($filter_valor_min > 0) ? $filter_valor_min : ''; ?>">
                            </div>
                            
                            <!-- Filtro por Valor Máximo -->
                            <div class="col-md-3">
                                <label for="filter_valor_max" class="form-label">Valor Máximo</label>
                                <input type="number" class="form-control" id="filter_valor_max" name="filter_valor_max"
                                       step="0.01" min="0" placeholder="0,00"
                                       value="<?php echo ($filter_valor_max > 0) ? $filter_valor_max : ''; ?>">
                            </div>
                            
                            <!-- Filtro por Data -->
                            <div class="col-md-4">
                                <label for="filter_data" class="form-label">Período</label>
                                <select class="form-select" id="filter_data" name="filter_data" onchange="toggleCustomDates()">
                                    <option value="">Todas as datas</option>
                                    <option value="hoje" <?php echo ($filter_data == 'hoje') ? 'selected' : ''; ?>>Hoje</option>
                                    <option value="ontem" <?php echo ($filter_data == 'ontem') ? 'selected' : ''; ?>>Ontem</option>
                                    <option value="ultimos_7_dias" <?php echo ($filter_data == 'ultimos_7_dias') ? 'selected' : ''; ?>>Últimos 7 dias</option>
                                    <option value="mes_atual" <?php echo ($filter_data == 'mes_atual') ? 'selected' : ''; ?>>Mês atual</option>
                                    <option value="mes_passado" <?php echo ($filter_data == 'mes_passado') ? 'selected' : ''; ?>>Mês passado</option>
                                    <option value="custom" <?php echo ($filter_data == 'custom') ? 'selected' : ''; ?>>Período personalizado</option>
                                </select>
                            </div>
                            
                            <!-- Datas Customizadas (aparecem quando "custom" é selecionado) -->
                            <div class="col-md-4" id="custom_dates" style="display: <?php echo ($filter_data == 'custom') ? 'block' : 'none'; ?>;">
                                <label for="filter_data_inicio" class="form-label">Data Início</label>
                                <input type="date" class="form-control" id="filter_data_inicio" name="filter_data_inicio"
                                       value="<?php echo htmlspecialchars($filter_data_inicio); ?>">
                            </div>
                            
                            <div class="col-md-4" id="custom_dates_fim" style="display: <?php echo ($filter_data == 'custom') ? 'block' : 'none'; ?>;">
                                <label for="filter_data_fim" class="form-label">Data Fim</label>
                                <input type="date" class="form-control" id="filter_data_fim" name="filter_data_fim"
                                       value="<?php echo htmlspecialchars($filter_data_fim); ?>">
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="bi bi-search"></i> Aplicar Filtros
                                </button>
                                <a href="?sort=<?php echo htmlspecialchars($sort); ?>&dir=<?php echo htmlspecialchars($dir); ?>&search=<?php echo htmlspecialchars($search); ?>"
                                   class="btn btn-outline-secondary">
                                    <i class="bi bi-x-circle"></i> Limpar Filtros
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <?php if (isset($_GET['status'])): ?>
            <?php if ($_GET['status'] == 'deleted'): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    Operação e seus recebíveis excluídos com sucesso!
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php elseif ($_GET['status'] == 'error'): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    Erro ao excluir operação: <?php echo htmlspecialchars(isset($_GET['msg']) ? $_GET['msg'] : 'Erro desconhecido.'); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (isset($error_message_count)) echo "<div class='alert alert-danger'>$error_message_count</div>"; ?>
        <?php if (isset($error_message_data)) echo "<div class='alert alert-danger'>$error_message_data</div>"; ?>


        <?php if (empty($operacoes) && (int)$total_results == 0 && empty($search) && !isset($error_message_count) && !isset($error_message_data)): ?>
            <p class="alert alert-info">Nenhuma operação registrada ainda.</p>
        <?php elseif (empty($operacoes) && (int)$total_results > 0): ?>
             <p class="alert alert-warning">Nenhuma operação encontrada para os filtros/busca selecionados nesta página.</p>
             <?php // Inclui paginação mesmo se página vazia ?>
        <?php elseif (empty($operacoes) && (int)$total_results == 0 && !empty($search)): ?>
             <p class="alert alert-warning">Nenhuma operação encontrada para a busca: "<?php echo htmlspecialchars($search); ?>"</p>
        <?php elseif (!empty($operacoes)): // Só mostra a tabela se $operacoes não estiver vazio ?>
            <div class="table-responsive">
                <table class="table table-striped table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <?php
                            $current_filters = [
                                'filter_cedente' => $filter_cedente,
                                'filter_status' => $filter_status,
                                'filter_valor_min' => $filter_valor_min > 0 ? $filter_valor_min : '',
                                'filter_valor_max' => $filter_valor_max > 0 ? $filter_valor_max : '',
                                'filter_data' => $filter_data,
                                'filter_data_inicio' => $filter_data_inicio,
                                'filter_data_fim' => $filter_data_fim
                            ];
                            ?>
                            <th><?php echo getSortLink('id', 'ID', $sort, $dir, $search, $current_filters); ?></th>
                            <th><?php echo getSortLink('cedente_nome', 'Cedente', $sort, $dir, $search, $current_filters); ?></th>
                            <th><?php echo getSortLink('taxa_mensal', 'Taxa', $sort, $dir, $search, $current_filters); ?></th>
                            <th class="text-end"><?php echo getSortLink('total_original_calc', 'T. Original', $sort, $dir, $search, $current_filters); ?></th>
                            <th class="text-end"><?php echo getSortLink('total_liquido_pago_calc', 'T. Líquido', $sort, $dir, $search, $current_filters); ?></th>
                            <th class="text-end"><?php echo getSortLink('total_lucro_liquido_calc', 'Lucro', $sort, $dir, $search, $current_filters); ?></th>
                            <th class="text-center"><?php echo getSortLink('media_dias_operacao', 'Dias Médios', $sort, $dir, $search, $current_filters); ?></th>
                            <th class="text-center"><?php echo getSortLink('status_operacao', 'Status', $sort, $dir, $search, $current_filters); ?></th>
                            <th class="text-center"><?php echo getSortLink('num_recebiveis', '# Rec.', $sort, $dir, $search, $current_filters); ?></th>
                            <th class="text-end"><?php echo getSortLink('saldo_em_aberto', 'Saldo', $sort, $dir, $search, $current_filters); ?></th>
                            <th><?php echo getSortLink('data_base_calculo', 'Data Base', $sort, $dir, $search, $current_filters); ?></th>
                            <th class="text-center acoes-col">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($operacoes as $operacao): ?>
                            <tr>
                                <th scope="row"><?php echo htmlspecialchars($operacao['id']); ?></th>
                                <td><?php echo htmlspecialchars($operacao['cedente_nome'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars(number_format(($operacao['taxa_mensal'] ?? 0) * 100, 2, ',', '.') . '%'); ?></td>
                                <td class="text-end"><?php echo formatHtmlCurrency($operacao['total_original_calc'] ?? 0); ?></td>
                                <td class="text-end"><?php echo formatHtmlCurrency($operacao['total_liquido_pago_calc'] ?? 0); ?></td>
                                <td class="text-end"><?php echo formatHtmlCurrency($operacao['total_lucro_liquido_calc'] ?? 0); ?></td>
                                <td class="text-center">
                                    <?php
                                    if (isset($operacao['media_dias_operacao'])) {
                                        echo htmlspecialchars(round((float)$operacao['media_dias_operacao']));
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </td>
                                <td class="text-center">
                                    <?php echo formatOperacaoStatusBadge($operacao['status_operacao']); ?>
                                </td>
                                <td class="text-center">
                                    <?php
                                        if (isset($operacao['num_recebiveis'])) {
                                            echo htmlspecialchars((int)$operacao['num_recebiveis']);
                                        } else {
                                            echo '0';
                                        }
                                    ?>
                                </td>
                                <td class="text-end">
                                    <?php
                                        // Exibe o saldo formatado como moeda
                                        echo formatHtmlCurrency($operacao['saldo_em_aberto'] ?? 0);
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars(isset($operacao['data_base_calculo']) ? date('d/m/Y', strtotime($operacao['data_base_calculo'])) : '-'); ?></td>
                                <td class="text-center acoes-col">
                                    <a href="detalhes_operacao.php?id=<?php echo $operacao['id']; ?>" class="btn btn-sm btn-info action-icon" title="Ver Detalhes">
                                        <i class="bi bi-eye-fill"></i>
                                    </a>
                                    <a href="excluir_operacao.php?id=<?php echo $operacao['id']; ?>"
                                       class="btn btn-sm btn-danger action-icon delete-operacao-btn"
                                       data-operacao-id="<?php echo $operacao['id']; ?>"
                                       title="Excluir Operação">
                                        <i class="bi bi-trash3-fill"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <?php if (!empty($operacoes)): ?>
                    <tfoot class="table-secondary">
                        <tr>
                            <th colspan="2" class="text-end"><strong>TOTAIS:</strong></th>
                            <th class="text-center">
                                <?php
                                // Calcular média das taxas
                                $soma_taxas = 0;
                                $count_taxas = 0;
                                foreach ($operacoes as $op) {
                                    if (isset($op['taxa_mensal']) && $op['taxa_mensal'] > 0) {
                                        $soma_taxas += $op['taxa_mensal'];
                                        $count_taxas++;
                                    }
                                }
                                $media_taxa = $count_taxas > 0 ? ($soma_taxas / $count_taxas) : 0;
                                echo number_format($media_taxa * 100, 2, ',', '.') . '%';
                                ?>
                            </th>
                            <th class="text-end">
                                <?php
                                // Somar total original
                                $total_original = array_sum(array_column($operacoes, 'total_original_calc'));
                                echo formatHtmlCurrency($total_original);
                                ?>
                            </th>
                            <th class="text-end">
                                <?php
                                // Somar total líquido
                                $total_liquido = array_sum(array_column($operacoes, 'total_liquido_pago_calc'));
                                echo formatHtmlCurrency($total_liquido);
                                ?>
                            </th>
                            <th class="text-end">
                                <?php
                                // Somar lucro
                                $total_lucro = array_sum(array_column($operacoes, 'total_lucro_liquido_calc'));
                                echo formatHtmlCurrency($total_lucro);
                                ?>
                            </th>
                            <th class="text-center">
                                <?php
                                // Calcular média dos dias
                                $soma_dias = 0;
                                $count_dias = 0;
                                foreach ($operacoes as $op) {
                                    if (isset($op['media_dias_operacao']) && $op['media_dias_operacao'] > 0) {
                                        $soma_dias += $op['media_dias_operacao'];
                                        $count_dias++;
                                    }
                                }
                                $media_dias = $count_dias > 0 ? ($soma_dias / $count_dias) : 0;
                                echo round($media_dias);
                                ?>
                            </th>
                            <th class="text-center">
                                <?php
                                // Contar operações por status
                                $status_count = [];
                                foreach ($operacoes as $op) {
                                    $status = $op['status_operacao'];
                                    $status_count[$status] = ($status_count[$status] ?? 0) + 1;
                                }
                                $status_principal = array_keys($status_count, max($status_count))[0];
                                echo "<small>{$status_principal} (" . max($status_count) . ")</small>";
                                ?>
                            </th>
                            <th class="text-center">
                                <?php
                                // Somar número de recebíveis
                                $total_recebiveis = array_sum(array_column($operacoes, 'num_recebiveis'));
                                echo $total_recebiveis;
                                ?>
                            </th>
                            <th class="text-end">
                                <?php
                                // Somar saldo em aberto
                                $total_saldo = array_sum(array_column($operacoes, 'saldo_em_aberto'));
                                echo formatHtmlCurrency($total_saldo);
                                ?>
                            </th>
                            <th colspan="2" class="text-center acoes-col">
                                <small><?php echo count($operacoes); ?> operações</small>
                            </th>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        <?php endif; ?>

            <?php if ((int)$total_pages > 1 && !empty($operacoes)): ?>
                <nav aria-label="Paginação">
                    <ul class="pagination justify-content-center">
                        <?php
                        // Construir URL base preservando todos os filtros
                        $pagination_params = [
                            'sort' => $sort,
                            'dir' => $dir,
                            'search' => $search,
                            'filter_cedente' => $filter_cedente,
                            'filter_status' => $filter_status,
                            'filter_valor_min' => $filter_valor_min > 0 ? $filter_valor_min : '',
                            'filter_valor_max' => $filter_valor_max > 0 ? $filter_valor_max : '',
                            'filter_data' => $filter_data,
                            'filter_data_inicio' => $filter_data_inicio,
                            'filter_data_fim' => $filter_data_fim
                        ];
                        
                        // Remover parâmetros vazios
                        $pagination_params = array_filter($pagination_params, function($value) {
                            return $value !== '' && $value !== null;
                        });
                        
                        $baseUrl = "?" . http_build_query($pagination_params);
                        ?>
                        <li class="page-item <?php echo ((int)$page <= 1) ? 'disabled' : ''; ?>"><a class="page-link" href="<?php echo $baseUrl . '&page=' . ((int)$page - 1); ?>">&laquo;</a></li>
                        <?php
                            $start_page = max(1, (int)$page - 2); $end_page = min((int)$total_pages, (int)$page + 2);
                            if ((int)$page <= 3) $end_page = min((int)$total_pages, 5);
                            if ((int)$page >= ((int)$total_pages - 2)) $start_page = max(1, ((int)$total_pages - 4));
                            if ($start_page > 1) { echo '<li class="page-item"><a class="page-link" href="' . $baseUrl . '&page=1">1</a></li>'; if ($start_page > 2) echo '<li class="page-item disabled"><span class="page-link">...</span></li>'; }
                            for ($i = $start_page; $i <= $end_page; $i++): ?> <li class="page-item <?php echo ((int)$i == (int)$page) ? 'active' : ''; ?>"><a class="page-link" href="<?php echo $baseUrl . '&page=' . (int)$i; ?>"><?php echo (int)$i; ?></a></li> <?php endfor;
                            if ((int)$end_page < (int)$total_pages) { if ((int)$end_page < ((int)$total_pages - 1)) echo '<li class="page-item disabled"><span class="page-link">...</span></li>'; echo '<li class="page-item"><a class="page-link" href="' . $baseUrl . '&page=' . (int)$total_pages . '">' . (int)$total_pages . '</a></li>'; }
                        ?>
                        <li class="page-item <?php echo ((int)$page >= (int)$total_pages) ? 'disabled' : ''; ?>"><a class="page-link" href="<?php echo $baseUrl . '&page=' . ((int)$page + 1); ?>">&raquo;</a></li>
                    </ul>
                </nav>
                 <p class="text-center text-muted">Página <?php echo (int)$page; ?> de <?php echo (int)$total_pages; ?> (Total: <?php echo (int)$total_results; ?> operaç<?php echo (int)$total_results == 1 ? 'ão' : 'ões'; ?>)</p>
            <?php elseif((int)$total_results > 0 && !empty($operacoes)): ?>
                <p class="text-center text-muted">Total: <?php echo (int)$total_results; ?> operaç<?php echo (int)$total_results == 1 ? 'ão' : 'ões'; ?></p>
            <?php endif; ?>

    </div>

    

    <script>
        // Função para mostrar/ocultar campos de data customizada
        function toggleCustomDates() {
            const filterData = document.getElementById('filter_data');
            const customDates = document.getElementById('custom_dates');
            const customDatesFim = document.getElementById('custom_dates_fim');
            
            if (filterData.value === 'custom') {
                customDates.style.display = 'block';
                customDatesFim.style.display = 'block';
            } else {
                customDates.style.display = 'none';
                customDatesFim.style.display = 'none';
            }
        }

        // Inicializar o estado correto ao carregar a página
        document.addEventListener('DOMContentLoaded', function() {
            toggleCustomDates();
            
            // Adicionar animação ao ícone do collapse
            const filtrosCollapse = document.getElementById('filtrosCollapse');
            const chevronIcon = document.querySelector('.card-header i.bi-chevron-down');
            
            if (filtrosCollapse && chevronIcon) {
                filtrosCollapse.addEventListener('show.bs.collapse', function () {
                    chevronIcon.style.transform = 'rotate(180deg)';
                });
                
                filtrosCollapse.addEventListener('hide.bs.collapse', function () {
                    chevronIcon.style.transform = 'rotate(0deg)';
                });
            }
        });
    </script>

    <script>
        // JavaScript para Confirmação de Exclusão
        document.addEventListener('DOMContentLoaded', function () {
            const deleteButtons = document.querySelectorAll('.delete-operacao-btn');

            deleteButtons.forEach(button => {
                button.addEventListener('click', function (event) {
                    event.preventDefault();

                    const operacaoId = this.dataset.operacaoId;
                    const linkUrl = this.href;

                    const confirm1 = confirm(
                        `ATENÇÃO!\n\nTem certeza que deseja excluir a Operação #${operacaoId}?\n\nAVISOS:\n- Esta ação NÃO pode ser desfeita.\n- TODOS os recebíveis associados a esta operação também serão EXCLUÍDOS permanentemente.`
                    );

                    if (confirm1) {
                        const confirm2 = confirm(
                            `CONFIRMAÇÃO FINAL:\n\nExcluir PERMANENTEMENTE a Operação #${operacaoId} e todos os seus recebíveis associados?\n\nClique em 'OK' para confirmar ou 'Cancelar' para desistir.`
                        );

                        if (confirm2) {
                            window.location.href = linkUrl;
                        }
                    }
                });
            });
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
