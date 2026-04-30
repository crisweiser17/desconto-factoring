-- Script para corrigir o problema da data_operacao sendo atualizada automaticamente
-- Remove o ON UPDATE current_timestamp() do campo data_operacao

USE rfqkezvjge;

-- Alterar a coluna data_operacao para remover o ON UPDATE current_timestamp()
ALTER TABLE operacoes 
MODIFY COLUMN data_operacao datetime DEFAULT NULL;

-- Verificar a estrutura da tabela após a alteração
DESCRIBE operacoes;