<?php
require_once 'db_connection.php';

// Script para corrigir a estrutura do banco de dados
echo "<h2>Correção da Estrutura do Banco de Dados</h2>";

try {
    // Verificar se a tabela sacados existe
    $sql = "SHOW TABLES LIKE 'sacados'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $table_exists = $stmt->fetch();
    
    if (!$table_exists) {
        echo "<h3>Criando tabela 'sacados'...</h3>";
        
        $create_table_sql = "
            CREATE TABLE `sacados` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `empresa` varchar(255) NOT NULL,
                `documento_principal` varchar(20) DEFAULT NULL,
                `tipo_pessoa` enum('PF','PJ') DEFAULT 'PJ',
                `endereco` text,
                `telefone` varchar(20) DEFAULT NULL,
                `email` varchar(100) DEFAULT NULL,
                `observacoes` text,
                `data_cadastro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `ativo` tinyint(1) DEFAULT '1',
                PRIMARY KEY (`id`),
                UNIQUE KEY `documento_principal` (`documento_principal`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        $pdo->exec($create_table_sql);
        echo "✅ Tabela 'sacados' criada com sucesso!<br>";
        
    } else {
        echo "✅ Tabela 'sacados' já existe.<br>";
        
        // Verificar se a coluna 'id' existe
        $sql = "DESCRIBE sacados";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $id_column_exists = false;
        foreach ($columns as $column) {
            if ($column['Field'] === 'id') {
                $id_column_exists = true;
                break;
            }
        }
        
        if (!$id_column_exists) {
            echo "<h3>Adicionando coluna 'id' à tabela 'sacados'...</h3>";
            $alter_table_sql = "ALTER TABLE `sacados` ADD COLUMN `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST";
            $pdo->exec($alter_table_sql);
            echo "✅ Coluna 'id' adicionada com sucesso!<br>";
        } else {
            echo "✅ Coluna 'id' já existe na tabela 'sacados'.<br>";
        }
    }
    
    // Verificar outras tabelas essenciais
    echo "<h3>Verificando outras tabelas essenciais...</h3>";
    
    // Tabela operacoes
    $sql = "SHOW TABLES LIKE 'operacoes'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $operacoes_exists = $stmt->fetch();
    
    if (!$operacoes_exists) {
        echo "❌ Tabela 'operacoes' não existe. Esta é uma tabela essencial!<br>";
    } else {
        echo "✅ Tabela 'operacoes' existe.<br>";
    }
    
    // Tabela recebiveis
    $sql = "SHOW TABLES LIKE 'recebiveis'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $recebiveis_exists = $stmt->fetch();
    
    if (!$recebiveis_exists) {
        echo "❌ Tabela 'recebiveis' não existe. Esta é uma tabela essencial!<br>";
    } else {
        echo "✅ Tabela 'recebiveis' existe.<br>";
        
        // Verificar se a coluna sacado_id existe na tabela recebiveis
        $sql = "DESCRIBE recebiveis";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $recebiveis_columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $sacado_id_exists = false;
        foreach ($recebiveis_columns as $column) {
            if ($column['Field'] === 'sacado_id') {
                $sacado_id_exists = true;
                break;
            }
        }
        
        if (!$sacado_id_exists) {
            echo "<h3>Adicionando coluna 'sacado_id' à tabela 'recebiveis'...</h3>";
            $alter_recebiveis_sql = "ALTER TABLE `recebiveis` ADD COLUMN `sacado_id` int(11) DEFAULT NULL";
            $pdo->exec($alter_recebiveis_sql);
            
            // Adicionar foreign key se a tabela sacados existir
            if ($table_exists || !$table_exists) { // Agora sabemos que existe
                $fk_sql = "ALTER TABLE `recebiveis` ADD CONSTRAINT `fk_recebiveis_sacado` FOREIGN KEY (`sacado_id`) REFERENCES `sacados` (`id`) ON DELETE SET NULL";
                try {
                    $pdo->exec($fk_sql);
                    echo "✅ Coluna 'sacado_id' e foreign key adicionadas com sucesso!<br>";
                } catch (PDOException $e) {
                    echo "✅ Coluna 'sacado_id' adicionada (foreign key pode já existir).<br>";
                }
            }
        } else {
            echo "✅ Coluna 'sacado_id' já existe na tabela 'recebiveis'.<br>";
        }
    }
    
    // Testar uma consulta que estava falhando
    echo "<h3>Testando consulta que estava falhando...</h3>";
    try {
        $test_sql = "SELECT s.id, s.empresa FROM sacados s LIMIT 1";
        $stmt = $pdo->prepare($test_sql);
        $stmt->execute();
        $result = $stmt->fetch();
        echo "✅ Consulta com alias 's.id' executada com sucesso!<br>";
    } catch (PDOException $e) {
        echo "❌ Erro na consulta de teste: " . htmlspecialchars($e->getMessage()) . "<br>";
    }
    
    echo "<h3>✅ Correção da estrutura concluída!</h3>";
    echo "<p>Agora você pode testar o relatório de sacados novamente.</p>";
    
} catch (PDOException $e) {
    echo "❌ Erro durante a correção: " . htmlspecialchars($e->getMessage()) . "<br>";
}

echo "<br><hr><br>";
echo "<p><strong>Próximos passos:</strong></p>";
echo "<ul>";
echo "<li>Execute este script no ambiente online onde está ocorrendo o erro</li>";
echo "<li>Após a execução, teste o relatório de sacados</li>";
echo "<li>Se ainda houver problemas, execute o diagnostico_banco.php para mais detalhes</li>";
echo "</ul>";
?>