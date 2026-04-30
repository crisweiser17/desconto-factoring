<?php
// Diagnóstico da Estrutura do Banco de Dados - Versão Local

// Configurações do banco LOCAL
$db_host = 'localhost';
$db_name = 'rfqkezvjge';
$db_user = 'root';
$db_pass = ''; // Senha vazia para ambiente local
$charset = 'utf8mb4';

$dsn = "mysql:host=$db_host;port=3306;dbname=$db_name;charset=$charset";

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    
    echo "<h2>Diagnóstico da Estrutura do Banco de Dados - AMBIENTE LOCAL</h2>";
    
    // 1. Verificar se a tabela 'sacados' existe
    echo "<h3>1. Verificando existência da tabela 'sacados':</h3>";
    $stmt = $pdo->query("SHOW TABLES LIKE 'sacados'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Tabela 'sacados' existe<br>";
        
        // 2. Mostrar estrutura da tabela sacados
        echo "<h3>2. Estrutura da tabela 'sacados':</h3>";
        $stmt = $pdo->query("DESCRIBE sacados");
        $columns = $stmt->fetchAll();
        
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Chave</th><th>Padrão</th><th>Extra</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($column['Field'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($column['Type'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($column['Null'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($column['Key'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($column['Default'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($column['Extra'] ?? '') . "</td>";
            echo "</tr>";
        }
        echo "</table><br>";
        
        // Verificar se a coluna 'id' existe
        $hasIdColumn = false;
        foreach ($columns as $column) {
            if ($column['Field'] === 'id') {
                $hasIdColumn = true;
                break;
            }
        }
        
        if ($hasIdColumn) {
            echo "✅ Coluna 'id' existe na tabela 'sacados'<br>";
        } else {
            echo "❌ Coluna 'id' NÃO existe na tabela 'sacados'<br>";
        }
        
        // 3. Contar registros
        echo "<h3>3. Contagem de registros:</h3>";
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM sacados");
        $count = $stmt->fetch();
        echo "Total de registros na tabela 'sacados': " . $count['total'] . "<br>";
        
    } else {
        echo "❌ Tabela 'sacados' NÃO existe<br>";
    }
    
    // 4. Verificar outras tabelas relacionadas
    echo "<h3>4. Verificando outras tabelas relacionadas:</h3>";
    $tables = ['operacoes', 'recebiveis', 'cedentes'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Tabela '$table' existe<br>";
        } else {
            echo "❌ Tabela '$table' NÃO existe<br>";
        }
    }
    
    // 5. Listar todas as tabelas
    echo "<h3>5. Todas as tabelas do banco:</h3>";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        echo $table . "<br>";
    }
    
    // 6. Teste de consulta simples
    echo "<h3>6. Teste de consulta simples:</h3>";
    try {
        $stmt = $pdo->query("SELECT id, empresa FROM sacados LIMIT 5");
        $results = $stmt->fetchAll();
        
        if (count($results) > 0) {
            echo "✅ Consulta executada com sucesso. Primeiros 5 registros:<br>";
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>Empresa</th></tr>";
            foreach ($results as $row) {
                echo "<tr><td>" . htmlspecialchars($row['id']) . "</td><td>" . htmlspecialchars($row['empresa']) . "</td></tr>";
            }
            echo "</table><br>";
        } else {
            echo "⚠️ Consulta executada, mas não há registros na tabela<br>";
        }
    } catch (Exception $e) {
        echo "❌ Erro na consulta: " . $e->getMessage() . "<br>";
    }
    
} catch (PDOException $e) {
    echo "❌ Erro de conexão: " . $e->getMessage() . "<br>";
}

echo "<br><h3>Instruções:</h3>";
echo "<ul>";
echo "<li>Execute este script tanto no ambiente local quanto no online</li>";
echo "<li>Compare os resultados para identificar diferenças</li>";
echo "<li>Se a tabela 'sacados' não existir no ambiente online, será necessário criá-la</li>";
echo "<li>Se a coluna 'id' não existir, será necessário adicioná-la</li>";
echo "</ul>";
?>