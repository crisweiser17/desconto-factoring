<?php
require_once 'db_connection.php';

try {
    $sql = "ALTER TABLE `cedentes`
            ADD COLUMN `banco` varchar(100) DEFAULT NULL AFTER `estado`,
            ADD COLUMN `agencia` varchar(20) DEFAULT NULL AFTER `banco`,
            ADD COLUMN `conta` varchar(30) DEFAULT NULL AFTER `agencia`,
            ADD COLUMN `tipo_conta` varchar(20) DEFAULT NULL AFTER `conta`,
            ADD COLUMN `chave_pix` varchar(100) DEFAULT NULL AFTER `tipo_conta`;";
    $pdo->exec($sql);
    echo "Sucesso: Colunas adicionadas à tabela cedentes.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Aviso: As colunas já existem.\n";
    } else {
        echo "Erro ao atualizar banco: " . $e->getMessage() . "\n";
    }
}
