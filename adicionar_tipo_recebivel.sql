-- Script para adicionar campo tipo_recebivel na tabela recebiveis
-- Execute este script no banco de dados para adicionar o novo campo

USE rfqkezvjge;

-- Adicionar coluna tipo_recebivel na tabela recebiveis
ALTER TABLE recebiveis 
ADD COLUMN tipo_recebivel ENUM('cheque', 'duplicata', 'nota_promissoria', 'boleto', 'outros') 
DEFAULT 'duplicata' 
AFTER sacado_id;

-- Comentário sobre o campo
ALTER TABLE recebiveis 
MODIFY COLUMN tipo_recebivel ENUM('cheque', 'duplicata', 'nota_promissoria', 'boleto', 'outros') 
DEFAULT 'duplicata' 
COMMENT 'Tipo do recebível: cheque, duplicata, nota promissória, boleto ou outros';

-- Verificar se a alteração foi aplicada
DESCRIBE recebiveis;

-- Mostrar alguns registros para confirmar
SELECT id, operacao_id, sacado_id, tipo_recebivel, valor_original, data_vencimento 
FROM recebiveis 
LIMIT 5;