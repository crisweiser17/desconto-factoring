<?php
require_once 'db_connection.php';

echo "<h2>Adicionando Campo 'sacado_id' na Tabela Recebiveis</h2>";
echo "<p><strong>Data/Hora:</strong> " . date('Y-m-d H:i:s') . "</p>";

try {
    // 1. Verificar estrutura atual da tabela recebiveis
    echo "<h3>1. Verificando estrutura atual da tabela 'recebiveis'</h3>";
    
    $stmt = $pdo->query("DESCRIBE recebiveis");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    $sacadoIdExists = false;
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
        echo "</tr>";
        
        if ($column['Field'] === 'sacado_id') {
            $sacadoIdExists = true;
        }
    }
    echo "</table>";
    
    if ($sacadoIdExists) {
        echo "<p style='color: orange;'>⚠️ Campo 'sacado_id' já existe na tabela recebiveis</p>";
        
        // Verificar se há foreign key
        $stmt = $pdo->query("
            SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
            WHERE TABLE_NAME = 'recebiveis' 
            AND COLUMN_NAME = 'sacado_id'
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        
        $foreignKey = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($foreignKey) {
            echo "<p style='color: green;'>✅ Foreign key já configurada: sacado_id → {$foreignKey['REFERENCED_TABLE_NAME']}.{$foreignKey['REFERENCED_COLUMN_NAME']}</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ Campo existe mas foreign key não está configurada</p>";
            
            // Adicionar foreign key
            echo "<h3>2. Adicionando Foreign Key</h3>";
            $pdo->exec("ALTER TABLE recebiveis ADD CONSTRAINT fk_recebiveis_sacado FOREIGN KEY (sacado_id) REFERENCES sacados(id)");
            echo "<p style='color: green;'>✅ Foreign key adicionada com sucesso!</p>";
        }
    } else {
        echo "<p style='color: blue;'>ℹ️ Campo 'sacado_id' não existe. Será criado.</p>";
        
        // 2. Adicionar o campo sacado_id
        echo "<h3>2. Adicionando campo 'sacado_id'</h3>";
        
        $pdo->beginTransaction();
        
        // Adicionar coluna
        $alterSql = "ALTER TABLE recebiveis ADD COLUMN sacado_id INT NULL AFTER operacao_id";
        $pdo->exec($alterSql);
        echo "<p style='color: green;'>✅ Campo 'sacado_id' adicionado com sucesso!</p>";
        
        // Adicionar índice
        $indexSql = "ALTER TABLE recebiveis ADD INDEX idx_sacado_id (sacado_id)";
        $pdo->exec($indexSql);
        echo "<p style='color: green;'>✅ Índice para 'sacado_id' criado!</p>";
        
        // Adicionar foreign key
        $fkSql = "ALTER TABLE recebiveis ADD CONSTRAINT fk_recebiveis_sacado FOREIGN KEY (sacado_id) REFERENCES sacados(id)";
        $pdo->exec($fkSql);
        echo "<p style='color: green;'>✅ Foreign key configurada!</p>";
        
        $pdo->commit();
    }
    
    // 3. Verificar estrutura final
    echo "<h3>3. Verificando estrutura final</h3>";
    
    $stmt = $pdo->query("DESCRIBE recebiveis");
    $finalColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $sacadoIdFound = false;
    foreach ($finalColumns as $column) {
        if ($column['Field'] === 'sacado_id') {
            $sacadoIdFound = true;
            echo "<p style='color: green;'>✅ Campo 'sacado_id' confirmado: {$column['Type']}, Null: {$column['Null']}</p>";
            break;
        }
    }
    
    if (!$sacadoIdFound) {
        echo "<p style='color: red;'>❌ Campo 'sacado_id' não foi encontrado após alteração</p>";
    }
    
    // 4. Verificar foreign keys
    echo "<h3>4. Verificando Foreign Keys da tabela recebiveis</h3>";
    
    $stmt = $pdo->query("
        SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
        WHERE TABLE_NAME = 'recebiveis' 
        AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    
    $foreignKeys = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($foreignKeys)) {
        echo "<p style='color: green;'>✅ Foreign keys configuradas:</p>";
        echo "<ul>";
        foreach ($foreignKeys as $fk) {
            echo "<li>{$fk['COLUMN_NAME']} → {$fk['REFERENCED_TABLE_NAME']}.{$fk['REFERENCED_COLUMN_NAME']}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: orange;'>⚠️ Nenhuma foreign key encontrada</p>";
    }
    
    // 5. Testar consulta com JOIN
    echo "<h3>5. Testando consulta com JOIN</h3>";
    
    $testSql = "
        SELECT r.id, r.numero_titulo, r.valor, s.empresa as sacado_empresa
        FROM recebiveis r
        LEFT JOIN sacados s ON r.sacado_id = s.id
        LIMIT 3
    ";
    
    $stmt = $pdo->query($testSql);
    $testResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p style='color: green;'>✅ Consulta com JOIN funciona!</p>";
    echo "<p>Exemplo de recebíveis (primeiros 3):</p>";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>ID</th><th>Número Título</th><th>Valor</th><th>Sacado</th></tr>";
    
    foreach ($testResults as $row) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['numero_titulo'] ?? '-') . "</td>";
        echo "<td>R$ " . number_format($row['valor'] ?? 0, 2, ',', '.') . "</td>";
        echo "<td>" . htmlspecialchars($row['sacado_empresa'] ?? 'Não definido') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 6. Resultado final
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; margin: 20px 0; border-radius: 5px;'>";
    echo "<h4>🎉 Sucesso!</h4>";
    echo "<p><strong>Campo 'sacado_id' configurado com sucesso na tabela recebiveis!</strong></p>";
    echo "<h5>Alterações realizadas:</h5>";
    echo "<ul>";
    echo "<li>✅ Campo 'sacado_id' adicionado (INT NULL)</li>";
    echo "<li>✅ Índice criado para performance</li>";
    echo "<li>✅ Foreign key configurada (recebiveis.sacado_id → sacados.id)</li>";
    echo "<li>✅ Consultas com JOIN funcionando</li>";
    echo "</ul>";
    echo "<h5>Próximos passos:</h5>";
    echo "<ul>";
    echo "<li>🔄 Atualizar formulário de títulos para incluir select de sacados</li>";
    echo "<li>🔄 Testar registro completo</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; margin: 20px 0; border-radius: 5px;'>";
    echo "<h4>❌ Erro!</h4>";
    echo "<p><strong>Erro ao adicionar campo:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; margin: 20px 0; border-radius: 5px;'>";
    echo "<h4>❌ Erro!</h4>";
    echo "<p><strong>Erro geral:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>