<?php
require_once 'db_connection.php';

echo "Iniciando configuração das tabelas de contratos...\n";

$queries = [
    // 1. contract_templates
    "CREATE TABLE IF NOT EXISTS contract_templates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(50) NOT NULL UNIQUE,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        template_content LONGTEXT NOT NULL,
        version VARCHAR(20) DEFAULT '1.0',
        active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",

    // 2. generated_contracts
    "CREATE TABLE IF NOT EXISTS generated_contracts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        operation_id INT NOT NULL,
        template_code VARCHAR(50) NOT NULL,
        file_path VARCHAR(500) NOT NULL,
        file_hash VARCHAR(64),
        status ENUM('generated', 'sent_to_signature', 'signed', 'cancelled') DEFAULT 'generated',
        signature_platform VARCHAR(50),
        signature_document_id VARCHAR(255),
        signed_at TIMESTAMP NULL,
        metadata JSON,
        created_by INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (operation_id) REFERENCES operacoes(id),
        INDEX idx_operation (operation_id),
        INDEX idx_status (status)
    )",

    // 3. master_cession_contracts
    "CREATE TABLE IF NOT EXISTS master_cession_contracts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        cedente_id INT NOT NULL,
        file_path VARCHAR(500),
        signed_at TIMESTAMP NULL,
        status ENUM('rascunho','ativo','encerrado') DEFAULT 'rascunho',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (cedente_id) REFERENCES cedentes(id)
    )",

    // 4. operation_vehicles
    "CREATE TABLE IF NOT EXISTS operation_vehicles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        operation_id INT NOT NULL,
        marca VARCHAR(50),
        modelo VARCHAR(100),
        ano_fab INT,
        ano_mod INT,
        cor VARCHAR(30),
        combustivel VARCHAR(30),
        chassi VARCHAR(17),
        placa VARCHAR(10),
        renavam VARCHAR(15),
        municipio_emplacamento VARCHAR(100),
        uf CHAR(2),
        valor_avaliacao DECIMAL(15,2),
        gravame_status ENUM('pendente','solicitado','registrado','cancelado') DEFAULT 'pendente',
        gravame_numero VARCHAR(50),
        gravame_data DATE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (operation_id) REFERENCES operacoes(id)
    )",

    // 5. operation_guarantors
    "CREATE TABLE IF NOT EXISTS operation_guarantors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        operation_id INT NOT NULL,
        nome VARCHAR(255) NOT NULL,
        cpf VARCHAR(14) NOT NULL,
        rg VARCHAR(30),
        nacionalidade VARCHAR(50) DEFAULT 'brasileiro(a)',
        estado_civil VARCHAR(30),
        profissao VARCHAR(100),
        endereco TEXT,
        email VARCHAR(100),
        whatsapp VARCHAR(20),
        casado TINYINT(1) DEFAULT 0,
        regime_casamento VARCHAR(50),
        conjuge_nome VARCHAR(255),
        conjuge_cpf VARCHAR(14),
        tipo ENUM('AVALISTA','FIADOR','CONJUGE_ANUENTE') DEFAULT 'AVALISTA',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (operation_id) REFERENCES operacoes(id)
    )",

    // 6. operation_witnesses
    "CREATE TABLE IF NOT EXISTS operation_witnesses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        operation_id INT NOT NULL,
        ordem TINYINT NOT NULL,
        nome VARCHAR(255) NOT NULL,
        cpf VARCHAR(14) NOT NULL,
        email VARCHAR(100),
        FOREIGN KEY (operation_id) REFERENCES operacoes(id)
    )"
];

foreach ($queries as $i => $sql) {
    try {
        $pdo->exec($sql);
        echo "Tabela criada com sucesso (" . ($i+1) . "/" . count($queries) . ").\n";
    } catch (PDOException $e) {
        echo "Erro ao criar tabela (" . ($i+1) . "): " . $e->getMessage() . "\n";
    }
}

// Alterações na tabela cedentes
$alterCedentes = [
    "ADD COLUMN tipo_pessoa ENUM('PF','PJ') NOT NULL DEFAULT 'PJ'",
    "ADD COLUMN porte ENUM('MEI','ME','EPP','MEDIO','GRANDE','PF') NULL",
    "ADD COLUMN representante_nome VARCHAR(255)",
    "ADD COLUMN representante_cpf VARCHAR(14)",
    "ADD COLUMN representante_rg VARCHAR(30)",
    "ADD COLUMN representante_nacionalidade VARCHAR(50) DEFAULT 'brasileiro(a)'",
    "ADD COLUMN representante_estado_civil VARCHAR(30)",
    "ADD COLUMN representante_profissao VARCHAR(100)",
    "ADD COLUMN representante_endereco TEXT"
];

echo "\nAtualizando tabela 'cedentes'...\n";
foreach ($alterCedentes as $alter) {
    try {
        $pdo->exec("ALTER TABLE cedentes $alter");
        echo "Coluna adicionada em cedentes: " . explode(" ", $alter)[2] . "\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false || strpos($e->getMessage(), '1060') !== false) {
            echo "Coluna já existe em cedentes: " . explode(" ", $alter)[2] . "\n";
        } else {
            echo "Erro ao alterar cedentes: " . $e->getMessage() . "\n";
        }
    }
}

// Alterações na tabela operacoes
$alterOperacoes = [
    "ADD COLUMN natureza ENUM('EMPRESTIMO','DESCONTO') NOT NULL DEFAULT 'DESCONTO'",
    "ADD COLUMN valor_principal DECIMAL(15,2)",
    "ADD COLUMN valor_total_devido DECIMAL(15,2)",
    "ADD COLUMN taxa_juros_mensal DECIMAL(6,4)",
    "ADD COLUMN taxa_juros_anual DECIMAL(6,4)",
    "ADD COLUMN cet_mensal DECIMAL(6,4)",
    "ADD COLUMN num_parcelas INT",
    "ADD COLUMN valor_parcela DECIMAL(15,2)",
    "ADD COLUMN data_primeiro_vencimento DATE",
    "ADD COLUMN periodicidade VARCHAR(20) DEFAULT 'mensais'",
    "ADD COLUMN taxa_desagio_mensal DECIMAL(6,4)"
];

echo "\nAtualizando tabela 'operacoes'...\n";
foreach ($alterOperacoes as $alter) {
    try {
        $pdo->exec("ALTER TABLE operacoes $alter");
        echo "Coluna adicionada em operacoes: " . explode(" ", $alter)[2] . "\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false || strpos($e->getMessage(), '1060') !== false) {
            echo "Coluna já existe em operacoes: " . explode(" ", $alter)[2] . "\n";
        } else {
            echo "Erro ao alterar operacoes: " . $e->getMessage() . "\n";
        }
    }
}

echo "\nSetup finalizado com sucesso!\n";
