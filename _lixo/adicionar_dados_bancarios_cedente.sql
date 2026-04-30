-- Script para adicionar campos de dados bancários na tabela cedentes
ALTER TABLE `cedentes`
ADD COLUMN `banco` varchar(100) DEFAULT NULL AFTER `estado`,
ADD COLUMN `agencia` varchar(20) DEFAULT NULL AFTER `banco`,
ADD COLUMN `conta` varchar(30) DEFAULT NULL AFTER `agencia`,
ADD COLUMN `tipo_conta` varchar(20) DEFAULT NULL AFTER `conta`,
ADD COLUMN `chave_pix` varchar(100) DEFAULT NULL AFTER `tipo_conta`;
