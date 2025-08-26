-- Script para corrigir a data da operação 51 de volta para 22 de agosto de 2025
-- e verificar se os cálculos não foram afetados

USE rfqkezvjge;

-- Primeiro, vamos ver o estado atual da operação 51
SELECT 
    id,
    cedente_id,
    taxa_mensal,
    data_operacao,
    data_base_calculo,
    tipo_pagamento,
    total_original_calc,
    total_presente_calc,
    iof_total_calc,
    total_liquido_pago_calc,
    total_lucro_liquido_calc,
    media_dias_pond_calc,
    notas
FROM operacoes 
WHERE id = 51;

-- Verificar os recebíveis da operação 51 para entender os cálculos
SELECT 
    id,
    operacao_id,
    sacado_id,
    valor_original,
    data_vencimento,
    valor_presente_calc,
    iof_calc,
    valor_liquido_calc,
    dias_prazo_calc,
    status
FROM recebiveis 
WHERE operacao_id = 51
ORDER BY data_vencimento;

-- Corrigir a data da operação 51 para 22 de agosto de 2025
UPDATE operacoes 
SET data_operacao = '2025-08-22 16:42:48',
    data_base_calculo = '2025-08-22'
WHERE id = 51;

-- Verificar se a correção foi aplicada
SELECT 
    id,
    cedente_id,
    taxa_mensal,
    data_operacao,
    data_base_calculo,
    tipo_pagamento,
    total_original_calc,
    total_presente_calc,
    iof_total_calc,
    total_liquido_pago_calc,
    total_lucro_liquido_calc,
    media_dias_pond_calc,
    notas
FROM operacoes 
WHERE id = 51;

-- Verificar se os cálculos dos recebíveis ainda estão corretos
-- (os cálculos devem estar baseados na data correta agora)
SELECT 
    r.id,
    r.valor_original,
    r.data_vencimento,
    r.valor_presente_calc,
    r.iof_calc,
    r.valor_liquido_calc,
    r.dias_prazo_calc,
    DATEDIFF(r.data_vencimento, o.data_base_calculo) AS dias_calculados_atual
FROM recebiveis r
JOIN operacoes o ON r.operacao_id = o.id
WHERE r.operacao_id = 51
ORDER BY r.data_vencimento;

-- Comentário: Se os dias_prazo_calc não coincidirem com dias_calculados_atual,
-- será necessário recalcular os valores usando as funções de cálculo do sistema.
-- Os valores corretos para a operação 51 com data base 22/08/2025 devem ser:
-- Recebível 104: 8 dias de prazo (30/08 - 22/08)
-- Recebível 105: 24 dias de prazo (15/09 - 22/08)