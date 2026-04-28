<?php
require "db_connection.php";
$stmt = $pdo->query("SHOW COLUMNS FROM sacados");
print_r($stmt->fetchAll(PDO::FETCH_COLUMN));
