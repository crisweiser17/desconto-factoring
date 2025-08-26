<?php
// db_connection.php

// --- Substitua pelos seus detalhes ---
$db_host = 'localhost'; // MySQL host
$db_name = 'rfqkezvjge'; // Nome do banco de dados que você criou
$db_user = 'root';      // Seu usuário do MySQL
$db_pass = '';          // Sua senha do MySQL (vazia para root local)
$charset = 'utf8mb4';
// ------------------------------------

// Opções do PDO
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Lança exceções em caso de erro
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Retorna resultados como array associativo
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Desabilita emulação de prepared statements (mais seguro)
];

// Data Source Name (DSN)
$dsn = "mysql:host=$db_host;port=3306;dbname=$db_name;charset=$charset";

try {
    // Cria a instância do PDO
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (\PDOException $e) {
    // Em caso de erro na conexão, exibe uma mensagem genérica e loga o erro real
    // Em produção, você não deveria exibir $e->getMessage() diretamente ao usuário
    error_log("Erro de Conexão BD: " . $e->getMessage()); // Loga o erro real
    // Você pode querer redirecionar para uma página de erro ou mostrar uma mensagem mais amigável
    die("Erro ao conectar com o banco de dados. Tente novamente mais tarde.");
}

// A variável $pdo está agora disponível para ser usada nos scripts que incluírem este arquivo.
?>
