<?php
require "db_connection.php";

$tables = ['cedentes', 'sacados'];

$columns_to_add = [
    'casado' => 'TINYINT(1) DEFAULT 0',
    'regime_casamento' => 'VARCHAR(100) DEFAULT NULL',
    'conjuge_nome' => 'VARCHAR(255) DEFAULT NULL',
    'conjuge_cpf' => 'VARCHAR(20) DEFAULT NULL',
    'conjuge_rg' => 'VARCHAR(50) DEFAULT NULL',
    'conjuge_nacionalidade' => 'VARCHAR(100) DEFAULT NULL',
    'conjuge_profissao' => 'VARCHAR(150) DEFAULT NULL',
    
    'conta_banco' => 'VARCHAR(100) DEFAULT NULL',
    'conta_agencia' => 'VARCHAR(50) DEFAULT NULL',
    'conta_numero' => 'VARCHAR(50) DEFAULT NULL',
    'conta_pix' => 'VARCHAR(255) DEFAULT NULL',
    'conta_tipo' => 'VARCHAR(50) DEFAULT NULL',
    'conta_titular' => 'VARCHAR(255) DEFAULT NULL',
    'conta_documento' => 'VARCHAR(50) DEFAULT NULL',
    
    'whatsapp' => 'VARCHAR(50) DEFAULT NULL'
];

foreach ($tables as $table) {
    echo "Atualizando tabela: $table\n";
    foreach ($columns_to_add as $column => $definition) {
        try {
            $stmt = $pdo->prepare("ALTER TABLE $table ADD COLUMN $column $definition");
            $stmt->execute();
            echo "  - Coluna $column adicionada com sucesso.\n";
        } catch (PDOException $e) {
            if ($e->getCode() == '42S21') { // 42S21: Duplicate column name
                echo "  - Coluna $column já existe.\n";
            } else {
                echo "  - Erro ao adicionar coluna $column: " . $e->getMessage() . "\n";
            }
        }
    }
}
echo "Atualização do banco de dados concluída.\n";