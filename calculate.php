<?php
require_once 'db_connection.php';
require_once 'functions.php';
require_once 'funcoes_compensacao.php';
require_once 'funcoes_lucro.php';

header('Content-Type: application/json; charset=utf-8');

// Verifica se é uma operação existente (tem ID)
$operacao_id = isset($_POST['operacao_id']) ? (int)$_POST['operacao_id'] : null;

// Se for operação existente, busca dados do banco
if ($operacao_id) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT total_original_calc, total_liquido_pago_calc, total_lucro_liquido_calc, media_dias_operacao FROM operacoes WHERE id = :id");
        $stmt->bindParam(':id', $operacao_id, PDO::PARAM_INT);
        $stmt->execute();
        $operacao = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($operacao) {
            // Usa valores do banco de dados
            $totalOriginal = (float)$operacao['total_original_calc'];
            $totalLiquidoPago = (float)$operacao['total_liquido_pago_calc'];
            $totalLucroLiquido = (float)$operacao['total_lucro_liquido_calc'];
            $mediaPonderadaDiasNumerico = (float)$operacao['media_dias_operacao'];
            
            // Calcula valores derivados
            $totalLucroPercentual = ($totalOriginal > 0) ? ($totalLucroLiquido / $totalOriginal) : 0;
            $retornoMensalDecimal = 0;
            if ($totalLiquidoPago > 0 && $mediaPonderadaDiasNumerico > 0) {
                $taxaPeriodo = $totalLucroLiquido / $totalLiquidoPago;
                $base = 1 + $taxaPeriodo;
                $expoente = 30.0 / $mediaPonderadaDiasNumerico;
                if ($base >= 0) {
                    $retornoMensalDecimal = pow($base, $expoente) - 1;
                } else {
                    $retornoMensalDecimal = -1;
                }
            }
            
            // Resposta simplificada para operação existente
            $response = [
                'mediaPonderadaDias' => round($mediaPonderadaDiasNumerico) . ' dias',
                'totalOriginal' => formatCurrency($totalOriginal),
                'totalPresente' => formatCurrency($totalOriginal), // Aproximação
                'totalIOF' => formatCurrency(0), // Não armazenado separadamente
                'totalLiquidoPago' => formatCurrency($totalLiquidoPago),
                'totalLucroLiquido' => formatCurrency($totalLucroLiquido),
                'totalLucroPercentual' => formatPercent($totalLucroPercentual),
                'retornoMensalFormatado' => ($retornoMensalDecimal == -1) ? 'N/A (Resultado Negativo)' : formatPercent($retornoMensalDecimal),
                'isProfit' => $totalLucroLiquido >= 0,
                'error' => null,
                'chartLabels' => [],
                'chartDataCapitalEmprestado' => [],
                'chartDataCapitalRetornado' => [],
                'chartDataLucro' => [],
                'calculatedTitlesDetails' => [],
                'custoCompensacao' => formatCurrency(0),
                'custoCompensacaoNumerico' => 0,
                'totalLiquidoPagoNumerico' => $totalLiquidoPago
            ];
            
            echo json_encode($response);
            exit;
        }
    } catch (Exception $e) {
        // Se houver erro, continua com cálculo normal
    }
}

// --- Helper Functions (Mantidas) ---
function formatCurrency($value) { return 'R$ ' . number_format($value ?? 0, 2, ',', '.'); }
function formatPercent($value) { return number_format(($value ?? 0) * 100, 2, ',', '.') . ' %'; }

// Modificado: getMonthLabels pode gerar mais meses do que 12 se o range de vencimentos for maior
function formatMonthYearLabel(DateTime $dateObj) {
    if (class_exists('IntlDateFormatter')) {
        return (new IntlDateFormatter('pt_BR', IntlDateFormatter::FULL, IntlDateFormatter::FULL, null, IntlDateFormatter::GREGORIAN, 'MMM/yy'))->format($dateObj);
    }
    $mesesPt = ['01'=>'jan','02'=>'fev','03'=>'mar','04'=>'abr','05'=>'mai','06'=>'jun','07'=>'jul','08'=>'ago','09'=>'set','10'=>'out','11'=>'nov','12'=>'dez'];
    return $mesesPt[$dateObj->format('m')] . './' . $dateObj->format('y');
}

function getMonthLabelsRange(DateTime $startDate, DateTime $endDate) {
    $labels = [];
    $currentDate = clone $startDate;
    $currentDate->setDate((int)$currentDate->format('Y'), (int)$currentDate->format('m'), 1); // Garante que começa no primeiro dia do mês da operação

    $interval = new DateInterval('P1M');
    // Adiciona 1 mês à data final para garantir que o último mês do período seja incluído
    $endDateForPeriod = clone $endDate;
    $endDateForPeriod->modify('+1 month');
    $period = new DatePeriod($currentDate, $interval, $endDateForPeriod);

    foreach ($period as $dt) {
        $labels[] = formatMonthYearLabel($dt);
    }
    return $labels;
}

// --- Função para ler o arquivo de configuração (COPIADA DE config.php) ---
function readConfig($filePath) {
    if (!file_exists($filePath)) {
        // Criar arquivo padrão se não existir
        $defaultConfig = [
            "default_taxa_mensal" => 5.00,
            "iof_adicional_rate" => 0.0038,
            "iof_diaria_rate" => 0.000082
        ];
        file_put_contents($filePath, json_encode($defaultConfig, JSON_PRETTY_PRINT));
        return $defaultConfig;
    }
    $configContent = file_get_contents($filePath);
    return json_decode($configContent, true);
}

// --- LER AS CONFIGURAÇÕES DO ARQUIVO config.json ---
$configFilePath = __DIR__ . '/config.json';
$appConfig = readConfig($configFilePath);

// DEFINIR AS TAXAS DE IOF COM BASE NAS CONFIGURAÇÕES
define('IOF_ADICIONAL_RATE', $appConfig['iof_adicional_rate'] ?? 0.0038);
define('IOF_DIARIA_RATE', $appConfig['iof_diaria_rate'] ?? 0.000082);


// --- Get Input Data (Mantida) ---
$input = json_decode(file_get_contents('php://input'), true);
if ($input === null && json_last_error() !== JSON_ERROR_NONE) { echo json_encode(['error' => 'Erro JSON: ' . json_last_error_msg()]); exit; }

$taxaMensal = isset($input['taxaMensal']) ? (float) $input['taxaMensal'] / 100 : 0;
$dataOperacaoStr = isset($input['data_base_calculo']) ? $input['data_base_calculo'] : (isset($input['data_operacao']) ? $input['data_operacao'] : null);
$dataOperacao = null;
$tipoPagamento = isset($input['tipo_pagamento']) ? $input['tipo_pagamento'] : 'direto';
$tipoOperacao = isset($input['tipoOperacao']) ? $input['tipoOperacao'] : 'antecipacao';
$valorEmprestimo = isset($input['valor_emprestimo']) ? (float)$input['valor_emprestimo'] : null;

$incorreIOF = isset($input['incorreIOF']) ? $input['incorreIOF'] === 'Sim' : false;
$cobrarIOF = isset($input['cobrarIOF']) ? $input['cobrarIOF'] === 'Sim' : false;

// Se for empréstimo, forçamos IOF para false
if ($tipoOperacao === 'emprestimo') {
    $incorreIOF = false;
    $cobrarIOF = false;
}

$notas = isset($input['notas']) ? trim($input['notas']) : '';
$titulos = isset($input['titulos']) && is_array($input['titulos']) ? $input['titulos'] : [];

// --- Dados de Compensação (NOVO) ---
$compensacaoData = isset($input['compensacao_data']) ? $input['compensacao_data'] : null;
$custoTotalCompensacao = 0; // Custo total da taxa de antecipação

// --- Initialization para nova operação (Mantida) ---
$totalOriginal = 0;
$totalPresente = 0;
$totalIOF = 0; // Acumula o IOF teórico total
$totalLiquidoPago = 0; // Acumula o que foi pago ao cliente
$totalLucroLiquido = 0; // Acumula o lucro líquido real da operação
$weightedDaysNumerator = 0;
$totalWeightForDays = 0;
$mediaPonderadaDiasNumerico = 0;
$error = null;

// Para o gráfico: estrutura auxiliar para acumular dados por mês
$fullChartData = []; // Key:YYYY-MM, Value: ['capital_emprestado' => 0, 'capital_retornado' => 0, 'lucro' => 0, 'displayLabel' => 'MMM/YY']
$firstChartMonth = null;
$lastChartMonth = null;

// *** INICIALIZA ARRAY PARA DETALHES ***
$calculatedTitlesDetails = [];
$titulosTemp = []; // Array temporário para armazenar dados dos títulos
// *** FIM INICIALIZAÇÃO ***

// --- Basic Validation (Mantida) ---
if ($taxaMensal <= 0) { $error = 'Taxa mensal deve ser maior que zero.'; }
elseif (empty($titulos)) { $error = 'Nenhum título foi fornecido.'; }
elseif (empty($dataOperacaoStr)) { $error = 'Data da operação não fornecida.'; }
else {
    try { $dataOperacao = new DateTime($dataOperacaoStr); $dataOperacao->setTime(0, 0, 0); }
    catch (Exception $e) { $error = 'Formato inválido para Data da Operação: ' . htmlspecialchars($dataOperacaoStr); }
}

// --- Process Each Title (Mantida, com correção da lógica de IOF) ---
if (!$error) {
    try {
        // Inicializa o mês da operação no fullChartData
        $monthKeyOperacao = $dataOperacao->format('Y-m');
        $displayLabelOperacao = formatMonthYearLabel($dataOperacao);
        $fullChartData[$monthKeyOperacao] = [
            'capital_emprestado' => 0, // Será preenchido com totalLiquidoPago ao final do loop
            'capital_retornado' => 0,
            'lucro' => 0,
            'displayLabel' => $displayLabelOperacao
        ];
        $firstChartMonth = clone $dataOperacao;
        $firstChartMonth->setDate($firstChartMonth->format('Y'), $firstChartMonth->format('m'), 1);

        foreach ($titulos as $titulo) {
            $valorOriginalTitulo = isset($titulo['valorOriginal']) ? (float) $titulo['valorOriginal'] : 0;
            $dataVencimentoStrTitulo = isset($titulo['dataVencimento']) ? $titulo['dataVencimento'] : null;
            if ($valorOriginalTitulo <= 0 || empty($dataVencimentoStrTitulo)) { $error = 'Dados inválidos em um título.'; break; }
            try { $dataVencimentoTitulo = new DateTime($dataVencimentoStrTitulo); $dataVencimentoTitulo->setTime(0, 0, 0); }
            catch (Exception $e) { $error = 'Data vencimento inválida: '.htmlspecialchars($dataVencimentoStrTitulo); break; }

            $dias = 0;
            if ($dataVencimentoTitulo < $dataOperacao) { $error = "Vencimento (" . $dataVencimentoTitulo->format('d/m/Y') . ") anterior à operação (" . $dataOperacao->format('d/m/Y') . ")."; break; }
            elseif ($dataVencimentoTitulo >= $dataOperacao) { $interval = $dataOperacao->diff($dataVencimentoTitulo); $dias = $interval->days; }

            // Cálculo Valor Presente
            $valorPresenteTitulo = $valorOriginalTitulo;
            if ($dias > 0 && (1 + $taxaMensal) > 1e-9) { $valorPresenteTitulo = $valorOriginalTitulo / pow(1 + $taxaMensal, $dias / 30.0); }
            elseif ($dias > 0) { $error = $error ?? 'Taxa inválida.'; $valorPresenteTitulo = 0; }

            // Acumula totais primeiro (sem IOF ainda)
            $totalOriginal += $valorOriginalTitulo;
            $totalPresente += $valorPresenteTitulo;
            $weightedDaysNumerator += $dias * $valorOriginalTitulo;
            $totalWeightForDays += $valorOriginalTitulo;

            // Armazena dados temporários para processamento posterior
            $titulosTemp[] = [
                'valorOriginal' => $valorOriginalTitulo,
                'valorPresente' => $valorPresenteTitulo,
                'dataVencimento' => $dataVencimentoTitulo,
                'dias' => $dias
            ];

            if ($error) break;

        } // end foreach

        // LÓGICA CORRIGIDA: Calcular IOF sobre o valor original de cada título
        if (!$error && !empty($titulosTemp)) {
            // Agora processa cada título individualmente
            foreach ($titulosTemp as $index => $tituloData) {
                $valorOriginalTitulo = $tituloData['valorOriginal'];
                $valorPresenteTitulo = $tituloData['valorPresente'];
                $dataVencimentoTitulo = $tituloData['dataVencimento'];
                $dias = $tituloData['dias'];
                
                // IOF calculado sobre o valor original do título (0,38%)
                $iofTitulo = $valorOriginalTitulo * 0.0038;
                
                // Acumula IOF total
                $totalIOF += $iofTitulo;
                
                // IOF que é descontado do valor que sua empresa PAGA ao cliente
                $iofDescontadoDoCliente = $cobrarIOF ? $iofTitulo : 0;
                
                // Custo real de IOF para sua empresa
                $custoRealIOFParaVoce = $incorreIOF ? $iofTitulo : 0;
                
                // Valor Líquido que sua empresa PAGA ao cliente por este título
                $valorLiquidoPagoTitulo = max(0, $valorPresenteTitulo - $iofDescontadoDoCliente);
                
                // Lucro Líquido Real para sua empresa
                $lucroLiquidoTitulo = $valorOriginalTitulo - $valorLiquidoPagoTitulo - $custoRealIOFParaVoce;
                
                // Acumula totais finais
                $totalLiquidoPago += $valorLiquidoPagoTitulo;
                $totalLucroLiquido += $lucroLiquidoTitulo;
                
                // Popula o array de detalhes
                $calculatedTitlesDetails[] = [
                    'dias' => $dias,
                    'valor_liquido_calc_dinamico' => $valorLiquidoPagoTitulo,
                    'lucro_liquido_calc_dinamico' => $lucroLiquidoTitulo
                ];
                
                // Acumula dados para o gráfico
                $monthKeyVencimento = $dataVencimentoTitulo->format('Y-m');
                $displayLabelVencimento = formatMonthYearLabel($dataVencimentoTitulo);
                
                if (!isset($fullChartData[$monthKeyVencimento])) {
                    $fullChartData[$monthKeyVencimento] = [
                        'capital_emprestado' => 0,
                        'capital_retornado' => 0,
                        'lucro' => 0,
                        'displayLabel' => $displayLabelVencimento
                    ];
                } else {
                    // Se o mês já existe, atualiza apenas o displayLabel se necessário
                    // (mantém o displayLabel mais descritivo)
                    if (empty($fullChartData[$monthKeyVencimento]['displayLabel'])) {
                        $fullChartData[$monthKeyVencimento]['displayLabel'] = $displayLabelVencimento;
                    }
                }
                $fullChartData[$monthKeyVencimento]['capital_retornado'] += $valorOriginalTitulo;
                $fullChartData[$monthKeyVencimento]['lucro'] += $lucroLiquidoTitulo;
                
                // Ajusta o último mês do gráfico
                if ($lastChartMonth === null || $dataVencimentoTitulo > $lastChartMonth) {
                    $lastChartMonth = clone $dataVencimentoTitulo;
                }
            }
        }

        // Define o Capital Emprestado TOTAL no mês da operação (APÓS calcular todos os títulos)
        if (isset($fullChartData[$monthKeyOperacao])) {
            $fullChartData[$monthKeyOperacao]['capital_emprestado'] = $totalLiquidoPago;
        }

        // Se for empréstimo, o valor líquido e presente devem cravar no valor emprestado
        if ($tipoOperacao === 'emprestimo' && $valorEmprestimo > 0) {
            $totalLiquidoPago = $valorEmprestimo;
            $totalPresente = $valorEmprestimo;
            $totalLucroLiquido = max(0, $totalOriginal - $valorEmprestimo);
            
            // Recalcula o capital emprestado no gráfico com o valor cravado
            if (isset($fullChartData[$monthKeyOperacao])) {
                $fullChartData[$monthKeyOperacao]['capital_emprestado'] = $valorEmprestimo;
            }
            // Recalcula o lucro para distribuição no gráfico (distribuindo proporcionalmente)
            if ($totalLucroLiquido > 0) {
                $lucroCalculadoTotal = 0;
                foreach ($fullChartData as $monthKey => $monthData) {
                    if ($monthData['lucro'] > 0) $lucroCalculadoTotal += $monthData['lucro'];
                }
                if ($lucroCalculadoTotal > 0) {
                    foreach ($fullChartData as $monthKey => &$monthData) {
                        if ($monthData['lucro'] > 0) {
                            $monthData['lucro'] = $totalLucroLiquido * ($monthData['lucro'] / $lucroCalculadoTotal);
                        }
                    }
                }
            }
        }

        // --- Calcular Custo da Compensação (CORRIGIDO) ---
        $custoTotalCompensacao = 0; // Inicializar sempre
        $valorPresenteTotalCompensacao = 0; // Valor presente total da compensação
        
        if ($compensacaoData && isset($compensacaoData['recebiveis']) && is_array($compensacaoData['recebiveis'])) {
            $taxaAntecipacao = isset($compensacaoData['taxa_antecipacao']) ? (float)$compensacaoData['taxa_antecipacao'] / 100 : 0;
            $custoTotalCompensacao = 0;
            
            // Calcular custo para cada recebível da compensação
            foreach ($compensacaoData['recebiveis'] as $recebivel) {
                $valorCompensacao = (float)$recebivel['valor'];
                $recebivelId = $recebivel['id'];
                
                // Buscar dados do recebível para obter os dias para vencimento
                try {
                    $stmt = $pdo->prepare("SELECT data_vencimento FROM recebiveis WHERE id = :id");
                    $stmt->bindParam(':id', $recebivelId, PDO::PARAM_INT);
                    $stmt->execute();
                    $recebivelData = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($recebivelData) {
                        $dataVencimentoRecebivel = new DateTime($recebivelData['data_vencimento']);
                        // CORREÇÃO: Usar data da operação em vez da data atual
                        $diasParaVencimento = max(0, $dataOperacao->diff($dataVencimentoRecebivel)->days);
                    } else {
                        $diasParaVencimento = 25; // Valor padrão se não encontrar
                    }
                } catch (Exception $e) {
                    $diasParaVencimento = 25; // Valor padrão em caso de erro
                }
                
                // Calcular o valor presente correto usando os dias reais
                if ($diasParaVencimento > 0 && $taxaAntecipacao > 0) {
                    $fatorDesconto = pow(1 + $taxaAntecipacao, $diasParaVencimento / 30.0);
                    $valorPresenteRecebivel = $valorCompensacao / $fatorDesconto;
                    $custoRecebivel = $valorCompensacao - $valorPresenteRecebivel;
                } else {
                    // Se não há dias ou taxa, o valor presente é igual ao valor nominal
                    $valorPresenteRecebivel = $valorCompensacao;
                    $custoRecebivel = 0;
                }
                
                $custoTotalCompensacao += $custoRecebivel;
                $valorPresenteTotalCompensacao += $valorPresenteRecebivel;
            }
            
            // VALIDAÇÃO: Verificar se o valor presente da compensação não excede o valor líquido da operação
            if ($valorPresenteTotalCompensacao > $totalLiquidoPago) {
                $error = sprintf(
                    'O valor presente da compensação (R$ %.2f) não pode exceder o valor líquido da operação (R$ %.2f).',
                    $valorPresenteTotalCompensacao,
                    $totalLiquidoPago
                );
            }
            
            // Deduzir o custo da compensação do lucro líquido total
            $totalLucroLiquido -= $custoTotalCompensacao;
            
            // Adicionar o valor presente da compensação como capital retornado no mês da operação
            // A compensação é uma entrada de caixa imediata que quita títulos em aberto
            if ($valorPresenteTotalCompensacao > 0) {
                if (isset($fullChartData[$monthKeyOperacao])) {
                    $fullChartData[$monthKeyOperacao]['capital_retornado'] += $valorPresenteTotalCompensacao;
                }
            }
            
            // Ajustar o lucro no gráfico (deduzir proporcionalmente por mês)
            if ($custoTotalCompensacao > 0 && count($fullChartData) > 0) {
                $totalLucroOriginal = $totalLucroLiquido + $custoTotalCompensacao;
                foreach ($fullChartData as $monthKey => &$monthData) {
                    if ($monthData['lucro'] > 0 && $totalLucroOriginal > 0) {
                        // Deduz proporcionalmente do lucro de cada mês
                        $proporcaoLucro = $monthData['lucro'] / $totalLucroOriginal;
                        $custoMes = $custoTotalCompensacao * $proporcaoLucro;
                        $monthData['lucro'] -= $custoMes;
                    }
                }
            }
        }

        if (!$error && $totalWeightForDays > 0) { $mediaPonderadaDiasNumerico = $weightedDaysNumerator / $totalWeightForDays; }

    } catch (Exception $e) { $error = 'Erro ao processar títulos: ' . $e->getMessage(); }
} // Fim if !$error inicial

// --- Calcula Resultados Finais (Mantida) ---
$mediaPonderadaDiasFormatado = '--'; $totalLucroPercentual = 0; $retornoMensalDecimal = 0;
if (!$error) {
    $mediaPonderadaDiasFormatado = round($mediaPonderadaDiasNumerico) . ' dias';
    $totalLucroPercentual = ($totalOriginal > 0) ? ($totalLucroLiquido / $totalOriginal) : 0;

    if ($tipoOperacao === 'emprestimo') {
        $retornoMensalDecimal = $taxaMensal; // Para empréstimo, o retorno é exatamente a taxa combinada
    } elseif ($totalLiquidoPago > 0 && $mediaPonderadaDiasNumerico > 0) {
        $taxaPeriodo = $totalLucroLiquido / $totalLiquidoPago;
        $base = 1 + $taxaPeriodo;
        $expoente = 30.0 / $mediaPonderadaDiasNumerico;
        if ($base >= 0) {
            $retornoMensalDecimal = pow($base, $expoente) - 1;
        } else {
            $retornoMensalDecimal = -1; // Indica que o retorno é negativo e não pode ser calculado como % a.m.
        }
    }
}

// --- Prepara os arrays finais para o Chart.js (Mantida, com ajuste para range) ---
$chartLabels = [];
$chartDataCapitalEmprestado = [];
$chartDataCapitalRetornado = [];
$chartDataLucro = [];

// Garante que o range de meses do gráfico inclua todos os meses desde a operação até o último vencimento
$finalLabelsPeriod = [];
if ($firstChartMonth && $lastChartMonth) {
    $currentLabelMonth = clone $firstChartMonth;
    while ($currentLabelMonth <= $lastChartMonth) {
        $monthKey = $currentLabelMonth->format('Y-m');
        if (!isset($fullChartData[$monthKey])) {
            // Se um mês no range não tem dados, inicializa com zeros
            $displayLabel = formatMonthYearLabel($currentLabelMonth);
            $fullChartData[$monthKey] = ['capital_emprestado' => 0, 'capital_retornado' => 0, 'lucro' => 0, 'displayLabel' => $displayLabel];
        }
        $currentLabelMonth->modify('+1 month');
    }
}
ksort($fullChartData); // Garante que os meses estejam em ordem cronológica

foreach ($fullChartData as $monthData) {
    $chartLabels[] = $monthData['displayLabel'];
    $chartDataCapitalEmprestado[] = $monthData['capital_emprestado'];
    $chartDataCapitalRetornado[] = $monthData['capital_retornado'];
    $chartDataLucro[] = $monthData['lucro'];
}


// --- Prepara Resposta JSON (Mantida) ---
$response = [];
if ($error) { $response['error'] = $error; }
else {
    $response = [
        'mediaPonderadaDias' => $mediaPonderadaDiasFormatado,
        'totalOriginal' => formatCurrency($totalOriginal),
        'totalPresente' => formatCurrency($totalPresente),
        'totalIOF' => formatCurrency($totalIOF),
        'totalLiquidoPago' => formatCurrency($totalLiquidoPago),
        'totalLucroLiquido' => formatCurrency($totalLucroLiquido),
        'totalLucroPercentual' => formatPercent($totalLucroPercentual),
        'retornoMensalFormatado' => ($retornoMensalDecimal == -1) ? 'N/A (Resultado Negativo)' : formatPercent($retornoMensalDecimal),
        'isProfit' => $totalLucroLiquido >= 0,
        'error' => null,
        // Dados para o gráfico conforme o novo design
        'chartLabels' => $chartLabels,
        'chartDataCapitalEmprestado' => $chartDataCapitalEmprestado,
        'chartDataCapitalRetornado' => $chartDataCapitalRetornado,
        'chartDataLucro' => $chartDataLucro,
        'calculatedTitlesDetails' => $calculatedTitlesDetails,
        // Dados de compensação (NOVO)
        'custoCompensacao' => formatCurrency($custoTotalCompensacao),
        'custoCompensacaoNumerico' => $custoTotalCompensacao,
        'totalLiquidoPagoNumerico' => $totalLiquidoPago
    ];
}

// --- Output JSON (Mantida) ---
echo json_encode($response);
exit;
?>
