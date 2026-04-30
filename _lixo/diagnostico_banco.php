<?php
require_once 'db_connection.php';

// Script de diagnóstico para verificar estrutura do banco de dados
echo "<h2>Diagnóstico da Estrutura do Banco de Dados</h2>";

try {
    // Verificar se a tabela sacados existe
    echo "<h3>1. Verificando existência da tabela 'sacados':</h3>";
    $sql = "SHOW TABLES LIKE 'sacados'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $table_exists = $stmt->fetch();
    
    if ($table_exists) {
        echo "✅ Tabela 'sacados' existe<br>";
        
        // Verificar estrutura da tabela sacados
        echo "<h3>2. Estrutura da tabela 'sacados':</h3>";
        $sql = "DESCRIBE sacados";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Chave</th><th>Padrão</th><th>Extra</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Default'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
            echo "</tr>";
        }
        echo "</table><br>";
        
        // Verificar se a coluna 'id' existe especificamente
        $id_column_exists = false;
        foreach ($columns as $column) {
            if ($column['Field'] === 'id') {
                $id_column_exists = true;
                break;
            }
        }
        
        if ($id_column_exists) {
            echo "✅ Coluna 'id' existe na tabela 'sacados'<br>";
        } else {
            echo "❌ Coluna 'id' NÃO existe na tabela 'sacados'<br>";
        }
        
        // Contar registros na tabela
        echo "<h3>3. Contagem de registros:</h3>";
        $sql = "SELECT COUNT(*) as total FROM sacados";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Total de registros na tabela 'sacados': " . $count['total'] . "<br>";
        
    } else {
        echo "❌ Tabela 'sacados' NÃO existe<br>";
    }
    
    // Verificar outras tabelas relacionadas
    echo "<h3>4. Verificando outras tabelas relacionadas:</h3>";
    $tables_to_check = ['operacoes', 'recebiveis', 'cedentes'];
    
    foreach ($tables_to_check as $table) {
        $sql = "SHOW TABLES LIKE '$table'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $table_exists = $stmt->fetch();
        
        if ($table_exists) {
            echo "✅ Tabela '$table' existe<br>";
        } else {
            echo "❌ Tabela '$table' NÃO existe<br>";
        }
    }
    
    // Listar todas as tabelas do banco
    echo "<h3>5. Todas as tabelas do banco:</h3>";
    $sql = "SHOW TABLES";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $all_tables = $stmt->fetchAll(PDO::FETCH_NUM);
    
    echo "<ul>";
    foreach ($all_tables as $table) {
        echo "<li>" . htmlspecialchars($table[0]) . "</li>";
    }
    echo "</ul>";
    
    // Testar uma consulta simples na tabela sacados (se existir)
    if ($table_exists) {
        echo "<h3>6. Teste de consulta simples:</h3>";
        try {
            $sql = "SELECT id, empresa FROM sacados LIMIT 5";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $sample_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "✅ Consulta executada com sucesso. Primeiros 5 registros:<br>";
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>Empresa</th></tr>";
            foreach ($sample_data as $row) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                echo "<td>" . htmlspecialchars($row['empresa']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
        } catch (PDOException $e) {
            echo "❌ Erro na consulta: " . htmlspecialchars($e->getMessage()) . "<br>";
        }
    }
    
} catch (PDOException $e) {
    echo "❌ Erro de conexão com o banco: " . htmlspecialchars($e->getMessage()) . "<br>";
}

echo "<br><hr><br>";
echo "<p><strong>Instruções:</strong></p>";
echo "<ul>";
echo "<li>Execute este script tanto no ambiente local quanto no online</li>";
echo "<li>Compare os resultados para identificar diferenças</li>";
echo "<li>Se a tabela 'sacados' não existir no ambiente online, será necessário criá-la</li>";
echo "<li>Se a coluna 'id' não existir, será necessário adicioná-la</li>";
echo "</ul>";
?>