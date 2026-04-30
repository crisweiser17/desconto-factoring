<?php require "db_connection.php"; $stmt = $pdo->query("DESCRIBE operation_vehicles"); print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
