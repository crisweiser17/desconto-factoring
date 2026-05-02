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
    $mes = $_GET['mes'] ?? date('m');
    $ano = $_GET['ano'] ?? date('Y');

    // 1. Recebimentos no mês (capital retornado + lucro bruto = total recebido)
    // Para "Parcialmente Compensado", usa valor_recebido e aplica pro-rata no capital/lucro
    $sqlRecebimentos = "
        SELECT
            SUM(CASE
                WHEN r.status IN ('Recebido', 'Compensado') THEN r.valor_liquido_calc
                WHEN r.status = 'Parcialmente Compensado' AND r.valor_original > 0
                    THEN r.valor_liquido_calc * (COALESCE(r.valor_recebido, 0) / r.valor_original)
                ELSE 0
            END) as retorno_capital,
            SUM(CASE
                WHEN r.status IN ('Recebido', 'Compensado') THEN (r.valor_original - r.valor_liquido_calc)
                WHEN r.status = 'Parcialmente Compensado' AND r.valor_original > 0
                    THEN COALESCE(r.valor_recebido, 0) - (r.valor_liquido_calc * (COALESCE(r.valor_recebido, 0) / r.valor_original))
                ELSE 0
            END) as lucro_bruto,
            SUM(CASE
                WHEN r.status IN ('Recebido', 'Compensado') THEN r.valor_original
                WHEN r.status = 'Parcialmente Compensado' THEN COALESCE(r.valor_recebido, 0)
                ELSE 0
            END) as total_recebido
        FROM recebiveis r
        WHERE r.status IN ('Recebido', 'Compensado', 'Parcialmente Compensado')
          AND MONTH(r.data_recebimento) = ?
          AND YEAR(r.data_recebimento) = ?
    ";
    $stmtRecebimentos = $pdo->prepare($sqlRecebimentos);
    $stmtRecebimentos->execute([$mes, $ano]);
    $dadosRecebimento = $stmtRecebimentos->fetch(PDO::FETCH_ASSOC);

    $total_recebido = (float) ($dadosRecebimento['total_recebido'] ?? 0);
    $retorno_capital = (float) ($dadosRecebimento['retorno_capital'] ?? 0);
    $lucro_bruto = (float) ($dadosRecebimento['lucro_bruto'] ?? 0);

    // 2. Despesas no mês
    $sqlDespesas = "
        SELECT SUM(valor) as total_despesas
        FROM despesas
        WHERE MONTH(data_despesa) = ? AND YEAR(data_despesa) = ?
    ";
    $stmtDespesas = $pdo->prepare($sqlDespesas);
    $stmtDespesas->execute([$mes, $ano]);
    $total_despesas = (float) ($stmtDespesas->fetchColumn() ?: 0);

    // 3. Lucro Líquido (antes da distribuição)
    $lucro_liquido = $lucro_bruto - $total_despesas;

    // 3.1. Total Distribuído no mês
    $sqlDistribuido = "
        SELECT SUM(valor) as total_distribuido
        FROM distribuicao_lucros
        WHERE MONTH(data) = ? AND YEAR(data) = ?
    ";
    $stmtDistribuido = $pdo->prepare($sqlDistribuido);
    $stmtDistribuido->execute([$mes, $ano]);
    $total_distribuido = (float) ($stmtDistribuido->fetchColumn() ?: 0);

    // 3.2. Lucro Retido (após distribuição aos sócios)
    $lucro_retido = $lucro_liquido - $total_distribuido;

    // 4. Títulos Atrasados — snapshot ao final do mês filtrado.
    // Mostra todos os recebíveis ainda em aberto cujo vencimento já tinha passado
    // até o último dia do mês selecionado (visão cumulativa, não só vencidos no mês).
    $ultimoDiaMes = date('Y-m-t', strtotime("$ano-$mes-01"));
    $sqlAtrasados = "
        SELECT
            r.id,
            r.data_vencimento,
            r.valor_original,
            DATEDIFF(CURDATE(), r.data_vencimento) as dias_atraso,
            COALESCE(s.empresa, c.empresa, 'Não informado') as pagador_nome
        FROM recebiveis r
        JOIN operacoes o ON r.operacao_id = o.id
        LEFT JOIN clientes s ON r.sacado_id = s.id
        LEFT JOIN clientes c ON o.cedente_id = c.id
        WHERE r.status IN ('Problema', 'Em Aberto')
          AND r.data_vencimento < CURDATE()
          AND r.data_vencimento <= ?
        ORDER BY r.data_vencimento ASC
    ";
    $stmtAtrasados = $pdo->prepare($sqlAtrasados);
    $stmtAtrasados->execute([$ultimoDiaMes]);
    $titulos_atrasados = $stmtAtrasados->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => [
            'total_recebido' => $total_recebido,
            'retorno_capital' => $retorno_capital,
            'lucro_bruto' => $lucro_bruto,
            'total_despesas' => $total_despesas,
            'lucro_liquido' => $lucro_liquido,
            'total_distribuido' => $total_distribuido,
            'lucro_retido' => $lucro_retido,
            'titulos_atrasados' => $titulos_atrasados
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
}
