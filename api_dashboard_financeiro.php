<?php
session_start();
require_once 'db_connection.php';

header('Content-Type: application/json; charset=utf-8');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Não autorizado.']);
    exit;
}

/**
 * Resolve um período em [data_inicio, data_fim, prev_inicio, prev_fim].
 * Quando period = 'all', retorna null (sem filtro de período).
 */
function resolverPeriodo(string $period): ?array {
    $hoje = date('Y-m-d');
    switch ($period) {
        case 'this_month':
            $ini = date('Y-m-01');
            $fim = date('Y-m-t');
            $prevIni = date('Y-m-01', strtotime('first day of last month'));
            $prevFim = date('Y-m-t', strtotime('last day of last month'));
            return ['ini' => $ini, 'fim' => $fim, 'prev_ini' => $prevIni, 'prev_fim' => $prevFim];
        case 'last_month':
            $ini = date('Y-m-01', strtotime('first day of last month'));
            $fim = date('Y-m-t', strtotime('last day of last month'));
            $prevIni = date('Y-m-01', strtotime('first day of -2 months'));
            $prevFim = date('Y-m-t', strtotime('last day of -2 months'));
            return ['ini' => $ini, 'fim' => $fim, 'prev_ini' => $prevIni, 'prev_fim' => $prevFim];
        case 'last_30':
            $ini = date('Y-m-d', strtotime('-30 days'));
            $fim = $hoje;
            $prevIni = date('Y-m-d', strtotime('-60 days'));
            $prevFim = date('Y-m-d', strtotime('-31 days'));
            return ['ini' => $ini, 'fim' => $fim, 'prev_ini' => $prevIni, 'prev_fim' => $prevFim];
        case 'last_90':
            $ini = date('Y-m-d', strtotime('-90 days'));
            $fim = $hoje;
            $prevIni = date('Y-m-d', strtotime('-180 days'));
            $prevFim = date('Y-m-d', strtotime('-91 days'));
            return ['ini' => $ini, 'fim' => $fim, 'prev_ini' => $prevIni, 'prev_fim' => $prevFim];
        case 'this_year':
            $ini = date('Y-01-01');
            $fim = date('Y-12-31');
            $prevIni = date('Y-01-01', strtotime('-1 year'));
            $prevFim = date('Y-12-31', strtotime('-1 year'));
            return ['ini' => $ini, 'fim' => $fim, 'prev_ini' => $prevIni, 'prev_fim' => $prevFim];
        case 'all':
        default:
            return null;
    }
}

try {
    $period = $_GET['period'] ?? 'all';
    $rangePeriod = resolverPeriodo($period);
    $dashboardData = [
        'period' => $period,
        'geral' => [
            'capital_adiantado' => 0,
            'amortizado' => 0,
            'lucro_realizado' => 0,
            'lucro_projetado' => 0,
            'inadimplencia' => 0,
            'inadimplencia_capital' => 0
        ],
        'emprestimo' => [
            'capital_adiantado' => 0,
            'amortizado' => 0,
            'lucro_realizado' => 0,
            'lucro_projetado' => 0,
            'inadimplencia' => 0,
            'inadimplencia_capital' => 0,
            'top_5_tomadores' => []
        ],
        'antecipacao' => [
            'capital_adiantado' => 0,
            'amortizado' => 0,
            'lucro_realizado' => 0,
            'lucro_projetado' => 0,
            'inadimplencia' => 0,
            'inadimplencia_capital' => 0,
            'top_5_cedentes' => [],
            'top_5_sacados' => []
        ],
        'grafico_lucro_12m' => [
            'meses' => [],
            'emprestimo' => [],
            'antecipacao' => []
        ],
        'grafico_recebimentos' => [
            'meses' => [],
            'emprestimo' => [],
            'antecipacao' => []
        ],
        'aging' => [
            'ate_30_dias' => 0,
            'de_31_a_60_dias' => 0,
            'de_61_a_90_dias' => 0,
            'mais_de_90_dias' => 0
        ]
    ];

    // 1. Métricas principais por tipo_operacao
    // Para "Parcialmente Compensado", aplica proporção pro-rata baseada em valor_recebido / valor_original
    // Inadimplência: separa em valor de face (valor original devido) e capital em risco (valor adiantado)
    // Métricas de fluxo (capital_adiantado, amortizado, lucro_realizado) respeitam o período se houver filtro;
    // métricas de stock (inadimplência, lucro_projetado) sempre são calculadas no presente.
    // PDO::ATTR_EMULATE_PREPARES está false: cada placeholder so pode aparecer uma vez,
    // entao usamos posicionais e replicamos os valores em cada CASE.
    if ($rangePeriod === null) {
        $capitalFilter = '1=1';
        $recebFilter = '1=1';
        // capitalFilter aparece 1x, recebFilter aparece 4x → nenhum placeholder
        $params = [];
    } else {
        $capitalFilter = 'DATE(o.data_operacao) BETWEEN ? AND ?';
        $recebFilter = 'DATE(r.data_recebimento) BETWEEN ? AND ?';
        // ordem: capital (1x), depois recebFilter (4x)
        $params = function (array $range): array {
            return [
                $range['ini'], $range['fim'],
                $range['ini'], $range['fim'],
                $range['ini'], $range['fim'],
                $range['ini'], $range['fim'],
                $range['ini'], $range['fim'],
            ];
        };
    }

    $sqlMetrics = "
        SELECT
            o.tipo_operacao,
            SUM(CASE WHEN $capitalFilter THEN r.valor_liquido_calc ELSE 0 END) as capital_adiantado,
            SUM(CASE
                WHEN ($recebFilter) AND r.status IN ('Recebido', 'Compensado') THEN r.valor_liquido_calc
                WHEN ($recebFilter) AND r.status = 'Parcialmente Compensado' AND r.valor_original > 0
                    THEN r.valor_liquido_calc * (COALESCE(r.valor_recebido, 0) / r.valor_original)
                ELSE 0
            END) as amortizado,
            SUM(CASE
                WHEN ($recebFilter) AND r.status IN ('Recebido', 'Compensado') THEN (r.valor_original - r.valor_liquido_calc)
                WHEN ($recebFilter) AND r.status = 'Parcialmente Compensado' AND r.valor_original > 0
                    THEN COALESCE(r.valor_recebido, 0) - (r.valor_liquido_calc * (COALESCE(r.valor_recebido, 0) / r.valor_original))
                ELSE 0
            END) as lucro_realizado,
            SUM(CASE WHEN r.status IN ('Problema', 'Em Aberto') AND r.data_vencimento < CURDATE() THEN r.valor_original ELSE 0 END) as inadimplencia_face,
            SUM(CASE WHEN r.status IN ('Problema', 'Em Aberto') AND r.data_vencimento < CURDATE() THEN r.valor_liquido_calc ELSE 0 END) as inadimplencia_capital,
            SUM(CASE WHEN r.status = 'Em Aberto' AND r.data_vencimento >= CURDATE() THEN (r.valor_original - r.valor_liquido_calc) ELSE 0 END) as lucro_projetado
        FROM recebiveis r
        JOIN operacoes o ON r.operacao_id = o.id
        GROUP BY o.tipo_operacao
    ";

    $stmtMetrics = $pdo->prepare($sqlMetrics);
    $stmtMetrics->execute($rangePeriod === null ? [] : $params($rangePeriod));
    $metrics = $stmtMetrics->fetchAll(PDO::FETCH_ASSOC);

    // 1.b. Métricas do período anterior (para trend)
    $dashboardData['previous'] = [
        'geral' => ['capital_adiantado' => 0, 'amortizado' => 0, 'lucro_realizado' => 0],
        'emprestimo' => ['capital_adiantado' => 0, 'amortizado' => 0, 'lucro_realizado' => 0],
        'antecipacao' => ['capital_adiantado' => 0, 'amortizado' => 0, 'lucro_realizado' => 0]
    ];
    if ($rangePeriod !== null) {
        $stmtPrev = $pdo->prepare($sqlMetrics);
        $stmtPrev->execute($params([
            'ini' => $rangePeriod['prev_ini'],
            'fim' => $rangePeriod['prev_fim'],
        ]));
        foreach ($stmtPrev->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $tipo = strtolower($row['tipo_operacao']);
            if (isset($dashboardData['previous'][$tipo])) {
                $dashboardData['previous'][$tipo]['capital_adiantado'] = (float) $row['capital_adiantado'];
                $dashboardData['previous'][$tipo]['amortizado'] = (float) $row['amortizado'];
                $dashboardData['previous'][$tipo]['lucro_realizado'] = (float) $row['lucro_realizado'];
                $dashboardData['previous']['geral']['capital_adiantado'] += (float) $row['capital_adiantado'];
                $dashboardData['previous']['geral']['amortizado'] += (float) $row['amortizado'];
                $dashboardData['previous']['geral']['lucro_realizado'] += (float) $row['lucro_realizado'];
            }
        }
    }

    foreach ($metrics as $row) {
        $tipo = strtolower($row['tipo_operacao']);
        if (isset($dashboardData[$tipo])) {
            $dashboardData[$tipo]['capital_adiantado'] = (float) $row['capital_adiantado'];
            $dashboardData[$tipo]['amortizado'] = (float) $row['amortizado'];
            $dashboardData[$tipo]['lucro_realizado'] = (float) $row['lucro_realizado'];
            $dashboardData[$tipo]['inadimplencia'] = (float) $row['inadimplencia_face'];
            $dashboardData[$tipo]['inadimplencia_capital'] = (float) $row['inadimplencia_capital'];
            $dashboardData[$tipo]['lucro_projetado'] = (float) $row['lucro_projetado'];

            // Soma Geral
            $dashboardData['geral']['capital_adiantado'] += $row['capital_adiantado'];
            $dashboardData['geral']['amortizado'] += $row['amortizado'];
            $dashboardData['geral']['lucro_realizado'] += $row['lucro_realizado'];
            $dashboardData['geral']['inadimplencia'] += $row['inadimplencia_face'];
            $dashboardData['geral']['inadimplencia_capital'] += $row['inadimplencia_capital'];
            $dashboardData['geral']['lucro_projetado'] += $row['lucro_projetado'];
        }
    }

    // 2. Top 5 Tomadores de Empréstimo (Sacado em Empréstimo)
    $sqlTopTomadores = "
        SELECT 
            s.empresa as nome,
            SUM(CASE WHEN r.status IN ('Problema', 'Em Aberto') THEN r.valor_original ELSE 0 END) as valor_em_aberto,
            SUM(CASE WHEN r.status IN ('Problema', 'Em Aberto') AND r.data_vencimento < CURDATE() THEN r.valor_original ELSE 0 END) as valor_vencido
        FROM recebiveis r
        JOIN operacoes o ON r.operacao_id = o.id
        JOIN clientes s ON r.sacado_id = s.id
        WHERE o.tipo_operacao = 'emprestimo'
        GROUP BY s.id, s.empresa
        HAVING valor_em_aberto > 0
        ORDER BY valor_em_aberto DESC
        LIMIT 5
    ";
    $stmtTomadores = $pdo->query($sqlTopTomadores);
    $dashboardData['emprestimo']['top_5_tomadores'] = $stmtTomadores->fetchAll(PDO::FETCH_ASSOC);

    // 3. Top 5 Cedentes de Antecipação
    $sqlTopCedentesAnt = "
        SELECT 
            c.empresa as nome,
            SUM(CASE WHEN r.status IN ('Problema', 'Em Aberto') THEN r.valor_original ELSE 0 END) as valor_em_aberto,
            SUM(CASE WHEN r.status IN ('Problema', 'Em Aberto') AND r.data_vencimento < CURDATE() THEN r.valor_original ELSE 0 END) as valor_vencido
        FROM recebiveis r
        JOIN operacoes o ON r.operacao_id = o.id
        JOIN clientes c ON o.cedente_id = c.id
        WHERE o.tipo_operacao = 'antecipacao'
        GROUP BY c.id, c.empresa
        HAVING valor_em_aberto > 0
        ORDER BY valor_em_aberto DESC
        LIMIT 5
    ";
    $stmtCedentesAnt = $pdo->query($sqlTopCedentesAnt);
    $dashboardData['antecipacao']['top_5_cedentes'] = $stmtCedentesAnt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Top 5 Sacados (Antecipação)
    $sqlTopSacados = "
        SELECT 
            s.empresa as nome,
            SUM(CASE WHEN r.status IN ('Problema', 'Em Aberto') THEN r.valor_original ELSE 0 END) as valor_em_aberto,
            SUM(CASE WHEN r.status IN ('Problema', 'Em Aberto') AND r.data_vencimento < CURDATE() THEN r.valor_original ELSE 0 END) as valor_vencido
        FROM recebiveis r
        JOIN operacoes o ON r.operacao_id = o.id
        JOIN clientes s ON r.sacado_id = s.id
        WHERE o.tipo_operacao = 'antecipacao'
        GROUP BY s.id, s.empresa
        HAVING valor_em_aberto > 0
        ORDER BY valor_em_aberto DESC
        LIMIT 5
    ";
    $stmtSacados = $pdo->query($sqlTopSacados);
    $dashboardData['antecipacao']['top_5_sacados'] = $stmtSacados->fetchAll(PDO::FETCH_ASSOC);

    // 5. Lucro Realizado nos últimos 12 meses
    // Aplica pro-rata para Parcialmente Compensado
    $sqlLucro12m = "
        SELECT
            DATE_FORMAT(r.data_recebimento, '%Y-%m') as mes,
            o.tipo_operacao,
            SUM(CASE
                WHEN r.status IN ('Recebido', 'Compensado') THEN (r.valor_original - r.valor_liquido_calc)
                WHEN r.status = 'Parcialmente Compensado' AND r.valor_original > 0
                    THEN COALESCE(r.valor_recebido, 0) - (r.valor_liquido_calc * (COALESCE(r.valor_recebido, 0) / r.valor_original))
                ELSE 0
            END) as lucro
        FROM recebiveis r
        JOIN operacoes o ON r.operacao_id = o.id
        WHERE r.status IN ('Recebido', 'Compensado', 'Parcialmente Compensado')
          AND r.data_recebimento >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
        GROUP BY mes, o.tipo_operacao
        ORDER BY mes ASC
    ";
    $stmtLucro12m = $pdo->query($sqlLucro12m);
    $lucro12mData = $stmtLucro12m->fetchAll(PDO::FETCH_ASSOC);

    // Estruturar dados do gráfico
    $mesesMap = [];
    // Preencher últimos 12 meses zerados para garantir todos os pontos
    for ($i = 11; $i >= 0; $i--) {
        $mesStr = date('Y-m', strtotime("-$i months"));
        $mesesMap[$mesStr] = ['emprestimo' => 0, 'antecipacao' => 0];
    }

    foreach ($lucro12mData as $row) {
        $mes = $row['mes'];
        $tipo = strtolower($row['tipo_operacao']);
        if (isset($mesesMap[$mes])) {
            $mesesMap[$mes][$tipo] = (float) $row['lucro'];
        }
    }

    foreach ($mesesMap as $mes => $valores) {
        // Converter 2026-04 para Abr/26
        $parts = explode('-', $mes);
        $mesesPt = ['01'=>'Jan','02'=>'Fev','03'=>'Mar','04'=>'Abr','05'=>'Mai','06'=>'Jun','07'=>'Jul','08'=>'Ago','09'=>'Set','10'=>'Out','11'=>'Nov','12'=>'Dez'];
        $label = $mesesPt[$parts[1]] . '/' . substr($parts[0], 2);
        
        $dashboardData['grafico_lucro_12m']['meses'][] = $label;
        $dashboardData['grafico_lucro_12m']['emprestimo'][] = $valores['emprestimo'];
        $dashboardData['grafico_lucro_12m']['antecipacao'][] = $valores['antecipacao'];
    }

    // 6. Recebimentos Projetados nos próximos 6 meses
    $dataInicioProj = date('Y-m-01');
    $dataFimProj = date('Y-m-01', strtotime('+6 months'));
    
    $sqlRecebimentos = "
        SELECT 
            DATE_FORMAT(r.data_vencimento, '%Y-%m') as mes,
            o.tipo_operacao,
            SUM(r.valor_original) as valor_projetado
        FROM recebiveis r
        JOIN operacoes o ON r.operacao_id = o.id
        WHERE r.status = 'Em Aberto' 
          AND r.data_vencimento >= :dataInicioProj
          AND r.data_vencimento < :dataFimProj
        GROUP BY mes, o.tipo_operacao
        ORDER BY mes ASC
    ";
    $stmtRecebimentos = $pdo->prepare($sqlRecebimentos);
    $stmtRecebimentos->execute([
        ':dataInicioProj' => $dataInicioProj,
        ':dataFimProj' => $dataFimProj
    ]);
    $recebimentosData = $stmtRecebimentos->fetchAll(PDO::FETCH_ASSOC);

    $mesesRecMap = [];
    for ($i = 0; $i < 6; $i++) {
        $mesStr = date('Y-m', strtotime("+$i months"));
        $mesesRecMap[$mesStr] = ['emprestimo' => 0, 'antecipacao' => 0];
    }

    foreach ($recebimentosData as $row) {
        $mes = $row['mes'];
        $tipo = strtolower($row['tipo_operacao']);
        if (isset($mesesRecMap[$mes])) {
            $mesesRecMap[$mes][$tipo] = (float) $row['valor_projetado'];
        }
    }

    foreach ($mesesRecMap as $mes => $valores) {
        $parts = explode('-', $mes);
        $mesesPt = ['01'=>'Jan','02'=>'Fev','03'=>'Mar','04'=>'Abr','05'=>'Mai','06'=>'Jun','07'=>'Jul','08'=>'Ago','09'=>'Set','10'=>'Out','11'=>'Nov','12'=>'Dez'];
        $label = $mesesPt[$parts[1]] . '/' . substr($parts[0], 2);
        
        $dashboardData['grafico_recebimentos']['meses'][] = $label;
        $dashboardData['grafico_recebimentos']['emprestimo'][] = $valores['emprestimo'];
        $dashboardData['grafico_recebimentos']['antecipacao'][] = $valores['antecipacao'];
    }

    // 8. Lista de Recebíveis Inadimplentes (todos os vencidos não pagos)
    $sqlInadimplentes = "
        SELECT
            r.id,
            r.data_vencimento,
            r.valor_original,
            DATEDIFF(CURDATE(), r.data_vencimento) as dias_atraso,
            o.tipo_operacao,
            o.id as operacao_id,
            COALESCE(s.empresa, c.empresa, 'Não informado') as pagador_nome
        FROM recebiveis r
        JOIN operacoes o ON r.operacao_id = o.id
        LEFT JOIN clientes s ON r.sacado_id = s.id
        LEFT JOIN clientes c ON o.cedente_id = c.id
        WHERE r.status IN ('Problema', 'Em Aberto')
          AND r.data_vencimento < CURDATE()
        ORDER BY r.data_vencimento ASC
    ";
    $stmtInadimplentes = $pdo->query($sqlInadimplentes);
    $dashboardData['inadimplentes'] = $stmtInadimplentes->fetchAll(PDO::FETCH_ASSOC);

    // 8.1. Caixa Realizado: lucro acumulado − despesas pagas − distribuições aos sócios
    $stmtDespesasTotal = $pdo->query("SELECT COALESCE(SUM(valor), 0) FROM despesas");
    $totalDespesasLifetime = (float) $stmtDespesasTotal->fetchColumn();

    $stmtDistribTotal = $pdo->query("SELECT COALESCE(SUM(valor), 0) FROM distribuicao_lucros");
    $totalDistribLifetime = (float) $stmtDistribTotal->fetchColumn();

    $dashboardData['caixa'] = [
        'lucro_acumulado' => $dashboardData['geral']['lucro_realizado'],
        'despesas_pagas' => $totalDespesasLifetime,
        'distribuido' => $totalDistribLifetime,
        'caixa_realizado' => $dashboardData['geral']['lucro_realizado'] - $totalDespesasLifetime - $totalDistribLifetime
    ];

    // 7. Aging de Inadimplência - Escada padrão (1-30 / 31-60 / 61-90 / 90+)
    $sqlAging = "
        SELECT
            SUM(CASE WHEN DATEDIFF(CURDATE(), r.data_vencimento) BETWEEN 1 AND 30 THEN r.valor_original ELSE 0 END) as ate_30_dias,
            SUM(CASE WHEN DATEDIFF(CURDATE(), r.data_vencimento) BETWEEN 31 AND 60 THEN r.valor_original ELSE 0 END) as de_31_a_60_dias,
            SUM(CASE WHEN DATEDIFF(CURDATE(), r.data_vencimento) BETWEEN 61 AND 90 THEN r.valor_original ELSE 0 END) as de_61_a_90_dias,
            SUM(CASE WHEN DATEDIFF(CURDATE(), r.data_vencimento) > 90 THEN r.valor_original ELSE 0 END) as mais_de_90_dias
        FROM recebiveis r
        WHERE r.status IN ('Em Aberto', 'Problema') AND r.data_vencimento < CURDATE()
    ";
    $stmtAging = $pdo->query($sqlAging);
    $agingData = $stmtAging->fetch(PDO::FETCH_ASSOC);
    if ($agingData) {
        $dashboardData['aging']['ate_30_dias'] = (float) $agingData['ate_30_dias'];
        $dashboardData['aging']['de_31_a_60_dias'] = (float) $agingData['de_31_a_60_dias'];
        $dashboardData['aging']['de_61_a_90_dias'] = (float) $agingData['de_61_a_90_dias'];
        $dashboardData['aging']['mais_de_90_dias'] = (float) $agingData['mais_de_90_dias'];
    }

    echo json_encode(['success' => true, 'data' => $dashboardData]);

} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao buscar dados do dashboard: ' . $e->getMessage()]);
}
