ALTER TABLE `operacoes`
ADD COLUMN `tem_garantia` TINYINT(1) NOT NULL DEFAULT 0 AFTER `tipo_operacao`,
ADD COLUMN `descricao_garantia` TEXT DEFAULT NULL AFTER `tem_garantia`;