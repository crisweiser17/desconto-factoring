<?php
// update_v3.php
// Script para aplicar as migrações referentes às atualizações de "Anotações em Operações"
// É idempotente, pode ser rodado múltiplas vezes sem quebrar o banco.

require_once 'db_connection.php';

echo "<h1>Atualização V3: Funcionalidade de Anotações em Operações</h1>";

try {
    echo "<h3>Tabela: operacao_anotacoes</h3><ul>";

    $sqlCreateTable = "
        CREATE TABLE IF NOT EXISTS `operacao_anotacoes` (
            `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
            `operacao_id` INT(11) NOT NULL,
            `recebivel_id` INT(11) NULL,
            `usuario_id` INT(11) NOT NULL,
            `anotacao` TEXT NOT NULL,
            `data_criacao` DATETIME NOT NULL,
            FOREIGN KEY (`operacao_id`) REFERENCES `operacoes`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`recebivel_id`) REFERENCES `recebiveis`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    $pdo->exec($sqlCreateTable);
    echo "<li>Tabela <b>operacao_anotacoes</b> criada/verificada com sucesso.</li>";
    
    echo "</ul>";
    
    echo "<h2 style='color: green;'>Atualização V3 concluída com sucesso!</h2>";
    echo "<p><a href='index.php'>Voltar para o Início</a></p>";

} catch (PDOException $e) {
    echo "<h2 style='color: red;'>Erro durante a atualização:</h2>";
    echo "<p><strong>Detalhes do banco de dados:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
} catch (Exception $e) {
    echo "<h2 style='color: red;'>Erro geral:</h2>";
    echo "<p><strong>Detalhes:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>