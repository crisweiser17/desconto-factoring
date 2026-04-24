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
    $sqlRecebimentos = "
        SELECT 
            SUM(r.valor_liquido_calc) as retorno_capital,
            SUM(r.valor_original - r.valor_liquido_calc) as lucro_bruto,
            SUM(r.valor_original) as total_recebido
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

    // 3. Lucro Líquido
    $lucro_liquido = $lucro_bruto - $total_despesas;

    // 4. Títulos Atrasados (vencidos no mês filtrado)
    $sqlAtrasados = "
        SELECT 
            r.id,
            r.data_vencimento,
            r.valor_original,
            DATEDIFF(CURDATE(), r.data_vencimento) as dias_atraso,
            COALESCE(s.empresa, c.empresa, 'Não informado') as pagador_nome
        FROM recebiveis r
        JOIN operacoes o ON r.operacao_id = o.id
        LEFT JOIN sacados s ON r.sacado_id = s.id
        LEFT JOIN cedentes c ON o.cedente_id = c.id
        WHERE r.status IN ('Problema', 'Em Aberto')
          AND r.data_vencimento < CURDATE()
          AND MONTH(r.data_vencimento) = ?
          AND YEAR(r.data_vencimento) = ?
        ORDER BY dias_atraso DESC
    ";
    $stmtAtrasados = $pdo->prepare($sqlAtrasados);
    $stmtAtrasados->execute([$mes, $ano]);
    $titulos_atrasados = $stmtAtrasados->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => [
            'total_recebido' => $total_recebido,
            'retorno_capital' => $retorno_capital,
            'lucro_bruto' => $lucro_bruto,
            'total_despesas' => $total_despesas,
            'lucro_liquido' => $lucro_liquido,
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
