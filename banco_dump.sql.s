-- Adminer 4.7.8 MySQL dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP DATABASE IF EXISTS `rfqkezvjge`;
CREATE DATABASE `rfqkezvjge` /*!40100 DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci */;
USE `rfqkezvjge`;

SET NAMES utf8mb4;

DROP TABLE IF EXISTS `cedentes`;
CREATE TABLE `cedentes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `telefone` varchar(50) DEFAULT NULL,
  `tipo_pessoa` enum('FISICA','JURIDICA') NOT NULL DEFAULT 'FISICA',
  `documento_principal` varchar(18) DEFAULT NULL,
  `documento_secundario` varchar(14) DEFAULT NULL,
  `cpf` varchar(14) DEFAULT NULL,
  `cnpj` varchar(18) DEFAULT NULL,
  `documento_socio` varchar(14) DEFAULT NULL,
  `empresa` varchar(255) DEFAULT NULL,
  `endereco` text DEFAULT NULL,
  `cep` varchar(10) DEFAULT NULL,
  `logradouro` varchar(255) DEFAULT NULL,
  `numero` varchar(20) DEFAULT NULL,
  `complemento` varchar(100) DEFAULT NULL,
  `bairro` varchar(100) DEFAULT NULL,
  `cidade` varchar(100) DEFAULT NULL,
  `estado` varchar(2) DEFAULT NULL,
  `data_cadastro` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `cpf` (`cpf`),
  UNIQUE KEY `cnpj` (`cnpj`),
  UNIQUE KEY `documento_principal` (`documento_principal`),
  KEY `idx_nome` (`nome`),
  KEY `idx_empresa` (`empresa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `cedentes` (`id`, `nome`, `email`, `telefone`, `tipo_pessoa`, `documento_principal`, `documento_secundario`, `cpf`, `cnpj`, `documento_socio`, `empresa`, `endereco`, `cep`, `logradouro`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `data_cadastro`) VALUES
(3,	'All Terra Terraplenagem',	'administrativo@allterraplenagem.com.br',	'(19) 99986-0107',	'JURIDICA',	'49915558000196',	NULL,	NULL,	NULL,	NULL,	'All Terra Terraplenagem',	'',	'13423-190',	'Avenida Doutor Alexandre Guimarães dos Santos',	'303',	'',	'Dois Córregos',	'Piracicaba',	'SP',	'2025-04-05 11:57:09'),
(14,	'Cristian Weiser',	'cristianweiser@gmail.com',	'(19) 99898-9999',	'JURIDICA',	'00000000000000',	NULL,	NULL,	NULL,	NULL,	'CRISWEISER',	'Avenida Dona Léddia, 1700',	'13405-235',	'Avenida Dona Lídia',	'1700',	'',	'Vila Rezende',	'Piracicaba',	'SP',	'2025-04-06 15:11:09'),
(16,	'C. V. BONASSOLI SOLUCOES',	'caiobonassoli@gmail.com',	'(15) 99821-1200',	'JURIDICA',	'35258176000122',	NULL,	NULL,	NULL,	NULL,	'C. V. BONASSOLI SOLUCOES',	'Rua Sophia Dias, R. Carlos Menk, 711 (end alternativo)',	'18460-017',	'Rua Frei Caneca',	'1328',	'',	'Centro',	'Itararé',	'SP',	'2025-08-15 13:52:47'),
(17,	'Teste Cedente LTDA',	'cedente@teste.com',	'(11) 88888-8888',	'JURIDICA',	'22222222000122',	NULL,	NULL,	NULL,	NULL,	'Teste Cedente LTDA',	'',	'12345-678',	'Av Teste',	'456',	'',	'Centro',	'São Paulo',	'SP',	'2025-08-27 00:29:09');

DROP TABLE IF EXISTS `cedentes_socios`;
CREATE TABLE `cedentes_socios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cedente_id` int(11) DEFAULT NULL,
  `nome` varchar(255) NOT NULL,
  `cpf` varchar(14) NOT NULL,
  `data_cadastro` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sacado_id` (`cedente_id`),
  KEY `idx_cpf` (`cpf`),
  CONSTRAINT `fk_cedentes_socios_cedente` FOREIGN KEY (`cedente_id`) REFERENCES `cedentes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `cedentes_socios` (`id`, `cedente_id`, `nome`, `cpf`, `data_cadastro`) VALUES
(3,	3,	'Bruno Tedesco',	'12345678901',	'2025-08-05 14:03:57'),
(4,	14,	'Cristian Weiser',	'22723140822',	'2025-08-05 14:41:08'),
(5,	16,	'MARIANA OLIVEIRA BONASSOLI',	'33863057848',	'2025-08-15 13:52:47'),
(6,	16,	'Caio Vinicius Bonassoli',	'39691428814',	'2025-08-15 13:52:47');

DROP TABLE IF EXISTS `compensacoes`;
CREATE TABLE `compensacoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `operacao_principal_id` int(11) NOT NULL COMMENT 'ID da operação principal (nova operação de desconto)',
  `recebivel_compensado_id` int(11) NOT NULL COMMENT 'ID do recebível que foi compensado',
  `valor_presente_compensacao` decimal(15,2) NOT NULL COMMENT 'Valor presente calculado da compensação',
  `valor_original_recebivel` decimal(15,2) NOT NULL COMMENT 'Valor original do recebível antes da compensação',
  `valor_compensado` decimal(15,2) NOT NULL COMMENT 'Valor efetivamente compensado nesta operação',
  `saldo_restante` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Saldo restante do recebível após esta compensação',
  `tipo_compensacao` enum('total','parcial') NOT NULL DEFAULT 'total' COMMENT 'Tipo de compensação: total ou parcial',
  `recebivel_saldo_id` int(11) DEFAULT NULL COMMENT 'ID do novo recebível criado para o saldo restante (se aplicável)',
  `taxa_antecipacao_aplicada` decimal(10,4) NOT NULL COMMENT 'Taxa de antecipação aplicada no cálculo',
  `data_compensacao` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Data e hora da compensação',
  `observacoes` text DEFAULT NULL COMMENT 'Observações sobre a compensação',
  PRIMARY KEY (`id`),
  KEY `idx_operacao_principal` (`operacao_principal_id`),
  KEY `idx_recebivel_compensado` (`recebivel_compensado_id`),
  KEY `idx_data_compensacao` (`data_compensacao`),
  KEY `idx_recebivel_saldo` (`recebivel_saldo_id`),
  KEY `idx_tipo_compensacao` (`tipo_compensacao`),
  CONSTRAINT `fk_compensacao_operacao` FOREIGN KEY (`operacao_principal_id`) REFERENCES `operacoes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_compensacao_recebivel` FOREIGN KEY (`recebivel_compensado_id`) REFERENCES `recebiveis` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabela para rastrear compensações de encontro de contas';

INSERT INTO `compensacoes` (`id`, `operacao_principal_id`, `recebivel_compensado_id`, `valor_presente_compensacao`, `valor_original_recebivel`, `valor_compensado`, `saldo_restante`, `tipo_compensacao`, `recebivel_saldo_id`, `taxa_antecipacao_aplicada`, `data_compensacao`, `observacoes`) VALUES
(4,	49,	55,	49344.25,	100000.00,	50000.00,	50000.00,	'parcial',	NULL,	2.0000,	'2025-08-04 17:34:08',	'Compensação total - Encontro de contas'),
(5,	50,	55,	49605.51,	100000.00,	50000.00,	0.00,	'total',	NULL,	2.0000,	'2025-08-09 12:40:14',	'Compensação total - Encontro de contas');

DROP TABLE IF EXISTS `operacao_arquivos`;
CREATE TABLE `operacao_arquivos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `operacao_id` int(11) NOT NULL COMMENT 'ID da operação à qual o arquivo pertence',
  `nome_original` varchar(255) NOT NULL COMMENT 'Nome original do arquivo enviado pelo usuário',
  `nome_arquivo` varchar(255) NOT NULL COMMENT 'Nome único do arquivo no servidor',
  `tipo_arquivo` varchar(100) NOT NULL COMMENT 'Tipo MIME do arquivo',
  `extensao` varchar(10) NOT NULL COMMENT 'Extensão do arquivo',
  `tamanho_bytes` bigint(20) NOT NULL COMMENT 'Tamanho do arquivo em bytes',
  `caminho_arquivo` varchar(500) NOT NULL COMMENT 'Caminho completo do arquivo no servidor',
  `data_upload` datetime DEFAULT current_timestamp() COMMENT 'Data e hora do upload',
  `usuario_upload` varchar(100) DEFAULT NULL COMMENT 'Usuário que fez o upload',
  `descricao` text DEFAULT NULL COMMENT 'Descrição opcional do arquivo',
  `ativo` tinyint(1) DEFAULT 1 COMMENT 'Se o arquivo está ativo (não foi excluído)',
  PRIMARY KEY (`id`),
  KEY `idx_operacao_id` (`operacao_id`),
  KEY `idx_data_upload` (`data_upload`),
  KEY `idx_ativo` (`ativo`),
  KEY `idx_tipo_arquivo` (`tipo_arquivo`),
  CONSTRAINT `fk_arquivo_operacao` FOREIGN KEY (`operacao_id`) REFERENCES `operacoes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabela para armazenar arquivos anexados às operações';

INSERT INTO `operacao_arquivos` (`id`, `operacao_id`, `nome_original`, `nome_arquivo`, `tipo_arquivo`, `extensao`, `tamanho_bytes`, `caminho_arquivo`, `data_upload`, `usuario_upload`, `descricao`, `ativo`) VALUES
(1,	50,	'FATURA_LOCACAO_CARVALHO_68.pdf',	'op50_2025-08-09_11-26-31_159f0b64_FATURA_LOCACAO_CARVALHO_68.pdf',	'application/pdf',	'pdf',	474851,	'/home/1224600.cloudwaysapps.com/rfqkezvjge/public_html/uploads/operacoes/50/op50_2025-08-09_11-26-31_159f0b64_FATURA_LOCACAO_CARVALHO_68.pdf',	'2025-08-09 14:26:31',	'sistema',	'Fatura 68',	1),
(2,	50,	'FATURA_LOCACAO_CARVALHO_69.pdf',	'op50_2025-08-09_11-27-07_470ebf35_FATURA_LOCACAO_CARVALHO_69.pdf',	'application/pdf',	'pdf',	475195,	'/home/1224600.cloudwaysapps.com/rfqkezvjge/public_html/uploads/operacoes/50/op50_2025-08-09_11-27-07_470ebf35_FATURA_LOCACAO_CARVALHO_69.pdf',	'2025-08-09 14:27:07',	'sistema',	'',	1),
(3,	50,	'FATURA_LOCACAO_CARVALHO_70.pdf',	'op50_2025-08-09_11-27-07_d087edef_FATURA_LOCACAO_CARVALHO_70.pdf',	'application/pdf',	'pdf',	474385,	'/home/1224600.cloudwaysapps.com/rfqkezvjge/public_html/uploads/operacoes/50/op50_2025-08-09_11-27-07_d087edef_FATURA_LOCACAO_CARVALHO_70.pdf',	'2025-08-09 14:27:07',	'sistema',	'',	1),
(4,	32,	'CONTRATO DE COMPROMISSO DE VENDA E COMPRA DE VEÍC... - 7_11_25, 12_35 PM (1).pdf',	'op32_2025-08-15_10-57-08_8ca50083_CONTRATO_DE_COMPROMISSO_DE_VENDA_E_COMPRA_DE_VEI_C..._-_7_11_25_12_35_PM_1.pdf',	'application/pdf',	'pdf',	446948,	'/home/1224600.cloudwaysapps.com/rfqkezvjge/public_html/uploads/operacoes/32/op32_2025-08-15_10-57-08_8ca50083_CONTRATO_DE_COMPROMISSO_DE_VENDA_E_COMPRA_DE_VEI_C..._-_7_11_25_12_35_PM_1.pdf',	'2025-08-15 13:57:08',	'sistema',	'Contrato de Compra e Venda Volvo',	1),
(5,	51,	'FATURA_ RESIDENCIAL UNINORTE_70.pdf',	'op51_2025-08-22_13-42-48_578aee27_FATURA_RESIDENCIAL_UNINORTE_70.pdf',	'application/pdf',	'pdf',	156184,	'/home/1224600.cloudwaysapps.com/rfqkezvjge/public_html/uploads/operacoes/51/op51_2025-08-22_13-42-48_578aee27_FATURA_RESIDENCIAL_UNINORTE_70.pdf',	'2025-08-22 16:42:48',	'sistema',	'',	1),
(6,	51,	'FATURA_LOCACAO_VICORP_69.pdf',	'op51_2025-08-22_13-42-48_d996a207_FATURA_LOCACAO_VICORP_69.pdf',	'application/pdf',	'pdf',	157872,	'/home/1224600.cloudwaysapps.com/rfqkezvjge/public_html/uploads/operacoes/51/op51_2025-08-22_13-42-48_d996a207_FATURA_LOCACAO_VICORP_69.pdf',	'2025-08-22 16:42:48',	'sistema',	'',	1),
(7,	21,	'2ª Medição Recanto do Lago - Rede de Esgoto.pdf',	'op21_2025-08-27_00-46-37_4a80661e_2_Medic_a_o_Recanto_do_Lago_-_Rede_de_Esgoto.pdf',	'application/pdf',	'pdf',	421591,	'/Users/crisweiser/Downloads/Projetos IDE/factor/uploads/operacoes/21/op21_2025-08-27_00-46-37_4a80661e_2_Medic_a_o_Recanto_do_Lago_-_Rede_de_Esgoto.pdf',	'2025-08-26 21:46:37',	'sistema',	'',	1),
(8,	30,	'ExportedReport (4).pdf',	'op30_2025-08-27_00-54-05_db8e749c_ExportedReport_4.pdf',	'application/pdf',	'pdf',	170167,	'/Users/crisweiser/Downloads/Projetos IDE/factor/uploads/operacoes/30/op30_2025-08-27_00-54-05_db8e749c_ExportedReport_4.pdf',	'2025-08-26 21:54:05',	'sistema',	'',	1),
(9,	30,	'Nota 7ª Medição Mão de Obra.pdf',	'op30_2025-08-27_00-55-46_68853326_Nota_7_Medic_a_o_Ma_o_de_Obra.pdf',	'application/pdf',	'pdf',	170203,	'/Users/crisweiser/Downloads/Projetos IDE/factor/uploads/operacoes/30/op30_2025-08-27_00-55-46_68853326_Nota_7_Medic_a_o_Ma_o_de_Obra.pdf',	'2025-08-26 21:55:46',	'sistema',	'',	1),
(10,	30,	'FATURA_LOCACAO_CARVALHO_51.pdf',	'op30_2025-08-27_00-56-24_abfd1698_FATURA_LOCACAO_CARVALHO_51.pdf',	'application/pdf',	'pdf',	157365,	'/Users/crisweiser/Downloads/Projetos IDE/factor/uploads/operacoes/30/op30_2025-08-27_00-56-24_abfd1698_FATURA_LOCACAO_CARVALHO_51.pdf',	'2025-08-26 21:56:24',	'sistema',	'',	1);

DROP TABLE IF EXISTS `operacoes`;
CREATE TABLE `operacoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cedente_id` int(11) DEFAULT NULL,
  `taxa_mensal` decimal(10,4) NOT NULL,
  `data_operacao` datetime DEFAULT NULL,
  `data_base_calculo` date DEFAULT NULL,
  `tipo_pagamento` varchar(50) DEFAULT 'direto',
  `total_original_calc` decimal(15,2) DEFAULT NULL,
  `total_presente_calc` decimal(15,2) DEFAULT NULL,
  `iof_total_calc` decimal(15,2) DEFAULT NULL,
  `total_liquido_pago_calc` decimal(15,2) DEFAULT NULL,
  `incorre_custo_iof` tinyint(1) NOT NULL,
  `cobrar_iof_cliente` tinyint(1) NOT NULL,
  `total_lucro_liquido_calc` decimal(15,2) DEFAULT NULL,
  `media_dias_pond_calc` int(11) DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `valor_total_compensacao` decimal(15,2) DEFAULT 0.00 COMMENT 'Valor total das compensações aplicadas nesta operação',
  PRIMARY KEY (`id`),
  KEY `fk_operacao_sacado` (`cedente_id`),
  CONSTRAINT `fk_operacoes_cedente` FOREIGN KEY (`cedente_id`) REFERENCES `cedentes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `operacoes` (`id`, `cedente_id`, `taxa_mensal`, `data_operacao`, `data_base_calculo`, `tipo_pagamento`, `total_original_calc`, `total_presente_calc`, `iof_total_calc`, `total_liquido_pago_calc`, `incorre_custo_iof`, `cobrar_iof_cliente`, `total_lucro_liquido_calc`, `media_dias_pond_calc`, `notas`, `valor_total_compensacao`) VALUES
(17,	3,	0.0500,	'2025-02-28 00:00:00',	'2025-02-28',	'indireto',	25000.00,	23720.45,	161.63,	23558.82,	0,	1,	1441.18,	33,	'',	0.00),
(20,	3,	0.0400,	'2025-01-22 00:00:00',	'2025-01-22',	'direto',	110250.00,	91208.33,	1743.44,	89464.88,	0,	1,	20785.12,	147,	'arredondando',	0.00),
(21,	3,	0.0502,	'2025-03-25 00:00:00',	'2025-03-25',	'indireto',	60000.00,	55658.84,	454.32,	55204.52,	0,	1,	4795.48,	46,	'fatura 60k',	0.00),
(22,	3,	0.0500,	'2025-01-22 00:00:00',	'2025-01-22',	'indireto',	5750.00,	5089.73,	57.21,	5032.52,	0,	1,	717.48,	75,	'',	0.00),
(29,	3,	0.0550,	'2025-05-26 00:00:00',	'2025-05-26',	'indireto',	30270.00,	28691.94,	189.49,	28502.45,	0,	1,	1767.55,	30,	'',	0.00),
(30,	3,	0.0550,	'2025-06-16 00:00:00',	'2025-06-16',	'indireto',	101355.00,	95709.76,	0.00,	95709.76,	0,	1,	5645.24,	26,	'56k colinas, 17k e 27k carvalho.',	0.00),
(31,	3,	0.0580,	'2025-07-08 00:00:00',	'2025-07-08',	'indireto',	100000.00,	92236.73,	0.00,	92236.73,	0,	1,	7763.27,	NULL,	'',	0.00),
(32,	16,	0.0000,	'2025-07-11 00:00:00',	'2025-07-11',	'direto',	39587.20,	39584.29,	867.50,	39587.20,	0,	0,	0.00,	221,	'Venda Volvo',	0.00),
(33,	3,	0.0550,	'2025-07-24 00:00:00',	'2025-08-01',	'indireto',	22530.00,	21203.54,	148.43,	21546.87,	0,	1,	983.13,	34,	'',	0.00),
(49,	3,	0.0550,	'2025-08-05 14:20:42',	NULL,	'escrow',	68653.40,	NULL,	260.88,	12354.90,	0,	1,	5642.75,	54,	'',	50000.00),
(50,	3,	0.0550,	'2025-08-09 12:40:14',	NULL,	'indireto',	93570.00,	NULL,	355.57,	36612.12,	0,	1,	6563.39,	41,	'escrow informada na fatura - nao é boleto',	50000.00),
(51,	3,	0.0600,	'2025-08-26 15:37:36',	NULL,	'indireto',	32500.00,	NULL,	123.50,	31122.23,	0,	1,	1377.77,	20,	'',	0.00);

DROP TABLE IF EXISTS `recebiveis`;
CREATE TABLE `recebiveis` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `operacao_id` int(11) NOT NULL,
  `sacado_id` int(11) DEFAULT NULL,
  `tipo_recebivel` enum('cheque','duplicata','nota_promissoria','boleto','fatura','nota_fiscal','outros') DEFAULT 'duplicata' COMMENT 'Tipo do recebível: cheque, duplicata, nota promissória, boleto, fatura, nota fiscal ou outros',
  `valor_original` decimal(15,2) NOT NULL,
  `data_vencimento` date NOT NULL,
  `valor_presente_calc` decimal(15,2) DEFAULT NULL,
  `iof_calc` decimal(15,2) DEFAULT NULL,
  `valor_liquido_calc` decimal(15,2) DEFAULT NULL,
  `dias_prazo_calc` int(11) DEFAULT NULL COMMENT 'Dias de prazo calculado entre operação e vencimento',
  `status` enum('Em Aberto','Recebido','Problema','Compensado','Parcialmente Compensado') DEFAULT 'Em Aberto',
  `data_recebimento` datetime DEFAULT NULL,
  `obs_problema` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_operacao_id` (`operacao_id`),
  KEY `idx_data_vencimento` (`data_vencimento`),
  KEY `idx_status` (`status`),
  KEY `idx_sacado_id` (`sacado_id`),
  CONSTRAINT `fk_recebiveis_sacado` FOREIGN KEY (`sacado_id`) REFERENCES `sacados` (`id`),
  CONSTRAINT `recebiveis_ibfk_1` FOREIGN KEY (`operacao_id`) REFERENCES `operacoes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `recebiveis` (`id`, `operacao_id`, `sacado_id`, `tipo_recebivel`, `valor_original`, `data_vencimento`, `valor_presente_calc`, `iof_calc`, `valor_liquido_calc`, `dias_prazo_calc`, `status`, `data_recebimento`, `obs_problema`) VALUES
(17,	17,	7,	'cheque',	12500.00,	'2025-03-17',	12159.14,	64.93,	12094.21,	17,	'Recebido',	'2025-03-17 00:00:00',	NULL),
(18,	17,	7,	'cheque',	12500.00,	'2025-04-17',	11561.31,	96.70,	11464.61,	48,	'Recebido',	'2025-04-17 00:00:00',	NULL),
(21,	20,	10,	'cheque',	7150.00,	'2025-04-07',	6482.20,	71.14,	6411.06,	75,	'Recebido',	'2025-04-07 00:00:00',	NULL),
(22,	20,	10,	'cheque',	10000.00,	'2025-04-20',	8913.24,	110.16,	8803.08,	88,	'Recebido',	'2025-04-20 00:00:00',	NULL),
(23,	20,	10,	'cheque',	7150.00,	'2025-05-07',	6232.89,	88.73,	6144.16,	105,	'Recebido',	'2025-05-07 00:00:00',	NULL),
(24,	20,	10,	'cheque',	5750.00,	'2025-05-07',	5012.46,	71.36,	4941.11,	105,	'Recebido',	'2025-05-07 00:00:00',	NULL),
(25,	20,	10,	'cheque',	10000.00,	'2025-05-20',	8570.42,	134.76,	8435.66,	118,	'Recebido',	'2025-05-20 00:00:00',	NULL),
(26,	20,	10,	'cheque',	5000.00,	'2025-06-04',	4202.00,	73.53,	4128.47,	133,	'Recebido',	'2025-06-04 00:00:00',	NULL),
(27,	20,	10,	'cheque',	7150.00,	'2025-06-07',	5985.33,	106.91,	5878.43,	136,	'Recebido',	'2025-06-07 00:00:00',	NULL),
(28,	20,	10,	'cheque',	5750.00,	'2025-06-07',	4813.38,	85.97,	4727.40,	136,	'Recebido',	'2025-06-07 00:00:00',	NULL),
(29,	20,	10,	'cheque',	10000.00,	'2025-06-20',	8230.02,	160.18,	8069.84,	149,	'Recebido',	'2025-06-20 00:00:00',	NULL),
(30,	20,	10,	'cheque',	5000.00,	'2025-07-04',	4040.38,	85.83,	3954.55,	163,	'Recebido',	'2025-07-04 00:00:00',	NULL),
(31,	20,	10,	'cheque',	7150.00,	'2025-07-07',	5755.13,	124.50,	5630.63,	166,	'Recebido',	'2025-07-06 00:00:00',	NULL),
(32,	20,	10,	'cheque',	5750.00,	'2025-07-07',	4628.25,	100.12,	4528.13,	166,	'Recebido',	'2025-07-07 00:00:00',	NULL),
(33,	20,	10,	'cheque',	7150.00,	'2025-08-07',	5526.55,	142.67,	5383.87,	197,	'Recebido',	'2025-08-09 00:00:00',	NULL),
(34,	20,	10,	'cheque',	5750.00,	'2025-08-07',	4444.43,	114.74,	4329.69,	197,	'Recebido',	'2025-08-09 00:00:00',	NULL),
(35,	20,	10,	'cheque',	5750.00,	'2025-09-07',	4267.90,	129.35,	4138.55,	228,	'Em Aberto',	NULL,	NULL),
(36,	20,	10,	'cheque',	5750.00,	'2025-10-07',	4103.75,	143.50,	3960.26,	258,	'Em Aberto',	NULL,	NULL),
(37,	21,	2,	'fatura',	60000.00,	'2025-05-10',	55658.84,	454.32,	55204.52,	46,	'Recebido',	'2025-05-10 00:00:00',	NULL),
(38,	22,	NULL,	'duplicata',	5750.00,	'2025-04-07',	5089.73,	57.21,	5032.52,	75,	'Recebido',	'2025-04-07 00:00:00',	NULL),
(49,	29,	1,	'fatura',	18623.00,	'2025-06-25',	17652.13,	116.58,	17535.55,	30,	'Recebido',	'2025-06-25 00:00:00',	NULL),
(50,	29,	2,	'fatura',	6338.00,	'2025-06-25',	6007.58,	39.68,	5967.91,	30,	'Recebido',	'2025-06-25 00:00:00',	NULL),
(51,	29,	NULL,	'duplicata',	5309.00,	'2025-06-25',	5032.23,	33.23,	4998.99,	30,	'Recebido',	'2025-06-25 00:00:00',	NULL),
(52,	30,	1,	'nota_fiscal',	56544.00,	'2025-07-17',	53500.64,	358.60,	53142.04,	31,	'Recebido',	'2025-07-08 00:00:00',	NULL),
(53,	30,	2,	'nota_fiscal',	17581.00,	'2025-07-12',	16783.84,	104.29,	16679.55,	26,	'Recebido',	'2025-07-08 00:00:00',	NULL),
(54,	30,	2,	'fatura',	27230.00,	'2025-07-16',	25810.43,	170.46,	25639.97,	30,	'Recebido',	'2025-07-08 00:00:00',	NULL),
(55,	31,	NULL,	'duplicata',	100000.00,	'2025-08-20',	92236.73,	732.60,	92236.73,	43,	'Compensado',	NULL,	NULL),
(56,	32,	6,	'cheque',	1004.00,	'2025-08-01',	1004.00,	4.72,	1004.00,	11,	'Recebido',	'2025-08-03 00:00:00',	NULL),
(57,	32,	6,	'cheque',	1690.00,	'2025-08-11',	1689.99,	9.33,	1689.99,	21,	'Recebido',	'2025-08-15 00:00:00',	NULL),
(58,	32,	6,	'cheque',	1004.00,	'2025-09-01',	1003.99,	7.27,	1003.99,	42,	'Em Aberto',	NULL,	NULL),
(59,	32,	6,	'cheque',	1403.00,	'2025-09-20',	1402.97,	12.35,	1402.97,	61,	'Em Aberto',	NULL,	NULL),
(60,	32,	6,	'cheque',	950.00,	'2025-10-25',	949.97,	11.09,	949.97,	96,	'Em Aberto',	NULL,	NULL),
(61,	32,	6,	'cheque',	950.00,	'2025-11-25',	949.96,	13.50,	949.96,	127,	'Em Aberto',	NULL,	NULL),
(62,	32,	6,	'cheque',	1202.40,	'2025-12-10',	1202.34,	18.57,	1202.34,	142,	'Em Aberto',	NULL,	NULL),
(63,	32,	6,	'cheque',	1403.00,	'2025-12-20',	1402.93,	22.82,	1402.93,	152,	'Em Aberto',	NULL,	NULL),
(64,	32,	6,	'cheque',	590.00,	'2025-12-25',	589.97,	9.84,	589.97,	157,	'Em Aberto',	NULL,	NULL),
(65,	32,	6,	'cheque',	1202.40,	'2026-01-10',	1202.33,	21.63,	1202.33,	173,	'Em Aberto',	NULL,	NULL),
(66,	32,	6,	'cheque',	1403.00,	'2026-01-20',	1402.91,	26.38,	1402.91,	183,	'Em Aberto',	NULL,	NULL),
(67,	32,	6,	'cheque',	590.00,	'2026-01-25',	589.96,	11.34,	589.96,	188,	'Em Aberto',	NULL,	NULL),
(68,	32,	6,	'cheque',	1202.40,	'2026-02-10',	1202.32,	24.68,	1202.32,	204,	'Em Aberto',	NULL,	NULL),
(69,	32,	6,	'cheque',	1403.00,	'2026-02-20',	1402.90,	29.95,	1402.90,	214,	'Em Aberto',	NULL,	NULL),
(70,	32,	6,	'cheque',	590.00,	'2026-02-25',	589.96,	12.84,	589.96,	219,	'Em Aberto',	NULL,	NULL),
(71,	32,	6,	'cheque',	4500.00,	'2026-03-20',	4499.64,	106.40,	4499.64,	242,	'Em Aberto',	NULL,	NULL),
(72,	32,	6,	'cheque',	7000.00,	'2026-04-15',	6999.37,	180.43,	6999.37,	268,	'Em Aberto',	NULL,	NULL),
(73,	32,	6,	'cheque',	4500.00,	'2026-05-20',	4499.55,	128.91,	4499.55,	303,	'Em Aberto',	NULL,	NULL),
(74,	32,	6,	'cheque',	7000.00,	'2026-06-15',	6999.23,	215.45,	6999.23,	329,	'Em Aberto',	NULL,	NULL),
(75,	33,	3,	'duplicata',	22530.00,	'2025-08-27',	21203.54,	148.43,	21055.11,	34,	'Em Aberto',	NULL,	NULL),
(98,	49,	1,	'duplicata',	24653.40,	'2025-08-25',	23577.61,	93.68,	23483.93,	25,	'Recebido',	'2025-08-22 00:00:00',	NULL),
(99,	49,	1,	'duplicata',	22000.00,	'2025-09-25',	19907.56,	83.60,	19823.96,	56,	'Em Aberto',	NULL,	NULL),
(100,	49,	1,	'duplicata',	22000.00,	'2025-10-25',	18869.73,	83.60,	18786.13,	86,	'Em Aberto',	NULL,	NULL),
(101,	50,	2,	'duplicata',	26100.00,	'2025-09-22',	24085.84,	99.18,	23986.66,	45,	'Em Aberto',	NULL,	NULL),
(102,	50,	2,	'duplicata',	32900.00,	'2025-09-18',	30578.60,	125.02,	30453.58,	41,	'Em Aberto',	NULL,	NULL),
(103,	50,	2,	'duplicata',	34570.00,	'2025-09-15',	32303.25,	131.37,	32171.89,	38,	'Em Aberto',	NULL,	NULL),
(104,	51,	4,	'duplicata',	7500.00,	'2025-08-30',	7384.36,	28.50,	7355.86,	8,	'Em Aberto',	NULL,	NULL),
(105,	51,	5,	'duplicata',	25000.00,	'2025-09-15',	23861.37,	95.00,	23766.37,	24,	'Em Aberto',	NULL,	NULL);

DROP TABLE IF EXISTS `sacados`;
CREATE TABLE `sacados` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `telefone` varchar(50) DEFAULT NULL,
  `tipo_pessoa` enum('FISICA','JURIDICA') NOT NULL DEFAULT 'JURIDICA',
  `documento_principal` varchar(18) DEFAULT NULL,
  `documento_secundario` varchar(14) DEFAULT NULL,
  `cpf` varchar(14) DEFAULT NULL,
  `cnpj` varchar(18) DEFAULT NULL,
  `documento_socio` varchar(14) DEFAULT NULL,
  `empresa` varchar(255) DEFAULT NULL,
  `endereco` text DEFAULT NULL,
  `cep` varchar(10) DEFAULT NULL,
  `logradouro` varchar(255) DEFAULT NULL,
  `numero` varchar(20) DEFAULT NULL,
  `complemento` varchar(100) DEFAULT NULL,
  `bairro` varchar(100) DEFAULT NULL,
  `cidade` varchar(100) DEFAULT NULL,
  `estado` varchar(2) DEFAULT NULL,
  `data_cadastro` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_documento_principal` (`documento_principal`),
  UNIQUE KEY `unique_cpf` (`cpf`),
  UNIQUE KEY `unique_cnpj` (`cnpj`),
  KEY `idx_nome` (`nome`),
  KEY `idx_empresa` (`empresa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `sacados` (`id`, `nome`, `email`, `telefone`, `tipo_pessoa`, `documento_principal`, `documento_secundario`, `cpf`, `cnpj`, `documento_socio`, `empresa`, `endereco`, `cep`, `logradouro`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `data_cadastro`) VALUES
(1,	'Empresa ABC Ltda',	'na@na.com',	'(19) 3438-1247',	'JURIDICA',	'55344469000130',	NULL,	NULL,	'12.345.678/0001-90',	NULL,	'AACP Associação dos Amigos do Colinas do Piracicaba',	'',	'13432-539',	'Rua Ibiúna',	'91',	'',	'Colinas do Piracicaba (Ártemis)',	'Piracicaba',	'SP',	'2025-08-08 15:41:38'),
(2,	'Comércio XYZ S.A.',	'na@na.com',	'(11) 88888-8888',	'JURIDICA',	'19998270000161',	NULL,	NULL,	'98.765.432/0001-10',	NULL,	'Carvalho Empreendimentos e Participacoes Spe Ltda',	'',	'13345-125',	'Avenida Francisco de Paula Leite',	'970',	'',	'Jardim Nely',	'Indaiatuba',	'SP',	'2025-08-08 15:41:38'),
(3,	'RIO PIRACICABA EMPREENDIMENTOS E PARTICIPACOES LTDA',	'',	'',	'JURIDICA',	'12429655000169',	NULL,	NULL,	NULL,	NULL,	'RIO PIRACICABA EMPREENDIMENTOS E PARTICIPACOES LTDA',	'',	'06543-001',	'Avenida Marcos Penteado de Ulhôa Rodrigues',	'4053',	'',	'Tamboré',	'Santana de Parnaíba',	'SP',	'2025-08-09 13:55:25'),
(4,	'VIPCOR-JUPIA EMPREENDIMENTOS IMOBILIARIOS SPE',	'',	'',	'JURIDICA',	'30082611000197',	NULL,	NULL,	NULL,	NULL,	'VIPCOR-JUPIA EMPREENDIMENTOS IMOBILIARIOS SPE',	'KM 158 MAIS 300 METROS, SALA 01',	'13423-170',	'Rodovia SP-308',	'km158',	'',	'Dois Córregos',	'Piracicaba',	'SP',	'2025-08-22 16:38:09'),
(5,	'RESIDENCIAL UNINORTE EMPREENDIMENTO IMOBILIARIO SPE LTDA',	'atendimento@hbrbrokers.com.br',	'(19) 3844-5654',	'JURIDICA',	'46712152000118',	NULL,	NULL,	NULL,	NULL,	'RESIDENCIAL UNINORTE EMPREENDIMENTO IMOBILIARIO SPE LTDA',	'Sala 01, Bairro Jardim Fortaleza, Paulínia/SP',	'13140-074',	'Avenida Aristóteles Costa',	'455',	'',	'Jardim Fortaleza',	'Paulínia',	'SP',	'2025-08-22 16:41:59'),
(6,	'Cheques Volvo',	'teste@empresa.com',	'(11) 99999-9999',	'JURIDICA',	'11111111000111',	NULL,	NULL,	NULL,	NULL,	'Cheques Volvo',	'',	'01234-567',	'Rua Teste',	'123',	'',	'Centro',	'São Paulo',	'SP',	'2025-08-27 00:27:58'),
(7,	'Arcanjo Terre e Locacoes',	'',	'',	'JURIDICA',	'11111111111111',	NULL,	NULL,	NULL,	NULL,	'Arcanjo Terre e Locacoes',	'',	'',	'',	'',	'',	'',	'',	'',	'2025-08-27 00:41:39'),
(10,	'Cheques - All Terra',	'',	'',	'JURIDICA',	'22222222222222',	NULL,	NULL,	NULL,	NULL,	'Cheques - All Terra',	'',	'',	'',	'',	'',	'',	'',	'',	'2025-08-27 00:43:37');

DROP TABLE IF EXISTS `sacados_socios`;
CREATE TABLE `sacados_socios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sacado_id` int(11) DEFAULT NULL,
  `nome` varchar(255) NOT NULL,
  `cpf` varchar(14) NOT NULL,
  `data_cadastro` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sacado_id` (`sacado_id`),
  KEY `idx_cpf` (`cpf`),
  CONSTRAINT `sacados_socios_ibfk_1` FOREIGN KEY (`sacado_id`) REFERENCES `sacados` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 2025-08-27 01:55:13
CREATE TABLE IF NOT EXISTS usuarios (id INT AUTO_INCREMENT PRIMARY KEY, email VARCHAR(255) NOT NULL UNIQUE, senha_hash VARCHAR(255) NOT NULL, criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO usuarios (email, senha_hash) VALUES ('admin', '$2y$10$JgK8wN0/l7O1nF2rNf1Rpe8BwP3gD.s0K0xJq3P4Q.P6Wq5Gq5q8i');
