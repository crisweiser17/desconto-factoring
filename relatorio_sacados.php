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
    // Para sacados não identificados, não aplicamos filtro de sacado_id
    // pois eles têm sacado_id = NULL
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
                FROM operacoes o2
                WHERE EXISTS (
                    SELECT 1 
                    FROM recebiveis r2 
                    WHERE r2.operacao_id = o2.id 
                      AND r2.sacado_id = s.id
                )
                $whereSQL_operacoes
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
    // Só executa se não há filtro por sacado específico
    $nao_identificados_data = [];
    if (!$sacado_id) {
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
    }
    
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
            font-size: 0.9rem;
            color: #6c757d;
        }
        .risk-low { border-left: 4px solid #28a745; }
        .risk-medium { border-left: 4px solid #ffc107; }
        .risk-high { border-left: 4px solid #dc3545; }
        .concentration-bar {
            height: 20px;
            background: linear-gradient(90deg, #28a745 0%, #ffc107 50%, #dc3545 100%);
            border-radius: 10px;
            position: relative;
        }
        .concentration-marker {
            position: absolute;
            top: -5px;
            width: 2px;
            height: 30px;
            background: #000;
        }
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        /* Tooltips personalizados - mais elegantes */
        .tooltip-kpi {
            position: relative;
            display: inline-block;
            width: 100%;
        }
        .tooltip-kpi .tooltip-text {
            visibility: hidden;
            width: 280px;
            background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
            color: #fff;
            text-align: left;
            border-radius: 8px;
            padding: 12px 16px;
            position: absolute;
            z-index: 1000;
            bottom: 120%;
            left: 50%;
            margin-left: -140px;
            opacity: 0;
            transition: all 0.3s ease;
            font-size: 0.8rem;
            line-height: 1.5;
            box-shadow: 0 8px 25px rgba(0,0,0,0.25);
            border: 1px solid rgba(255,255,255,0.1);
        }
        .tooltip-kpi .tooltip-text::after {
            content: "";
            position: absolute;
            top: 100%;
            left: 50%;
            margin-left: -6px;
            border-width: 6px;
            border-style: solid;
            border-color: #2d3748 transparent transparent transparent;
        }
        .tooltip-kpi:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <?php include 'menu.php'; ?>
    
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <h2><i class="bi bi-building me-2"></i>Relatório por Sacado - Capital e Risco</h2>
                <p class="text-muted">Análise de capital alocado, lucro e diluição de risco por sacado</p>
            </div>
        </div>
        
        <!-- Filtros -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Data Início</label>
                        <input type="date" class="form-control" name="data_inicio" value="<?php echo htmlspecialchars($data_inicio); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Data Fim</label>
                        <input type="date" class="form-control" name="data_fim" value="<?php echo htmlspecialchars($data_fim); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Sacado</label>
                        <select class="form-select" name="sacado_id">
                            <option value="">Todos os sacados</option>
                            <?php foreach ($lista_sacados as $sacado): ?>
                                <option value="<?php echo $sacado['id']; ?>" <?php echo $sacado_id == $sacado['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($sacado['empresa'] . ($sacado['documento_principal'] ? ' - ' . $sacado['documento_principal'] : '')); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary d-block w-100">
                            <i class="bi bi-search"></i> Filtrar
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php else: ?>
            
            <!-- Resumo Geral -->
            <?php if (!empty($totais)): ?>
            <div class="summary-card">
                <h4 class="mb-4"><i class="bi bi-graph-up me-2"></i>Resumo Geral</h4>
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="metric-card tooltip-kpi">
                            <div class="metric-value text-primary"><?php echo formatHtmlCurrency($totais['capital_investido']); ?></div>
                            <div class="metric-label">Volume Total Transacionado</div>
                            <span class="tooltip-text">
                                <strong>Volume Total Transacionado</strong><br>
                                Soma de todos os valores originais dos recebíveis negociados com os sacados.<br>
                                Representa o valor bruto total da carteira de factoring por sacado.
                            </span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card tooltip-kpi">
                            <div class="metric-value text-success"><?php echo formatHtmlCurrency($totais['lucro_estimado']); ?></div>
                            <div class="metric-label">Lucro Estimado Total</div>
                            <span class="tooltip-text">
                                <strong>Lucro Estimado Total</strong><br>
                                Soma de todos os lucros projetados da carteira por sacado.<br>
                                Diferença entre valores originais e valores pagos aos cedentes.
                            </span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card tooltip-kpi">
                            <div class="metric-value text-warning"><?php echo formatHtmlCurrency($totais['capital_em_risco']); ?></div>
                            <div class="metric-label">Capital em Risco</div>
                            <span class="tooltip-text">
                                <strong>Capital Total em Risco</strong><br>
                                Soma dos valores de todos os recebíveis com status "Em Aberto" por sacado.<br>
                                Representa o valor total ainda pendente de recebimento.
                            </span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card tooltip-kpi">
                            <div class="metric-value text-info"><?php echo count($sacados_data); ?></div>
                            <div class="metric-label">Sacados Ativos</div>
                            <span class="tooltip-text">
                                <strong>Sacados Ativos</strong><br>
                                Número total de sacados que possuem operações no período selecionado.<br>
                                Indica a diversificação da carteira de devedores.
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Tabela Detalhada -->
            <?php if (!empty($sacados_data)): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Sacado</th>
                            <th class="text-end">
                                <span class="tooltip-kpi">
                                    Volume Transacionado
                                    <span class="tooltip-text">
                                        <strong>Volume Total Transacionado</strong><br>
                                        Soma dos valores originais de todos os recebíveis negociados com este sacado.<br>
                                        Representa o valor total dos títulos comprados e o volume bruto das operações de factoring.
                                    </span>
                                </span>
                            </th>
                            <th class="text-end">
                                <span class="tooltip-kpi">
                                    Lucro Estimado
                                    <span class="tooltip-text">
                                        <strong>Lucro Estimado Total</strong><br>
                                        Diferença entre o valor original dos recebíveis e o valor pago ao cedente.<br>
                                        Representa o lucro projetado considerando todos os recebíveis ainda não liquidados.
                                    </span>
                                </span>
                            </th>
                            <th class="text-end">
                                <span class="tooltip-kpi">
                                    Margem %
                                    <span class="tooltip-text">
                                        <strong>Margem de Lucro Percentual</strong><br>
                                        (Lucro Estimado ÷ Volume Transacionado) × 100<br>
                                        Indica o percentual de retorno sobre o capital investido neste sacado.
                                    </span>
                                </span>
                            </th>
                            <th class="text-end">
                                <span class="tooltip-kpi">
                                    Capital em Risco
                                    <span class="tooltip-text">
                                        <strong>Capital em Risco</strong><br>
                                        Soma dos valores originais dos recebíveis com status "Em Aberto".<br>
                                        Representa o valor que ainda está pendente de recebimento deste sacado.
                                    </span>
                                </span>
                            </th>
                            <th class="text-end">
                                <span class="tooltip-kpi">
                                    % Risco
                                    <span class="tooltip-text">
                                        <strong>Percentual de Risco</strong><br>
                                        (Capital em Risco ÷ Volume Transacionado) × 100<br>
                                        Indica quanto % do capital investido ainda está pendente de recebimento.<br>
                                        <strong>Verde:</strong> ≤ 40% (Baixo risco)<br>
                                        <strong>Amarelo:</strong> 41-70% (Risco médio)<br>
                                        <strong>Vermelho:</strong> > 70% (Alto risco)
                                    </span>
                                </span>
                            </th>
                            <th class="text-end">
                                <span class="tooltip-kpi">
                                    Lucro Realizado
                                    <span class="tooltip-text">
                                        <strong>Lucro Realizado</strong><br>
                                        Lucro efetivamente obtido com recebíveis já liquidados (status "Recebido").<br>
                                        Representa o retorno real já confirmado das operações com este sacado.
                                    </span>
                                </span>
                            </th>
                            <th class="text-center">
                                <span class="tooltip-kpi">
                                    Concentração
                                    <span class="tooltip-text">
                                        <strong>Concentração de Risco</strong><br>
                                        Percentual que este sacado representa no portfólio total.<br>
                                        Indica o nível de concentração de risco em um único devedor.<br>
                                        <strong>Verde:</strong> ≤ 10% (Baixa concentração)<br>
                                        <strong>Amarelo:</strong> 11-25% (Concentração média)<br>
                                        <strong>Vermelho:</strong> > 25% (Alta concentração)
                                    </span>
                                </span>
                            </th>
                            <th class="text-center">Operações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sacados_data as $sacado): 
                            $margem = $sacado['capital_investido'] > 0 ? ($sacado['lucro_estimado'] / $sacado['capital_investido']) * 100 : 0;
                            $risco_pct = $sacado['capital_investido'] > 0 ? ($sacado['capital_em_risco'] / $sacado['capital_investido']) * 100 : 0;
                            $concentracao = $totais['capital_investido'] > 0 ? ($sacado['capital_investido'] / $totais['capital_investido']) * 100 : 0;
                            
                            // Classificação de risco
                            $risk_class = 'risk-low';
                            if ($risco_pct > 70) $risk_class = 'risk-high';
                            elseif ($risco_pct > 40) $risk_class = 'risk-medium';
                        ?>
                            <tr class="<?php echo $risk_class; ?>">
                                <td>
                                    <strong><?php echo htmlspecialchars($sacado['sacado_nome'] ?? ''); ?></strong>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($sacado['sacado_documento'] ?? ''); ?></small>
                                    <br><span class="badge <?php echo $sacado['sacado_tipo'] == 'PF' ? 'bg-info' : 'bg-secondary'; ?>">
                                        <?php echo $sacado['sacado_tipo'] == 'PF' ? 'Pessoa Física' : 'Pessoa Jurídica'; ?>
                                    </span>
                                </td>
                                <td class="text-end"><?php echo formatHtmlCurrency($sacado['capital_investido']); ?></td>
                                <td class="text-end text-success"><?php echo formatHtmlCurrency($sacado['lucro_estimado']); ?></td>
                                <td class="text-end"><?php echo formatHtmlPercentage($margem, 1); ?></td>
                                <td class="text-end text-warning"><?php echo formatHtmlCurrency($sacado['capital_em_risco']); ?></td>
                                <td class="text-end">
                                    <span class="<?php 
                                        if ($risco_pct <= 40) echo 'text-success';
                                        elseif ($risco_pct <= 70) echo 'text-warning';
                                        else echo 'text-danger';
                                    ?>">
                                        <?php echo formatHtmlPercentage($risco_pct, 1); ?>
                                    </span>
                                </td>
                                <td class="text-end text-success"><?php echo formatHtmlCurrency($sacado['lucro_realizado']); ?></td>
                                <td class="text-center">
                                    <div class="concentration-bar">
                                        <div class="concentration-marker" style="left: <?php echo min(100, $concentracao); ?>%;"></div>
                                    </div>
                                    <small class="<?php 
                                        if ($concentracao <= 10) echo 'text-success';
                                        elseif ($concentracao <= 25) echo 'text-warning';
                                        else echo 'text-danger';
                                    ?>">
                                        <?php echo formatHtmlPercentage($concentracao, 1); ?>
                                    </small>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-primary"><?php echo $sacado['num_operacoes']; ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <th>TOTAIS</th>
                            <th class="text-end"><?php echo formatHtmlCurrency($totais['capital_investido']); ?></th>
                            <th class="text-end text-success"><?php echo formatHtmlCurrency($totais['lucro_estimado']); ?></th>
                            <th class="text-end">
                                <?php echo formatHtmlPercentage($totais['capital_investido'] > 0 ? ($totais['lucro_estimado'] / $totais['capital_investido']) * 100 : 0, 1); ?>
                            </th>
                            <th class="text-end text-warning"><?php echo formatHtmlCurrency($totais['capital_em_risco']); ?></th>
                            <th class="text-end">
                                <?php echo formatHtmlPercentage($totais['capital_investido'] > 0 ? ($totais['capital_em_risco'] / $totais['capital_investido']) * 100 : 0, 1); ?>
                            </th>
                            <th class="text-end text-success"><?php echo formatHtmlCurrency($totais['lucro_realizado']); ?></th>
                            <th class="text-center">100,0%</th>
                            <th class="text-center">
                                <span class="badge bg-primary"><?php echo $totais['num_operacoes']; ?></span>
                            </th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>Nenhum dado encontrado para os filtros selecionados.
                </div>
            <?php endif; ?>
            
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>