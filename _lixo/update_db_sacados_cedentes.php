<?php
require_once 'db_connection.php';

try {
    echo "Iniciando atualização do banco de dados...\n";

    // Atualizando tabela sacados
    echo "Verificando tabela 'sacados'...\n";
    
    // Coluna anotacoes
    $stmt = $pdo->query("SHOW COLUMNS FROM sacados LIKE 'anotacoes'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE sacados ADD COLUMN anotacoes TEXT NULL");
        echo "Coluna 'anotacoes' adicionada na tabela 'sacados'.\n";
    } else {
        echo "Coluna 'anotacoes' já existe na tabela 'sacados'.\n";
    }

    // Coluna conta_pix_tipo
    $stmt = $pdo->query("SHOW COLUMNS FROM sacados LIKE 'conta_pix_tipo'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE sacados ADD COLUMN conta_pix_tipo VARCHAR(50) NULL");
        echo "Coluna 'conta_pix_tipo' adicionada na tabela 'sacados'.\n";
    } else {
        echo "Coluna 'conta_pix_tipo' já existe na tabela 'sacados'.\n";
    }

    // Atualizando tabela cedentes
    echo "Verificando tabela 'cedentes'...\n";

    // Coluna anotacoes
    $stmt = $pdo->query("SHOW COLUMNS FROM cedentes LIKE 'anotacoes'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE cedentes ADD COLUMN anotacoes TEXT NULL");
        echo "Coluna 'anotacoes' adicionada na tabela 'cedentes'.\n";
    } else {
        echo "Coluna 'anotacoes' já existe na tabela 'cedentes'.\n";
    }

    // Coluna conta_pix_tipo
    $stmt = $pdo->query("SHOW COLUMNS FROM cedentes LIKE 'conta_pix_tipo'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE cedentes ADD COLUMN conta_pix_tipo VARCHAR(50) NULL");
        echo "Coluna 'conta_pix_tipo' adicionada na tabela 'cedentes'.\n";
    } else {
        echo "Coluna 'conta_pix_tipo' já existe na tabela 'cedentes'.\n";
    }

    echo "Atualização concluída com sucesso!\n";

} catch (PDOException $e) {
    echo "Erro ao atualizar banco de dados: " . $e->getMessage() . "\n";
}
