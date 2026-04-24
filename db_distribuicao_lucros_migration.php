<?php
require_once 'db_connection.php';

try {
    $sql = "
    CREATE TABLE IF NOT EXISTS distribuicao_lucros (
        id INT AUTO_INCREMENT PRIMARY KEY,
        socio_nome VARCHAR(255) NOT NULL,
        valor DECIMAL(15,2) NOT NULL,
        data DATE NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    $pdo->exec($sql);
    echo "Tabela 'distribuicao_lucros' criada ou já existente com sucesso.\n";
} catch (PDOException $e) {
    echo "Erro ao criar tabela: " . $e->getMessage() . "\n";
}
