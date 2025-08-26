<?php
// Funções para gerenciar compensações e status de recebíveis

require_once 'db_connection.php';

/**
 * Verifica o status real de um recebível considerando compensações
 * @param int $recebivel_id ID do recebível
 * @param PDO $pdo Conexão com banco de dados
 * @return array Status detalhado do recebível
 */
function verificarStatusRecebivel($recebivel_id, $pdo) {
    try {
        // Buscar dados básicos do recebível
        $sql = "SELECT r.*, o.cedente_id, o.tipo_pagamento 
                FROM recebiveis r 
                LEFT JOIN operacoes o ON r.operacao_id = o.id 
                WHERE r.id = :recebivel_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':recebivel_id' => $recebivel_id]);
        $recebivel = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$recebivel) {
            return ['erro' => 'Recebível não encontrado'];
        }
        
        // Buscar compensações relacionadas a este recebível
        $sqlComp = "SELECT 
                        SUM(c.valor_compensado) as total_compensado,
                        COUNT(*) as num_compensacoes,
                        MAX(c.data_compensacao) as ultima_compensacao,
                        GROUP_CONCAT(c.operacao_principal_id) as operacoes_compensadoras
                    FROM compensacoes c 
                    WHERE c.recebivel_compensado_id = :recebivel_id";
        $stmtComp = $pdo->prepare($sqlComp);
        $stmtComp->execute([':recebivel_id' => $recebivel_id]);
        $compensacoes = $stmtComp->fetch(PDO::FETCH_ASSOC);
        
        $valor_original = (float)$recebivel['valor_original'];
        $total_compensado = (float)($compensacoes['total_compensado'] ?? 0);
        $saldo_disponivel = $valor_original - $total_compensado;
        
        // Determinar status real
        $status_real = $recebivel['status'];
        $detalhes = [];
        
        if ($total_compensado > 0) {
            if ($saldo_disponivel <= 0.01) { // Considerando precisão decimal
                $status_real = 'Totalmente Compensado';
                $detalhes['compensacao'] = 'total';
            } else {
                $status_real = 'Parcialmente Compensado';
                $detalhes['compensacao'] = 'parcial';
            }
            
            $detalhes['valor_compensado'] = $total_compensado;
            $detalhes['num_compensacoes'] = $compensacoes['num_compensacoes'];
            $detalhes['ultima_compensacao'] = $compensacoes['ultima_compensacao'];
            $detalhes['operacoes_compensadoras'] = explode(',', $compensacoes['operacoes_compensadoras']);
        }
        
        // Calcular percentual compensado
        $percentual_compensado = $valor_original > 0 ? round(($total_compensado / $valor_original) * 100, 2) : 0;
        
        return [
            'recebivel_id' => $recebivel_id,
            'valor_original' => $valor_original,
            'status_banco' => $recebivel['status'],
            'status_real' => $status_real,
            'status' => $status_real, // Adicionar para compatibilidade
            'total_compensado' => $total_compensado,
            'saldo_disponivel' => $saldo_disponivel,
            'percentual_compensado' => $percentual_compensado,
            'disponivel_para_compensacao' => ($saldo_disponivel > 0.01 && $recebivel['tipo_pagamento'] === 'indireto'),
            'detalhes' => $detalhes
        ];
        
    } catch (Exception $e) {
        return ['erro' => 'Erro ao verificar status: ' . $e->getMessage()];
    }
}

/**
 * Busca recebíveis indiretos disponíveis para compensação (considerando compensações parciais)
 * @param int $cedente_id ID do sacado
 * @param PDO $pdo Conexão com banco de dados
 * @return array Lista de recebíveis disponíveis
 */
function buscarRecebiveisDisponiveis($cedente_id, $pdo) {
    try {
        // Buscar todos os recebíveis indiretos do sacado
        $sql = "SELECT r.id, r.valor_original, r.data_vencimento,
                       DATEDIFF(r.data_vencimento, CURDATE()) as dias_para_vencimento,
                       COALESCE(SUM(c.valor_compensado), 0) as total_compensado
                FROM recebiveis r
                INNER JOIN operacoes o ON r.operacao_id = o.id
                LEFT JOIN compensacoes c ON r.id = c.recebivel_compensado_id
                WHERE o.cedente_id = :cedente_id
                  AND o.tipo_pagamento = 'indireto'
                  AND r.status IN ('Em Aberto', 'Parcialmente Compensado', 'Compensado')
                GROUP BY r.id, r.valor_original, r.data_vencimento
                HAVING (r.valor_original - COALESCE(SUM(c.valor_compensado), 0)) > 0.01
                ORDER BY r.data_vencimento ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':cedente_id' => $cedente_id]);
        $recebiveis = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calcular saldo disponível para cada recebível
        foreach ($recebiveis as &$recebivel) {
            $valor_original = (float)$recebivel['valor_original'];
            $total_compensado = (float)$recebivel['total_compensado'];
            $recebivel['saldo_disponivel'] = $valor_original - $total_compensado;
            $recebivel['percentual_compensado'] = $total_compensado > 0 ? round(($total_compensado / $valor_original) * 100, 2) : 0;
        }
        
        return $recebiveis;
        
    } catch (Exception $e) {
        return ['erro' => 'Erro ao buscar recebíveis: ' . $e->getMessage()];
    }
}

/**
 * Processa compensação parcial ou total
 * @param int $operacao_id ID da operação principal
 * @param array $recebiveis_compensacao Lista de recebíveis para compensar
 * @param float $taxa_antecipacao Taxa de antecipação aplicada
 * @param PDO $pdo Conexão com banco de dados
 * @return array Resultado do processamento
 */
function processarCompensacao($operacao_id, $recebiveis_compensacao, $taxa_antecipacao, $pdo) {
    try {
        // Não inicia nova transação se já existe uma ativa
        $transacao_iniciada_aqui = false;
        if (!$pdo->inTransaction()) {
            $pdo->beginTransaction();
            $transacao_iniciada_aqui = true;
        }
        
        $total_compensado = 0;
        $compensacoes_processadas = [];
        
        foreach ($recebiveis_compensacao as $comp) {
            $recebivel_id = $comp['id'];
            $valor_a_compensar = (float)$comp['valor'];
            
            // Verificar status atual do recebível
            $status = verificarStatusRecebivel($recebivel_id, $pdo);
            if (isset($status['erro'])) {
                throw new Exception("Erro ao verificar recebível {$recebivel_id}: " . $status['erro']);
            }
            
            $saldo_disponivel = $status['saldo_disponivel'];
            
            if ($valor_a_compensar > $saldo_disponivel) {
                throw new Exception("Valor a compensar (R$ {$valor_a_compensar}) excede saldo disponível (R$ {$saldo_disponivel}) do recebível {$recebivel_id}");
            }
            
            // Determinar tipo de compensação
            $tipo_compensacao = ($valor_a_compensar >= $saldo_disponivel - 0.01) ? 'total' : 'parcial';
            $saldo_restante = $saldo_disponivel - $valor_a_compensar;
            
            // Calcular valor presente da compensação
            // Buscar dados da operação e do recebível para calcular corretamente
            $sqlOperacao = "SELECT data_operacao FROM operacoes WHERE id = :operacao_id";
            $stmtOp = $pdo->prepare($sqlOperacao);
            $stmtOp->execute([':operacao_id' => $operacao_id]);
            $operacao = $stmtOp->fetch(PDO::FETCH_ASSOC);
            
            $sqlRecebivel = "SELECT data_vencimento FROM recebiveis WHERE id = :recebivel_id";
            $stmtRec = $pdo->prepare($sqlRecebivel);
            $stmtRec->execute([':recebivel_id' => $recebivel_id]);
            $recebivel = $stmtRec->fetch(PDO::FETCH_ASSOC);
            
            // Calcular valor presente correto
            $dataOperacao = new DateTime($operacao['data_operacao']);
            $dataVencimento = new DateTime($recebivel['data_vencimento']);
            $dias = $dataOperacao->diff($dataVencimento)->days;
            $meses = $dias / 30;
            $taxaMensalDecimal = $taxa_antecipacao / 100; // Converter % para decimal
            $fatorDesconto = pow(1 + $taxaMensalDecimal, -$meses);
            $valorPresenteCalculado = $valor_a_compensar * $fatorDesconto;
            
            // Inserir registro na tabela compensacoes
            $sqlComp = "INSERT INTO compensacoes (
                            operacao_principal_id, recebivel_compensado_id,
                            valor_presente_compensacao, valor_original_recebivel,
                            valor_compensado, saldo_restante, tipo_compensacao,
                            taxa_antecipacao_aplicada, data_compensacao, observacoes
                        ) VALUES (
                            :operacao_id, :recebivel_id,
                            :valor_presente, :valor_original,
                            :valor_compensado, :saldo_restante, :tipo_compensacao,
                            :taxa_antecipacao, NOW(), :observacoes
                        )";
            
            $stmtComp = $pdo->prepare($sqlComp);
            $stmtComp->execute([
                ':operacao_id' => $operacao_id,
                ':recebivel_id' => $recebivel_id,
                ':valor_presente' => $valorPresenteCalculado,
                ':valor_original' => $status['valor_original'],
                ':valor_compensado' => $valor_a_compensar,
                ':saldo_restante' => $saldo_restante,
                ':tipo_compensacao' => $tipo_compensacao,
                ':taxa_antecipacao' => $taxa_antecipacao,
                ':observacoes' => "Compensação {$tipo_compensacao} - Encontro de contas"
            ]);
            
            // Atualizar status do recebível
            $novo_status = ($tipo_compensacao === 'total') ? 'Compensado' : 'Parcialmente Compensado';
            $sqlUpdate = "UPDATE recebiveis SET status = :status WHERE id = :recebivel_id";
            $stmtUpdate = $pdo->prepare($sqlUpdate);
            $stmtUpdate->execute([
                ':status' => $novo_status,
                ':recebivel_id' => $recebivel_id
            ]);
            
            $total_compensado += $valor_a_compensar;
            $compensacoes_processadas[] = [
                'recebivel_id' => $recebivel_id,
                'valor_compensado' => $valor_a_compensar,
                'tipo' => $tipo_compensacao,
                'saldo_restante' => $saldo_restante
            ];
        }
        
        // Atualizar lucro líquido considerando o custo da compensação
        require_once 'funcoes_lucro.php';
        atualizarLucroComCompensacao($operacao_id);
        
        // Só faz commit se a transação foi iniciada aqui
        if ($transacao_iniciada_aqui) {
            $pdo->commit();
        }
        
        return [
            'sucesso' => true,
            'total_compensado' => $total_compensado,
            'compensacoes' => $compensacoes_processadas
        ];
        
    } catch (Exception $e) {
        // Só faz rollback se a transação foi iniciada aqui
        if ($transacao_iniciada_aqui && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['erro' => 'Erro ao processar compensação: ' . $e->getMessage()];
    }
}

// Função formatarMoeda() já está definida em funcoes_lucro.php

/**
 * Formata status para exibição com cores
 */
function formatarStatusCompensacao($status, $detalhes = []) {
    $classes = [
        'Em Aberto' => 'badge bg-info text-dark',
        'Parcialmente Compensado' => 'badge bg-warning text-dark',
        'Totalmente Compensado' => 'badge bg-secondary',
        'Compensado' => 'badge bg-secondary',
        'Recebido' => 'badge bg-success',
        'Problema' => 'badge bg-danger'
    ];
    
    $classe = $classes[$status] ?? 'badge bg-secondary';
    $tooltip = '';
    
    if (isset($detalhes['valor_compensado']) && $detalhes['valor_compensado'] > 0) {
        $tooltip = 'title="Compensado: ' . formatarMoeda($detalhes['valor_compensado']) . '"';
    }
    
    return "<span class='{$classe}' {$tooltip}>{$status}</span>";
}
?>