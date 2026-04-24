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

try {
    $dashboardData = [
        'geral' => [
            'capital_adiantado' => 0,
            'amortizado' => 0,
            'lucro_realizado' => 0,
            'lucro_projetado' => 0,
            'inadimplencia' => 0
        ],
        'emprestimo' => [
            'capital_adiantado' => 0,
            'amortizado' => 0,
            'lucro_realizado' => 0,
            'lucro_projetado' => 0,
            'inadimplencia' => 0,
            'top_5_tomadores' => []
        ],
        'antecipacao' => [
            'capital_adiantado' => 0,
            'amortizado' => 0,
            'lucro_realizado' => 0,
            'lucro_projetado' => 0,
            'inadimplencia' => 0,
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
            'ate_15_dias' => 0,
            'de_16_a_30_dias' => 0,
            'mais_de_30_dias' => 0
        ]
    ];

    // 1. Métricas principais por tipo_operacao
    $sqlMetrics = "
        SELECT 
            o.tipo_operacao,
            SUM(r.valor_liquido_calc) as capital_adiantado,
            SUM(CASE WHEN r.status IN ('Recebido', 'Compensado', 'Parcialmente Compensado') THEN r.valor_liquido_calc ELSE 0 END) as amortizado,
            SUM(CASE WHEN r.status IN ('Recebido', 'Compensado', 'Parcialmente Compensado') THEN (r.valor_original - r.valor_liquido_calc) ELSE 0 END) as lucro_realizado,
            SUM(CASE WHEN r.status IN ('Problema', 'Em Aberto') AND r.data_vencimento < CURDATE() THEN r.valor_original ELSE 0 END) as inadimplencia,
            SUM(CASE WHEN r.status IN ('Em Aberto') AND r.data_vencimento >= CURDATE() THEN (r.valor_original - r.valor_liquido_calc) ELSE 0 END) as lucro_projetado
        FROM recebiveis r
        JOIN operacoes o ON r.operacao_id = o.id
        GROUP BY o.tipo_operacao
    ";
    
    $stmtMetrics = $pdo->query($sqlMetrics);
    $metrics = $stmtMetrics->fetchAll(PDO::FETCH_ASSOC);

    foreach ($metrics as $row) {
        $tipo = strtolower($row['tipo_operacao']);
        if (isset($dashboardData[$tipo])) {
            $dashboardData[$tipo]['capital_adiantado'] = (float) $row['capital_adiantado'];
            $dashboardData[$tipo]['amortizado'] = (float) $row['amortizado'];
            $dashboardData[$tipo]['lucro_realizado'] = (float) $row['lucro_realizado'];
            $dashboardData[$tipo]['inadimplencia'] = (float) $row['inadimplencia'];
            $dashboardData[$tipo]['lucro_projetado'] = (float) $row['lucro_projetado'];

            // Soma Geral
            $dashboardData['geral']['capital_adiantado'] += $row['capital_adiantado'];
            $dashboardData['geral']['amortizado'] += $row['amortizado'];
            $dashboardData['geral']['lucro_realizado'] += $row['lucro_realizado'];
            $dashboardData['geral']['inadimplencia'] += $row['inadimplencia'];
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
        JOIN sacados s ON r.sacado_id = s.id
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
        JOIN cedentes c ON o.cedente_id = c.id
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
        JOIN sacados s ON r.sacado_id = s.id
        WHERE o.tipo_operacao = 'antecipacao'
        GROUP BY s.id, s.empresa
        HAVING valor_em_aberto > 0
        ORDER BY valor_em_aberto DESC
        LIMIT 5
    ";
    $stmtSacados = $pdo->query($sqlTopSacados);
    $dashboardData['antecipacao']['top_5_sacados'] = $stmtSacados->fetchAll(PDO::FETCH_ASSOC);

    // 5. Lucro Realizado nos últimos 12 meses
    $sqlLucro12m = "
        SELECT 
            DATE_FORMAT(r.data_recebimento, '%Y-%m') as mes,
            o.tipo_operacao,
            SUM(r.valor_original - r.valor_liquido_calc) as lucro
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

    // 7. Aging de Inadimplência
    $sqlAging = "
        SELECT 
            SUM(CASE WHEN DATEDIFF(CURDATE(), r.data_vencimento) BETWEEN 1 AND 15 THEN r.valor_original ELSE 0 END) as ate_15_dias,
            SUM(CASE WHEN DATEDIFF(CURDATE(), r.data_vencimento) BETWEEN 16 AND 30 THEN r.valor_original ELSE 0 END) as de_16_a_30_dias,
            SUM(CASE WHEN DATEDIFF(CURDATE(), r.data_vencimento) > 30 THEN r.valor_original ELSE 0 END) as mais_de_30_dias
        FROM recebiveis r
        WHERE r.status IN ('Em Aberto', 'Problema') AND r.data_vencimento < CURDATE()
    ";
    $stmtAging = $pdo->query($sqlAging);
    $agingData = $stmtAging->fetch(PDO::FETCH_ASSOC);
    if ($agingData) {
        $dashboardData['aging']['ate_15_dias'] = (float) $agingData['ate_15_dias'];
        $dashboardData['aging']['de_16_a_30_dias'] = (float) $agingData['de_16_a_30_dias'];
        $dashboardData['aging']['mais_de_30_dias'] = (float) $agingData['mais_de_30_dias'];
    }

    echo json_encode(['success' => true, 'data' => $dashboardData]);

} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao buscar dados do dashboard: ' . $e->getMessage()]);
}
