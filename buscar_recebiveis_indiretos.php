<?php
require_once 'auth_check.php';
require_once 'db_connection.php';
require_once 'funcoes_compensacao.php';

// Log para debug
error_log("DEBUG: buscar_recebiveis_indiretos.php chamado");

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['cedente_id'])) {
        error_log("DEBUG: cedente_id não fornecido");
        echo json_encode(['success' => false, 'error' => 'ID do cedente não fornecido']);
        exit;
    }
    
    $cedente_id = (int)$input['cedente_id'];
    $check_only = isset($input['check_only']) && $input['check_only'];
    
    error_log("DEBUG: Buscando recebíveis para cedente_id: $cedente_id, check_only: " . ($check_only ? 'true' : 'false'));
    
    // Buscar dados do sacado para debug
    $stmt_sacado = $pdo->prepare("SELECT id, empresa, documento_principal, tipo_pessoa FROM clientes WHERE id = :id");
    $stmt_sacado->execute([':id' => $cedente_id]);
    $cedente = $stmt_sacado->fetch(PDO::FETCH_ASSOC);
    
    if (!$cedente) {
        error_log("DEBUG: Cedente não encontrado com ID: $cedente_id");
        echo json_encode(['success' => false, 'error' => 'Cedente não encontrado']);
        exit;
    }
    
    error_log("DEBUG: Sacado encontrado - Empresa: {$cedente['empresa']}, Documento: {$cedente['documento_principal']}, Tipo: {$cedente['tipo_pessoa']}");
    
    if ($check_only) {
        // Verificar apenas se existem recebíveis indiretos disponíveis
        $sql_check = "SELECT COUNT(*) as total
                      FROM recebiveis r
                      INNER JOIN operacoes o ON r.operacao_id = o.id
                      WHERE o.cedente_id = :cedente_id
                        AND o.tipo_pagamento = 'indireto'
                        AND r.status IN ('Em Aberto', 'Parcialmente Compensado')";
        
        error_log("DEBUG: Query check_only: $sql_check");
        
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute([':cedente_id' => $cedente_id]);
        $result = $stmt_check->fetch(PDO::FETCH_ASSOC);
        
        $has_recebiveis = $result['total'] > 0;
        error_log("DEBUG: Has recebíveis indiretos: " . ($has_recebiveis ? 'true' : 'false') . " (total: {$result['total']})");
        
        echo json_encode([
            'success' => true,
            'has_recebiveis' => $has_recebiveis,
            'total' => $result['total']
        ]);
        exit;
    }
    
    // Buscar recebíveis indiretos disponíveis para compensação
    $recebiveis = buscarRecebiveisDisponiveis($cedente_id, $pdo);
    
    if (isset($recebiveis['erro'])) {
        error_log("DEBUG: Erro ao buscar recebíveis: " . $recebiveis['erro']);
        echo json_encode(['success' => false, 'error' => $recebiveis['erro']]);
        exit;
    }
    
    error_log("DEBUG: Recebíveis encontrados: " . count($recebiveis));
    
    // Adicionar informações de debug para cada recebível
    foreach ($recebiveis as &$recebivel) {
        error_log("DEBUG: Recebível ID {$recebivel['id']} - Valor: {$recebivel['valor_original']}, Saldo: {$recebivel['saldo_disponivel']}, Dias: {$recebivel['dias_para_vencimento']}");
    }
    
    echo json_encode([
        'success' => true,
        'recebiveis' => $recebiveis,
        'sacado_info' => [
            'id' => $cedente['id'],
            'empresa' => $cedente['empresa'],
            'documento_principal' => $cedente['documento_principal'],
            'tipo_pessoa' => $cedente['tipo_pessoa']
        ]
    ]);
    
} catch (Exception $e) {
    error_log("DEBUG: Exceção em buscar_recebiveis_indiretos.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erro interno: ' . $e->getMessage()]);
}
?>