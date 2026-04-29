<?php
require_once 'db_connection.php';

$queries = [
    "ALTER TABLE sacados ADD COLUMN porte ENUM('MEI','ME','EPP','MEDIO','GRANDE','PF') NULL",
    "ALTER TABLE sacados ADD COLUMN representante_nome VARCHAR(255)",
    "ALTER TABLE sacados ADD COLUMN representante_cpf VARCHAR(14)",
    "ALTER TABLE sacados ADD COLUMN representante_rg VARCHAR(30)",
    "ALTER TABLE sacados ADD COLUMN representante_estado_civil VARCHAR(50)",
    "ALTER TABLE sacados ADD COLUMN representante_profissao VARCHAR(100)",
    "ALTER TABLE sacados ADD COLUMN representante_nacionalidade VARCHAR(50) DEFAULT 'brasileiro(a)'",
    "ALTER TABLE sacados ADD COLUMN representante_endereco TEXT"
];

foreach ($queries as $sql) {
    try {
        $pdo->exec($sql);
        echo "Sucesso: $sql\n";
    } catch (PDOException $e) {
        if ($e->getCode() == '42S21') {
            echo "A coluna já existe.\n";
        } else {
            echo "Erro na query: $sql\n" . $e->getMessage() . "\n";
        }
    }
}
echo "Atualização concluída.\n";
?>