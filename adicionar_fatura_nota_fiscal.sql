-- Script para adicionar 'fatura' e 'nota_fiscal' aos tipos de recebíveis
-- Execute este script no banco de dados para atualizar o ENUM

USE rfqkezvjge;

-- Modificar a coluna tipo_recebivel para incluir 'fatura' e 'nota_fiscal'
ALTER TABLE recebiveis 
MODIFY COLUMN tipo_recebivel ENUM('cheque', 'duplicata', 'nota_promissoria', 'boleto', 'fatura', 'nota_fiscal', 'outros') 
DEFAULT 'duplicata' 
COMMENT 'Tipo do recebível: cheque, duplicata, nota promissória, boleto, fatura, nota fiscal ou outros';

-- Verificar se a alteração foi aplicada
DESCRIBE recebiveis;

-- Mostrar alguns registros para confirmar
SELECT id, operacao_id, sacado_id, tipo_recebivel, valor_original, data_vencimento 
FROM recebiveis 
LIMIT 5;