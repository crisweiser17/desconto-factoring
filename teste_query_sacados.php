<?php
require_once 'db_connection.php';

// Script para testar as queries do relatório de sacados
// e identificar onde está ocorrendo o erro 's.id'

echo "<h2>Teste das Queries do Relatório de Sacados</h2>";
echo "<hr>";

try {
    // Teste 1: Verificar se a tabela sacados existe e tem dados
    echo "<h3>Teste 1: Verificando tabela sacados</h3>";
    $sql_test1 = "SELECT COUNT(*) as total FROM sacados";
    $stmt = $pdo->prepare($sql_test1);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total de sacados: " . $result['total'] . "<br><br>";
    
    // Teste 2: Verificar estrutura da tabela sacados
    echo "<h3>Teste 2: Estrutura da tabela sacados</h3>";
    $sql_test2 = "DESCRIBE sacados";
    $stmt = $pdo->prepare($sql_test2);
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Default'] ?? '') . "</td>";
        echo "</tr>";
    }
    echo "</table><br>";
    
    // Teste 3: Query simples com JOIN
    echo "<h3>Teste 3: Query simples com JOIN sacados-recebiveis</h3>";
    $sql_test3 = "SELECT s.id, s.empresa, COUNT(r.id) as total_recebiveis 
                  FROM sacados s 
                  LEFT JOIN recebiveis r ON r.sacado_id = s.id 
                  GROUP BY s.id, s.empresa 
                  LIMIT 5";
    $stmt = $pdo->prepare($sql_test3);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Empresa</th><th>Total Recebíveis</th></tr>";
    foreach ($results as $row) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['empresa']) . "</td>";
        echo "<td>" . htmlspecialchars($row['total_recebiveis']) . "</td>";
        echo "</tr>";
    }
    echo "</table><br>";
    
    // Teste 4: Query com filtro por sacado_id (onde ocorre o erro)
    echo "<h3>Teste 4: Query com filtro WHERE s.id = 1</h3>";
    $sql_test4 = "SELECT s.id, s.empresa 
                  FROM sacados s 
                  WHERE s.id = 1";
    $stmt = $pdo->prepare($sql_test4);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        echo "Sacado encontrado: ID=" . $result['id'] . ", Empresa=" . htmlspecialchars($result['empresa']) . "<br>";
    } else {
        echo "Nenhum sacado encontrado com ID=1<br>";
    }
    echo "<br>";
    
    // Teste 5: Simular a query principal do relatório (versão simplificada)
    echo "<h3>Teste 5: Query principal simplificada</h3>";
    $sql_test5 = "SELECT 
                    s.id as sacado_id,
                    s.empresa as sacado_nome,
                    SUM(r.valor_original) as capital_investido
                  FROM sacados s
                  LEFT JOIN recebiveis r ON r.sacado_id = s.id
                  LEFT JOIN operacoes o ON r.operacao_id = o.id
                  GROUP BY s.id, s.empresa
                  HAVING capital_investido > 0
                  LIMIT 5";
    $stmt = $pdo->prepare($sql_test5);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1'>";
    echo "<tr><th>Sacado ID</th><th>Nome</th><th>Capital Investido</th></tr>";
    foreach ($results as $row) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['sacado_id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['sacado_nome']) . "</td>";
        echo "<td>R$ " . number_format($row['capital_investido'], 2, ',', '.') . "</td>";
        echo "</tr>";
    }
    echo "</table><br>";
    
    // Teste 6: Verificar se há problema com parâmetros
    echo "<h3>Teste 6: Query com parâmetro sacado_id</h3>";
    $sacado_id = 1;
    $sql_test6 = "SELECT 
                    s.id as sacado_id,
                    s.empresa as sacado_nome
                  FROM sacados s
                  WHERE s.id = :sacado_id";
    $stmt = $pdo->prepare($sql_test6);
    $stmt->execute([':sacado_id' => $sacado_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        echo "Sacado encontrado com parâmetro: ID=" . $result['sacado_id'] . ", Empresa=" . htmlspecialchars($result['sacado_nome']) . "<br>";
    } else {
        echo "Nenhum sacado encontrado com parâmetro sacado_id=1<br>";
    }
    
    echo "<br><div style='color: green; font-weight: bold;'>✓ Todos os testes executados com sucesso!</div>";
    
} catch (PDOException $e) {
    echo "<div style='color: red; font-weight: bold;'>✗ Erro encontrado: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<br><strong>Código do erro:</strong> " . $e->getCode();
    echo "<br><strong>Arquivo:</strong> " . $e->getFile();
    echo "<br><strong>Linha:</strong> " . $e->getLine();
}
?>