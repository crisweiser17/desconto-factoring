<?php
require_once 'db_connection.php';

try {
    $pdo->beginTransaction();

    // Cria a tabela de usuários se não existir
    $sql = "CREATE TABLE IF NOT EXISTS usuarios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL UNIQUE,
        senha_hash VARCHAR(255) NOT NULL,
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $pdo->exec($sql);

    // Verifica se já existe o usuário admin
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = 'admin'");
    $stmt->execute();
    
    if (!$stmt->fetch()) {
        // Insere o usuário padrão com a senha 'Qazwsx123@' hasheada
        $senha = 'Qazwsx123@';
        $hash = password_hash($senha, PASSWORD_DEFAULT);
        
        $stmtInsert = $pdo->prepare("INSERT INTO usuarios (email, senha_hash) VALUES ('admin', :hash)");
        $stmtInsert->bindParam(':hash', $hash);
        $stmtInsert->execute();
        echo "Usuário 'admin' criado com sucesso.\n";
    } else {
        echo "Usuário 'admin' já existe.\n";
    }

    $pdo->commit();
    echo "Tabela de usuários verificada/criada com sucesso.\n";

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Erro ao criar tabela de usuários: " . $e->getMessage() . "\n";
}
?>
