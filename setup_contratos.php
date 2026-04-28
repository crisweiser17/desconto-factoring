<?php
require_once 'db_connection.php';

try {
    // 1. Add status_contrato to operacoes if it doesn't exist
    $colExists = false;
    $stmt = $pdo->query("SHOW COLUMNS FROM operacoes LIKE 'status_contrato'");
    if ($stmt->rowCount() > 0) {
        $colExists = true;
    }

    if (!$colExists) {
        $pdo->exec("
            ALTER TABLE operacoes 
            ADD COLUMN status_contrato ENUM('pendente', 'aguardando_assinatura', 'assinado') DEFAULT 'pendente'
        ");
        echo "Coluna 'status_contrato' adicionada à tabela operacoes.\n";
    } else {
        echo "Coluna 'status_contrato' já existe.\n";
    }

    // 2. Create operacao_documentos table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS operacao_documentos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            operacao_id INT NOT NULL,
            nome_arquivo VARCHAR(255) NOT NULL,
            caminho_arquivo VARCHAR(500) NOT NULL,
            is_assinado TINYINT(1) DEFAULT 0,
            data_geracao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (operacao_id) REFERENCES operacoes(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "Tabela 'operacao_documentos' criada com sucesso.\n";

} catch (PDOException $e) {
    echo "Erro ao configurar banco de dados: " . $e->getMessage() . "\n";
}
