<?php
require_once 'db_connection.php';
$stmt = $pdo->query("SELECT id, caminho_arquivo, nome_arquivo, operacao_id FROM operacao_arquivos LIMIT 5");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
