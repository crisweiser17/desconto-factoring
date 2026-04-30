-- Script para criar tabela de arquivos das operações
-- Data: 2025-08-09
-- Versão: 1.0

-- Criar tabela para armazenar arquivos das operações
CREATE TABLE IF NOT EXISTS `operacao_arquivos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `operacao_id` int NOT NULL COMMENT 'ID da operação à qual o arquivo pertence',
  `nome_original` varchar(255) NOT NULL COMMENT 'Nome original do arquivo enviado pelo usuário',
  `nome_arquivo` varchar(255) NOT NULL COMMENT 'Nome único do arquivo no servidor',
  `tipo_arquivo` varchar(100) NOT NULL COMMENT 'Tipo MIME do arquivo',
  `extensao` varchar(10) NOT NULL COMMENT 'Extensão do arquivo',
  `tamanho_bytes` bigint NOT NULL COMMENT 'Tamanho do arquivo em bytes',
  `caminho_arquivo` varchar(500) NOT NULL COMMENT 'Caminho completo do arquivo no servidor',
  `data_upload` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Data e hora do upload',
  `usuario_upload` varchar(100) DEFAULT NULL COMMENT 'Usuário que fez o upload',
  `descricao` text COMMENT 'Descrição opcional do arquivo',
  `ativo` tinyint(1) DEFAULT 1 COMMENT 'Se o arquivo está ativo (não foi excluído)',
  PRIMARY KEY (`id`),
  KEY `idx_operacao_id` (`operacao_id`),
  KEY `idx_data_upload` (`data_upload`),
  KEY `idx_ativo` (`ativo`),
  KEY `idx_tipo_arquivo` (`tipo_arquivo`),
  CONSTRAINT `fk_arquivo_operacao` FOREIGN KEY (`operacao_id`) REFERENCES `operacoes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabela para armazenar arquivos anexados às operações';

-- Criar diretório de uploads (será criado via PHP)
-- uploads/operacoes/{operacao_id}/

-- Tipos de arquivo permitidos (será validado via PHP):
-- PDF: application/pdf
-- Imagens: image/jpeg, image/png, image/gif, image/webp
-- Documentos: application/msword, application/vnd.openxmlformats-officedocument.wordprocessingml.document
-- Planilhas: application/vnd.ms-excel, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet
-- Texto: text/plain

-- Tamanho máximo por arquivo: 10MB (será validado via PHP)
-- Máximo de arquivos por operação: 20 (será validado via PHP)