-- Script SQL para corrigir a estrutura do banco de dados
-- Execute este script no ambiente online onde está ocorrendo o erro

-- 1. Criar tabela sacados se não existir
CREATE TABLE IF NOT EXISTS `sacados` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `empresa` varchar(255) NOT NULL,
    `documento_principal` varchar(20) DEFAULT NULL,
    `tipo_pessoa` enum('PF','PJ') DEFAULT 'PJ',
    `endereco` text,
    `telefone` varchar(20) DEFAULT NULL,
    `email` varchar(100) DEFAULT NULL,
    `observacoes` text,
    `data_cadastro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `ativo` tinyint(1) DEFAULT '1',
    PRIMARY KEY (`id`),
    UNIQUE KEY `documento_principal` (`documento_principal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Verificar se a coluna sacado_id existe na tabela recebiveis
-- Se não existir, adicionar a coluna
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'recebiveis' 
AND COLUMN_NAME = 'sacado_id';

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `recebiveis` ADD COLUMN `sacado_id` int(11) DEFAULT NULL', 
    'SELECT "Coluna sacado_id já existe" as status');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. Adicionar foreign key se não existir
SET @fk_exists = 0;
SELECT COUNT(*) INTO @fk_exists 
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'recebiveis' 
AND CONSTRAINT_NAME = 'fk_recebiveis_sacado';

SET @sql = IF(@fk_exists = 0, 
    'ALTER TABLE `recebiveis` ADD CONSTRAINT `fk_recebiveis_sacado` FOREIGN KEY (`sacado_id`) REFERENCES `sacados` (`id`) ON DELETE SET NULL', 
    'SELECT "Foreign key já existe" as status');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 4. Inserir alguns dados de exemplo se a tabela estiver vazia
INSERT IGNORE INTO `sacados` (`empresa`, `documento_principal`, `tipo_pessoa`) VALUES
('Empresa Exemplo 1', '12345678901', 'PJ'),
('Empresa Exemplo 2', '98765432100', 'PJ'),
('Cliente Pessoa Física', '12345678900', 'PF');

-- 5. Verificar se tudo está funcionando
SELECT 'Estrutura corrigida com sucesso!' as status;
SELECT COUNT(*) as total_sacados FROM sacados;
SELECT 
    TABLE_NAME,
    COLUMN_NAME,
    DATA_TYPE,
    IS_NULLABLE
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME IN ('sacados', 'recebiveis')
AND COLUMN_NAME IN ('id', 'sacado_id')
ORDER BY TABLE_NAME, COLUMN_NAME;