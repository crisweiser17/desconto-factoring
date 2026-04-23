-- Script para adicionar parcela_emprestimo ao ENUM de tipo_recebivel

ALTER TABLE `recebiveis` 
MODIFY COLUMN `tipo_recebivel` ENUM('cheque', 'duplicata', 'nota_promissoria', 'boleto', 'fatura', 'nota_fiscal', 'parcela_emprestimo', 'outros') 
COLLATE utf8mb4_unicode_ci DEFAULT 'duplicata' COMMENT 'Tipo do recebível';
