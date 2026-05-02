Warning: A partial dump from a server that has GTIDs will by default include the GTIDs of all transactions, even those that changed suppressed parts of the database. If you don't want to restore GTIDs, pass --set-gtid-purged=OFF. To make a complete dump, pass --all-databases --triggers --routines --events. 
Warning: A dump from a server that has GTIDs enabled will by default include the GTIDs of all transactions, even those that were executed during its extraction and might not be represented in the dumped data. This might result in an inconsistent data dump. 
In order to ensure a consistent backup of the database, pass --single-transaction or --lock-all-tables or --source-data. 
-- MySQL dump 10.13  Distrib 9.5.0, for macos26.1 (arm64)
--
-- Host: localhost    Database: rfqkezvjge
-- ------------------------------------------------------
-- Server version	9.5.0

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
SET @MYSQLDUMP_TEMP_LOG_BIN = @@SESSION.SQL_LOG_BIN;
SET @@SESSION.SQL_LOG_BIN= 0;

--
-- GTID state at the beginning of the backup 
--

SET @@GLOBAL.GTID_PURGED=/*!80000 '+'*/ '8bc205fe-eb02-11f0-bd3f-4db39ae9c315:1-26553';

--
-- Table structure for table `clientes`
--

DROP TABLE IF EXISTS `clientes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clientes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo_pessoa` enum('FISICA','JURIDICA') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'FISICA',
  `documento_principal` varchar(18) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `documento_secundario` varchar(14) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cpf` varchar(14) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cnpj` varchar(18) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `documento_socio` varchar(14) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `empresa` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `endereco` text COLLATE utf8mb4_unicode_ci,
  `cep` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logradouro` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `complemento` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bairro` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cidade` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_cadastro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `porte` enum('MEI','ME','EPP','MEDIO','GRANDE','PF') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `representante_nome` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `representante_cpf` varchar(14) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `representante_rg` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `representante_nacionalidade` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'brasileiro(a)',
  `representante_estado_civil` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `representante_profissao` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `representante_endereco` text COLLATE utf8mb4_unicode_ci,
  `conta_banco` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `conta_agencia` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `conta_numero` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `conta_pix` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `conta_tipo` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `conta_titular` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `conta_documento` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `whatsapp` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `anotacoes` text COLLATE utf8mb4_unicode_ci,
  `conta_pix_tipo` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cpf` (`cpf`),
  UNIQUE KEY `cnpj` (`cnpj`),
  UNIQUE KEY `documento_principal` (`documento_principal`),
  KEY `idx_nome` (`nome`),
  KEY `idx_empresa` (`empresa`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clientes`
--

LOCK TABLES `clientes` WRITE;
/*!40000 ALTER TABLE `clientes` DISABLE KEYS */;
INSERT INTO `clientes` VALUES (3,'All Terra Terraplenagem','administrativo@allterraplenagem.com.br','(19) 99986-0107','JURIDICA','49915558000196',NULL,NULL,NULL,NULL,'All Terra Terraplenagem','','13423-190','Avenida Doutor Alexandre Guimarães dos Santos','303','','Dois Córregos','Piracicaba','SP','2025-04-05 11:57:09','MEI','Bruno Tedesco','366.260.908-87','55555','brasileiro(a)','casado','Adm','Av 123 Pira','Banco itau','5189','003345','22723140822','Corrente','All Terra Terraplenagem','49.915.558/0001-96','','','CPF'),(14,'Cristian Weiser','cristianweiser@gmail.com','(19) 99898-9999','JURIDICA','00000000000000',NULL,NULL,NULL,NULL,'CRISWEISER','Avenida Dona Léddia, 1700','13405-235','Avenida Dona Lídia','1700','','Vila Rezende','Piracicaba','SP','2025-04-06 15:11:09',NULL,NULL,NULL,NULL,'brasileiro(a)',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(16,'C. V. BONASSOLI SOLUCOES','caiobonassoli@gmail.com','(15) 99821-1200','JURIDICA','35258176000122',NULL,NULL,NULL,NULL,'C. V. BONASSOLI SOLUCOES','Rua Sophia Dias, R. Carlos Menk, 711 (end alternativo)','18460-017','Rua Frei Caneca','1328','','Centro','Itararé','SP','2025-08-15 13:52:47',NULL,NULL,NULL,NULL,'brasileiro(a)',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(17,'Empresa ABC Ltda','na@na.com','(19) 3438-1247','JURIDICA','55344469000130',NULL,NULL,'12.345.678/0001-90',NULL,'AACP Associação dos Amigos do Colinas do Piracicaba','','13432-539','Rua Ibiúna','91','','Colinas do Piracicaba (Ártemis)','Piracicaba','SP','2025-08-08 15:41:38',NULL,NULL,NULL,NULL,'brasileiro(a)',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(18,'Carvalho Empreendimentos e Participacoes Spe Ltda','carvalho@teste.com','(11) 88888-8555','JURIDICA','19998270000161',NULL,NULL,'98.765.432/0001-10',NULL,'Carvalho Empreendimentos e Participacoes Spe Ltda','cobertura do predio comercial','13345-125','Avenida Francisco de Paula Leite','970','casaaaa','Jardim Nely','Indaiatuba','SP','2025-08-08 15:41:38','ME','Bruno TedTed','366.260.908-87','43423432423','brasileiro(a)','Casado(a)','GARI','Rua Acme 123344','ITub','42422','4242422','pix@pix.net','Corrente','Carvalho Empreendimentos e Participacoes Spe Ltda','19998270000161','(32) 43434-3434','',''),(19,'RIO PIRACICABA EMPREENDIMENTOS E PARTICIPACOES LTDA','','','JURIDICA','12429655000169',NULL,NULL,NULL,NULL,'RIO PIRACICABA EMPREENDIMENTOS E PARTICIPACOES LTDA','','06543-001','Avenida Marcos Penteado de Ulhôa Rodrigues','4053','','Tamboré','Santana de Parnaíba','SP','2025-08-09 13:55:25',NULL,NULL,NULL,NULL,'brasileiro(a)',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(20,'VIPCOR-JUPIA EMPREENDIMENTOS IMOBILIARIOS SPE','','','JURIDICA','30082611000197',NULL,NULL,NULL,NULL,'VIPCOR-JUPIA EMPREENDIMENTOS IMOBILIARIOS SPE','KM 158 MAIS 300 METROS, SALA 01','13423-170','Rodovia SP-308','km158','','Dois Córregos','Piracicaba','SP','2025-08-22 16:38:09',NULL,NULL,NULL,NULL,'brasileiro(a)',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(21,'RESIDENCIAL UNINORTE EMPREENDIMENTO IMOBILIARIO SPE LTDA','atendimento@hbrbrokers.com.br','(19) 3844-5654','JURIDICA','46712152000118',NULL,NULL,NULL,NULL,'RESIDENCIAL UNINORTE EMPREENDIMENTO IMOBILIARIO SPE LTDA','advogada','13140-074','Rua Teste','123','','Centro','Sao Paulo','SP','2025-08-22 16:41:59',NULL,'Joao','111','222','brasileiro(a)',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(22,'TESTE','a@a.com','111','JURIDICA','55344469000139',NULL,NULL,NULL,NULL,'TESTE','','','','','','','','','2026-04-29 20:10:31',NULL,'','','','','','','','','','','','','','','111',NULL,NULL);
/*!40000 ALTER TABLE `clientes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `operacoes`
--

DROP TABLE IF EXISTS `operacoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `operacoes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cedente_id` int DEFAULT NULL,
  `taxa_mensal` decimal(10,4) NOT NULL,
  `data_operacao` datetime DEFAULT NULL,
  `data_base_calculo` date DEFAULT NULL,
  `tipo_pagamento` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'direto',
  `tipo_operacao` enum('antecipacao','emprestimo') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'antecipacao',
  `tem_garantia` tinyint(1) NOT NULL DEFAULT '0',
  `descricao_garantia` text COLLATE utf8mb4_unicode_ci,
  `total_original_calc` decimal(15,2) DEFAULT NULL,
  `total_presente_calc` decimal(15,2) DEFAULT NULL,
  `iof_total_calc` decimal(15,2) DEFAULT NULL,
  `total_liquido_pago_calc` decimal(15,2) DEFAULT NULL,
  `incorre_custo_iof` tinyint(1) NOT NULL,
  `cobrar_iof_cliente` tinyint(1) NOT NULL,
  `total_lucro_liquido_calc` decimal(15,2) DEFAULT NULL,
  `media_dias_pond_calc` int DEFAULT NULL,
  `notas` text COLLATE utf8mb4_unicode_ci,
  `valor_total_compensacao` decimal(15,2) DEFAULT '0.00' COMMENT 'Valor total das compensações aplicadas nesta operação',
  `status_contrato` enum('pendente','aguardando_assinatura','assinado') COLLATE utf8mb4_unicode_ci DEFAULT 'pendente',
  `natureza` enum('EMPRESTIMO','DESCONTO') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'DESCONTO',
  `valor_principal` decimal(15,2) DEFAULT NULL,
  `valor_total_devido` decimal(15,2) DEFAULT NULL,
  `taxa_juros_mensal` decimal(6,4) DEFAULT NULL,
  `taxa_juros_anual` decimal(6,4) DEFAULT NULL,
  `cet_mensal` decimal(6,4) DEFAULT NULL,
  `num_parcelas` int DEFAULT NULL,
  `valor_parcela` decimal(15,2) DEFAULT NULL,
  `data_primeiro_vencimento` date DEFAULT NULL,
  `periodicidade` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'mensais',
  `taxa_desagio_mensal` decimal(6,4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_operacao_sacado` (`cedente_id`),
  CONSTRAINT `fk_operacoes_cliente` FOREIGN KEY (`cedente_id`) REFERENCES `clientes` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=60 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `operacoes`
--

LOCK TABLES `operacoes` WRITE;
/*!40000 ALTER TABLE `operacoes` DISABLE KEYS */;
INSERT INTO `operacoes` VALUES (17,3,0.0500,'2025-02-28 00:00:00','2025-02-28','indireto','antecipacao',0,NULL,25000.00,23720.45,161.63,23558.82,0,1,1441.18,33,'',0.00,'aguardando_assinatura','EMPRESTIMO',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'mensais',NULL),(20,3,0.0400,'2025-01-22 00:00:00','2025-01-22','cheque','antecipacao',0,NULL,110250.00,91208.33,1743.44,89464.88,0,1,20785.12,147,'arredondando',0.00,'pendente','DESCONTO',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'mensais',NULL),(21,3,0.0502,'2025-03-25 00:00:00','2025-03-25','indireto','antecipacao',0,NULL,60000.00,55658.84,454.32,55204.52,0,1,4795.48,46,'fatura 60k',0.00,'pendente','DESCONTO',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'mensais',NULL),(22,3,0.0500,'2025-01-22 00:00:00','2025-01-22','indireto','antecipacao',0,NULL,5750.00,5089.73,57.21,5032.52,0,1,717.48,75,'',0.00,'pendente','DESCONTO',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'mensais',NULL),(29,3,0.0550,'2025-05-26 00:00:00','2025-05-26','indireto','antecipacao',0,NULL,30270.00,28691.94,189.49,28502.45,0,1,1767.55,30,'',0.00,'pendente','DESCONTO',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'mensais',NULL),(30,3,0.0550,'2025-06-16 00:00:00','2025-06-16','indireto','antecipacao',0,NULL,101355.00,95709.76,0.00,95709.76,0,1,5645.24,26,'56k colinas, 17k e 27k carvalho.',0.00,'pendente','DESCONTO',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'mensais',NULL),(31,3,0.0580,'2025-07-08 00:00:00','2025-07-08','indireto','antecipacao',0,NULL,100000.00,92236.73,0.00,92236.73,0,1,7763.27,NULL,'',0.00,'pendente','DESCONTO',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'mensais',NULL),(32,16,0.0000,'2025-07-11 00:00:00','2025-07-11','cheque','antecipacao',0,NULL,39587.20,39584.29,867.50,39587.20,0,0,0.00,221,'Venda Volvo',0.00,'pendente','DESCONTO',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'mensais',NULL),(33,3,0.0550,'2025-07-24 00:00:00','2025-08-01','indireto','antecipacao',0,NULL,22530.00,21203.54,148.43,21546.87,0,1,983.13,34,'',0.00,'pendente','DESCONTO',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'mensais',NULL),(49,3,0.0550,'2025-08-05 14:20:42',NULL,'escrow','antecipacao',0,NULL,68653.40,NULL,260.88,12354.90,0,1,5642.75,54,'',50000.00,'pendente','DESCONTO',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'mensais',NULL),(50,3,0.0550,'2025-08-09 12:40:14',NULL,'escrow','antecipacao',0,NULL,93570.00,NULL,355.57,36612.12,0,1,6563.39,41,'escrow informada na fatura - nao é boleto',50000.00,'pendente','DESCONTO',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'mensais',NULL),(51,3,0.0600,'2025-08-22 16:42:48','2025-08-22','indireto','antecipacao',0,NULL,32500.00,NULL,123.50,31122.23,0,1,1377.77,20,'',0.00,'aguardando_assinatura','EMPRESTIMO',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'mensais',NULL),(53,NULL,0.0550,'2026-04-22 00:00:00',NULL,'direto','emprestimo',0,'',7582.66,NULL,28.81,7000.00,0,0,582.66,46,'',0.00,'assinado','DESCONTO',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'mensais',NULL),(55,NULL,0.0550,'2026-04-22 00:00:00',NULL,'direto','emprestimo',0,'',5416.18,NULL,20.58,5000.00,0,0,416.18,46,'',0.00,'pendente','DESCONTO',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'mensais',NULL),(56,NULL,0.0550,'2026-04-22 00:00:00',NULL,'direto','emprestimo',0,'',3706.54,NULL,14.08,10000.00,0,0,0.00,30,'',0.00,'pendente','DESCONTO',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'mensais',NULL),(57,NULL,0.0550,'2026-04-22 00:00:00',NULL,'direto','emprestimo',0,NULL,5000.00,10000.00,19.00,10000.00,0,0,0.00,46,'',0.00,'pendente','DESCONTO',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'mensais',NULL),(58,NULL,0.0550,'2026-04-22 00:00:00',NULL,'direto','emprestimo',1,'',90157.95,77000.00,342.60,77000.00,0,0,13157.95,91,'',0.00,'pendente','DESCONTO',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'mensais',NULL),(59,NULL,0.0550,'2026-04-23 00:00:00',NULL,'direto','emprestimo',1,'',58544.10,50000.00,222.47,50000.00,0,0,8544.10,91,'',0.00,'pendente','EMPRESTIMO',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'mensais',NULL);
/*!40000 ALTER TABLE `operacoes` ENABLE KEYS */;
UNLOCK TABLES;
SET @@SESSION.SQL_LOG_BIN = @MYSQLDUMP_TEMP_LOG_BIN;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-01 18:18:58
