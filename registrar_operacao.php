<?php
// registrar_operacao.php
header('Content-Type: application/json');

// Inicia a sessão se ainda não foi iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica se o usuário está logado, mas retorna JSON em vez de redirecionar
if (false) {
    echo json_encode(['success' => false, 'error' => 'Usuário não autenticado. Faça login novamente.']);
    exit;
}

// Inclui a conexão com o banco
try {
    require_once 'db_connection.php';
require_once 'funcoes_compensacao.php';
require_once 'funcoes_lucro.php'; // Garante que $pdo está disponível
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Erro ao carregar dependências.']);
    exit;
}

// Constantes (mantidas para cálculo interno)
define('IOF_ADICIONAL_RATE', 0.0038);
define('IOF_DIARIA_RATE', 0.000082);

// Recebe os dados enviados via POST (JSON)
try {
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, TRUE); // Decodifica como array associativo
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Erro ao processar dados JSON.']);
    exit;
}

// Validação inicial dos dados recebidos
if (!$input || !isset($input['taxaMensal']) || !isset($input['titulos']) || !is_array($input['titulos']) || (!array_key_exists('cedente_id', $input) && !array_key_exists('tomador_id', $input))) {
    echo json_encode(['success' => false, 'error' => 'Dados inválidos recebidos (estrutura base ou sacado ausente).']);
    exit;
}

// --- Leitura e Validação dos Inputs ---
$cedente_id = isset($input['cedente_id']) ? filter_var($input['cedente_id'], FILTER_VALIDATE_INT) : null;
$taxaMensal = isset($input['taxaMensal']) ? (float) $input['taxaMensal'] / 100 : null; // Taxa como decimal
$dataOperacaoStr = isset($input['data_operacao']) ? trim($input['data_operacao']) : null; // Lê a string da data E REMOVE ESPAÇOS
$dataOperacao = null; // Objeto DateTime para validação e cálculo
$dataOperacaoFormatted = null; // String formatada para o BD
$tipoOperacao = isset($input['tipoOperacao']) ? trim($input['tipoOperacao']) : 'antecipacao';
if (!in_array($tipoOperacao, ['antecipacao', 'emprestimo'])) {
    $tipoOperacao = 'antecipacao';
}
$valorEmprestimo = isset($input['valor_emprestimo']) ? (float)$input['valor_emprestimo'] : null;
$tipoPagamento = isset($input['tipo_pagamento']) ? trim($input['tipo_pagamento']) : 'direto';
$temGarantia = isset($input['tem_garantia']) ? (int)$input['tem_garantia'] : 0;
$descricaoGarantia = isset($input['descricao_garantia']) ? trim($input['descricao_garantia']) : null;
$incorreIOF = isset($input['incorreIOF']) ? $input['incorreIOF'] === 'Sim' : false;
$cobrarIOF = isset($input['cobrarIOF']) ? $input['cobrarIOF'] === 'Sim' : false;
$notas = isset($input['notas']) ? trim($input['notas']) : '';
$titulos = $input['titulos'];

// Dados de compensação (encontro de contas)
$compensacaoData = null;
if (isset($input['compensacao_data'])) {
    if (is_string($input['compensacao_data'])) {
        $compensacaoData = json_decode($input['compensacao_data'], true);
    } elseif (is_array($input['compensacao_data'])) {
        $compensacaoData = $input['compensacao_data'];
    }
}
$valorTotalCompensacao = 0;
$recebiveisCompensados = [];

// Validações adicionais
if ($tipoOperacao !== 'emprestimo' && ($cedente_id === null || $cedente_id === false || $cedente_id <= 0)) {
    echo json_encode(['success' => false, 'error' => 'ID do Cedente inválido ou ausente.']);
    exit;
}

// Se for empréstimo, cedente_id é nulo no banco. Usaremos null no bindParam.
if ($tipoOperacao === 'emprestimo') {
    $cedente_id = null;
}

if ($taxaMensal === null || $taxaMensal <= 0) {
    echo json_encode(['success' => false, 'error' => 'Taxa mensal inválida.']);
    exit;
}

// ==============================================================
// ***** CORREÇÃO NA VALIDAÇÃO DA DATA *****
// ==============================================================
if (empty($dataOperacaoStr)) {
    echo json_encode(['success' => false, 'error' => 'Data da operação não fornecida.']);
    exit;
} else {
    // --- SUBSTITUA 'Y-m-d' ABAIXO PELO FORMATO REAL DA SUA STRING ---
    // Exemplos: 'd/m/Y' se for '05/04/2025'
    //           'Y-m-d' se for '2025-04-05'
    $formatoEsperado = 'Y-m-d'; // <--- !!! AJUSTE AQUI !!!

    $dataOperacao = DateTime::createFromFormat($formatoEsperado, $dataOperacaoStr);

    // Verifica se o parse deu certo
    if ($dataOperacao === false) {
        $errors = DateTime::getLastErrors();
        echo json_encode(['success' => false, 'error' => 'Formato inválido para Data da Operação: ' . htmlspecialchars($dataOperacaoStr) . ". Esperava o formato: $formatoEsperado"]);
        exit;
    }

    // Se deu certo, normaliza a hora e cria a string formatada para o SQL
    $dataOperacao->setTime(0, 0, 0);
    // Formato padrão para colunas DATE ou DATETIME no SQL
    $dataOperacaoFormatted = $dataOperacao->format('Y-m-d H:i:s');
    // Se sua coluna for só DATE, use: $dataOperacaoFormatted = $dataOperacao->format('Y-m-d');
}
// ==============================================================
// ***** FIM DA CORREÇÃO DA DATA *****
// ==============================================================


if (empty($titulos)) {
    echo json_encode(['success' => false, 'error' => 'Nenhum título válido fornecido para registrar.']);
    exit;
}


// Usar função centralizada para calcular lucro
$recebiveisParaSalvar = [];
$error_recalc = '';

// --- Recálculo dos Valores usando função centralizada ---
try {
    // Preparar dados dos recebíveis para a função centralizada
    $recebiveisParaCalculo = [];
    foreach ($titulos as $index => $titulo) {
        $valorOriginalTitulo = isset($titulo['valorOriginal']) ? (float) $titulo['valorOriginal'] : 0;
        $dataVencimentoStrTitulo = isset($titulo['dataVencimento']) ? trim($titulo['dataVencimento']) : null;
        $sacadoIdTitulo = isset($titulo['sacadoId']) && !empty($titulo['sacadoId']) ? (int) $titulo['sacadoId'] : null;

        if ($valorOriginalTitulo <= 0 || empty($dataVencimentoStrTitulo)) {
            $error_recalc = "Dados inválidos no título #".($index+1).". (Valor ou Vencimento)"; break;
        }

        // Parse da data de vencimento
        $formatoVencimento = 'Y-m-d';
        $dataVencimentoTitulo = DateTime::createFromFormat($formatoVencimento, $dataVencimentoStrTitulo);

        if ($dataVencimentoTitulo === false) {
            $error_recalc = "Data venc inválida no título #".($index+1). ". Formato esperado: $formatoVencimento, Recebido: " . htmlspecialchars($dataVencimentoStrTitulo); break;
        }
        $dataVencimentoTitulo->setTime(0,0,0);

        if ($dataVencimentoTitulo < $dataOperacao) {
            $error_recalc = "Vencimento (". $dataVencimentoTitulo->format('Y-m-d') .") anterior à operação (". $dataOperacao->format('Y-m-d') .") no título #".($index+1); break;
        }

        // Capturar tipo de recebível
        $tipoRecebivel = isset($titulo['tipo']) ? trim($titulo['tipo']) : 'fatura';
        if (!in_array($tipoRecebivel, ['duplicata', 'cheque', 'nota_promissoria', 'boleto', 'fatura', 'nota_fiscal', 'parcela_emprestimo', 'outros'])) {
            $tipoRecebivel = 'fatura'; // Default
        }

        $recebiveisParaCalculo[] = [
            'id' => $index + 1, // ID temporário
            'valor_original' => $valorOriginalTitulo,
            'data_vencimento' => $dataVencimentoTitulo->format('Y-m-d'),
            'tipo_recebivel' => $tipoRecebivel,
            'sacado_id' => $sacadoIdTitulo
        ];
    }
    
    if (!$error_recalc) {
        // Usar função centralizada para calcular lucro
        $resultadoCalculo = calcularLucroOperacao(
            $recebiveisParaCalculo,
            $incorreIOF,
            $cobrarIOF,
            $taxaMensal,
            $dataOperacao
        );
        
        // Extrair resultados
        $totalOriginal_recalc = $resultadoCalculo['totalOriginal'];
        $totalIOF_recalc = $resultadoCalculo['totalIOF'];
        $totalLiquidoPago_recalc = $resultadoCalculo['totalLiquidoPago'];
        $totalLucroLiquido_recalc = $resultadoCalculo['lucroAjustado']; // Usar lucro ajustado
        // calcularLucroOperacao não retorna 'totalPresente'; somamos os valores presentes dos detalhes
        $totalPresente_recalc = array_sum(array_column($resultadoCalculo['detalhesRecebiveis'] ?? [], 'valorPresente'));
        
        // Se for empréstimo e valorEmprestimo for válido, reescrever
        if ($tipoOperacao === 'emprestimo' && $valorEmprestimo > 0) {
            $totalLiquidoPago_recalc = $valorEmprestimo;
            $totalPresente_recalc = $valorEmprestimo;
            $totalLucroLiquido_recalc = max(0, $totalOriginal_recalc - $valorEmprestimo);
        }
        
        // Preparar dados para salvar no banco
        foreach ($resultadoCalculo['detalhesRecebiveis'] as $index => $detalhe) {
            $recebiveisParaSalvar[] = [
                'valor_original' => $detalhe['valorOriginal'],
                'data_vencimento' => $recebiveisParaCalculo[$index]['data_vencimento'],
                'valor_presente_calc' => $detalhe['valorPresente'],
                'iof_calc' => $detalhe['iofTitulo'],
                'valor_liquido_calc' => $detalhe['valorLiquidoPago'],
                'dias_calc' => $detalhe['dias'],
                'sacado_id' => $recebiveisParaCalculo[$index]['sacado_id'],
                'tipo_recebivel' => $recebiveisParaCalculo[$index]['tipo_recebivel']
            ];
        }
        
        // Calcular média ponderada de dias
        $weightedDaysNumerator = 0;
        $totalWeightForDays = 0;
        foreach ($resultadoCalculo['detalhesRecebiveis'] as $detalhe) {
            $weightedDaysNumerator += $detalhe['dias'] * $detalhe['valorOriginal'];
            $totalWeightForDays += $detalhe['valorOriginal'];
        }
        $mediaDiasPondCalc = ($totalWeightForDays > 0) ? round($weightedDaysNumerator / $totalWeightForDays) : 0;
    }
} catch (Exception $e) { 
    $error_recalc = 'Erro durante recálculo: ' . $e->getMessage(); 
}


if ($error_recalc) { echo json_encode(['success' => false, 'error' => $error_recalc]); exit; }

// Processar compensação se existir
if ($compensacaoData && isset($compensacaoData['recebiveis']) && is_array($compensacaoData['recebiveis'])) {
    $valorTotalCompensacao = (float)$compensacaoData['valor_total'];
    $recebiveisCompensados = $compensacaoData['recebiveis'];
    
    // Validar cada recebível individualmente considerando compensações parciais
    $valorTotalValidado = 0;
    foreach ($recebiveisCompensados as $comp) {
        $recebivel_id = $comp['id'];
        $valor_a_compensar = (float)$comp['valor'];
        
        // Verificar status atual do recebível
        $status = verificarStatusRecebivel($recebivel_id, $pdo);
        if (isset($status['erro'])) {
            echo json_encode(['success' => false, 'error' => "Erro ao verificar recebível {$recebivel_id}: " . $status['erro']]);
            exit;
        }
        
        if (!$status['disponivel_para_compensacao']) {
            echo json_encode(['success' => false, 'error' => "Recebível {$recebivel_id} não está disponível para compensação."]);
            exit;
        }
        
        if ($valor_a_compensar > $status['saldo_disponivel']) {
            echo json_encode(['success' => false, 'error' => "Valor a compensar (R$ {$valor_a_compensar}) excede saldo disponível (R$ {$status['saldo_disponivel']}) do recebível {$recebivel_id}."]);
            exit;
        }
        
        $valorTotalValidado += $valor_a_compensar;
    }
    
    // Validar se o valor total da compensação não excede o valor líquido da operação
    if ($valorTotalValidado > $totalLiquidoPago_recalc) {
        echo json_encode(['success' => false, 'error' => 'O valor total da compensação excede o valor líquido da operação.']);
        exit;
    }
    
    // Ajustar o valor líquido pago com a compensação
    $totalLiquidoPago_recalc -= $valorTotalValidado;
    $valorTotalCompensacao = $valorTotalValidado;
}

// --- Inserção no Banco de Dados ---
// --- Inserção no Banco de Dados ---
// --- Inserção no Banco de Dados ---
try { // <<< O TRY COMEÇA AQUI e engloba TUDO até o commit
    $pdo->beginTransaction();

    // Prepara INSERT para 'operacoes'
    $sqlOperacao = "INSERT INTO operacoes (
                        cedente_id, taxa_mensal, data_operacao, tipo_pagamento, tipo_operacao, notas,
                        incorre_custo_iof, cobrar_iof_cliente,
                        total_original_calc, total_presente_calc, iof_total_calc,
                        total_liquido_pago_calc, total_lucro_liquido_calc, media_dias_pond_calc,
                        valor_total_compensacao, tem_garantia, descricao_garantia
                    ) VALUES (
                        :cedente_id, :taxa, :data_operacao, :tipo_pagamento, :tipo_operacao, :notas,
                        :incorre_custo_iof, :cobrar_iof_cliente,
                        :total_original, :total_presente, :total_iof,
                        :total_liquido, :total_lucro, :media_dias,
                        :valor_compensacao, :tem_garantia, :descricao_garantia
                    )";
    $stmtOperacao = $pdo->prepare($sqlOperacao);

    // Bind dos parâmetros (Todo o bind está correto)
    if ($cedente_id === null) {
        $stmtOperacao->bindValue(':cedente_id', null, PDO::PARAM_NULL);
    } else {
        $stmtOperacao->bindParam(':cedente_id', $cedente_id, PDO::PARAM_INT);
    }
    $stmtOperacao->bindParam(':tem_garantia', $temGarantia, PDO::PARAM_INT);
    if ($descricaoGarantia === null) {
        $stmtOperacao->bindValue(':descricao_garantia', null, PDO::PARAM_NULL);
    } else {
        $stmtOperacao->bindParam(':descricao_garantia', $descricaoGarantia);
    }
    $stmtOperacao->bindParam(':taxa', $taxaMensal);
    $stmtOperacao->bindParam(':data_operacao', $dataOperacaoFormatted, PDO::PARAM_STR);
    $stmtOperacao->bindParam(':tipo_pagamento', $tipoPagamento, PDO::PARAM_STR);
    $stmtOperacao->bindParam(':tipo_operacao', $tipoOperacao, PDO::PARAM_STR);
    $stmtOperacao->bindParam(':notas', $notas, PDO::PARAM_STR); // Bind para :notas -> coluna notas
    $stmtOperacao->bindParam(':incorre_custo_iof', $incorreIOF, PDO::PARAM_BOOL);
    $stmtOperacao->bindParam(':cobrar_iof_cliente', $cobrarIOF, PDO::PARAM_BOOL);
    $stmtOperacao->bindParam(':total_original', $totalOriginal_recalc);
    $stmtOperacao->bindParam(':total_presente', $totalPresente_recalc);
    $stmtOperacao->bindParam(':total_iof', $totalIOF_recalc);
    $stmtOperacao->bindParam(':total_liquido', $totalLiquidoPago_recalc);
    $stmtOperacao->bindParam(':total_lucro', $totalLucroLiquido_recalc);
    $stmtOperacao->bindParam(':media_dias', $mediaDiasPondCalc, PDO::PARAM_INT);
    $stmtOperacao->bindParam(':valor_compensacao', $valorTotalCompensacao);

    // EXECUTE A INSERÇÃO DA OPERAÇÃO (DENTRO DO TRY)
    $stmtOperacao->execute(); // <<< SÓ PRECISA DE UM EXECUTE AQUI

    // OBTENHA O ID DA OPERAÇÃO RECÉM-CRIADA (DENTRO DO TRY)
    $operacaoId = $pdo->lastInsertId();

    // Prepara INSERT para 'recebiveis' (DENTRO DO TRY)
    $sqlRecebivel = "INSERT INTO recebiveis (
                        operacao_id, valor_original, data_vencimento, sacado_id,
                        valor_presente_calc, iof_calc, valor_liquido_calc, dias_prazo_calc, status, tipo_recebivel
                    ) VALUES (
                        :op_id, :val_orig, :dt_venc, :sacado_id,
                        :val_pres, :iof, :val_liq, :dias, :status, :tipo_recebivel
                    )";
    $stmtRecebivel = $pdo->prepare($sqlRecebivel);
    $statusInicial = 'Em Aberto';

    // Insere cada recebível (DENTRO DO TRY)
    foreach ($recebiveisParaSalvar as $recebivel) {
        $stmtRecebivel->execute([ // Execute para cada recebível
            ':op_id' => $operacaoId,
            ':val_orig' => $recebivel['valor_original'],
            ':dt_venc' => $recebivel['data_vencimento'],
            ':sacado_id' => $recebivel['sacado_id'],
            ':val_pres' => $recebivel['valor_presente_calc'],
            ':iof' => $recebivel['iof_calc'],
            ':val_liq' => $recebivel['valor_liquido_calc'],
            ':dias' => $recebivel['dias_calc'],
            ':status' => $statusInicial,
            ':tipo_recebivel' => $recebivel['tipo_recebivel']
        ]);
    }
    
    // Processar compensação se existir usando as novas funções
    if (!empty($recebiveisCompensados)) {
        $taxaAntecipacao = isset($compensacaoData['taxa_antecipacao']) ? $compensacaoData['taxa_antecipacao'] : 0;
        
        // Usar a função de processamento de compensação
        $resultado = processarCompensacao($operacaoId, $recebiveisCompensados, $taxaAntecipacao, $pdo);
        
        if (isset($resultado['erro'])) {
            throw new Exception($resultado['erro']);
        }
        

    }

    // SE TUDO DEU CERTO ATÉ AQUI, CONFIRMA A TRANSAÇÃO (DENTRO DO TRY)
    $pdo->commit();

    // ENVIA A RESPOSTA DE SUCESSO (DENTRO DO TRY, APÓS COMMIT)
    echo json_encode(['success' => true, 'operacao_id' => $operacaoId]);

// <<< O TRY TERMINA AQUI, ANTES DOS CATCHES >>>
} catch (PDOException $e) { // <<< CATCH PARA ERROS DE BANCO (PDO)
    // SE DEU ERRO NO BANCO, DESFAZ A TRANSAÇÃO
    if ($pdo->inTransaction()) { // Verifica se a transação foi iniciada antes de tentar reverter
         $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'error' => 'Erro no banco de dados ao registrar. (' . $e->getMessage() . ')']);

} catch (Exception $e) { // <<< CATCH PARA OUTROS ERROS GERAIS
     // SE DEU UM ERRO GERAL, TENTA DESFAZER A TRANSAÇÃO SE ELA ESTIVER ATIVA
     if ($pdo->inTransaction()) {
          $pdo->rollBack();
     }
     echo json_encode(['success' => false, 'error' => 'Erro inesperado no registro. (' . $e->getMessage() . ')']);
}
// <<< FIM DOS BLOCOS CATCH >>>

exit; // O exit fica fora do try/catch
?>
