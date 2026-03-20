<?php
require_once 'db_connection.php';

header('Content-Type: application/json; charset=utf-8');

$input = json_decode(file_get_contents('php://input'), true);
if ($input === null) {
    echo json_encode(['error' => 'Erro JSON']);
    exit;
}

$valorAlvo = isset($input['valorAlvo']) ? (float)$input['valorAlvo'] : 0;
$dataOperacaoStr = isset($input['data_operacao']) ? $input['data_operacao'] : null;
$cobrarIOF = isset($input['cobrarIOF']) ? $input['cobrarIOF'] === 'Sim' : false;
$titulos = isset($input['titulos']) && is_array($input['titulos']) ? $input['titulos'] : [];
$compensacaoData = isset($input['compensacao_data']) ? $input['compensacao_data'] : null;

if ($valorAlvo <= 0 || empty($titulos) || empty($dataOperacaoStr)) {
    echo json_encode(['error' => 'Dados insuficientes para calcular a taxa.']);
    exit;
}

try {
    $dataOperacao = new DateTime($dataOperacaoStr);
    $dataOperacao->setTime(0, 0, 0);
} catch (Exception $e) {
    echo json_encode(['error' => 'Data da operação inválida.']);
    exit;
}

// 1. Calcular o Valor Presente da Compensação (independente da taxa da operação)
$valorPresenteTotalCompensacao = 0;
if ($compensacaoData && isset($compensacaoData['recebiveis']) && is_array($compensacaoData['recebiveis'])) {
    $pdo = getConnection();
    $taxaAntecipacao = isset($compensacaoData['taxa_antecipacao']) ? (float)$compensacaoData['taxa_antecipacao'] / 100 : 0;
    
    foreach ($compensacaoData['recebiveis'] as $recebivel) {
        $valorCompensacao = (float)$recebivel['valor'];
        $recebivelId = $recebivel['id'];
        
        try {
            $stmt = $pdo->prepare("SELECT data_vencimento FROM recebiveis WHERE id = :id");
            $stmt->bindParam(':id', $recebivelId, PDO::PARAM_INT);
            $stmt->execute();
            $recebivelData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($recebivelData) {
                $dataVencimentoRecebivel = new DateTime($recebivelData['data_vencimento']);
                $diasParaVencimento = max(0, $dataOperacao->diff($dataVencimentoRecebivel)->days);
            } else {
                $diasParaVencimento = 25;
            }
        } catch (Exception $e) {
            $diasParaVencimento = 25;
        }
        
        if ($diasParaVencimento > 0 && $taxaAntecipacao > 0) {
            $fatorDesconto = pow(1 + $taxaAntecipacao, $diasParaVencimento / 30.0);
            $valorPresenteRecebivel = $valorCompensacao / $fatorDesconto;
        } else {
            $valorPresenteRecebivel = $valorCompensacao;
        }
        
        $valorPresenteTotalCompensacao += $valorPresenteRecebivel;
    }
}

// 2. Preparar títulos
$titulosProcessados = [];
$totalOriginal = 0;
foreach ($titulos as $titulo) {
    $valorOrig = (float)$titulo['valorOriginal'];
    $dataVenc = new DateTime($titulo['dataVencimento']);
    $dataVenc->setTime(0, 0, 0);
    
    $dias = 0;
    if ($dataVenc >= $dataOperacao) {
        $dias = $dataOperacao->diff($dataVenc)->days;
    }
    
    $titulosProcessados[] = [
        'valorOriginal' => $valorOrig,
        'dias' => $dias
    ];
    $totalOriginal += $valorOrig;
}

// O valor alvo ajustado é o valor alvo + o que foi descontado pela compensação
$alvoAjustado = $valorAlvo + $valorPresenteTotalCompensacao;

if ($alvoAjustado >= $totalOriginal) {
    echo json_encode(['success' => true, 'taxaMensal' => 0]);
    exit;
}

// 3. Busca binária
$low = 0.0;
$high = 1.0; // 100% ao mês
$bestTaxa = 0.0;

for ($i = 0; $i < 100; $i++) {
    $mid = ($low + $high) / 2;
    $testTotalLiquido = 0;
    
    foreach ($titulosProcessados as $t) {
        $valorPres = $t['valorOriginal'] / pow(1 + $mid, $t['dias'] / 30.0);
        $iof = $cobrarIOF ? ($t['valorOriginal'] * 0.0038) : 0;
        $liquido = max(0, $valorPres - $iof);
        $testTotalLiquido += $liquido;
    }
    
    if ($testTotalLiquido > $alvoAjustado) {
        // Se o valor líquido calculado for maior que o alvo, a taxa está muito baixa
        $low = $mid;
    } else {
        // Se o valor líquido calculado for menor que o alvo, a taxa está muito alta
        $high = $mid;
    }
    $bestTaxa = $mid;
}

// Converter para porcentagem e arredondar
$taxaPercentual = $bestTaxa * 100;

echo json_encode(['success' => true, 'taxaMensal' => $taxaPercentual]);
