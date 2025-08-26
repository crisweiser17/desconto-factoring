<?php require_once 'auth_check.php'; ?>
<?php
// --- DEBUG (Remover em produção) ---
// ini_set('display_errors', 1); // Descomente para ver erros no navegador
// ini_set('display_startup_errors', 1); // Descomente para ver erros de inicialização
// error_reporting(E_ALL); // Descomente para reportar todos os tipos de erros
// --- FIM DEBUG ---

require_once 'db_connection.php'; // Conexão $pdo

// --- Funções de Formatação (Incluídas diretamente) ---
if (!function_exists('formatHtmlCurrency')) {
    function formatHtmlCurrency($value) { return 'R$ ' . number_format($value ?? 0, 2, ',', '.'); }
}
if (!function_exists('formatHtmlPercentage')) {
    function formatHtmlPercentage($value, $decimals = 2) { return number_format($value ?? 0, $decimals, ',', '.') . '%'; }
}
// --- Fim Funções ---

// --- Processamento do Filtro de Data (para os KPIs do relatório) ---
$data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : '';
$data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : '';

$params_op = [];
$whereClauses_op = [];

// Validar e adicionar filtro de data de início
if ($data_inicio && DateTime::createFromFormat('Y-m-d', $data_inicio)) {
    $whereClauses_op[] = "o.data_operacao >= :data_inicio";
    $params_op[':data_inicio'] = $data_inicio;
} else {
    $data_inicio = '';
}

// Validar e adicionar filtro de data de fim
if ($data_fim && DateTime::createFromFormat('Y-m-d', $data_fim)) {
    $whereClauses_op[] = "o.data_operacao <= :data_fim_final";
    $params_op[':data_fim_final'] = $data_fim;
} else {
    $data_fim = '';
}

$whereSql_op = !empty($whereClauses_op) ? "WHERE " . implode(" AND ", $whereClauses_op) : "";

// --- Cálculos dos Indicadores (REFATORADO - USA FUNÇÃO CENTRALIZADA) ---
require_once 'funcoes_lucro.php';

$indicadores = [];
$error_message = null;

try {
    // USAR FUNÇÃO CENTRALIZADA EM VEZ DE CÁLCULOS PRÓPRIOS
    $indicadores = obterDadosRelatorio($data_inicio, $data_fim);
    
    if ($indicadores === null) {
        throw new Exception("Erro ao obter dados do relatório");
    }
    
    // Adicionar campos para compatibilidade com código existente
    $indicadores['num_total_recebiveis'] = $indicadores['count_recebiveis_recebidos'] +
                                          $indicadores['count_recebiveis_em_aberto'] +
                                          $indicadores['count_recebiveis_problema'] +
                                          ($indicadores['count_recebiveis_parcialmente_compensado'] ?? 0);
    
    // Calcular valor total da carteira para o valor médio
    $valor_total_carteira = $indicadores['valor_total_recebido'] +
                           $indicadores['valor_total_em_aberto'] +
                           $indicadores['valor_total_em_problema'] +
                           ($indicadores['valor_total_parcialmente_compensado'] ?? 0);
    
    $indicadores['valor_medio_recebiveis'] = $indicadores['num_total_recebiveis'] > 0 ?
        $valor_total_carteira / $indicadores['num_total_recebiveis'] : 0;
    
    // Mapear status para compatibilidade
    $indicadores['operacoes_em_andamento'] = $indicadores['operacoes_em_andamento'] +
                                            ($indicadores['operacoes_parcialmente_compensadas'] ?? 0);

    // --- NOVO: Dados para o Gráfico de 12 Meses (Lógica de data CORRIGIDA) ---
    $chartMonthlyData = [];
    $currentDateTime = new DateTime();

    $startDateObj = (new DateTime())->modify('-11 months');
    $startDateObj->setDate((int)$startDateObj->format('Y'), (int)$startDateObj->format('m'), 1);
    $startDateSQL = $startDateObj->format('Y-m-d');

    $endDateObj = new DateTime();
    $endDateObj->modify('last day of this month');
    $endDateSQL = $endDateObj->format('Y-m-d');

    $iteratorDate = clone $startDateObj;
    for ($i = 0; $i < 12; $i++) {
        $monthKey = $iteratorDate->format('Y-m');
        $chartMonthlyData[$monthKey] = [
            'valorOriginalSum' => 0,
            'lucroLiquidoSum' => 0, // Regime de competência (por vencimento)
            'lucroRealizadoSum' => 0, // NOVO: Regime de caixa (por recebimento efetivo)
            'capitalEmRiscoSum' => 0, // Mantido no PHP para acumulação, mas não será plotado
            'valorComProblemaSum' => 0,
            'valorParcialmenteCompensadoSum' => 0, // NOVO: Recebíveis parcialmente compensados
            'valorJaRecebidoSum' => 0, // NOVO: Valor já recebido dos parciais
            'saldoRestanteSum' => 0, // NOVO: Saldo ainda em aberto dos parciais
            'capitalEmprestadoSum' => 0, // Capital emprestado no mês
            'displayLabel' => $iteratorDate->format('M/Y')
        ];
        $iteratorDate->modify('+1 month');
    }

    // Consulta para dados de recebíveis (por data de vencimento) - REGIME DE COMPETÊNCIA
    $sql_chart_data = "SELECT
                            DATE_FORMAT(r.data_vencimento, '%Y-%m') as month_key,
                            SUM(r.valor_original) as total_original_mes,
                            SUM(r.valor_original - r.valor_liquido_calc) as total_lucro_mes,
                            SUM(CASE WHEN r.status = 'Em Aberto' THEN r.valor_original ELSE 0 END) as total_capital_em_risco_mes,
                            SUM(CASE WHEN r.status = 'Problema' THEN r.valor_original ELSE 0 END) as total_valor_com_problema_mes,
                            SUM(CASE WHEN r.status = 'Parcialmente Compensado' THEN (r.valor_original - COALESCE((SELECT SUM(c.valor_compensado) FROM compensacoes c WHERE c.recebivel_compensado_id = r.id), 0)) ELSE 0 END) as total_parcialmente_compensado_mes,
                            SUM(CASE WHEN r.status = 'Parcialmente Compensado' THEN COALESCE((SELECT SUM(c.valor_compensado) FROM compensacoes c WHERE c.recebivel_compensado_id = r.id), 0) ELSE 0 END) as total_valor_ja_recebido_mes,
                            SUM(CASE WHEN r.status = 'Parcialmente Compensado' THEN (r.valor_original - COALESCE((SELECT SUM(c.valor_compensado) FROM compensacoes c WHERE c.recebivel_compensado_id = r.id), 0)) ELSE 0 END) as total_saldo_restante_mes
                        FROM
                            recebiveis r
                        WHERE
                            r.data_vencimento >= :start_date_chart AND r.data_vencimento <= :end_date_chart
                        GROUP BY
                            month_key
                        ORDER BY
                            month_key ASC";
    $stmt_chart_data = $pdo->prepare($sql_chart_data);
    $stmt_chart_data->bindParam(':start_date_chart', $startDateSQL);
    $stmt_chart_data->bindParam(':end_date_chart', $endDateSQL);
    $stmt_chart_data->execute();
    $chart_results = $stmt_chart_data->fetchAll(PDO::FETCH_ASSOC);

    foreach ($chart_results as $row) {
        if (isset($chartMonthlyData[$row['month_key']])) {
            $chartMonthlyData[$row['month_key']]['valorOriginalSum'] = (float)($row['total_original_mes'] ?? 0);
            $chartMonthlyData[$row['month_key']]['lucroLiquidoSum'] = (float)($row['total_lucro_mes'] ?? 0);
            $chartMonthlyData[$row['month_key']]['capitalEmRiscoSum'] = (float)($row['total_capital_em_risco_mes'] ?? 0);
            $chartMonthlyData[$row['month_key']]['valorComProblemaSum'] = (float)($row['total_valor_com_problema_mes'] ?? 0);
            $chartMonthlyData[$row['month_key']]['valorParcialmenteCompensadoSum'] = (float)($row['total_parcialmente_compensado_mes'] ?? 0);
            $chartMonthlyData[$row['month_key']]['valorJaRecebidoSum'] = (float)($row['total_valor_ja_recebido_mes'] ?? 0);
            $chartMonthlyData[$row['month_key']]['saldoRestanteSum'] = (float)($row['total_saldo_restante_mes'] ?? 0);
        }
    }

    // NOVA consulta para capital emprestado por mês (por data da operação)
    $sql_capital_emprestado = "SELECT
                                DATE_FORMAT(o.data_operacao, '%Y-%m') as month_key,
                                SUM(o.total_liquido_pago_calc) as total_capital_emprestado_mes
                            FROM
                                operacoes o
                            WHERE
                                o.data_operacao >= :start_date_chart AND o.data_operacao <= :end_date_chart
                            GROUP BY
                                month_key
                            ORDER BY
                                month_key ASC";
    $stmt_capital_emprestado = $pdo->prepare($sql_capital_emprestado);
    $stmt_capital_emprestado->bindParam(':start_date_chart', $startDateSQL);
    $stmt_capital_emprestado->bindParam(':end_date_chart', $endDateSQL);
    $stmt_capital_emprestado->execute();
    $capital_emprestado_results = $stmt_capital_emprestado->fetchAll(PDO::FETCH_ASSOC);

    foreach ($capital_emprestado_results as $row) {
        if (isset($chartMonthlyData[$row['month_key']])) {
            $chartMonthlyData[$row['month_key']]['capitalEmprestadoSum'] = (float)($row['total_capital_emprestado_mes'] ?? 0);
        }
    }

    // NOVA consulta para lucro realizado por mês (REGIME DE CAIXA) - baseado na data de recebimento
    $sql_lucro_realizado = "SELECT
                                DATE_FORMAT(r.data_recebimento, '%Y-%m') as month_key,
                                SUM(r.valor_original - r.valor_liquido_calc) as total_lucro_realizado_mes
                            FROM
                                recebiveis r
                            WHERE
                                r.data_recebimento IS NOT NULL
                                AND r.data_recebimento >= :start_date_chart 
                                AND r.data_recebimento <= :end_date_chart
                                AND r.status = 'Recebido'
                            GROUP BY
                                month_key
                            ORDER BY
                                month_key ASC";
    $stmt_lucro_realizado = $pdo->prepare($sql_lucro_realizado);
    $stmt_lucro_realizado->bindParam(':start_date_chart', $startDateSQL);
    $stmt_lucro_realizado->bindParam(':end_date_chart', $endDateSQL);
    $stmt_lucro_realizado->execute();
    $lucro_realizado_results = $stmt_lucro_realizado->fetchAll(PDO::FETCH_ASSOC);

    foreach ($lucro_realizado_results as $row) {
        if (isset($chartMonthlyData[$row['month_key']])) {
            $chartMonthlyData[$row['month_key']]['lucroRealizadoSum'] = (float)($row['total_lucro_realizado_mes'] ?? 0);
        }
    }

    // NOVA consulta para capital retornado por mês (regime de competência) - baseado na data de vencimento
    // INCLUI: valor líquido dos recebidos + valor já compensado dos parciais
    $sql_capital_retornado = "SELECT
                                DATE_FORMAT(r.data_vencimento, '%Y-%m') as month_key,
                                SUM(
                                    CASE
                                        WHEN r.status = 'Recebido' THEN r.valor_liquido_calc
                                        WHEN r.status = 'Parcialmente Compensado' THEN COALESCE((SELECT SUM(c.valor_compensado) FROM compensacoes c WHERE c.recebivel_compensado_id = r.id), 0)
                                        ELSE 0
                                    END
                                ) as total_capital_retornado_mes
                            FROM
                                recebiveis r
                            WHERE
                                r.data_vencimento >= :start_date_chart AND r.data_vencimento <= :end_date_chart
                            GROUP BY
                                month_key
                            ORDER BY
                                month_key ASC";
    $stmt_capital_retornado = $pdo->prepare($sql_capital_retornado);
    $stmt_capital_retornado->bindParam(':start_date_chart', $startDateSQL);
    $stmt_capital_retornado->bindParam(':end_date_chart', $endDateSQL);
    $stmt_capital_retornado->execute();
    $capital_retornado_results = $stmt_capital_retornado->fetchAll(PDO::FETCH_ASSOC);

    foreach ($capital_retornado_results as $row) {
        if (isset($chartMonthlyData[$row['month_key']])) {
            $chartMonthlyData[$row['month_key']]['capitalRetornadoSum'] = (float)($row['total_capital_retornado_mes'] ?? 0);
        }
    }

    // NOVA consulta para capital retornado realizado por mês (regime de caixa) - baseado na data de recebimento
    $sql_capital_retornado_realizado = "SELECT
                                        DATE_FORMAT(r.data_recebimento, '%Y-%m') as month_key,
                                        SUM(r.valor_liquido_calc) as total_capital_retornado_realizado_mes
                                    FROM
                                        recebiveis r
                                    WHERE
                                        r.data_recebimento IS NOT NULL
                                        AND r.data_recebimento >= :start_date_chart 
                                        AND r.data_recebimento <= :end_date_chart
                                        AND r.status = 'Recebido'
                                    GROUP BY
                                        month_key
                                    ORDER BY
                                        month_key ASC";
    $stmt_capital_retornado_realizado = $pdo->prepare($sql_capital_retornado_realizado);
    $stmt_capital_retornado_realizado->bindParam(':start_date_chart', $startDateSQL);
    $stmt_capital_retornado_realizado->bindParam(':end_date_chart', $endDateSQL);
    $stmt_capital_retornado_realizado->execute();
    $capital_retornado_realizado_results = $stmt_capital_retornado_realizado->fetchAll(PDO::FETCH_ASSOC);

    foreach ($capital_retornado_realizado_results as $row) {
        if (isset($chartMonthlyData[$row['month_key']])) {
            $chartMonthlyData[$row['month_key']]['capitalRetornadoRealizadoSum'] = (float)($row['total_capital_retornado_realizado_mes'] ?? 0);
        }
    }

    $chartLabels = [];
    $chartDataValorOriginal = [];
    $chartDataLucroCompetencia = []; // Regime de competência
    $chartDataLucroCaixa = []; // NOVO: Regime de caixa
    $chartDataCapitalEmRisco = [];
    $chartDataValorComProblema = [];
    $chartDataParcialmenteCompensado = []; // NOVO: Parcialmente compensados
    $chartDataCapitalEmprestado = [];
    $chartDataCapitalRetornado = []; // NOVO: Capital retornado (competência)
    $chartDataCapitalRetornadoRealizado = []; // NOVO: Capital retornado (caixa)

    foreach ($chartMonthlyData as $monthData) {
        $chartLabels[] = [
            'text' => $monthData['displayLabel'],
            'lucro_competencia' => (float)($monthData['lucroLiquidoSum'] ?? 0),
            'lucro_caixa' => (float)($monthData['lucroRealizadoSum'] ?? 0),
            'capital_em_risco' => (float)($monthData['capitalEmRiscoSum'] ?? 0),
            'valor_com_problema' => (float)($monthData['valorComProblemaSum'] ?? 0),
            'valor_parcialmente_compensado' => (float)($monthData['valorParcialmenteCompensadoSum'] ?? 0),
            'valor_ja_recebido' => (float)($monthData['valorJaRecebidoSum'] ?? 0),
            'saldo_restante' => (float)($monthData['saldoRestanteSum'] ?? 0),
            'capital_emprestado' => (float)($monthData['capitalEmprestadoSum'] ?? 0),
            'capital_retornado' => (float)($monthData['capitalRetornadoSum'] ?? 0),
            'capital_retornado_realizado' => (float)($monthData['capitalRetornadoRealizadoSum'] ?? 0)
        ];
        $chartDataValorOriginal[] = (float)($monthData['valorOriginalSum'] ?? 0);
        $chartDataLucroCompetencia[] = (float)($monthData['lucroLiquidoSum'] ?? 0);
        $chartDataLucroCaixa[] = (float)($monthData['lucroRealizadoSum'] ?? 0);
        $chartDataCapitalEmRisco[] = (float)($monthData['capitalEmRiscoSum'] ?? 0);
        $chartDataValorComProblema[] = (float)($monthData['valorComProblemaSum'] ?? 0);
        $chartDataParcialmenteCompensado[] = (float)($monthData['valorParcialmenteCompensadoSum'] ?? 0);
        $chartDataCapitalEmprestado[] = (float)($monthData['capitalEmprestadoSum'] ?? 0); // Positivo para exibir acima do eixo X
        $chartDataCapitalRetornado[] = (float)($monthData['capitalRetornadoSum'] ?? 0);
        $chartDataCapitalRetornadoRealizado[] = (float)($monthData['capitalRetornadoRealizadoSum'] ?? 0);
    }
    // --- FIM NOVO: Dados para o Gráfico Histórico ---

    // --- NOVO: Dados para o Gráfico de Projeção (Meses Futuros) ---
    $chartFutureData = [];
    $currentDate = new DateTime();
    
    // Buscar até 12 meses futuros com recebíveis
    $futureStartDate = clone $currentDate;
    $futureStartDate->modify('first day of this month');
    $futureEndDate = clone $currentDate;
    $futureEndDate->modify('+12 months last day of this month');
    
    $futureStartSQL = $futureStartDate->format('Y-m-d');
    $futureEndSQL = $futureEndDate->format('Y-m-d');
    
    // Consulta para dados futuros (por data de vencimento) - EXCLUIR RECEBIDOS E CORRIGIR VALOR ORIGINAL
    $sql_future_data = "SELECT
                            DATE_FORMAT(r.data_vencimento, '%Y-%m') as month_key,
                            SUM(
                                CASE
                                    WHEN r.status = 'Em Aberto' THEN r.valor_original
                                    WHEN r.status = 'Problema' THEN r.valor_original
                                    WHEN r.status = 'Parcialmente Compensado' THEN (r.valor_original - COALESCE((SELECT SUM(c.valor_compensado) FROM compensacoes c WHERE c.recebivel_compensado_id = r.id), 0))
                                    ELSE 0
                                END
                            ) as total_original_mes,
                            SUM(
                                CASE
                                    WHEN r.status = 'Em Aberto' THEN (r.valor_original - r.valor_liquido_calc)
                                    WHEN r.status = 'Problema' THEN (r.valor_original - r.valor_liquido_calc)
                                    WHEN r.status = 'Parcialmente Compensado' THEN (r.valor_original - r.valor_liquido_calc)
                                    ELSE 0
                                END
                            ) as total_lucro_mes,
                            SUM(CASE WHEN r.status = 'Em Aberto' THEN r.valor_original ELSE 0 END) as total_capital_em_risco_mes,
                            SUM(CASE WHEN r.status = 'Problema' THEN r.valor_original ELSE 0 END) as total_valor_com_problema_mes,
                            SUM(CASE WHEN r.status = 'Parcialmente Compensado' THEN (r.valor_original - COALESCE((SELECT SUM(c.valor_compensado) FROM compensacoes c WHERE c.recebivel_compensado_id = r.id), 0)) ELSE 0 END) as total_parcialmente_compensado_mes
                        FROM
                            recebiveis r
                        WHERE
                            r.data_vencimento >= :future_start_date AND r.data_vencimento <= :future_end_date
                            AND r.status != 'Recebido'
                        GROUP BY
                            month_key
                        ORDER BY
                            month_key ASC";
    $stmt_future_data = $pdo->prepare($sql_future_data);
    $stmt_future_data->bindParam(':future_start_date', $futureStartSQL);
    $stmt_future_data->bindParam(':future_end_date', $futureEndSQL);
    $stmt_future_data->execute();
    $future_results = $stmt_future_data->fetchAll(PDO::FETCH_ASSOC);
    
    // Criar estrutura de meses futuros
    $iteratorFutureDate = clone $futureStartDate;
    $maxFutureMonths = 12;
    $monthsWithData = array_column($future_results, 'month_key');
    
    // Se há dados futuros, criar até o último mês com dados (máximo 12)
    if (!empty($monthsWithData)) {
        $lastMonthWithData = max($monthsWithData);
        $lastDataDate = DateTime::createFromFormat('Y-m', $lastMonthWithData);
        $lastDataDate->modify('last day of this month');
        
        // Limitar a 12 meses no máximo
        $maxAllowedDate = clone $futureStartDate;
        $maxAllowedDate->modify('+11 months last day of this month');
        
        if ($lastDataDate > $maxAllowedDate) {
            $lastDataDate = $maxAllowedDate;
        }
        
        while ($iteratorFutureDate <= $lastDataDate) {
            $monthKey = $iteratorFutureDate->format('Y-m');
            $chartFutureData[$monthKey] = [
                'valorOriginalSum' => 0,
                'lucroLiquidoSum' => 0,
                'capitalEmRiscoSum' => 0,
                'valorComProblemaSum' => 0,
                'valorParcialmenteCompensadoSum' => 0,
                'displayLabel' => $iteratorFutureDate->format('M/Y')
            ];
            $iteratorFutureDate->modify('+1 month');
        }
        
        // Preencher dados dos meses futuros
        foreach ($future_results as $row) {
            if (isset($chartFutureData[$row['month_key']])) {
                $chartFutureData[$row['month_key']]['valorOriginalSum'] = (float)($row['total_original_mes'] ?? 0);
                $chartFutureData[$row['month_key']]['lucroLiquidoSum'] = (float)($row['total_lucro_mes'] ?? 0);
                $chartFutureData[$row['month_key']]['capitalEmRiscoSum'] = (float)($row['total_capital_em_risco_mes'] ?? 0);
                $chartFutureData[$row['month_key']]['valorComProblemaSum'] = (float)($row['total_valor_com_problema_mes'] ?? 0);
                $chartFutureData[$row['month_key']]['valorParcialmenteCompensadoSum'] = (float)($row['total_parcialmente_compensado_mes'] ?? 0);
            }
        }
    }
    
    // Preparar arrays para o gráfico futuro
    $chartFutureLabels = [];
    $chartFutureDataValorOriginal = [];
    $chartFutureDataLucro = [];
    $chartFutureDataCapitalEmRisco = [];
    $chartFutureDataValorComProblema = [];
    $chartFutureDataParcialmenteCompensado = [];
    
    foreach ($chartFutureData as $monthData) {
        $chartFutureLabels[] = [
            'text' => $monthData['displayLabel'],
            'valor_original' => (float)($monthData['valorOriginalSum'] ?? 0),
            'lucro' => (float)($monthData['lucroLiquidoSum'] ?? 0),
            'capital_em_risco' => (float)($monthData['capitalEmRiscoSum'] ?? 0),
            'valor_com_problema' => (float)($monthData['valorComProblemaSum'] ?? 0),
            'valor_parcialmente_compensado' => (float)($monthData['valorParcialmenteCompensadoSum'] ?? 0)
        ];
        $chartFutureDataValorOriginal[] = (float)($monthData['valorOriginalSum'] ?? 0);
        $chartFutureDataLucro[] = (float)($monthData['lucroLiquidoSum'] ?? 0);
        $chartFutureDataCapitalEmRisco[] = (float)($monthData['capitalEmRiscoSum'] ?? 0);
        $chartFutureDataValorComProblema[] = (float)($monthData['valorComProblemaSum'] ?? 0);
        $chartFutureDataParcialmenteCompensado[] = (float)($monthData['valorParcialmenteCompensadoSum'] ?? 0);
    }
    // --- FIM NOVO: Dados para o Gráfico de Projeção ---

} catch (PDOException $e) {
    $error_message = "Erro ao calcular indicadores: " . $e->getMessage();
    error_log("Erro SQL no relatorio.php: " . $e->getMessage() . " | Query: " . ($e->getCode() ?? 'N/A'));
} catch (Exception $e) {
    $error_message = "Erro inesperado: " . $e->getMessage();
    error_log("Erro geral no relatorio.php: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório Geral de Operações</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
    <style>
        /* Estilos dos KPIs reorganizados - MELHORADO */
        .kpi-group {
            margin-bottom: 1.5rem;
            border: 1px solid #e3e6ea;
            border-radius: 0.75rem;
            padding: 1.25rem;
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        .kpi-group h4 {
            margin-bottom: 1rem;
            color: #2c3e50;
            font-size: 1rem;
            font-weight: 600;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 0.5rem;
            display: flex;
            align-items: center;
        }
        .kpi-group h4 i {
            color: #6c757d;
        }
        
        /* Cards mais compactos e elegantes */
        .kpi-card {
            transition: all 0.3s ease;
            cursor: pointer;
            border: 1px solid #e9ecef;
            border-radius: 0.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            background: #ffffff;
            height: 100px; /* Altura fixa para uniformidade */
        }
        .kpi-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
            border-color: #0d6efd;
        }
        
        /* Body do card mais compacto */
        .kpi-card .card-body { 
            font-size: 1.3rem; 
            font-weight: 700; 
            padding: 0.75rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            height: 60px;
            line-height: 1.2;
        }
        
        /* Footer/título mais limpo */
        .kpi-card .card-footer { 
            font-size: 0.8rem; 
            font-weight: 500; 
            color: #495057; 
            margin-bottom: 0; 
            padding: 0.5rem 0.75rem;
            background-color: #f8f9fa;
            border-top: 1px solid #e9ecef;
            text-align: center;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Cores de texto para cards brancos (se necessário) */
        .text-info-custom { color: #0ea5e9 !important; }
        .text-primary-custom { color: #3b82f6 !important; }
        .text-success-custom { color: #10b981 !important; }
        .text-warning-custom { color: #f59e0b !important; }
        .text-danger-custom { color: #ef4444 !important; }
        
        /* Sistema de cores inteligente para KPIs */
        
        /* Cores neutras - informações gerais */
        .bg-neutral-custom {
            background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%) !important;
            color: white !important;
        }
        .bg-neutral-light-custom {
            background: linear-gradient(135deg, #9ca3af 0%, #6b7280 100%) !important;
            color: white !important;
        }
        
        /* Cores azuis - indicadores positivos/importantes */
        .bg-info-custom {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%) !important;
            color: white !important;
        }
        .bg-primary-custom {
            background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%) !important;
            color: white !important;
        }
        
        /* Cores verdes - lucro/sucesso */
        .bg-success-custom {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
            color: white !important;
        }
        .bg-success-dark-custom {
            background: linear-gradient(135deg, #059669 0%, #047857 100%) !important;
            color: white !important;
        }
        
        /* Cores laranja/amarelo - alertas */
        .bg-warning-custom {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important;
            color: white !important;
        }
        .bg-warning-light-custom {
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%) !important;
            color: white !important;
        }
        
        /* Cores vermelhas - problemas/riscos */
        .bg-danger-custom {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
            color: white !important;
        }
        .bg-danger-dark-custom {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%) !important;
            color: white !important;
        }
        
        /* Footers dos cards coloridos */
        .bg-neutral-custom .card-footer,
        .bg-neutral-light-custom .card-footer,
        .bg-info-custom .card-footer,
        .bg-primary-custom .card-footer,
        .bg-success-custom .card-footer,
        .bg-success-dark-custom .card-footer,
        .bg-warning-custom .card-footer,
        .bg-warning-light-custom .card-footer,
        .bg-danger-custom .card-footer,
        .bg-danger-dark-custom .card-footer {
            background-color: rgba(0,0,0,0.15) !important;
            border-top-color: rgba(255,255,255,0.2) !important;
            color: rgba(255,255,255,0.95) !important;
        }
        
        /* Remover cores de texto dos cards coloridos */
        .bg-neutral-custom .card-body,
        .bg-neutral-light-custom .card-body,
        .bg-info-custom .card-body,
        .bg-primary-custom .card-body,
        .bg-success-custom .card-body,
        .bg-success-dark-custom .card-body,
        .bg-warning-custom .card-body,
        .bg-warning-light-custom .card-body,
        .bg-danger-custom .card-body,
        .bg-danger-dark-custom .card-body {
            color: white !important;
        }
        
        /* Espaçamento reduzido entre cards */
        .row.g-3 {
            --bs-gutter-x: 1rem;
            --bs-gutter-y: 1rem;
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
        
        /* Cores de borda para grupos - mais sutis */
        .kpi-negocio { border-left: 4px solid #3b82f6; }
        .kpi-rentabilidade { border-left: 4px solid #10b981; }
        .kpi-operacoes { border-left: 4px solid #f59e0b; }
        .kpi-recebiveis { border-left: 4px solid #8b5cf6; }
        .kpi-temporal { border-left: 4px solid #ef4444; }
        
        /* Responsividade melhorada */
        @media (max-width: 768px) {
            .kpi-group {
                padding: 1rem;
                margin-bottom: 1rem;
            }
            .kpi-card {
                height: auto;
                min-height: 90px;
            }
            .kpi-card .card-body {
                font-size: 1.1rem;
                height: auto;
                min-height: 50px;
            }
        }
        
                 /* Estilos do gráfico */
        .chart-wrapper {
            position: relative;
            height: 500px;
            margin-top: 20px;
            margin-bottom: 30px;
        }
        .chart-wrapper canvas {
            max-width: 100%;
            height: 100%;
        }
        
        /* Estilos dos filtros melhorados */
        .form-select:focus {
            border-color: #10b981;
            box-shadow: 0 0 0 0.2rem rgba(16, 185, 129, 0.25);
        }
        
        /* Feedback visual para seleção de período */
        .periodo-selecionado {
            border: 2px solid #10b981 !important;
            background-color: #f0fdf4 !important;
        }
        
        /* Melhorar espaçamento dos filtros */
        .form-label {
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.5rem;
        }
        
        /* Ícones do Bootstrap */
        .bi {
            font-size: 1rem;
        }
        
        /* Botões com ícones */
        .btn .bi {
            margin-right: 0.25rem;
        }
        
        /* Estilo para destaque do período ativo */
        .periodo-ativo {
            background-color: #e6fffa !important;
            border-color: #10b981 !important;
        }
        
        /* Tooltip customizado para os períodos */
        .tooltip-periodo {
            position: absolute;
            background: #1f2937;
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 0.8rem;
            z-index: 1000;
            display: none;
            max-width: 200px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            pointer-events: none;
        }
        
        .tooltip-periodo::after {
            content: '';
            position: absolute;
            top: 100%;
            left: 50%;
            margin-left: -5px;
            border-width: 5px;
            border-style: solid;
            border-color: #1f2937 transparent transparent transparent;
        }
    </style>
</head>
<body>
    <?php require_once 'menu.php'; ?>

    <div class="container mt-4">
        <h1 class="mb-4">Relatório Geral de Operações</h1>

        <form method="GET" action="relatorio.php" class="border p-3 rounded bg-light mb-4">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label for="periodo_preset" class="form-label">
                        <i class="bi bi-calendar-range me-1"></i>Período Rápido:
                    </label>
                    <select id="periodo_preset" name="periodo_preset" class="form-select">
                        <option value="">Selecione um período</option>
                        <option value="hoje">Hoje</option>
                        <option value="ontem">Ontem</option>
                        <option value="ultimos_7_dias">Últimos 7 dias</option>
                        <option value="esse_mes">Este mês (MTD)</option>
                        <option value="mes_passado">Mês passado</option>
                        <option value="ultimos_30_dias">Últimos 30 dias</option>
                        <option value="ultimos_90_dias">Últimos 90 dias</option>
                        <option value="esse_ano">Este ano (YTD)</option>
                        <option value="ano_passado">Ano passado</option>
                        <option value="custom">Período personalizado</option>
                    </select>
                </div>
                <div class="col-md-3" id="data_inicio_container">
                    <label for="data_inicio" class="form-label">Data Início:</label>
                    <input type="date" name="data_inicio" id="data_inicio" class="form-control" value="<?php echo htmlspecialchars($data_inicio); ?>">
                </div>
                <div class="col-md-3" id="data_fim_container">
                    <label for="data_fim" class="form-label">Data Fim:</label>
                    <input type="date" name="data_fim" id="data_fim" class="form-control" value="<?php echo htmlspecialchars($data_fim); ?>">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary w-100" title="Gerar Relatório">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
                <div class="col-md-2">
                    <a href="relatorio.php" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-arrow-clockwise me-1"></i>Ver Todos
                    </a>
                </div>
            </div>
            <div class="form-text mt-2">
                <i class="bi bi-info-circle me-1"></i>
                <strong>Dica:</strong> Escolha um período rápido ou use "Período personalizado" para datas específicas.
            </div>
        </form>

        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
        <?php else: ?>
            <h2 class="mb-4">Dashboard Executivo <?php
                 if ($data_inicio || $data_fim) {
                     echo " (" . ($data_inicio ? date('d/m/Y', strtotime($data_inicio)) : 'Início') . " - " . ($data_fim ? date('d/m/Y', strtotime($data_fim)) : 'Fim') . ")";
                 } else {
                     echo "(Total Geral)";
                 }
            ?></h2>

            <!-- GRUPO 1: VISÃO GERAL DO NEGÓCIO -->
            <div class="kpi-group kpi-negocio">
                <h4><i class="bi bi-graph-up me-2"></i>Visão Geral do Negócio</h4>
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="tooltip-kpi">
                            <div class="card kpi-card text-center h-100 bg-neutral-custom">
                                <div class="card-body">
                                    <?php echo $indicadores['num_operacoes']; ?>
                                </div>
                                <div class="card-footer card-title">Operações Realizadas</div>
                            </div>
                            <span class="tooltip-text">
                                <strong>Operações Realizadas</strong><br>
                                Total de operações de factoring executadas no período.<br>
                                Indica o volume de atividade do negócio.
                            </span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="tooltip-kpi">
                            <div class="card kpi-card text-center h-100 bg-info-custom">
                                <div class="card-body">
                                    <?php echo formatHtmlCurrency($indicadores['volume_nominal_total']); ?>
                                </div>
                                <div class="card-footer card-title">Volume Transacionado</div>
                            </div>
                            <span class="tooltip-text">
                                <strong>Volume Transacionado</strong><br>
                                Soma dos valores originais de todos os recebíveis negociados.<br>
                                Representa o valor total dos títulos comprados dos clientes.
                            </span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="tooltip-kpi">
                            <div class="card kpi-card text-center h-100 bg-primary-custom">
                                <div class="card-body">
                                    <?php echo formatHtmlCurrency($indicadores['total_adiantado']); ?>
                                </div>
                                <div class="card-footer card-title">Capital Ativo</div>
                            </div>
                            <span class="tooltip-text">
                                <strong>Capital Ativo</strong><br>
                                Total de capital efetivamente emprestado aos clientes.<br>
                                Valor líquido pago após descontos de juros e IOF.
                            </span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="tooltip-kpi">
                            <div class="card kpi-card text-center h-100 bg-neutral-light-custom">
                                <div class="card-body">
                                    <?php echo formatHtmlCurrency($indicadores['valor_medio_titulos']); ?>
                                </div>
                                <div class="card-footer card-title">Valor Médio por Título</div>
                            </div>
                            <span class="tooltip-text">
                                <strong>Valor Médio por Título</strong><br>
                                Volume Transacionado ÷ Quantidade Total de Títulos<br>
                                Indica o ticket médio dos recebíveis negociados.<br>
                                Exemplo: R$ 570.000 ÷ 100 títulos = R$ 5.700 por título.
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- GRUPO 2: RENTABILIDADE E LUCROS -->
            <div class="kpi-group kpi-rentabilidade">
                <h4><i class="bi bi-currency-dollar me-2"></i>Rentabilidade e Lucros</h4>
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="tooltip-kpi">
                            <div class="card kpi-card text-center h-100 bg-success-custom">
                                <div class="card-body">
                                    <?php echo formatHtmlPercentage($indicadores['rentabilidade_media_estimada']); ?>
                                </div>
                                <div class="card-footer card-title">Margem de Lucro</div>
                            </div>
                            <span class="tooltip-text">
                                <strong>Margem de Lucro</strong><br>
                                Lucro Estimado ÷ Capital Emprestado<br>
                                Indica quantos % você ganha sobre cada real investido.
                            </span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="tooltip-kpi">
                            <div class="card kpi-card text-center h-100 bg-success-custom">
                                <div class="card-body">
                                    <?php echo formatHtmlPercentage($indicadores['rentabilidade_mensal']); ?>
                                </div>
                                <div class="card-footer card-title">Rentabilidade Mensal</div>
                            </div>
                            <span class="tooltip-text">
                                <strong>Rentabilidade Mensal</strong><br>
                                Margem de Lucro ÷ Prazo Médio × 30 dias<br>
                                Representa a rentabilidade média mensal das operações.
                            </span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="tooltip-kpi">
                            <div class="card kpi-card text-center h-100 bg-success-custom">
                                <div class="card-body">
                                    <?php echo formatHtmlCurrency($indicadores['lucro_bruto_estimado']); ?>
                                </div>
                                <div class="card-footer card-title">Lucro Estimado</div>
                            </div>
                            <span class="tooltip-text">
                                <strong>Lucro Estimado</strong><br>
                                Lucro total esperado de todas as operações.<br>
                                Baseado nos valores originais e prazos dos títulos.
                            </span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="tooltip-kpi">
                            <div class="card kpi-card text-center h-100 bg-success-dark-custom">
                                <div class="card-body">
                                    <?php echo formatHtmlCurrency($indicadores['lucro_liquido_realizado']); ?>
                                </div>
                                <div class="card-footer card-title">Lucro Realizado</div>
                            </div>
                            <span class="tooltip-text">
                                <strong>Lucro Realizado</strong><br>
                                Lucro efetivamente recebido dos títulos já pagos.<br>
                                Representa o ganho real já convertido em caixa.
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- GRUPO 3: STATUS DAS OPERAÇÕES -->
            <div class="kpi-group kpi-operacoes">
                <h4><i class="bi bi-list-check me-2"></i>Status das Operações</h4>
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="tooltip-kpi">
                            <div class="card kpi-card text-center h-100 bg-success-custom">
                                <div class="card-body">
                                    <?php echo $indicadores['operacoes_concluidas']; ?>
                                </div>
                                <div class="card-footer card-title">Concluídas</div>
                            </div>
                            <span class="tooltip-text">
                                <strong>Operações Concluídas</strong><br>
                                Operações com 100% dos recebíveis pagos.<br>
                                Indicador de efetividade do negócio.
                            </span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="tooltip-kpi">
                            <div class="card kpi-card text-center h-100 bg-warning-custom">
                                <div class="card-body">
                                    <?php echo $indicadores['operacoes_em_andamento']; ?>
                                </div>
                                <div class="card-footer card-title">Em Andamento</div>
                            </div>
                            <span class="tooltip-text">
                                <strong>Operações em Andamento</strong><br>
                                Operações com recebíveis ainda em aberto.<br>
                                Aguardando recebimento dos pagadores.
                            </span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="tooltip-kpi">
                            <div class="card kpi-card text-center h-100 bg-danger-custom">
                                <div class="card-body">
                                    <?php echo $indicadores['operacoes_com_problema']; ?>
                                </div>
                                <div class="card-footer card-title">Com Problemas</div>
                            </div>
                            <span class="tooltip-text">
                                <strong>Operações com Problemas</strong><br>
                                Operações com pelo menos um recebível problemático.<br>
                                Requerem atenção e possível renegociação.
                            </span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="tooltip-kpi">
                            <div class="card kpi-card text-center h-100 bg-success-custom">
                                <div class="card-body">
                                    <?php echo formatHtmlPercentage($indicadores['taxa_sucesso']); ?>
                                </div>
                                <div class="card-footer card-title">Taxa de Sucesso</div>
                            </div>
                            <span class="tooltip-text">
                                <strong>Taxa de Sucesso</strong><br>
                                % de operações concluídas com sucesso.<br>
                                Operações Concluídas ÷ Total de Operações.
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- GRUPO 4: CARTEIRA DE RECEBÍVEIS -->
            <div class="kpi-group kpi-recebiveis">
                <h4><i class="bi bi-wallet2 me-2"></i>Carteira de Recebíveis</h4>
                <div class="row g-3">
                    <div class="col">
                        <div class="tooltip-kpi">
                            <div class="card kpi-card text-center h-100 bg-success-custom">
                                <div class="card-body">
                                    <div><?php echo $indicadores['count_recebiveis_recebidos']; ?> títulos</div>
                                    <small><?php echo formatHtmlCurrency($indicadores['valor_total_recebido']); ?></small>
                                </div>
                                <div class="card-footer card-title">Recebidos</div>
                            </div>
                            <span class="tooltip-text">
                                <strong>Recebíveis Recebidos</strong><br>
                                Títulos já pagos pelos sacados.<br>
                                Valor: soma dos valores originais recebidos.
                            </span>
                        </div>
                    </div>
                    <div class="col">
                        <div class="tooltip-kpi">
                            <div class="card kpi-card text-center h-100 bg-info-custom">
                                <div class="card-body">
                                    <?php
                                    // Calcular projeção total futura
                                    $currentDate = new DateTime();
                                    $futureStartDate = clone $currentDate;
                                    $futureStartDate->modify('first day of this month');
                                    $futureEndDate = clone $currentDate;
                                    $futureEndDate->modify('+12 months last day of this month');
                                    
                                    $futureStartSQL = $futureStartDate->format('Y-m-d');
                                    $futureEndSQL = $futureEndDate->format('Y-m-d');
                                    
                                    $sql_projecao_total = "SELECT
                                                            COUNT(r.id) as quantidade_total,
                                                            SUM(
                                                                CASE
                                                                    WHEN r.status = 'Em Aberto' THEN r.valor_original
                                                                    WHEN r.status = 'Problema' THEN r.valor_original
                                                                    WHEN r.status = 'Parcialmente Compensado' THEN (r.valor_original - COALESCE((SELECT SUM(c.valor_compensado) FROM compensacoes c WHERE c.recebivel_compensado_id = r.id), 0))
                                                                    ELSE 0
                                                                END
                                                            ) as valor_total
                                                         FROM recebiveis r
                                                         WHERE r.data_vencimento >= :future_start_date
                                                           AND r.data_vencimento <= :future_end_date
                                                           AND r.status != 'Recebido'";
                                    
                                    $stmt_projecao = $pdo->prepare($sql_projecao_total);
                                    $stmt_projecao->bindParam(':future_start_date', $futureStartSQL);
                                    $stmt_projecao->bindParam(':future_end_date', $futureEndSQL);
                                    $stmt_projecao->execute();
                                    $projecao_total = $stmt_projecao->fetch(PDO::FETCH_ASSOC);
                                    
                                    $qtd_projecao = $projecao_total['quantidade_total'] ?? 0;
                                    $valor_projecao = $projecao_total['valor_total'] ?? 0;
                                    ?>
                                    <div><?php echo intval($qtd_projecao); ?> títulos</div>
                                    <small><?php echo formatHtmlCurrency($valor_projecao); ?></small>
                                </div>
                                <div class="card-footer card-title">Projeção Total</div>
                            </div>
                            <span class="tooltip-text">
                                <strong>Projeção Total</strong><br>
                                Todos os recebíveis com vencimento futuro.<br>
                                Inclui todos os status: Em Aberto, Recebidos, Parciais, etc.<br>
                                <em><strong>IMPORTANTE:</strong> Este card sempre mostra o total futuro e NÃO varia de acordo com filtros de data.</em>
                            </span>
                        </div>
                    </div>
                    <div class="col">
                        <div class="tooltip-kpi">
                            <div class="card kpi-card text-center h-100 bg-danger-custom">
                                <div class="card-body">
                                    <div><?php echo $indicadores['count_recebiveis_problema']; ?> títulos</div>
                                    <small><?php echo formatHtmlCurrency($indicadores['valor_total_em_problema']); ?></small>
                                </div>
                                <div class="card-footer card-title">Problemas</div>
                            </div>
                            <span class="tooltip-text">
                                <strong>Recebíveis com Problema</strong><br>
                                Títulos com dificuldades de recebimento.<br>
                                Valor: soma dos valores originais problemáticos.
                            </span>
                        </div>
                    </div>
                    <div class="col">
                        <div class="tooltip-kpi">
                            <div class="card kpi-card text-center h-100 bg-warning-light-custom">
                                <div class="card-body">
                                    <?php
                                    // Usar os dados já calculados pela função obterDadosRelatorio() para consistência
                                    $qtd_parcial = $indicadores['count_recebiveis_parcialmente_compensado'] ?? 0;
                                    
                                    // Calcular saldo em aberto usando a mesma lógica da função obterDadosRelatorio()
                                    if (empty($params_op)) {
                                        // SEM FILTRO: Buscar saldo de todos os parcialmente compensados
                                        $sql_saldo_parcial = "SELECT
                                                                SUM(r.valor_original - COALESCE((SELECT SUM(c.valor_compensado) FROM compensacoes c WHERE c.recebivel_compensado_id = r.id), 0)) as saldo_em_aberto
                                                              FROM recebiveis r
                                                              WHERE r.status = 'Parcialmente Compensado'";
                                        $stmt_saldo = $pdo->prepare($sql_saldo_parcial);
                                        $stmt_saldo->execute();
                                    } else {
                                        // COM FILTRO: Aplicar filtro de data da operação (mesma lógica da função)
                                        $sql_saldo_parcial = "SELECT
                                                                SUM(r.valor_original - COALESCE((SELECT SUM(c.valor_compensado) FROM compensacoes c WHERE c.recebivel_compensado_id = r.id), 0)) as saldo_em_aberto
                                                              FROM recebiveis r
                                                              JOIN operacoes o ON r.operacao_id = o.id
                                                              WHERE r.status = 'Parcialmente Compensado' " .
                                                              (!empty($whereClauses_op) ? " AND " . implode(" AND ", $whereClauses_op) : "");
                                        $stmt_saldo = $pdo->prepare($sql_saldo_parcial);
                                        foreach ($params_op as $key => $value) {
                                            $stmt_saldo->bindParam($key, $value);
                                        }
                                        $stmt_saldo->execute();
                                    }
                                    $saldo_result = $stmt_saldo->fetch(PDO::FETCH_ASSOC);
                                    $saldo_aberto = $saldo_result['saldo_em_aberto'] ?? 0;
                                    ?>
                                    <div><?php echo $qtd_parcial; ?> títulos</div>
                                    <small>Saldo: <?php echo formatHtmlCurrency($saldo_aberto); ?></small>
                                </div>
                                <div class="card-footer card-title">Parcialmente Compensado</div>
                            </div>
                            <span class="tooltip-text">
                                <strong>Recebíveis Parcialmente Compensados</strong><br>
                                Títulos com pagamento parcial recebido.<br>
                                Saldo: valor ainda em aberto (original - compensado).<br>
                                <em>Respeita filtros de data da operação.</em>
                            </span>
                        </div>
                    </div>
                    <div class="col">
                        <div class="tooltip-kpi">
                            <div class="card kpi-card text-center h-100 bg-neutral-light-custom">
                                <div class="card-body">
                                    <div><?php echo $indicadores['num_total_recebiveis']; ?> títulos</div>
                                    <small><?php echo formatHtmlCurrency($valor_total_carteira); ?></small>
                                </div>
                                <div class="card-footer card-title">Total Carteira</div>
                            </div>
                            <span class="tooltip-text">
                                <strong>Total da Carteira</strong><br>
                                Quantidade total de títulos na carteira.<br>
                                Valor total de todos os recebíveis.
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- GRUPO 5: INDICADORES TEMPORAIS -->
            <div class="kpi-group kpi-temporal">
                <h4><i class="bi bi-clock me-2"></i>Indicadores Temporais</h4>
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="tooltip-kpi">
                            <div class="card kpi-card text-center h-100 bg-neutral-light-custom">
                                <div class="card-body">
                                    <?php
                                    // Calcular prazo médio simples (66 dias) - mesma lógica do listar_operacoes.php
                                    // Primeiro calcular média por operação, depois média das operações
                                    $sql_prazo_simples = "SELECT AVG(media_dias_por_operacao) as prazo_medio_simples
                                                         FROM (
                                                             SELECT AVG(DATEDIFF(r.data_vencimento, o.data_operacao)) as media_dias_por_operacao
                                                             FROM recebiveis r
                                                             JOIN operacoes o ON r.operacao_id = o.id" .
                                                             (!empty($whereClauses_op) ? " WHERE " . implode(" AND ", $whereClauses_op) : "") . "
                                                             GROUP BY o.id
                                                         ) as operacoes_media";
                                    $stmt_prazo_simples = $pdo->prepare($sql_prazo_simples);
                                    foreach ($params_op as $key => $value) {
                                        $stmt_prazo_simples->bindParam($key, $value);
                                    }
                                    $stmt_prazo_simples->execute();
                                    $prazo_simples = $stmt_prazo_simples->fetch(PDO::FETCH_ASSOC);
                                    $prazo_medio_simples = round($prazo_simples['prazo_medio_simples'] ?? 0);
                                    
                                    // Usar o prazo médio ponderado já calculado (74 dias)
                                    $prazo_medio_ponderado = round($indicadores['prazo_medio_dias']);
                                    ?>
                                    <div style="font-size: 1.1rem;"><?php echo $prazo_medio_simples; ?> & <?php echo $prazo_medio_ponderado; ?></div>
                                    <small style="font-size: 0.7rem; opacity: 0.8;">dias</small>
                                </div>
                                <div class="card-footer card-title">Prazo Médio</div>
                            </div>
                            <span class="tooltip-text">
                                <strong>Prazo Médio Dual</strong><br>
                                <strong><?php echo $prazo_medio_simples; ?> dias:</strong> Média simples (aritmética) dos prazos<br>
                                <strong><?php echo $prazo_medio_ponderado; ?> dias:</strong> Média ponderada por valores<br><br>
                                <em>A média simples trata todos os títulos igualmente, enquanto a ponderada dá mais peso aos títulos de maior valor.</em>
                            </span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="tooltip-kpi">
                            <div class="card kpi-card text-center h-100 bg-warning-light-custom">
                                <div class="card-body">
                                    <?php echo formatHtmlCurrency($indicadores['valor_proximos_30_dias']); ?>
                                </div>
                                <div class="card-footer card-title">Próximos 30 Dias</div>
                            </div>
                            <span class="tooltip-text">
                                <strong>Recebimentos Próximos 30 Dias</strong><br>
                                Valor dos títulos em aberto com vencimento nos próximos 30 dias.<br>
                                Indica o fluxo de caixa esperado.
                            </span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="tooltip-kpi">
                            <div class="card kpi-card text-center h-100 bg-danger-custom">
                                <div class="card-body">
                                    <?php echo formatHtmlCurrency($indicadores['valor_vencidos']); ?>
                                </div>
                                <div class="card-footer card-title">Vencidos</div>
                            </div>
                            <span class="tooltip-text">
                                <strong>Títulos Vencidos</strong><br>
                                Valor dos títulos em aberto com vencimento já passado.<br>
                                Requerem acompanhamento e possível cobrança.
                            </span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="tooltip-kpi">
                            <div class="card kpi-card text-center h-100 bg-danger-dark-custom">
                                <div class="card-body">
                                    <?php echo formatHtmlPercentage($indicadores['indice_problemas'], 1); ?>
                                </div>
                                <div class="card-footer card-title">Índice de Problemas</div>
                            </div>
                            <span class="tooltip-text">
                                <strong>Índice de Problemas</strong><br>
                                % do volume nominal com problemas.<br>
                                Valor Problemático ÷ Volume Total Transacionado.
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <h3 class="mb-3 mt-5">Fluxo de Caixa dos Últimos 12 Meses (Vencimento)</h3>
            
            <!-- Toggle Switch para Regime de Competência/Caixa -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="regimeToggle" checked>
                    <label class="form-check-label fw-bold" for="regimeToggle">
                        <span id="regimeLabel">Regime de Competência</span>
                    </label>
                </div>
                <div class="badge bg-primary fs-6 px-3 py-2" id="regimeStatus">
                    Exibindo dados em regime de competência
                </div>
            </div>
            
            <div class="chart-wrapper">
                <?php if (empty($chartLabels)): ?>
                    <div class="alert alert-info text-center">Nenhum dado de recebível nos últimos 12 meses para gerar o gráfico.</div>
                <?php else: ?>
                    <canvas id="monthlyFlowChart"></canvas>
                <?php endif; ?>
            
            
            </div>
            <!-- Segundo Gráfico: Projeção de Recebíveis Futuros -->
            <?php if (!empty($chartFutureData)): ?>
            <h3 class="mb-3 mt-5">Projeção de Recebíveis Futuros</h3>
            
            <div class="alert alert-info mb-3">
                <i class="bi bi-info-circle me-2"></i>
                <strong>Projeção:</strong> Este gráfico mostra os recebíveis com vencimento futuro, baseado nas operações já realizadas. 
                Total projetado: <strong><?php echo formatHtmlCurrency(array_sum($chartFutureDataValorOriginal)); ?></strong> 
                em <?php echo count($chartFutureData); ?> meses.
            </div>
            
            <div class="chart-wrapper">
                <canvas id="futureProjectionChart"></canvas>
            </div>
            <?php else: ?>
            <h3 class="mb-3 mt-5">Projeção de Recebíveis Futuros</h3>
            <div class="alert alert-warning text-center">
                <i class="bi bi-calendar-x me-2"></i>
                Nenhum recebível com vencimento futuro encontrado para gerar a projeção.
            </div>
            <?php endif; ?>


        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Função auxiliar para formatar moeda no JS
        function formatCurrencyJS(value) {
            if (typeof value === 'number') {
                return 'R$ ' + value.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }
            return '--';
        }

        let myMonthlyChart = null; // Instância do Chart.js para este gráfico
        let currentRegime = 'competencia'; // Variável para controlar o regime atual

        // Dados do PHP para o JavaScript
        const chartLabels = <?php echo json_encode($chartLabels) ?: '[]'; ?>; // Adicionado fallback
        const chartDataValorOriginal = <?php echo json_encode($chartDataValorOriginal) ?: '[]'; ?>; // Adicionado fallback
        const chartDataLucroCompetencia = <?php echo json_encode($chartDataLucroCompetencia) ?: '[]'; ?>; // Adicionado fallback
        const chartDataLucroCaixa = <?php echo json_encode($chartDataLucroCaixa) ?: '[]'; ?>; // Adicionado fallback
        const chartDataCapitalEmRisco = <?php echo json_encode($chartDataCapitalEmRisco) ?: '[]'; ?>; // Adicionado fallback
        const chartDataValorComProblema = <?php echo json_encode($chartDataValorComProblema) ?: '[]'; ?>; // Adicionado fallback
        const chartDataParcialmenteCompensado = <?php echo json_encode($chartDataParcialmenteCompensado) ?: '[]'; ?>; // NOVO: Parcialmente compensados
        const chartDataCapitalEmprestado = <?php echo json_encode($chartDataCapitalEmprestado) ?: '[]'; ?>; // Adicionado fallback
        const chartDataCapitalRetornado = <?php echo json_encode($chartDataCapitalRetornado) ?: '[]'; ?>; // Adicionado fallback
        const chartDataCapitalRetornadoRealizado = <?php echo json_encode($chartDataCapitalRetornadoRealizado) ?: '[]'; ?>; // Adicionado fallback

        // Função para inicializar o gráfico (primeira vez)
        function initializeMonthlyChart(labels, dataValorOriginal, dataLucroCompetencia, dataLucroCaixa, dataCapitalEmRisco, dataValorComProblema, dataParcialmenteCompensado, dataCapitalEmprestado, dataCapitalRetornado, dataCapitalRetornadoRealizado, regime = 'competencia') {
            if (!labels || !dataValorOriginal || !dataLucroCompetencia || !dataLucroCaixa || !dataCapitalEmRisco || !dataValorComProblema || !dataParcialmenteCompensado || !dataCapitalEmprestado || !dataCapitalRetornado || !dataCapitalRetornadoRealizado ||
                !Array.isArray(labels) || !Array.isArray(dataValorOriginal) || !Array.isArray(dataLucroCompetencia) || !Array.isArray(dataLucroCaixa) || !Array.isArray(dataCapitalEmRisco) || !Array.isArray(dataValorComProblema) || !Array.isArray(dataParcialmenteCompensado) || !Array.isArray(dataCapitalEmprestado) || !Array.isArray(dataCapitalRetornado) || !Array.isArray(dataCapitalRetornadoRealizado) ||
                labels.length === 0 || dataValorOriginal.length !== labels.length || dataLucroCompetencia.length !== labels.length || dataLucroCaixa.length !== labels.length || dataCapitalEmRisco.length !== labels.length || dataValorComProblema.length !== labels.length || dataParcialmenteCompensado.length !== labels.length || dataCapitalEmprestado.length !== labels.length || dataCapitalRetornado.length !== labels.length || dataCapitalRetornadoRealizado.length !== labels.length) {
                console.warn("Dados inválidos ou inconsistentes para o gráfico mensal.");
                return;
            }

            const chartCanvasElement = document.getElementById('monthlyFlowChart');
            if (!chartCanvasElement) {
                console.error("Canvas do gráfico mensal não encontrado!");
                return;
            }
            const ctx = chartCanvasElement.getContext('2d');
            if (!ctx) {
                console.error("Contexto 2D do gráfico mensal não obtido.");
                return;
            }

            // Definir o regime atual
            currentRegime = regime;

            // Selecionar dados de lucro e capital retornado conforme o regime
            const dataLucroAtual = regime === 'competencia' ? dataLucroCompetencia : dataLucroCaixa;
            const dataCapitalRetornadoAtual = regime === 'competencia' ? dataCapitalRetornado : dataCapitalRetornadoRealizado;
            const labelLucro = regime === 'competencia' ? 'Lucro Adicional (Competência)' : 'Lucro Adicional (Caixa)';
            const labelCapitalRetornado = regime === 'competencia' ? 'Capital Retornado (Competência)' : 'Capital Retornado (Caixa)';

            try {
                myMonthlyChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: 'Capital Emprestado (Saída)',
                                data: dataCapitalEmprestado,
                                backgroundColor: 'rgba(148, 0, 211, 0.7)', // Roxa
                                borderColor: 'rgba(148, 0, 211, 1)',
                                borderWidth: 1,
                                stack: 'Stack 0'
                            },
                            {
                                label: labelCapitalRetornado,
                                data: dataCapitalRetornadoAtual,
                                backgroundColor: 'rgba(0, 123, 255, 0.7)', // Azul
                                borderColor: 'rgba(0, 123, 255, 1)',
                                borderWidth: 1,
                                stack: 'Stack 1'
                            },
                            {
                                label: labelLucro,
                                data: dataLucroAtual,
                                backgroundColor: 'rgba(25, 135, 84, 0.7)', // Verde
                                borderColor: 'rgba(25, 135, 84, 1)',
                                borderWidth: 1,
                                stack: 'Stack 1'
                            },
                            {
                                label: 'Valor com Problema',
                                data: dataValorComProblema,
                                backgroundColor: 'rgba(220, 53, 69, 0.7)', // Vermelho
                                borderColor: 'rgba(220, 53, 69, 1)',
                                borderWidth: 1,
                                stack: 'Stack 2'
                            },
                            {
                                label: 'Saldo em Aberto (Parciais)',
                                data: dataParcialmenteCompensado,
                                backgroundColor: 'rgba(255, 193, 7, 0.7)', // Amarelo/Laranja
                                borderColor: 'rgba(255, 193, 7, 1)',
                                borderWidth: 1,
                                stack: 'Stack 2'
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: {
                                stacked: true,
                                ticks: {
                                    callback: function(value, index, ticks) {
                                        const labelData = this.chart.data.labels[index];
                                        if (labelData && typeof labelData === 'object') {
                                            const mesAno = labelData.text;
                                            const capitalEmprestado = formatCurrencyJS(labelData.capital_emprestado);
                                            const capitalRetornado = currentRegime === 'competencia' ?
                                                formatCurrencyJS(labelData.capital_retornado) :
                                                formatCurrencyJS(labelData.capital_retornado_realizado);
                                            const lucro = currentRegime === 'competencia' ?
                                                formatCurrencyJS(labelData.lucro_competencia) :
                                                formatCurrencyJS(labelData.lucro_caixa);
                                            
                                            return [
                                                mesAno,
                                                'Capital Emprestado: ' + capitalEmprestado,
                                                'Capital Retornado: ' + capitalRetornado,
                                                'Lucro: ' + lucro
                                            ];
                                        }
                                        return String(labelData || '');
                                    },
                                    maxRotation: 45,
                                    minRotation: 45
                                }
                            },
                            y: {
                                beginAtZero: true,
                                stacked: true,
                                ticks: {
                                    callback: function(value) {
                                        return value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL', minimumFractionDigits: 0, maximumFractionDigits: 0 });
                                    }
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top',
                            },
                            tooltip: {
                                callbacks: {
                                    title: function(context) {
                                        // Tentar diferentes formas de acessar o label
                                        let labelData = context[0].label;
                                        
                                        // Se for um array, pegar o primeiro elemento
                                        if (Array.isArray(labelData)) {
                                            labelData = labelData[0];
                                        }
                                        
                                        // Se for objeto, extrair o texto
                                        if (labelData && typeof labelData === 'object' && labelData.text) {
                                            return labelData.text;
                                        }
                                        
                                        // Se for string simples, retornar
                                        if (typeof labelData === 'string') {
                                            return labelData;
                                        }
                                        
                                        // Fallback: tentar converter para string
                                        return String(labelData || 'Dados do mês');
                                    },
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label) { label += ': '; }
                                        if (context.parsed.y !== null) {
                                            const value = context.parsed.y;
                                            label += value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
                                        }
                                        return label;
                                    }
                                }
                            }
                        }
                    }
                });
                
            } catch (chartError) {
                console.error("Erro Chart.js no Relatório:", chartError);
            }
        }

        // Função para atualizar o gráfico (mudança de regime)
        function updateMonthlyChart(regime = 'competencia') {
            if (!myMonthlyChart) {
                console.error("Gráfico não foi inicializado!");
                return;
            }

            // Atualizar o regime atual
            currentRegime = regime;

            // Selecionar dados de lucro e capital retornado conforme o regime
        
            const dataLucroAtual = regime === 'competencia' ? chartDataLucroCompetencia : chartDataLucroCaixa;
            const dataCapitalRetornadoAtual = regime === 'competencia' ? chartDataCapitalRetornado : chartDataCapitalRetornadoRealizado;
            const labelLucro = regime === 'competencia' ? 'Lucro Adicional (Competência)' : 'Lucro Adicional (Caixa)';
            const labelCapitalRetornado = regime === 'competencia' ? 'Capital Retornado (Competência)' : 'Capital Retornado (Caixa)';

            // Atualizar os dados do dataset de capital retornado (índice 1)
            myMonthlyChart.data.datasets[1].data = dataCapitalRetornadoAtual;
            myMonthlyChart.data.datasets[1].label = labelCapitalRetornado;
            myMonthlyChart.data.datasets[1].stack = 'Stack 1'; // Manter empilhado com o lucro

            // Atualizar os dados do dataset de lucro (índice 2)
            myMonthlyChart.data.datasets[2].data = dataLucroAtual;
            myMonthlyChart.data.datasets[2].label = labelLucro;
            myMonthlyChart.data.datasets[2].stack = 'Stack 1'; // Manter empilhado com a base

            // Forçar atualização do gráfico (incluindo os labels do eixo X)
            myMonthlyChart.update();
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Controle do Toggle Switch para Regime de Competência/Caixa
            const regimeToggle = document.getElementById('regimeToggle');
            const regimeLabel = document.getElementById('regimeLabel');
            const regimeStatus = document.getElementById('regimeStatus');
            
            if (regimeToggle) {
                regimeToggle.addEventListener('change', function() {
                    if (this.checked) {
                        // Regime de Competência
                        regimeLabel.textContent = 'Regime de Competência';
                        regimeStatus.textContent = 'Exibindo dados em regime de competência';
                        regimeStatus.className = 'badge bg-primary fs-6 px-3 py-2';
                        console.log('Alterado para Regime de Competência');
                        // Atualizar gráfico com dados de competência
                        updateMonthlyChart('competencia');
                    } else {
                        // Regime de Caixa
                        regimeLabel.textContent = 'Regime de Caixa';
                        regimeStatus.textContent = 'Exibindo dados em regime de caixa';
                        regimeStatus.className = 'badge bg-success fs-6 px-3 py-2';
                        console.log('Alterado para Regime de Caixa');
                        // Atualizar gráfico com dados de caixa
                        updateMonthlyChart('caixa');
                    }
                });
            }
            
            if (chartLabels.length > 0) {
                // Inicializar com regime de competência (toggle marcado por padrão)
                initializeMonthlyChart(chartLabels, chartDataValorOriginal, chartDataLucroCompetencia, chartDataLucroCaixa, chartDataCapitalEmRisco, chartDataValorComProblema, chartDataParcialmenteCompensado, chartDataCapitalEmprestado, chartDataCapitalRetornado, chartDataCapitalRetornadoRealizado, 'competencia');
            } else {
                console.log("Sem dados para o gráfico mensal.");
            }
            
            // JavaScript para gerenciar os filtros de período
            const periodoSelect = document.getElementById('periodo_preset');
            const dataInicioInput = document.getElementById('data_inicio');
            const dataFimInput = document.getElementById('data_fim');
            
            if (periodoSelect) {
                // Função para formatar data para input date (YYYY-MM-DD)
                function formatDateForInput(date) {
                    const year = date.getFullYear();
                    const month = String(date.getMonth() + 1).padStart(2, '0');
                    const day = String(date.getDate()).padStart(2, '0');
                    return `${year}-${month}-${day}`;
                }

                // Função para calcular datas baseado no período selecionado
                function calcularDatas(periodo) {
                    const hoje = new Date();
                    const ontem = new Date(hoje);
                    ontem.setDate(ontem.getDate() - 1);
                    
                    let dataInicio, dataFim;

                    switch (periodo) {
                        case 'hoje':
                            dataInicio = hoje;
                            dataFim = hoje;
                            break;
                        
                        case 'ontem':
                            dataInicio = ontem;
                            dataFim = ontem;
                            break;
                        
                        case 'ultimos_7_dias':
                            dataInicio = new Date(hoje);
                            dataInicio.setDate(hoje.getDate() - 6);
                            dataFim = hoje;
                            break;
                        
                        case 'esse_mes':
                            dataInicio = new Date(hoje.getFullYear(), hoje.getMonth(), 1);
                            dataFim = hoje;
                            break;
                        
                        case 'mes_passado':
                            dataInicio = new Date(hoje.getFullYear(), hoje.getMonth() - 1, 1);
                            dataFim = new Date(hoje.getFullYear(), hoje.getMonth(), 0);
                            break;
                        
                        case 'ultimos_30_dias':
                            dataInicio = new Date(hoje);
                            dataInicio.setDate(hoje.getDate() - 29);
                            dataFim = hoje;
                            break;
                        
                        case 'ultimos_90_dias':
                            dataInicio = new Date(hoje);
                            dataInicio.setDate(hoje.getDate() - 89);
                            dataFim = hoje;
                            break;
                        
                        case 'esse_ano':
                            dataInicio = new Date(hoje.getFullYear(), 0, 1);
                            dataFim = hoje;
                            break;
                        
                        case 'ano_passado':
                            dataInicio = new Date(hoje.getFullYear() - 1, 0, 1);
                            dataFim = new Date(hoje.getFullYear() - 1, 11, 31);
                            break;
                        
                        default:
                            return null;
                    }

                    return {
                        inicio: formatDateForInput(dataInicio),
                        fim: formatDateForInput(dataFim)
                    };
                }

                // Event listener para mudança no select
                periodoSelect.addEventListener('change', function() {
                    const periodo = this.value;
                    
                    if (periodo === '' || periodo === 'custom') {
                        // Sem período selecionado ou personalizado - não alterar datas
                        return;
                    }

                    // Período predefinido - calcular e preencher datas
                    const datas = calcularDatas(periodo);
                    if (datas) {
                        dataInicioInput.value = datas.inicio;
                        dataFimInput.value = datas.fim;
                    }
                });

                // Feedback visual para o usuário
                periodoSelect.addEventListener('change', function() {
                    const periodo = this.value;
                    if (periodo && periodo !== 'custom') {
                        // Adicionar um pequeno feedback visual
                        this.style.border = '2px solid #10b981';
                        setTimeout(() => {
                            this.style.border = '';
                        }, 1000);
                    }
                });
            }

            // --- SEGUNDO GRÁFICO: Projeção de Recebíveis Futuros ---
            let myFutureChart = null; // Instância do Chart.js para o gráfico futuro

            // Dados do PHP para o JavaScript (gráfico futuro)
            const chartFutureLabels = <?php echo json_encode($chartFutureLabels) ?: '[]'; ?>;
            const chartFutureDataValorOriginal = <?php echo json_encode($chartFutureDataValorOriginal) ?: '[]'; ?>;
            const chartFutureDataLucro = <?php echo json_encode($chartFutureDataLucro) ?: '[]'; ?>;
            const chartFutureDataCapitalEmRisco = <?php echo json_encode($chartFutureDataCapitalEmRisco) ?: '[]'; ?>;
            const chartFutureDataValorComProblema = <?php echo json_encode($chartFutureDataValorComProblema) ?: '[]'; ?>;
            const chartFutureDataParcialmenteCompensado = <?php echo json_encode($chartFutureDataParcialmenteCompensado) ?: '[]'; ?>;

            // Função para inicializar o gráfico de projeção futura
            function initializeFutureChart(labels, dataValorOriginal, dataLucro, dataCapitalEmRisco, dataValorComProblema, dataParcialmenteCompensado) {
                if (!labels || !dataValorOriginal || !dataLucro || !dataCapitalEmRisco || !dataValorComProblema || !dataParcialmenteCompensado ||
                    !Array.isArray(labels) || !Array.isArray(dataValorOriginal) || !Array.isArray(dataLucro) || !Array.isArray(dataCapitalEmRisco) || !Array.isArray(dataValorComProblema) || !Array.isArray(dataParcialmenteCompensado) ||
                    labels.length === 0 || dataValorOriginal.length !== labels.length || dataLucro.length !== labels.length || dataCapitalEmRisco.length !== labels.length || dataValorComProblema.length !== labels.length || dataParcialmenteCompensado.length !== labels.length) {
                    console.warn("Dados inválidos ou inconsistentes para o gráfico de projeção futura.");
                    return;
                }

                const chartCanvasElement = document.getElementById('futureProjectionChart');
                if (!chartCanvasElement) {
                    console.error("Canvas do gráfico de projeção futura não encontrado!");
                    return;
                }
                const ctx = chartCanvasElement.getContext('2d');
                if (!ctx) {
                    console.error("Contexto 2D do gráfico de projeção futura não obtido.");
                    return;
                }

                try {
                    myFutureChart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: labels,
                            datasets: [
                                {
                                    label: 'Valor Original Projetado',
                                    data: dataValorOriginal,
                                    backgroundColor: 'rgba(54, 162, 235, 0.7)', // Azul claro
                                    borderColor: 'rgba(54, 162, 235, 1)',
                                    borderWidth: 1,
                                    stack: 'Stack 0'
                                },
                                {
                                    label: 'Lucro Projetado',
                                    data: dataLucro,
                                    backgroundColor: 'rgba(75, 192, 192, 0.7)', // Verde água
                                    borderColor: 'rgba(75, 192, 192, 1)',
                                    borderWidth: 1,
                                    stack: 'Stack 0'
                                },
                                {
                                    label: 'Em Aberto',
                                    data: dataCapitalEmRisco,
                                    backgroundColor: 'rgba(255, 206, 86, 0.7)', // Amarelo
                                    borderColor: 'rgba(255, 206, 86, 1)',
                                    borderWidth: 1,
                                    stack: 'Stack 1'
                                },
                                {
                                    label: 'Parcialmente Compensado',
                                    data: dataParcialmenteCompensado,
                                    backgroundColor: 'rgba(153, 102, 255, 0.7)', // Roxo
                                    borderColor: 'rgba(153, 102, 255, 1)',
                                    borderWidth: 1,
                                    stack: 'Stack 1'
                                },
                                {
                                    label: 'Com Problema',
                                    data: dataValorComProblema,
                                    backgroundColor: 'rgba(255, 99, 132, 0.7)', // Vermelho
                                    borderColor: 'rgba(255, 99, 132, 1)',
                                    borderWidth: 1,
                                    stack: 'Stack 1'
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                x: {
                                    stacked: true,
                                    ticks: {
                                        callback: function(value, index, ticks) {
                                            const labelData = this.chart.data.labels[index];
                                            if (labelData && typeof labelData === 'object') {
                                                const mesAno = labelData.text;
                                                const valorOriginal = formatCurrencyJS(labelData.valor_original);
                                                const lucro = formatCurrencyJS(labelData.lucro);
                                                const problema = formatCurrencyJS(labelData.valor_com_problema);
                                                return [mesAno, 'Valor: ' + valorOriginal, 'Lucro: ' + lucro, 'Problema: ' + problema];
                                            }
                                            return '';
                                        }
                                    }
                                },
                                y: {
                                    beginAtZero: true,
                                    stacked: true,
                                    ticks: {
                                        callback: function(value) {
                                            return value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL', minimumFractionDigits: 0, maximumFractionDigits: 0 });
                                        }
                                    }
                                }
                            },
                            plugins: {
                                legend: {
                                    display: true,
                                    position: 'top',
                                },
                                tooltip: {
                                    callbacks: {
                                        title: function(context) {
                                            // Tentar diferentes formas de acessar o label
                                            let labelData = context[0].label;
                                            
                                            // Se for um array, pegar o primeiro elemento
                                            if (Array.isArray(labelData)) {
                                                labelData = labelData[0];
                                            }
                                            
                                            // Se for objeto, extrair o texto
                                            if (labelData && typeof labelData === 'object' && labelData.text) {
                                                return labelData.text;
                                            }
                                            
                                            // Se for string simples, retornar
                                            if (typeof labelData === 'string') {
                                                return labelData;
                                            }
                                            
                                            // Fallback: tentar converter para string
                                            return String(labelData || 'Projeção');
                                        },
                                        label: function(context) {
                                            let label = context.dataset.label || '';
                                            if (label) { label += ': '; }
                                            if (context.parsed.y !== null) {
                                                const value = context.parsed.y;
                                                label += value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
                                            }
                                            return label;
                                        }
                                    }
                                }
                            }
                        }
                    });
                    
                } catch (chartError) {
                    console.error("Erro Chart.js no Gráfico de Projeção:", chartError);
                }
            }

            // Inicializar o gráfico de projeção futura se houver dados
            if (chartFutureLabels.length > 0) {
                initializeFutureChart(chartFutureLabels, chartFutureDataValorOriginal, chartFutureDataLucro, chartFutureDataCapitalEmRisco, chartFutureDataValorComProblema, chartFutureDataParcialmenteCompensado);
            } else {
                console.log("Sem dados para o gráfico de projeção futura.");
            }
        });
    </script>
</body>
</html>
