<?php
require "db_connection.php";
$stmt = $pdo->query("SELECT id, tipo_operacao, natureza FROM operacoes ORDER BY id DESC LIMIT 5");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
