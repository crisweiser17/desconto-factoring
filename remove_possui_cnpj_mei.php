<?php
require_once 'db_connection.php';

$queries = [
    "ALTER TABLE sacados DROP COLUMN possui_cnpj_mei",
    "ALTER TABLE cedentes DROP COLUMN possui_cnpj_mei"
];

foreach ($queries as $sql) {
    try {
        $pdo->exec($sql);
        echo "Sucesso: $sql\n";
    } catch (PDOException $e) {
        if ($e->getCode() == '42000' || strpos($e->getMessage(), "check that column/key exists") !== false || strpos($e->getMessage(), "Can't DROP") !== false) {
            echo "A coluna já foi removida ou não existe.\n";
        } else {
            echo "Erro na query: $sql\n" . $e->getMessage() . "\n";
        }
    }
}
echo "Atualização concluída.\n";
?>