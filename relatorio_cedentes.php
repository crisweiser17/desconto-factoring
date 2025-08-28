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
$cedente_id = $_GET['cedente_id'] ?? '';

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
$whereClausesOperacoes = [];

if ($data_inicio) {
    $whereClauses[] = "o.data_operacao >= :data_inicio";
    $whereClausesOperacoes[] = "o2.data_operacao >= :data_inicio";
    $params[':data_inicio'] = $data_inicio;
}
if ($data_fim) {
    $whereClauses[] = "o.data_operacao <= :data_fim";
    $whereClausesOperacoes[] = "o2.data_operacao <= :data_fim";
    $params[':data_fim'] = $data_fim;
}
if ($cedente_id) {
    $whereClauses[] = "c.id = :cedente_id";
    $params[':cedente_id'] = $cedente_id;
}

$whereSQL = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";
$whereSQL_operacoes = !empty($whereClausesOperacoes) ? "AND " . implode(" AND ", $whereClausesOperacoes) : "";

try {
    // Query principal: dados por cedente
    $sql = "
        SELECT 
            c.id as cedente_id,
            c.nome as cedente_nome,
            c.empresa as cedente_empresa,
            
            -- Capital Alocado (usando subquery para evitar duplicação)
            COALESCE((
                SELECT SUM(o2.total_original_calc) 
                FROM operacoes o2 
                WHERE o2.cedente_id = c.id
                $whereSQL_operacoes
            ), 0) as capital_investido,
            
            -- Lucro Estimado Total (usando subquery para evitar duplicação)
            COALESCE((
                SELECT SUM(o2.total_lucro_liquido_calc) 
                FROM operacoes o2 
                WHERE o2.cedente_id = c.id
                $whereSQL_operacoes
            ), 0) as lucro_estimado,
            
            -- Número de Operações (usando subquery para evitar duplicação)
            COALESCE((
                SELECT COUNT(o2.id) 
                FROM operacoes o2 
                WHERE o2.cedente_id = c.id
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
                WHERE o2.cedente_id = c.id
                $whereSQL_operacoes
            ), 0) as total_compensacoes,
            
            -- Média de dias ponderada (usando subquery para evitar duplicação)
            COALESCE((
                SELECT AVG(o2.media_dias_pond_calc) 
                FROM operacoes o2 
                WHERE o2.cedente_id = c.id
                $whereSQL_operacoes
            ), 0) as prazo_medio,
            
            -- Taxa média (usando subquery para evitar duplicação)
            COALESCE((
                SELECT AVG(o2.taxa_mensal) 
                FROM operacoes o2 
                WHERE o2.cedente_id = c.id
                $whereSQL_operacoes
            ), 0) as taxa_media
            
        FROM cedentes c
        LEFT JOIN recebiveis r ON r.operacao_id IN (
            SELECT o3.id FROM operacoes o3 WHERE o3.cedente_id = c.id
            $whereSQL_operacoes
        )
        GROUP BY c.id, c.nome, c.empresa
        HAVING capital_investido > 0
        ORDER BY capital_investido DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $cedentes_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
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
    
    foreach ($cedentes_data as $cedente) {
        $totais['capital_investido'] += $cedente['capital_investido'];
        $totais['lucro_estimado'] += $cedente['lucro_estimado'];
        $totais['capital_em_risco'] += $cedente['capital_em_risco'];
        $totais['capital_com_problema'] += $cedente['capital_com_problema'];
        $totais['capital_recebido'] += $cedente['capital_recebido'];
        $totais['lucro_realizado'] += $cedente['lucro_realizado'];
        $totais['total_compensacoes'] += $cedente['total_compensacoes'];
        $totais['num_operacoes'] += $cedente['num_operacoes'];
    }
    
    // Buscar lista de cedentes para o filtro
    $sql_cedentes = "SELECT id, nome, empresa FROM cedentes ORDER BY nome";
    $stmt_cedentes = $pdo->prepare($sql_cedentes);
    $stmt_cedentes->execute();
    $lista_cedentes = $stmt_cedentes->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error_message = "Erro ao buscar dados: " . $e->getMessage();
    $cedentes_data = [];
    $totais = [];
    $lista_cedentes = [];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório por Cedente - Capital e Risco</title>
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
                <h2><i class="bi bi-people-fill me-2"></i>Relatório por Cedente - Capital e Risco</h2>
                <p class="text-muted">Análise de capital alocado, lucro e diluição de risco por cedente</p>
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
                        <label class="form-label">Cedente</label>
                        <select class="form-select" name="cedente_id">
                            <option value="">Todos os cedentes</option>
                            <?php foreach ($lista_cedentes as $cedente): ?>
                                <option value="<?php echo $cedente['id']; ?>" <?php echo $cedente_id == $cedente['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cedente['nome'] . ($cedente['empresa'] ? ' - ' . $cedente['empresa'] : '')); ?>
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
                                Soma de todos os valores originais dos recebíveis negociados.<br>
                                Representa o valor bruto total da carteira de factoring.
                            </span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card tooltip-kpi">
                            <div class="metric-value text-success"><?php echo formatHtmlCurrency($totais['lucro_estimado']); ?></div>
                            <div class="metric-label">Lucro Estimado Total</div>
                            <span class="tooltip-text">
                                <strong>Lucro Estimado Total</strong><br>
                                Soma de todos os lucros projetados da carteira.<br>
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
                                Soma dos valores de todos os recebíveis com status "Em Aberto".<br>
                                Representa o valor total ainda pendente de recebimento.
                            </span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card tooltip-kpi">
                            <div class="metric-value text-info"><?php echo count($cedentes_data); ?></div>
                            <div class="metric-label">Cedentes Ativos</div>
                            <span class="tooltip-text">
                                <strong>Cedentes Ativos</strong><br>
                                Número total de cedentes que possuem operações no período selecionado.<br>
                                Indica a diversificação da carteira de clientes.
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Tabela Detalhada -->
            <?php if (!empty($cedentes_data)): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Cedente</th>
                            <th class="text-end">
                                <span class="tooltip-kpi">
                                    Volume Transacionado
                                    <span class="tooltip-text">
                                        <strong>Volume Total Transacionado</strong><br>
                                        Soma dos valores originais de todos os recebíveis negociados com este cedente.<br>
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
                                        Indica o percentual de retorno sobre o capital investido neste cedente.
                                    </span>
                                </span>
                            </th>
                            <th class="text-end">
                                <span class="tooltip-kpi">
                                    Capital em Risco
                                    <span class="tooltip-text">
                                        <strong>Capital em Risco</strong><br>
                                        Soma dos valores originais dos recebíveis com status "Em Aberto".<br>
                                        Representa o valor que ainda está pendente de recebimento.
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
                                        Lucro efetivamente obtido com recebíveis já liquidados (status "Pago").<br>
                                        Representa o retorno real já confirmado das operações.
                                    </span>
                                </span>
                            </th>
                            <th class="text-center">
                                <span class="tooltip-kpi">
                                    Concentração
                                    <span class="tooltip-text">
                                        <strong>Concentração de Risco</strong><br>
                                        (Volume do Cedente ÷ Volume Total da Carteira) × 100<br>
                                        Indica o percentual que este cedente representa na carteira total.<br>
                                        Ajuda a identificar concentração excessiva de risco em poucos cedentes.
                                    </span>
                                </span>
                            </th>
                            <th class="text-center">
                                <span class="tooltip-kpi">
                                    Operações
                                    <span class="tooltip-text">
                                        <strong>Número de Operações</strong><br>
                                        Quantidade total de operações realizadas com este cedente.<br>
                                        Indica o nível de relacionamento e atividade comercial.
                                    </span>
                                </span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cedentes_data as $cedente): 
                            $margem = $cedente['capital_investido'] > 0 ? ($cedente['lucro_estimado'] / $cedente['capital_investido']) * 100 : 0;
                            $risco_pct = $cedente['capital_investido'] > 0 ? ($cedente['capital_em_risco'] / $cedente['capital_investido']) * 100 : 0;
                            $concentracao = $totais['capital_investido'] > 0 ? ($cedente['capital_investido'] / $totais['capital_investido']) * 100 : 0;
                            
                            // Classificação de risco
                            $risk_class = 'risk-low';
                            if ($risco_pct > 70) $risk_class = 'risk-high';
                            elseif ($risco_pct > 40) $risk_class = 'risk-medium';
                        ?>
                        <tr class="<?php echo $risk_class; ?>">
                            <td>
                                <strong><?php echo htmlspecialchars($cedente['cedente_nome']); ?></strong>
                                <?php if ($cedente['cedente_empresa']): ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($cedente['cedente_empresa']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="text-end"><?php echo formatHtmlCurrency($cedente['capital_investido']); ?></td>
                            <td class="text-end text-success"><?php echo formatHtmlCurrency($cedente['lucro_estimado']); ?></td>
                            <td class="text-end"><?php echo formatHtmlPercentage($margem, 1); ?></td>
                            <td class="text-end text-warning"><?php echo formatHtmlCurrency($cedente['capital_em_risco']); ?></td>
                            <td class="text-end"><?php echo formatHtmlPercentage($risco_pct, 1); ?></td>
                            <td class="text-end text-success"><?php echo formatHtmlCurrency($cedente['lucro_realizado']); ?></td>
                            <td class="text-center">
                                <div class="concentration-bar">
                                    <div class="concentration-marker" style="left: <?php echo min(100, $concentracao); ?>%;"></div>
                                </div>
                                <small><?php echo formatHtmlPercentage($concentracao, 1); ?></small>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-primary"><?php echo $cedente['num_operacoes']; ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-secondary">
                        <tr>
                            <th>TOTAL</th>
                            <th class="text-end"><?php echo formatHtmlCurrency($totais['capital_investido']); ?></th>
                            <th class="text-end"><?php echo formatHtmlCurrency($totais['lucro_estimado']); ?></th>
                            <th class="text-end"><?php echo $totais['capital_investido'] > 0 ? formatHtmlPercentage(($totais['lucro_estimado'] / $totais['capital_investido']) * 100, 1) : '0%'; ?></th>
                            <th class="text-end"><?php echo formatHtmlCurrency($totais['capital_em_risco']); ?></th>
                            <th class="text-end"><?php echo $totais['capital_investido'] > 0 ? formatHtmlPercentage(($totais['capital_em_risco'] / $totais['capital_investido']) * 100, 1) : '0%'; ?></th>
                            <th class="text-end"><?php echo formatHtmlCurrency($totais['lucro_realizado']); ?></th>
                            <th class="text-center">100%</th>
                            <th class="text-center"><?php echo $totais['num_operacoes']; ?></th>
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