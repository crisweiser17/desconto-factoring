<?php
require "db_connection.php";
$stmt = $pdo->query("SHOW COLUMNS FROM cedentes");
print_r(array_filter($stmt->fetchAll(PDO::FETCH_COLUMN), fn($c) => in_array($c, ['email', 'whatsapp'])));
$stmt = $pdo->query("SHOW COLUMNS FROM sacados");
print_r(array_filter($stmt->fetchAll(PDO::FETCH_COLUMN), fn($c) => in_array($c, ['email', 'whatsapp'])));
