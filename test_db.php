<?php
require "db_connection.php";
$tables = ['cedentes', 'sacados', 'operation_vehicles', 'operation_guarantors'];
foreach($tables as $t) {
    $stmt = $pdo->query("SHOW COLUMNS FROM $t");
    echo "--- $t ---\n";
    print_r($stmt->fetchAll(PDO::FETCH_COLUMN));
}
