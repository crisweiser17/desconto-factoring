<?php require_once 'auth_check.php'; ?>
<?php
require_once 'db_connection.php';

// Funções de formatação
if (!function_exists('formatHtmlCurrency')) {
    function formatHtmlCurrency($value) { return 'R$ ' . number_format($value ?? 0, 2, ',', '.'); }
}
if (!function_exists('formatHtmlPercentage')) {
    function formatHtmlPercentage($value, $decimals = 2) { return number_format($value ?? 0, $decimals, ',', '.') . '%'; }
}

// Processamento de filtros
$data_inicio = $_GET['data_inicio'] ?? '';
$data_fim = $_GET['data_fim'] ?? '';
$sacado_id = $_GET['sacado_id'] ?? '';

// Validação de datas
if ($data_inicio && !DateTime::createFromFormat('Y-m-d', $data_inicio)) {
    $data_inicio = '';
}
if ($data_fim && !DateTime::createFromFormat('Y-m-d', $data_fim)) {
    $data_fim = '';
}

// Construção da query principal
$params = [];
$whereClauses = [];
$whereClausesNaoIdentificados = [];
$whereClausesOperacoes = [];

if ($data_inicio) {
    $whereClauses[] = "o.data_operacao >= :data_inicio";
    $whereClausesNaoIdentificados[] = "o.data_operacao >= :data_inicio";
    $whereClausesOperacoes[] = "o2.data_operacao >= :data_inicio";
    $params[':data_inicio'] = $data_inicio;
}
if ($data_fim) {
    $whereClauses[] = "o.data_operacao <= :data_fim";
    $whereClausesNaoIdentificados[] = "o.data_operacao <= :data_fim";
    $whereClausesOperacoes[] = "o2.data_operacao <= :data_fim";
    $params[':data_fim'] = $data_fim;
}
if ($sacado_id) {
    $whereClauses[] = "s.id = :sacado_id";
    $params[':sacado_id'] = $sacado_id;
}

$whereSQL = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";
$whereSQL_nao_identificados = !empty($whereClausesNaoIdentificados) ? "AND " . implode(" AND ", $whereClausesNaoIdentificados) : "";
$whereSQL_operacoes = !empty($whereClausesOperacoes) ? "AND " . implode(" AND ", $whereClausesOperacoes) : "";

try {
    // Query principal: dados por sacado
    $sql = "
        SELECT 
            s.id as sacado_id,
            s.empresa as sacado_nome,
            s.documento_principal as sacado_documento,
            s.tipo_pessoa as sacado_tipo,
            
            -- Capital Alocado (soma dos valores originais dos recebíveis)
            SUM(r.valor_original) as capital_investido,
            
            -- Lucro Estimado Total (evitando duplicação)
            COALESCE((
                SELECT SUM(
                    o2.total_lucro_liquido_calc / (
                        SELECT COUNT(DISTINCT r3.sacado_id) 
                        FROM recebiveis r3 
                        WHERE r3.operacao_id = o2.id AND r3.sacado_id IS NOT NULL
                    )
                ) 
                FROM (
                    SELECT DISTINCT o2.id, o2.total_lucro_liquido_calc
                    FROM operacoes o2 
                    INNER JOIN recebiveis r2 ON r2.operacao_id = o2.id
                    WHERE r2.sacado_id = s.id
                    $whereSQL_operacoes
                ) o2
            ), 0) as lucro_estimado,
            
            -- Número de Operações (usando subquery para evitar duplicação)
            COALESCE((
                SELECT COUNT(DISTINCT o2.id) 
                FROM operacoes o2 
                INNER JOIN recebiveis r2 ON r2.operacao_id = o2.id
                WHERE r2.sacado_id = s.id
                $whereSQL_operacoes
            ), 0) as num_operacoes,
            
            -- Valor Total Original dos Recebíveis
            SUM(r.valor_original) as valor_total_recebiveis,
            
            -- Capital em Risco (recebíveis em aberto)
            SUM(CASE WHEN r.status = 'Em Aberto' THEN r.valor_original ELSE 0 END) as capital_em_risco,
            
            -- Capital com Problema
            SUM(CASE WHEN r.status = 'Problema' THEN r.valor_original ELSE 0 END) as capital_com_problema,
            
            -- Capital Já Recebido
            SUM(CASE WHEN r.status = 'Recebido' THEN r.valor_original ELSE 0 END) as capital_recebido,
            
            -- Lucro Realizado (apenas dos recebidos)
            SUM(CASE WHEN r.status = 'Recebido' THEN (r.valor_original - r.valor_liquido_calc) ELSE 0 END) as lucro_realizado,
            
            -- Compensações (usando subquery para evitar duplicação)
            COALESCE((
                SELECT SUM(o2.valor_total_compensacao) 
                FROM operacoes o2 
                INNER JOIN recebiveis r2 ON r2.operacao_id = o2.id
                WHERE r2.sacado_id = s.id
                $whereSQL_operacoes
            ), 0) as total_compensacoes,
            
            -- Média de dias ponderada (usando subquery para evitar duplicação)
            COALESCE((
                SELECT AVG(o2.media_dias_pond_calc) 
                FROM operacoes o2 
                INNER JOIN recebiveis r2 ON r2.operacao_id = o2.id
                WHERE r2.sacado_id = s.id
                $whereSQL_operacoes
            ), 0) as prazo_medio,
            
            -- Taxa média (usando subquery para evitar duplicação)
            COALESCE((
                SELECT AVG(o2.taxa_mensal) 
                FROM operacoes o2 
                INNER JOIN recebiveis r2 ON r2.operacao_id = o2.id
                WHERE r2.sacado_id = s.id
                $whereSQL_operacoes
            ), 0) as taxa_media
            
        FROM sacados s
        LEFT JOIN recebiveis r ON r.sacado_id = s.id
        LEFT JOIN operacoes o ON r.operacao_id = o.id
        $whereSQL
        GROUP BY s.id, s.empresa, s.documento_principal, s.tipo_pessoa
        HAVING capital_investido > 0
        ORDER BY capital_investido DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $sacados_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Query para sacados não identificados (sacado_id = NULL)
    $sql_nao_identificados = "
        SELECT 
            NULL as sacado_id,
            'Sacado Não Identificado' as sacado_nome,
            NULL as sacado_documento,
            NULL as sacado_tipo,
            
            -- Capital Alocado para recebíveis sem sacado
            SUM(r.valor_original) as capital_investido,
            
            -- Lucro Estimado para recebíveis sem sacado (incluindo operações mistas)
            COALESCE((
                SELECT SUM(
                    o2.total_lucro_liquido_calc * (
                        (SELECT COUNT(*) FROM recebiveis r4 WHERE r4.operacao_id = o2.id AND r4.sacado_id IS NULL) /
                        (SELECT COUNT(*) FROM recebiveis r5 WHERE r5.operacao_id = o2.id)
                    )
                ) 
                FROM (
                    SELECT DISTINCT o2.id, o2.total_lucro_liquido_calc
                    FROM operacoes o2 
                    INNER JOIN recebiveis r2 ON r2.operacao_id = o2.id
                    WHERE r2.sacado_id IS NULL
                    $whereSQL_operacoes
                ) o2
            ), 0) as lucro_estimado,
            
            -- Número de Operações para recebíveis sem sacado
            COALESCE((
                SELECT COUNT(DISTINCT o2.id) 
                FROM operacoes o2 
                INNER JOIN recebiveis r2 ON r2.operacao_id = o2.id
                WHERE r2.sacado_id IS NULL
                $whereSQL_operacoes
            ), 0) as num_operacoes,
            
            -- Valor Total Original dos Recebíveis sem sacado
            SUM(r.valor_original) as valor_total_recebiveis,
            
            -- Capital em Risco (recebíveis em aberto) sem sacado
            SUM(CASE WHEN r.status = 'Em Aberto' THEN r.valor_original ELSE 0 END) as capital_em_risco,
            
            -- Capital com Problema sem sacado
            SUM(CASE WHEN r.status = 'Problema' THEN r.valor_original ELSE 0 END) as capital_com_problema,
            
            -- Capital Já Recebido sem sacado
            SUM(CASE WHEN r.status = 'Recebido' THEN r.valor_original ELSE 0 END) as capital_recebido,
            
            -- Lucro Realizado (apenas dos recebidos) sem sacado
            SUM(CASE WHEN r.status = 'Recebido' THEN (r.valor_original - r.valor_liquido_calc) ELSE 0 END) as lucro_realizado,
            
            -- Compensações para recebíveis sem sacado
            COALESCE((
                SELECT SUM(o2.valor_total_compensacao) 
                FROM operacoes o2 
                INNER JOIN recebiveis r2 ON r2.operacao_id = o2.id
                WHERE r2.sacado_id IS NULL
                $whereSQL_operacoes
            ), 0) as total_compensacoes,
            
            -- Média de dias ponderada para recebíveis sem sacado
            COALESCE((
                SELECT AVG(o2.media_dias_pond_calc) 
                FROM operacoes o2 
                INNER JOIN recebiveis r2 ON r2.operacao_id = o2.id
                WHERE r2.sacado_id IS NULL
                $whereSQL_operacoes
            ), 0) as prazo_medio,
            
            -- Taxa média para recebíveis sem sacado
            COALESCE((
                SELECT AVG(o2.taxa_mensal) 
                FROM operacoes o2 
                INNER JOIN recebiveis r2 ON r2.operacao_id = o2.id
                WHERE r2.sacado_id IS NULL
                $whereSQL_operacoes
            ), 0) as taxa_media
            
        FROM recebiveis r
        LEFT JOIN operacoes o ON r.operacao_id = o.id
        WHERE r.sacado_id IS NULL
        $whereSQL_nao_identificados
        HAVING capital_investido > 0
    ";
    
    $stmt_nao_identificados = $pdo->prepare($sql_nao_identificados);
    $stmt_nao_identificados->execute($params);
    $nao_identificados_data = $stmt_nao_identificados->fetchAll(PDO::FETCH_ASSOC);
    
    // Combinar os dados
    $sacados_data = array_merge($sacados_data, $nao_identificados_data);
    
    // Calcular totais gerais
    $totais = [
        'capital_investido' => 0,
        'lucro_estimado' => 0,
        'capital_em_risco' => 0,
        'capital_com_problema' => 0,
        'capital_recebido' => 0,
        'lucro_realizado' => 0,
        'total_compensacoes' => 0,
        'num_operacoes' => 0
    ];
    
    foreach ($sacados_data as $sacado) {
        $totais['capital_investido'] += $sacado['capital_investido'];
        $totais['lucro_estimado'] += $sacado['lucro_estimado'];
        $totais['capital_em_risco'] += $sacado['capital_em_risco'];
        $totais['capital_com_problema'] += $sacado['capital_com_problema'];
        $totais['capital_recebido'] += $sacado['capital_recebido'];
        $totais['lucro_realizado'] += $sacado['lucro_realizado'];
        $totais['total_compensacoes'] += $sacado['total_compensacoes'];
        $totais['num_operacoes'] += $sacado['num_operacoes'];
    }
    
    // Buscar lista de sacados para o filtro
    $sql_sacados = "SELECT id, empresa, documento_principal FROM sacados ORDER BY empresa";
    $stmt_sacados = $pdo->prepare($sql_sacados);
    $stmt_sacados->execute();
    $lista_sacados = $stmt_sacados->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error_message = "Erro ao buscar dados: " . $e->getMessage();
    $sacados_data = [];
    $totais = [];
    $lista_sacados = [];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório por Sacado - Capital e Risco</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .summary-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .metric-card {
            background: white;
            border-radius: 10px;
            padding: 1rem;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .metric-card:hover {
            transform: translateY(-5px);
        }
        .metric-value {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        .metric-label {
            color: #6c757d;
            font-size: 0.9rem;
        }
        .table-container {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .btn-export {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .btn-export:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
            color: white;
        }
        .filter-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        .status-em-aberto { background-color: #fff3cd; color: #856404; }
        .status-recebido { background-color: #d1edff; color: #0c5460; }
        .status-problema { background-color: #f8d7da; color: #721c24; }
        .positive { color: #28a745; }
        .negative { color: #dc3545; }
        .neutral { color: #6c757d; }
        .table th {
            background-color: #f8f9fa;
            border-top: none;
            font-weight: 600;
            color: #495057;
        }
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(0,0,0,.02);
        }
        .currency {
            font-family: 'Courier New', monospace;
            font-weight: 500;
        }
        .percentage {
            font-weight: 500;
        }
        .total-row {
            background-color: #e9ecef !important;
            font-weight: bold;
            border-top: 2px solid #dee2e6;
        }
        .alert-custom {
            border-radius: 15px;
            border: none;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="bg-light">
    <?php include 'menu.php'; ?>
    
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4">
                    <i class="bi bi-people-fill text-primary"></i>
                    Relatório por Sacado - Capital e Risco
                </h1>
                
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger alert-custom">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <?= htmlspecialchars($error_message) ?>
                    </div>
                <?php endif; ?>
                
                <!-- Filtros -->
                <div class="filter-card">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="data_inicio" class="form-label">
                                <i class="bi bi-calendar-event"></i> Data Início
                            </label>
                            <input type="date" class="form-control" id="data_inicio" name="data_inicio" 
                                   value="<?= htmlspecialchars($data_inicio) ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="data_fim" class="form-label">
                                <i class="bi bi-calendar-event"></i> Data Fim
                            </label>
                            <input type="date" class="form-control" id="data_fim" name="data_fim" 
                                   value="<?= htmlspecialchars($data_fim) ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="sacado_id" class="form-label">
                                <i class="bi bi-person"></i> Sacado
                            </label>
                            <select class="form-select" id="sacado_id" name="sacado_id">
                                <option value="">Todos os Sacados</option>
                                <?php foreach ($lista_sacados as $sacado): ?>
                                    <option value="<?= $sacado['id'] ?>" 
                                            <?= $sacado_id == $sacado['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($sacado['empresa']) ?>
                                        <?php if ($sacado['documento_principal']): ?>
                                            - <?= htmlspecialchars($sacado['documento_principal']) ?>
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-funnel"></i> Filtrar
                            </button>
                        </div>
                    </form>
                </div>
                
                <?php if (!empty($totais)): ?>
                    <!-- Resumo Executivo -->
                    <div class="summary-card">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="metric-card">
                                    <div class="metric-value currency positive">
                                        <?= formatHtmlCurrency($totais['capital_investido']) ?>
                                    </div>
                                    <div class="metric-label">Capital Total Investido</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="metric-card">
                                    <div class="metric-value currency <?= $totais['lucro_estimado'] >= 0 ? 'positive' : 'negative' ?>">
                                        <?= formatHtmlCurrency($totais['lucro_estimado']) ?>
                                    </div>
                                    <div class="metric-label">Lucro Estimado Total</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="metric-card">
                                    <div class="metric-value currency <?= $totais['capital_em_risco'] > 0 ? 'negative' : 'positive' ?>">
                                        <?= formatHtmlCurrency($totais['capital_em_risco']) ?>
                                    </div>
                                    <div class="metric-label">Capital em Risco</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="metric-card">
                                    <div class="metric-value neutral">
                                        <?= number_format($totais['num_operacoes']) ?>
                                    </div>
                                    <div class="metric-label">Total de Operações</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tabela de Dados -->
                    <div class="table-container">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h3 class="mb-0">
                                <i class="bi bi-table"></i>
                                Detalhamento por Sacado
                            </h3>
                            <div>
                                <a href="exportar_csv.php?relatorio=sacados<?= $data_inicio ? '&data_inicio=' . $data_inicio : '' ?><?= $data_fim ? '&data_fim=' . $data_fim : '' ?><?= $sacado_id ? '&sacado_id=' . $sacado_id : '' ?>" 
                                   class="btn btn-export me-2">
                                    <i class="bi bi-file-earmark-spreadsheet"></i> Exportar CSV
                                </a>
                                <a href="export_pdf.php?relatorio=sacados<?= $data_inicio ? '&data_inicio=' . $data_inicio : '' ?><?= $data_fim ? '&data_fim=' . $data_fim : '' ?><?= $sacado_id ? '&sacado_id=' . $sacado_id : '' ?>" 
                                   class="btn btn-export" target="_blank">
                                    <i class="bi bi-file-earmark-pdf"></i> Exportar PDF
                                </a>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Sacado</th>
                                        <th>Documento</th>
                                        <th>Tipo</th>
                                        <th class="text-end">Capital Investido</th>
                                        <th class="text-end">Lucro Estimado</th>
                                        <th class="text-center">Nº Operações</th>
                                        <th class="text-end">Capital em Risco</th>
                                        <th class="text-end">Capital c/ Problema</th>
                                        <th class="text-end">Capital Recebido</th>
                                        <th class="text-end">Lucro Realizado</th>
                                        <th class="text-end">Compensações</th>
                                        <th class="text-center">Prazo Médio</th>
                                        <th class="text-center">Taxa Média</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($sacados_data as $sacado): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($sacado['sacado_nome']) ?></strong>
                                            </td>
                                            <td>
                                                <?php if ($sacado['sacado_documento']): ?>
                                                    <span class="text-muted"><?= htmlspecialchars($sacado['sacado_documento']) ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($sacado['sacado_tipo']): ?>
                                                    <span class="badge bg-secondary"><?= htmlspecialchars($sacado['sacado_tipo']) ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end currency positive">
                                                <?= formatHtmlCurrency($sacado['capital_investido']) ?>
                                            </td>
                                            <td class="text-end currency <?= $sacado['lucro_estimado'] >= 0 ? 'positive' : 'negative' ?>">
                                                <?= formatHtmlCurrency($sacado['lucro_estimado']) ?>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-info"><?= number_format($sacado['num_operacoes']) ?></span>
                                            </td>
                                            <td class="text-end currency <?= $sacado['capital_em_risco'] > 0 ? 'negative' : 'neutral' ?>">
                                                <?= formatHtmlCurrency($sacado['capital_em_risco']) ?>
                                            </td>
                                            <td class="text-end currency <?= $sacado['capital_com_problema'] > 0 ? 'negative' : 'neutral' ?>">
                                                <?= formatHtmlCurrency($sacado['capital_com_problema']) ?>
                                            </td>
                                            <td class="text-end currency positive">
                                                <?= formatHtmlCurrency($sacado['capital_recebido']) ?>
                                            </td>
                                            <td class="text-end currency <?= $sacado['lucro_realizado'] >= 0 ? 'positive' : 'negative' ?>">
                                                <?= formatHtmlCurrency($sacado['lucro_realizado']) ?>
                                            </td>
                                            <td class="text-end currency <?= $sacado['total_compensacoes'] > 0 ? 'negative' : 'neutral' ?>">
                                                <?= formatHtmlCurrency($sacado['total_compensacoes']) ?>
                                            </td>
                                            <td class="text-center">
                                                <?= number_format($sacado['prazo_medio'], 0) ?> dias
                                            </td>
                                            <td class="text-center percentage">
                                                <?= formatHtmlPercentage($sacado['taxa_media']) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="total-row">
                                        <td colspan="3"><strong>TOTAIS</strong></td>
                                        <td class="text-end currency">
                                            <strong><?= formatHtmlCurrency($totais['capital_investido']) ?></strong>
                                        </td>
                                        <td class="text-end currency">
                                            <strong><?= formatHtmlCurrency($totais['lucro_estimado']) ?></strong>
                                        </td>
                                        <td class="text-center">
                                            <strong><?= number_format($totais['num_operacoes']) ?></strong>
                                        </td>
                                        <td class="text-end currency">
                                            <strong><?= formatHtmlCurrency($totais['capital_em_risco']) ?></strong>
                                        </td>
                                        <td class="text-end currency">
                                            <strong><?= formatHtmlCurrency($totais['capital_com_problema']) ?></strong>
                                        </td>
                                        <td class="text-end currency">
                                            <strong><?= formatHtmlCurrency($totais['capital_recebido']) ?></strong>
                                        </td>
                                        <td class="text-end currency">
                                            <strong><?= formatHtmlCurrency($totais['lucro_realizado']) ?></strong>
                                        </td>
                                        <td class="text-end currency">
                                            <strong><?= formatHtmlCurrency($totais['total_compensacoes']) ?></strong>
                                        </td>
                                        <td class="text-center">-</td>
                                        <td class="text-center">-</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info alert-custom">
                        <i class="bi bi-info-circle-fill"></i>
                        Nenhum dado encontrado para os filtros selecionados.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>