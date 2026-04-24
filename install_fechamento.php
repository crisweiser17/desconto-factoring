<?php
require_once 'db_connection.php';

try {
    echo "Criando tabela de despesas...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `despesas` (
            `id` int NOT NULL AUTO_INCREMENT,
            `titulo` varchar(255) NOT NULL,
            `descricao` text,
            `valor` decimal(15,2) NOT NULL,
            `data_despesa` date NOT NULL,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "Tabela de despesas criada com sucesso!\n\n";

    echo "Criando tabela de distribuicao_lucros...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `distribuicao_lucros` (
            `id` int NOT NULL AUTO_INCREMENT,
            `socio_nome` varchar(255) NOT NULL,
            `valor` decimal(15,2) NOT NULL,
            `data` date NOT NULL,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "Tabela de distribuicao_lucros criada com sucesso!\n\n";

    echo "Instalação do Fechamento concluída com sucesso!";
} catch (PDOException $e) {
    echo "Erro ao criar tabelas: " . $e->getMessage();
}
