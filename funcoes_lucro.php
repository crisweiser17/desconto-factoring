<?php
/**
 * Funções centralizadas para cálculo de lucro
 * Este arquivo centraliza todos os cálculos de lucro para garantir consistência
 */

require_once 'db_connection.php';
require_once 'funcoes_compensacao.php';

// Função para obter conexão PDO
function getConnection() {
    global $pdo;
    return $pdo;
}

/**
 * Calcula o lucro de uma operação de forma centralizada
 * 
 * @param array $recebiveis Array de recebíveis da operação
 * @param bool $incorreCustoIOF Se a operação incorre custo de IOF
 * @param bool $cobrarIOFCliente Se cobra IOF do cliente
 * @param float $taxaMensal Taxa mensal aplicada
 * @param DateTime $dataOperacao Data da operação
 * @return array Array com todos os cálculos de lucro
 */
function calcularLucroOperacao($recebiveis, $incorreCustoIOF, $cobrarIOFCliente, $taxaMensal, $dataOperacao) {
    $resultado = [
        'totalOriginal' => 0,
        'totalPresente' => 0,
        'totalIOF' => 0,
        'totalLiquidoPago' => 0,
        'totalLucroLiquido' => 0,
        'percentualLucro' => 0,
        'detalhesRecebiveis' => [],
        'custoCompensacao' => 0,
        'lucroAjustado' => 0
    ];
    
    foreach ($recebiveis as $recebivel) {
        $valorOriginal = floatval($recebivel['valor_original']);
        $dataVencimento = new DateTime($recebivel['data_vencimento']);
        
        // Calcular dias entre operação e vencimento
        $dias = $dataOperacao->diff($dataVencimento)->days;
        if ($dataVencimento < $dataOperacao) {
            $dias = -$dias; // Negativo se já vencido
        }
        
        // Calcular valor presente usando a taxa mensal
        $meses = $dias / 30;
        $fatorDesconto = pow(1 + $taxaMensal, -$meses);
        $valorPresente = $valorOriginal * $fatorDesconto;
        
        // IOF calculado sobre o valor original (0,38%)
        $iofTitulo = $valorOriginal * 0.0038;
        
        // IOF descontado do cliente (se flag ativa)
        $iofDescontadoCliente = $cobrarIOFCliente ? $iofTitulo : 0;
        
        // Custo real de IOF para a empresa (se flag ativa)
        $custoRealIOF = $incorreCustoIOF ? $iofTitulo : 0;
        
        // Valor líquido pago ao cliente
        $valorLiquidoPago = max(0, $valorPresente - $iofDescontadoCliente);
        
        // Lucro líquido do título
        $lucroLiquido = $valorOriginal - $valorLiquidoPago - $custoRealIOF;
        
        // Acumular totais
        $resultado['totalOriginal'] += $valorOriginal;
        $resultado['totalPresente'] += $valorPresente;
        $resultado['totalIOF'] += $iofTitulo;
        $resultado['totalLiquidoPago'] += $valorLiquidoPago;
        $resultado['totalLucroLiquido'] += $lucroLiquido;
        
        // Armazenar detalhes do recebível
        $resultado['detalhesRecebiveis'][] = [
            'id' => isset($recebivel['id']) ? $recebivel['id'] : null,
            'valorOriginal' => $valorOriginal,
            'valorPresente' => $valorPresente,
            'dias' => $dias,
            'iofTitulo' => $iofTitulo,
            'iofDescontadoCliente' => $iofDescontadoCliente,
            'custoRealIOF' => $custoRealIOF,
            'valorLiquidoPago' => $valorLiquidoPago,
            'lucroLiquido' => $lucroLiquido
        ];
    }
    
    // Calcular percentual de lucro
    if ($resultado['totalOriginal'] > 0) {
        $resultado['percentualLucro'] = ($resultado['totalLucroLiquido'] / $resultado['totalOriginal']) * 100;
    }
    
    // Calcular custo de compensação (implementação simplificada)
    $resultado['custoCompensacao'] = 0; // Por enquanto, será implementado posteriormente
    
    // Lucro ajustado (descontando compensação)
    $resultado['lucroAjustado'] = $resultado['totalLucroLiquido'] - $resultado['custoCompensacao'];
    
    return $resultado;
}

/**
 * Atualiza o lucro calculado no banco de dados
 * 
 * @param int $operacaoId ID da operação
 * @param float $lucroCalculado Lucro calculado
 * @return bool Sucesso da operação
 */
function atualizarLucroBanco($operacaoId, $lucroCalculado) {
    try {
        $pdo = getConnection();
        
        $sql = "UPDATE operacoes SET total_lucro_liquido_calc = :lucro WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':lucro', $lucroCalculado);
        $stmt->bindParam(':id', $operacaoId);
        
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Erro ao atualizar lucro no banco: " . $e->getMessage());
        return false;
    }
}

/**
 * Busca o lucro calculado do banco de dados
 * 
 * @param int $operacaoId ID da operação
 * @return float|null Lucro calculado ou null se não encontrado
 */
function buscarLucroBanco($operacaoId) {
    try {
        $pdo = getConnection();
        
        $sql = "SELECT total_lucro_liquido_calc FROM operacoes WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $operacaoId);
        $stmt->execute();
        
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado ? floatval($resultado['total_lucro_liquido_calc']) : null;
    } catch (PDOException $e) {
        error_log("Erro ao buscar lucro do banco: " . $e->getMessage());
        return null;
    }
}

/**
 * Recalcula e atualiza o lucro de uma operação existente
 * 
 * @param int $operacaoId ID da operação
 * @return array|null Resultado do cálculo ou null em caso de erro
 */
function recalcularLucroOperacao($operacaoId) {
    try {
        $pdo = getConnection();
        
        // Buscar dados da operação
        $sqlOperacao = "SELECT * FROM operacoes WHERE id = :id";
        $stmtOperacao = $pdo->prepare($sqlOperacao);
        $stmtOperacao->bindParam(':id', $operacaoId);
        $stmtOperacao->execute();
        $operacao = $stmtOperacao->fetch(PDO::FETCH_ASSOC);
        
        if (!$operacao) {
            return null;
        }
        
        // Buscar recebíveis da operação
        $sqlRecebiveis = "SELECT * FROM recebiveis WHERE operacao_id = :operacao_id";
        $stmtRecebiveis = $pdo->prepare($sqlRecebiveis);
        $stmtRecebiveis->bindParam(':operacao_id', $operacaoId);
        $stmtRecebiveis->execute();
        $recebiveis = $stmtRecebiveis->fetchAll(PDO::FETCH_ASSOC);
        
        // Calcular lucro
        $dataOperacao = new DateTime($operacao['data_operacao']);
        $resultado = calcularLucroOperacao(
            $recebiveis,
            (bool)$operacao['incorre_custo_iof'],
            (bool)$operacao['cobra_iof_cliente'],
            floatval($operacao['taxa_mensal']),
            $dataOperacao
        );
        
        // Atualizar no banco
        atualizarLucroBanco($operacaoId, $resultado['lucroAjustado']);
        
        return $resultado;
    } catch (Exception $e) {
        error_log("Erro ao recalcular lucro da operação: " . $e->getMessage());
        return null;
    }
}

/**
 * Calcula o custo da compensação para uma operação
 * 
 * @param int $operacaoId ID da operação
 * @return float Custo da compensação
 */
function calcularCustoCompensacaoOperacao($operacaoId) {
    try {
        $pdo = getConnection();
        
        $sql = "SELECT SUM(valor_compensado) as total_compensado, SUM(valor_presente_compensacao) as total_presente
                FROM compensacoes 
                WHERE operacao_principal_id = :operacao_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':operacao_id', $operacaoId);
        $stmt->execute();
        
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($resultado && $resultado['total_compensado'] > 0) {
            return floatval($resultado['total_compensado']) - floatval($resultado['total_presente']);
        }
        
        return 0;
    } catch (Exception $e) {
        error_log("Erro ao calcular custo da compensação: " . $e->getMessage());
        return 0;
    }
}

/**
 * Atualiza o lucro líquido no banco considerando o custo da compensação
 * 
 * @param int $operacaoId ID da operação
 * @return bool Sucesso da operação
 */
function atualizarLucroComCompensacao($operacaoId) {
    try {
        $pdo = getConnection();
        
        // Buscar lucro original calculado
        $lucroOriginal = buscarLucroBanco($operacaoId);
        if ($lucroOriginal === null) {
            return false;
        }
        
        // Calcular custo da compensação
        $custoCompensacao = calcularCustoCompensacaoOperacao($operacaoId);
        
        // Calcular lucro ajustado
        $lucroAjustado = $lucroOriginal - $custoCompensacao;
        
        // Atualizar no banco
        $sql = "UPDATE operacoes SET total_lucro_liquido_calc = :lucro WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':lucro', $lucroAjustado);
        $stmt->bindParam(':id', $operacaoId);
        
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Erro ao atualizar lucro com compensação: " . $e->getMessage());
        return false;
    }
}

/**
 * Formata valor monetário para exibição
 * 
 * @param float $valor Valor a ser formatado
 * @return string Valor formatado
 */
function formatarMoeda($valor) {
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

/**
 * Formata percentual para exibição
 * 
 * @param float $percentual Percentual a ser formatado
 * @return string Percentual formatado
 */
function formatarPercentual($percentual) {
    return number_format($percentual, 2, ',', '.') . '%';
}

/**
 * CENTRO DE VERDADE - FUNÇÕES CENTRALIZADAS
 * Estas funções garantem que todos os cálculos sejam feitos de forma consistente
 */

/**
 * Calcula lucro completo da operação incluindo compensação
 * 
 * @param array $recebiveis Array de recebíveis da operação
 * @param bool $incorreIOF Se incorre custo de IOF
 * @param bool $cobraIOF Se cobra IOF do cliente
 * @param float $taxaMensal Taxa mensal da operação
 * @param DateTime $dataOperacao Data da operação
 * @param int $operacaoId ID da operação (para buscar compensações)
 * @return array Resultado completo com compensação
 */
function calcularLucroOperacaoCompleto($recebiveis, $incorreIOF, $cobraIOF, $taxaMensal, $dataOperacao, $operacaoId = null) {
    // Cálculo base usando função existente
    $resultado = calcularLucroOperacao($recebiveis, $incorreIOF, $cobraIOF, $taxaMensal, $dataOperacao);
    
    // Adicionar cálculo de compensação se operacaoId fornecido
    if ($operacaoId) {
        $compensacao = calcularCompensacaoCompleta($operacaoId);
        $resultado['compensacao'] = $compensacao;
        
        // Aplicar compensação conforme lógica do registrar_operacao.php
        $resultado['totalLiquidoPagoOriginal'] = $resultado['totalLiquidoPago'];
        $resultado['totalLiquidoPago'] = $resultado['totalLiquidoPago'] - $compensacao['valorTotal'];
        $resultado['totalLucroLiquidoOriginal'] = $resultado['totalLucroLiquido'];
        $resultado['totalLucroLiquido'] = $resultado['totalLucroLiquido'] - $compensacao['custoTotal'];
    }
    
    return $resultado;
}

/**
 * Calcula compensação completa de uma operação
 * 
 * @param int $operacaoId ID da operação
 * @return array Dados completos da compensação
 */
function calcularCompensacaoCompleta($operacaoId) {
    try {
        $pdo = getConnection();
        
        $sql = "SELECT 
                    SUM(valor_compensado) as valorTotal,
                    SUM(valor_presente_compensacao) as valorPresenteTotal,
                    COUNT(*) as quantidadeRecebiveis
                FROM compensacoes 
                WHERE operacao_principal_id = :operacao_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':operacao_id', $operacaoId);
        $stmt->execute();
        
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($resultado && $resultado['valorTotal'] > 0) {
            $valorTotal = floatval($resultado['valorTotal']);
            $valorPresenteTotal = floatval($resultado['valorPresenteTotal']);
            $custoTotal = $valorTotal - $valorPresenteTotal;
            
            return [
                'valorTotal' => $valorTotal,
                'valorPresenteTotal' => $valorPresenteTotal,
                'custoTotal' => $custoTotal,
                'quantidadeRecebiveis' => intval($resultado['quantidadeRecebiveis']),
                'temCompensacao' => true
            ];
        }
        
        return [
            'valorTotal' => 0,
            'valorPresenteTotal' => 0,
            'custoTotal' => 0,
            'quantidadeRecebiveis' => 0,
            'temCompensacao' => false
        ];
        
    } catch (Exception $e) {
        error_log("Erro ao calcular compensação completa: " . $e->getMessage());
        return [
            'valorTotal' => 0,
            'valorPresenteTotal' => 0,
            'custoTotal' => 0,
            'quantidadeRecebiveis' => 0,
            'temCompensacao' => false
        ];
    }
}

/**
 * Obtém cálculos da operação (do banco ou recalcula)
 * CENTRO DE VERDADE - Esta é a função principal que deve ser usada por todas as páginas
 * 
 * @param int $operacaoId ID da operação
 * @param bool $forcarRecalculo Se deve forçar recálculo
 * @return array Dados calculados da operação
 */
function obterCalculosOperacao($operacaoId, $forcarRecalculo = false) {
    try {
        $pdo = getConnection();
        
        if (!$forcarRecalculo) {
            // Tentar buscar do banco primeiro
            $stmt = $pdo->prepare("SELECT * FROM operacoes WHERE id = ?");
            $stmt->execute([$operacaoId]);
            $operacao = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($operacao && $operacao['total_liquido_pago_calc'] !== null) {
                // Adicionar dados de compensação se existirem
                $compensacao = calcularCompensacaoCompleta($operacaoId);
                $operacao['compensacao'] = $compensacao;
                
                return $operacao; // Retorna dados do banco se existirem
            }
        }
        
        // Recalcular se necessário
        return recalcularEAtualizarOperacao($operacaoId);
        
    } catch (Exception $e) {
        error_log("Erro ao obter cálculos da operação: " . $e->getMessage());
        return null;
    }
}

/**
 * Recalcula e atualiza uma operação completa no banco
 * 
 * @param int $operacaoId ID da operação
 * @return array|null Dados recalculados ou null em caso de erro
 */
function recalcularEAtualizarOperacao($operacaoId) {
    try {
        $pdo = getConnection();
        
        // Buscar dados da operação
        $sqlOperacao = "SELECT * FROM operacoes WHERE id = :id";
        $stmtOperacao = $pdo->prepare($sqlOperacao);
        $stmtOperacao->bindParam(':id', $operacaoId);
        $stmtOperacao->execute();
        $operacao = $stmtOperacao->fetch(PDO::FETCH_ASSOC);
        
        if (!$operacao) {
            return null;
        }
        
        // Buscar recebíveis da operação
        $sqlRecebiveis = "SELECT * FROM recebiveis WHERE operacao_id = :operacao_id";
        $stmtRecebiveis = $pdo->prepare($sqlRecebiveis);
        $stmtRecebiveis->bindParam(':operacao_id', $operacaoId);
        $stmtRecebiveis->execute();
        $recebiveis = $stmtRecebiveis->fetchAll(PDO::FETCH_ASSOC);
        
        // Preparar parâmetros
        $dataOperacao = new DateTime($operacao['data_operacao']);
        $incorreIOF = isset($operacao['incorre_custo_iof']) ? (bool)$operacao['incorre_custo_iof'] : false;
        $cobraIOF = isset($operacao['cobra_iof_cliente']) ? (bool)$operacao['cobra_iof_cliente'] : false;
        $taxaMensal = isset($operacao['taxa_mensal']) ? (float)$operacao['taxa_mensal'] : 0.0;
        
        // Calcular usando função centralizada completa
        $resultado = calcularLucroOperacaoCompleto(
            $recebiveis,
            $incorreIOF,
            $cobraIOF,
            $taxaMensal,
            $dataOperacao,
            $operacaoId
        );
        
        // Atualizar no banco (sem calculado_em pois a coluna não existe)
        $sqlUpdate = "UPDATE operacoes SET 
                        total_original_calc = :total_original,
                        total_liquido_pago_calc = :total_liquido,
                        total_lucro_liquido_calc = :total_lucro
                      WHERE id = :id";
        $stmtUpdate = $pdo->prepare($sqlUpdate);
        $stmtUpdate->execute([
            ':total_original' => $resultado['totalOriginal'],
            ':total_liquido' => $resultado['totalLiquidoPago'],
            ':total_lucro' => $resultado['totalLucroLiquido'],
            ':id' => $operacaoId
        ]);
        
        // Retornar dados no formato do banco
        return [
            'id' => $operacaoId,
            'total_original_calc' => $resultado['totalOriginal'],
            'total_liquido_pago_calc' => $resultado['totalLiquidoPago'],
            'total_lucro_liquido_calc' => $resultado['totalLucroLiquido'],
            'compensacao' => $resultado['compensacao'] ?? ['temCompensacao' => false],
            'recebiveis' => $resultado['recebiveis'] ?? [],
            'calculado_em' => date('Y-m-d H:i:s') // Timestamp atual para controle
        ];
        
    } catch (Exception $e) {
        error_log("Erro ao recalcular e atualizar operação: " . $e->getMessage());
        return null;
    }
}

/**
 * Formata resultados para visualização web
 * 
 * @param array $dados Dados calculados
 * @return array Dados formatados para web
 */
function formatarResultadosParaVisualizacao($dados) {
    $formatados = [
        'totalOriginalFormatado' => formatarMoeda($dados['total_original_calc'] ?? 0),
        'totalLiquidoPagoFormatado' => formatarMoeda($dados['total_liquido_pago_calc'] ?? 0),
        'totalLucroLiquidoFormatado' => formatarMoeda($dados['total_lucro_liquido_calc'] ?? 0),
        'calculadoEm' => $dados['calculado_em'] ?? null
    ];
    
    // Adicionar dados de compensação se existirem
    if (isset($dados['compensacao']) && $dados['compensacao']['temCompensacao']) {
        $comp = $dados['compensacao'];
        $formatados['compensacao'] = [
            'valorTotalFormatado' => formatarMoeda($comp['valorTotal']),
            'custoTotalFormatado' => formatarMoeda($comp['custoTotal']),
            'quantidadeRecebiveis' => $comp['quantidadeRecebiveis']
        ];
    }
    
    return $formatados;
}

/**
 * Formata resultados para PDF
 * 
 * @param array $dados Dados calculados
 * @return array Dados formatados para PDF
 */
function formatarResultadosParaPDF($dados) {
    // Para PDF, usar a mesma formatação da web por enquanto
    // Pode ser customizada no futuro se necessário
    return formatarResultadosParaVisualizacao($dados);
}

/**
 * Verifica se uma operação precisa ser recalculada
 * 
 * @param int $operacaoId ID da operação
 * @return bool True se precisa recalcular
 */
function precisaRecalcular($operacaoId) {
    try {
        $pdo = getConnection();
        
        // Buscar hash atual dos recebíveis
        $stmt = $pdo->prepare("SELECT MD5(GROUP_CONCAT(CONCAT(id, valor_original, data_vencimento) ORDER BY id)) as hash_atual FROM recebiveis WHERE operacao_id = ?");
        $stmt->execute([$operacaoId]);
        $hashAtual = $stmt->fetchColumn();
        
        // Buscar dados da operação
        $stmt = $pdo->prepare("SELECT calculado_em, total_liquido_pago_calc FROM operacoes WHERE id = ?");
        $stmt->execute([$operacaoId]);
        $operacao = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Se nunca foi calculado, precisa calcular
        if (!$operacao || $operacao['total_liquido_pago_calc'] === null) {
            return true;
        }
        
        // Se foi calculado há mais de 1 hora, recalcular por segurança
        if ($operacao['calculado_em']) {
            $calculadoEm = new DateTime($operacao['calculado_em']);
            $agora = new DateTime();
            $diff = $agora->diff($calculadoEm);
            if ($diff->h >= 1 || $diff->days > 0) {
                return true;
            }
        }
        
        return false;
        
    } catch (Exception $e) {
        error_log("Erro ao verificar se precisa recalcular: " . $e->getMessage());
        return true; // Em caso de erro, recalcular por segurança
    }
}

/**
 * Obtém dados centralizados para relatórios
 * CENTRO DE VERDADE - Esta função deve ser usada por relatorio.php
 *
 * @param string $data_inicio Data de início (Y-m-d) ou null para todos
 * @param string $data_fim Data de fim (Y-m-d) ou null para todos
 * @return array Dados completos para relatório
 */
function obterDadosRelatorio($data_inicio = null, $data_fim = null) {
    try {
        $pdo = getConnection();
        
        // Construir filtros de data
        $whereClauses = [];
        $params = [];
        
        if ($data_inicio) {
            $whereClauses[] = "o.data_operacao >= :data_inicio";
            $params[':data_inicio'] = $data_inicio;
        }
        
        if ($data_fim) {
            $whereClauses[] = "o.data_operacao <= :data_fim";
            $params[':data_fim'] = $data_fim;
        }
        
        $whereSql = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";
        
        // 1. Indicadores principais das operações (CORRIGIDO - Capital Ativo sem compensações)
        $sql_operacoes = "SELECT
                            COUNT(o.id) as num_operacoes,
                            SUM(o.total_original_calc) as volume_nominal_total,
                            SUM(o.total_lucro_liquido_calc) as lucro_bruto_estimado
                          FROM operacoes o
                          $whereSql";
        
        $stmt = $pdo->prepare($sql_operacoes);
        $stmt->execute($params);
        $indicadores = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // 1.1. Capital Ativo calculado corretamente (soma dos valores líquidos dos recebíveis, sem compensações)
        if (empty($params)) {
            // SEM FILTRO: Buscar todos os recebíveis
            $sql_capital_ativo = "SELECT SUM(r.valor_liquido_calc) as total_adiantado
                                  FROM recebiveis r";
            $stmt_capital = $pdo->prepare($sql_capital_ativo);
            $stmt_capital->execute();
        } else {
            // COM FILTRO: Aplicar filtro de data da operação
            $sql_capital_ativo = "SELECT SUM(r.valor_liquido_calc) as total_adiantado
                                  FROM recebiveis r
                                  JOIN operacoes o ON r.operacao_id = o.id
                                  $whereSql";
            $stmt_capital = $pdo->prepare($sql_capital_ativo);
            $stmt_capital->execute($params);
        }
        $capital_ativo = $stmt_capital->fetch(PDO::FETCH_ASSOC);
        $indicadores['total_adiantado'] = (float)($capital_ativo['total_adiantado'] ?? 0);
        
        // 2. Status dos recebíveis (LÓGICA CORRIGIDA)
        // Se não há filtro de data, buscar TODOS os recebíveis
        // Se há filtro, aplicar filtro apenas nas operações
        if (empty($params)) {
            // SEM FILTRO: Buscar todos os recebíveis diretamente
            $sql_recebiveis = "SELECT
                                 r.status,
                                 SUM(r.valor_original) as total_valor_status,
                                 COUNT(r.id) as count_recebiveis
                               FROM recebiveis r
                               GROUP BY r.status";
            $stmt = $pdo->prepare($sql_recebiveis);
            $stmt->execute();
        } else {
            // COM FILTRO: Aplicar filtro de data da operação
            $sql_recebiveis = "SELECT
                                 r.status,
                                 SUM(r.valor_original) as total_valor_status,
                                 COUNT(r.id) as count_recebiveis
                               FROM recebiveis r
                               JOIN operacoes o ON r.operacao_id = o.id
                               $whereSql
                               GROUP BY r.status";
            $stmt = $pdo->prepare($sql_recebiveis);
            $stmt->execute($params);
        }
        $recebiveis_dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Converter para array associativo por status
        $recebiveis_por_status = [];
        $count_por_status = [];
        foreach ($recebiveis_dados as $row) {
            $recebiveis_por_status[$row['status']] = (float)$row['total_valor_status'];
            $count_por_status[$row['status']] = (int)$row['count_recebiveis'];
        }
        
        // 3. Status das operações (USAR LÓGICA PADRONIZADA)
        $sql_status_operacoes = "SELECT status_operacao, COUNT(*) as num_operacoes_status
                                FROM (
                                    SELECT o.id,
                                        CASE
                                            WHEN SUM(CASE WHEN r.status = 'Problema' THEN 1 ELSE 0 END) > 0 THEN 'Com Problema'
                                            WHEN SUM(CASE WHEN r.status = 'Em Aberto' THEN 1 ELSE 0 END) > 0 THEN 'Em Aberto'
                                            WHEN SUM(CASE WHEN r.status = 'Parcialmente Compensado' THEN 1 ELSE 0 END) > 0 THEN 'Parcialmente Compensada'
                                            WHEN SUM(CASE WHEN r.status IN ('Recebido', 'Compensado') THEN 1 ELSE 0 END) = COUNT(r.id) AND COUNT(r.id) > 0 THEN 'Concluída'
                                            ELSE 'Em Aberto'
                                        END AS status_operacao
                                    FROM operacoes o
                                    LEFT JOIN recebiveis r ON o.id = r.operacao_id
                                    $whereSql
                                    GROUP BY o.id
                                ) AS operacoes_com_status
                                GROUP BY status_operacao";
        
        $stmt = $pdo->prepare($sql_status_operacoes);
        $stmt->execute($params);
        $status_operacoes = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        // 4. Lucro realizado (CORRIGIDO - usar apenas recebidos)
        $sql_lucro_realizado = "SELECT SUM(o.total_lucro_liquido_calc *
                                          (SELECT COUNT(*) FROM recebiveis r2 WHERE r2.operacao_id = o.id AND r2.status = 'Recebido') /
                                          (SELECT COUNT(*) FROM recebiveis r3 WHERE r3.operacao_id = o.id)
                                      ) as lucro_liquido_realizado
                                FROM operacoes o
                                $whereSql
                                HAVING COUNT(o.id) > 0";
        
        $stmt = $pdo->prepare($sql_lucro_realizado);
        $stmt->execute($params);
        $lucro_realizado = $stmt->fetchColumn() ?: 0;
        
        // 5. Aging (próximos 30 dias e vencidos)
        $sql_aging = "SELECT
                        SUM(CASE WHEN r.data_vencimento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                                 AND r.status = 'Em Aberto' THEN r.valor_original ELSE 0 END) as proximos_30_dias,
                        SUM(CASE WHEN r.data_vencimento < CURDATE() AND r.status = 'Em Aberto' THEN r.valor_original ELSE 0 END) as vencidos
                      FROM recebiveis r
                      JOIN operacoes o ON r.operacao_id = o.id
                      $whereSql";
        
        $stmt = $pdo->prepare($sql_aging);
        $stmt->execute($params);
        $aging = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // 6. Prazo médio (USAR DADOS CALCULADOS)
        $sql_prazo_medio = "SELECT
                              CASE
                                  WHEN SUM(r.valor_original) > 0 THEN
                                      SUM(DATEDIFF(r.data_vencimento, o.data_operacao) * r.valor_original) / SUM(r.valor_original)
                                  ELSE
                                      AVG(DATEDIFF(r.data_vencimento, o.data_operacao))
                              END as prazo_medio_dias
                            FROM operacoes o
                            JOIN recebiveis r ON o.id = r.operacao_id
                            $whereSql";
        
        $stmt = $pdo->prepare($sql_prazo_medio);
        $stmt->execute($params);
        $prazo_medio = $stmt->fetchColumn() ?: 0;
        
        // Montar resultado final
        $resultado = [
            // Indicadores principais
            'num_operacoes' => (int)($indicadores['num_operacoes'] ?? 0),
            'volume_nominal_total' => (float)($indicadores['volume_nominal_total'] ?? 0),
            'total_adiantado' => (float)($indicadores['total_adiantado'] ?? 0),
            'lucro_bruto_estimado' => (float)($indicadores['lucro_bruto_estimado'] ?? 0),
            'lucro_liquido_realizado' => (float)$lucro_realizado,
            
            // Recebíveis por status
            'valor_total_recebido' => $recebiveis_por_status['Recebido'] ?? 0,
            'valor_total_em_aberto' => $recebiveis_por_status['Em Aberto'] ?? 0,
            'valor_total_em_problema' => $recebiveis_por_status['Problema'] ?? 0,
            'valor_total_parcialmente_compensado' => $recebiveis_por_status['Parcialmente Compensado'] ?? 0,
            
            // Contagem de recebíveis
            'count_recebiveis_recebidos' => $count_por_status['Recebido'] ?? 0,
            'count_recebiveis_em_aberto' => $count_por_status['Em Aberto'] ?? 0,
            'count_recebiveis_problema' => $count_por_status['Problema'] ?? 0,
            'count_recebiveis_parcialmente_compensado' => $count_por_status['Parcialmente Compensado'] ?? 0,
            
            // Status das operações
            'operacoes_concluidas' => (int)($status_operacoes['Concluída'] ?? 0),
            'operacoes_em_andamento' => (int)($status_operacoes['Em Aberto'] ?? 0),
            'operacoes_com_problema' => (int)($status_operacoes['Com Problema'] ?? 0),
            'operacoes_parcialmente_compensadas' => (int)($status_operacoes['Parcialmente Compensada'] ?? 0),
            
            // Aging
            'valor_proximos_30_dias' => (float)($aging['proximos_30_dias'] ?? 0),
            'valor_vencidos' => (float)($aging['vencidos'] ?? 0),
            
            // Outros indicadores
            'prazo_medio_dias' => (float)$prazo_medio,
        ];
        
        // Calcular valor médio dos títulos
        $total_titulos = $resultado['count_recebiveis_recebidos'] +
                        $resultado['count_recebiveis_em_aberto'] +
                        $resultado['count_recebiveis_problema'] +
                        $resultado['count_recebiveis_parcialmente_compensado'];
        
        if ($total_titulos > 0) {
            $resultado['valor_medio_titulos'] = $resultado['volume_nominal_total'] / $total_titulos;
        } else {
            $resultado['valor_medio_titulos'] = 0;
        }
        
        // Calcular indicadores derivados
        if ($resultado['total_adiantado'] > 0) {
            $resultado['rentabilidade_media_estimada'] = ($resultado['lucro_bruto_estimado'] / $resultado['total_adiantado']) * 100;
        } else {
            $resultado['rentabilidade_media_estimada'] = 0;
        }
        
        if ($resultado['volume_nominal_total'] > 0) {
            $resultado['indice_problemas'] = ($resultado['valor_total_em_problema'] / $resultado['volume_nominal_total']) * 100;
        } else {
            $resultado['indice_problemas'] = 0;
        }
        
        if ($resultado['num_operacoes'] > 0) {
            $resultado['taxa_sucesso'] = ($resultado['operacoes_concluidas'] / $resultado['num_operacoes']) * 100;
        } else {
            $resultado['taxa_sucesso'] = 0;
        }
        
        if ($resultado['prazo_medio_dias'] > 0) {
            $resultado['rentabilidade_mensal'] = ($resultado['rentabilidade_media_estimada'] / $resultado['prazo_medio_dias']) * 30;
        } else {
            $resultado['rentabilidade_mensal'] = 0;
        }
        
        return $resultado;
        
    } catch (Exception $e) {
        error_log("Erro ao obter dados do relatório: " . $e->getMessage());
        return null;
    }
}

?>