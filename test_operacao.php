<?php
require "db_connection.php";
$stmt = $pdo->query("SHOW COLUMNS FROM operacoes");
print_r($stmt->fetchAll(PDO::FETCH_COLUMN));
