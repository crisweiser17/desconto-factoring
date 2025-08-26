<?php
/**
 * CENTRO DE VERDADE ÚNICO - FUNÇÕES DE CÁLCULO
 * 
 * Este arquivo contém todas as funções centralizadas para cálculos financeiros.
 * Todas as telas do sistema devem usar estas funções para garantir consistência.
 * 
 * @author Sistema Factor
 * @version 1.0
 */

/**
 * Carrega configurações de IOF do arquivo config.json
 * @return array Configurações de IOF
 */
function carregarConfiguracaoIOF() {
    $config_path = __DIR__ . '/config.json';
    if (file_exists($config_path)) {
        $config = json_decode(file_get_contents($config_path), true);
        if ($config) {
            // Mapear as chaves do config.json para as chaves esperadas
            return [
                'taxa_diaria' => $config['iof_diaria_rate'] ?? 0.000082,
                'taxa_adicional' => $config['iof_adicional_rate'] ?? 0.0038,
                'limite_dias' => $config['limite_dias'] ?? 365,
                'limite_valor' => $config['limite_valor'] ?? 6000
            ];
        }
    }
    
    // Valores padrão caso o arquivo não exista ou seja inválido
    return [
        'taxa_diaria' => 0.000082,
        'taxa_adicional' => 0.0038,
        'limite_dias' => 365,
        'limite_valor' => 6000
    ];
}

/**
 * Calcula o valor presente de um título
 * @param float $valor_original Valor original do título
 * @param string $data_vencimento Data de vencimento (Y-m-d)
 * @param string $data_operacao Data da operação (Y-m-d)
 * @param float $taxa_mensal Taxa mensal (ex: 0.055 para 5.5%)
 * @return array [valor_presente, dias]
 */
function calcularValorPresente($valor_original, $data_vencimento, $data_operacao, $taxa_mensal) {
    $data_op = new DateTime($data_operacao);
    $data_venc = new DateTime($data_vencimento);
    $dias = $data_op->diff($data_venc)->days;
    
    // Converter taxa mensal para diária
    $taxa_diaria = pow(1 + $taxa_mensal, 1/30) - 1;
    
    // Calcular valor presente
    $valor_presente = $valor_original / pow(1 + $taxa_diaria, $dias);
    
    return [
        'valor_presente' => $valor_presente,
        'dias' => $dias
    ];
}

/**
 * Calcula o IOF de um título
 * @param float $valor_original Valor original do título
 * @param int $dias Dias até o vencimento
 * @param array $config_iof Configuração de IOF
 * @return float Valor do IOF
 */
function calcularIOF($valor_original, $dias, $config_iof = null) {
    if ($config_iof === null) {
        $config_iof = carregarConfiguracaoIOF();
    }
    
    $taxa_diaria = $config_iof['taxa_diaria'];
    $taxa_adicional = $config_iof['taxa_adicional'];
    $limite_dias = $config_iof['limite_dias'];
    $limite_valor = $config_iof['limite_valor'];
    
    // Limitar dias ao máximo
    $dias_calculo = min($dias, $limite_dias);
    
    // Calcular IOF conforme regulamentação (0.0082% diário + 0.38% adicional)
    $iof_diario = $valor_original * $taxa_diaria * $dias_calculo;
    $iof_adicional = $valor_original * $taxa_adicional;
    $iof_total = $iof_diario + $iof_adicional;
    
    // Aplicar limite máximo de 3.38% do valor original (0.38% + máximo 3% diário)
    $limite_maximo = $valor_original * 0.0338; // 3.38% do valor do título
    return min($iof_total, $limite_maximo);
}

/**
 * Calcula os valores de um título individual
 * @param array $titulo Dados do título [valor, data_vencimento]
 * @param string $data_operacao Data da operação
 * @param float $taxa_mensal Taxa mensal
 * @param bool $cobrar_iof_cliente Se cobra IOF do cliente
 * @param array $config_iof Configuração de IOF
 * @return array Dados calculados do título
 */
function calcularTitulo($titulo, $data_operacao, $taxa_mensal, $cobrar_iof_cliente, $config_iof = null) {
    if ($config_iof === null) {
        $config_iof = carregarConfiguracaoIOF();
    }
    
    $valor_original = $titulo['valor'];
    $data_vencimento = $titulo['data_vencimento'];
    
    // Calcular valor presente
    $resultado_vp = calcularValorPresente($valor_original, $data_vencimento, $data_operacao, $taxa_mensal);
    $valor_presente = $resultado_vp['valor_presente'];
    $dias = $resultado_vp['dias'];
    
    // Calcular IOF
    $iof = calcularIOF($valor_original, $dias, $config_iof);
    
    // Calcular valor líquido pago ao cliente
    $valor_liquido_pago = $cobrar_iof_cliente ? ($valor_presente - $iof) : $valor_presente;
    
    // Calcular lucro líquido
    $lucro_liquido = $valor_original - $valor_presente;
    
    return [
        'valor_original' => $valor_original,
        'valor_presente' => $valor_presente,
        'dias' => $dias,
        'iof' => $iof,
        'valor_liquido_pago' => $valor_liquido_pago,
        'lucro_liquido' => $lucro_liquido
    ];
}

/**
 * Calcula o custo de uma compensação
 * @param float $valor_compensacao Valor da compensação
 * @param string $data_vencimento_recebivel Data de vencimento do recebível
 * @param string $data_operacao Data da operação
 * @param float $taxa_antecipacao Taxa de antecipação (ex: 0.02 para 2%)
 * @return array [valor_presente, custo_compensacao]
 */
function calcularCompensacao($valor_compensacao, $data_vencimento_recebivel, $data_operacao, $taxa_antecipacao) {
    $data_op = new DateTime($data_operacao);
    $data_venc = new DateTime($data_vencimento_recebivel);
    $dias = $data_op->diff($data_venc)->days;
    
    // Converter taxa anual para diária
    $taxa_diaria = pow(1 + $taxa_antecipacao, 1/365) - 1;
    
    // Calcular valor presente da compensação
    $valor_presente = $valor_compensacao / pow(1 + $taxa_diaria, $dias);
    
    // Custo da compensação é a diferença
    $custo_compensacao = $valor_compensacao - $valor_presente;
    
    return [
        'valor_presente' => $valor_presente,
        'custo_compensacao' => $custo_compensacao,
        'dias' => $dias
    ];
}

/**
 * Calcula os totais de uma operação completa
 * @param array $titulos Array de títulos
 * @param array $compensacoes Array de compensações (opcional)
 * @param string $data_operacao Data da operação
 * @param float $taxa_mensal Taxa mensal
 * @param bool $cobrar_iof_cliente Se cobra IOF do cliente
 * @param array $config_iof Configuração de IOF
 * @return array Totais calculados
 */
function calcularTotaisOperacao($titulos, $data_operacao, $taxa_mensal, $cobrar_iof_cliente, $compensacoes = [], $config_iof = null) {
    if ($config_iof === null) {
        $config_iof = carregarConfiguracaoIOF();
    }
    
    $totais = [
        'total_original' => 0,
        'total_presente' => 0,
        'total_iof' => 0,
        'total_liquido_pago' => 0,
        'total_lucro_liquido' => 0,
        'total_dias_ponderados' => 0,
        'detalhes_titulos' => [],
        'detalhes_compensacoes' => [],
        'custo_total_compensacao' => 0
    ];
    
    // Processar títulos
    foreach ($titulos as $titulo) {
        $resultado = calcularTitulo($titulo, $data_operacao, $taxa_mensal, $cobrar_iof_cliente, $config_iof);
        
        $totais['total_original'] += $resultado['valor_original'];
        $totais['total_presente'] += $resultado['valor_presente'];
        $totais['total_iof'] += $resultado['iof'];
        $totais['total_liquido_pago'] += $resultado['valor_liquido_pago'];
        $totais['total_lucro_liquido'] += $resultado['lucro_liquido'];
        $totais['total_dias_ponderados'] += $resultado['dias'] * $resultado['valor_original'];
        
        $totais['detalhes_titulos'][] = $resultado;
    }
    
    // Processar compensações
    foreach ($compensacoes as $compensacao) {
        $resultado = calcularCompensacao(
            $compensacao['valor'],
            $compensacao['data_vencimento'],
            $data_operacao,
            $compensacao['taxa_antecipacao']
        );
        
        $totais['custo_total_compensacao'] += $resultado['custo_compensacao'];
        $totais['detalhes_compensacoes'][] = $resultado;
    }
    
    // Ajustar lucro líquido com custo da compensação
    $totais['total_lucro_liquido'] -= $totais['custo_total_compensacao'];
    
    // Calcular média ponderada de dias
    $totais['media_dias'] = $totais['total_original'] > 0 ? 
        $totais['total_dias_ponderados'] / $totais['total_original'] : 0;
    
    // Calcular percentuais
    $totais['lucro_percentual'] = $totais['total_original'] > 0 ? 
        ($totais['total_lucro_liquido'] / $totais['total_original']) * 100 : 0;
    
    $totais['retorno_mensal'] = $totais['media_dias'] > 0 ? 
        (($totais['lucro_percentual'] / 100) * 30) / $totais['media_dias'] : 0;
    
    return $totais;
}

/**
 * Formata valor monetário para exibição
 * @param float $valor Valor a ser formatado
 * @return string Valor formatado (ex: "R$ 1.234,56")
 */
function formatarMoeda($valor) {
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

/**
 * Formata percentual para exibição
 * @param float $percentual Percentual a ser formatado
 * @param int $decimais Número de casas decimais
 * @return string Percentual formatado (ex: "5,50%")
 */
function formatarPercentual($percentual, $decimais = 2) {
    return number_format($percentual, $decimais, ',', '.') . '%';
}

/**
 * Valida se uma taxa está dentro dos limites aceitáveis
 * @param float $taxa Taxa a ser validada
 * @param float $min Valor mínimo (default: 0)
 * @param float $max Valor máximo (default: 1 = 100%)
 * @return bool True se válida
 */
function validarTaxa($taxa, $min = 0, $max = 1) {
    return is_numeric($taxa) && $taxa >= $min && $taxa <= $max;
}

?>