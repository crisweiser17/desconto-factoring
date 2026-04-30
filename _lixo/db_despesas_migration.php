<?php
require_once 'db_connection.php';

$sql = "
CREATE TABLE IF NOT EXISTS despesas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT,
    valor DECIMAL(15, 2) NOT NULL,
    data_despesa DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
";

try {
    $pdo->exec($sql);
    echo "Tabela despesas criada com sucesso.";
} catch (PDOException $e) {
    echo "Erro ao criar tabela despesas: " . $e->getMessage();
}
