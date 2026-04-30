-- Script para adicionar o campo tipo_operacao na tabela operacoes

-- Adicionar coluna tipo_operacao na tabela operacoes
ALTER TABLE `operacoes` 
ADD COLUMN `tipo_operacao` ENUM('antecipacao', 'emprestimo') NOT NULL DEFAULT 'antecipacao' AFTER `tipo_pagamento`;

-- Verificar as operações existentes e garantir que estão como antecipacao
UPDATE `operacoes` SET `tipo_operacao` = 'antecipacao' WHERE `tipo_operacao` IS NULL OR `tipo_operacao` = '';
