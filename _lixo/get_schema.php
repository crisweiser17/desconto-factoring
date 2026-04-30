<?php
require_once 'db_connection.php';
$stmt = $pdo->query("DESCRIBE cedentes");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach($columns as $col) {
    echo $col['Field'] . " - " . $col['Type'] . "\n";
}
