<?php
// debug_erro_sid.php - Script para identificar exatamente onde ocorre o erro 's.id'

require_once 'db_connection.php';

header('Content-Type: text/html; charset=UTF-8');
echo "<!DOCTYPE html>\n<html>\n<head>\n<title>Debug Erro s.id</title>\n<style>\nbody { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }\n.container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }\n.test { margin: 15px 0; padding: 15px; border-left: 4px solid #007cba; background: #f8f9fa; }\n.success { border-left-color: #28a745; background: #d4edda; }\n.error { border-left-color: #dc3545; background: #f8d7da; }\n.warning { border-left-color: #ffc107; background: #fff3cd; }\n.query-box { background: #e9ecef; padding: 10px; border-radius: 5px; margin: 10px 0; font-family: monospace; font-size: 12px; overflow-x: auto; }\n.params { background: #d1ecf1; padding: 8px; border-radius: 3px; margin: 5px 0; }\ntable { width: 100%; border-collapse: collapse; margin: 10px 0; }\nth, td { border: 1px solid #ddd; padding: 8px; text-align: left; }\nth { background: #f2f2f2; }\n</style>\n</head>\n<body>\n<div class='container'>\n<h1>🔍 Debug do Erro 's.id' - Relatório de Sacados</h1>\n";

$test_number = 1;

function runTest($title, $sql, $params = []) {
    global $pdo, $test_number;
    
    echo "<div class='test'>\n";
    echo "<h3>Teste $test_number: $title</h3>\n";
    
    echo "<div class='query-box'>" . htmlspecialchars($sql) . "</div>\n";
    
    if (!empty($params)) {
        echo "<div class='params'><strong>Parâmetros:</strong> " . json_encode($params) . "</div>\n";
    }
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<div class='success'>✅ Query executada com sucesso!</div>\n";
        echo "<div>Registros encontrados: " . count($results) . "</div>\n";
        
        if (count($results) > 0 && count($results) <= 5) {
            echo "<table>\n<tr>";
            foreach (array_keys($results[0]) as $col) {
                echo "<th>" . htmlspecialchars($col) . "</th>";
            }
            echo "</tr>\n";
            
            foreach ($results as $row) {
                echo "<tr>";
                foreach ($row as $value) {
                    echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
                }
                echo "</tr>\n";
            }
            echo "</table>\n";
        } elseif (count($results) > 5) {
            echo "<div>Primeiros 3 registros:</div>\n";
            echo "<table>\n<tr>";
            foreach (array_keys($results[0]) as $col) {
                echo "<th>" . htmlspecialchars($col) . "</th>";
            }
            echo "</tr>\n";
            
            for ($i = 0; $i < min(3, count($results)); $i++) {
                echo "<tr>";
                foreach ($results[$i] as $value) {
                    echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
                }
                echo "</tr>\n";
            }
            echo "</table>\n";
        }
        
    } catch (PDOException $e) {
        echo "<div class='error'>❌ ERRO: " . htmlspecialchars($e->getMessage()) . "</div>\n";
        echo "<div class='error'>Código: " . $e->getCode() . "</div>\n";
        
        // Se for o erro que procuramos, destacar
        if (strpos($e->getMessage(), 's.id') !== false) {
            echo "<div class='warning'>🎯 <strong>ENCONTRADO O ERRO 's.id'!</strong></div>\n";
        }
    }
    
    echo "</div>\n";
    $test_number++;
}

// Parâmetros de teste
$test_params = [':sacado_id' => 1];
$test_date_params = [':data_inicio' => '2024-01-01', ':data_fim' => '2024-12-31'];
$all_params = [':sacado_id' => 1, ':data_inicio' => '2024-01-01', ':data_fim' => '2024-12-31'];

// Teste 1: Verificar estrutura básica
runTest(
    "Verificar estrutura da tabela sacados",
    "DESCRIBE sacados"
);

// Teste 2: Query simples com alias 's'
runTest(
    "Query simples com alias 's'",
    "SELECT s.id, s.empresa FROM sacados s LIMIT 3"
);

// Teste 3: JOIN básico
runTest(
    "JOIN básico sacados-recebiveis",
    "SELECT s.id, s.empresa, COUNT(r.id) as total_recebiveis 
     FROM sacados s 
     LEFT JOIN recebiveis r ON r.sacado_id = s.id 
     GROUP BY s.id, s.empresa 
     LIMIT 3"
);

// Teste 4: Filtro por sacado_id
runTest(
    "Filtro WHERE s.id = :sacado_id",
    "SELECT s.id, s.empresa FROM sacados s WHERE s.id = :sacado_id",
    $test_params
);

// Teste 5: Query principal simplificada (parte que costuma dar erro)
runTest(
    "Query principal simplificada",
    "SELECT 
        s.id as sacado_id,
        s.empresa as sacado_nome,
        SUM(r.valor_original) as capital_investido
     FROM sacados s
     LEFT JOIN recebiveis r ON r.sacado_id = s.id
     LEFT JOIN operacoes o ON r.operacao_id = o.id
     WHERE s.id = :sacado_id
     GROUP BY s.id, s.empresa",
    $test_params
);

// Teste 6: Subquery problemática (lucro estimado)
runTest(
    "Subquery lucro estimado",
    "SELECT s.id,
            (SELECT SUM(o2.total_lucro_liquido_calc) 
             FROM operacoes o2 
             INNER JOIN recebiveis r2 ON o2.id = r2.operacao_id 
             WHERE r2.sacado_id = s.id) as lucro_estimado
     FROM sacados s 
     WHERE s.id = :sacado_id",
    $test_params
);

// Teste 7: Query completa dividida em partes - Parte 1
runTest(
    "Query principal - SELECT básico",
    "SELECT 
        s.id as sacado_id,
        s.empresa as sacado_nome,
        s.documento_principal as sacado_documento,
        s.tipo_pessoa as sacado_tipo
     FROM sacados s
     WHERE s.id = :sacado_id",
    $test_params
);

// Teste 8: Query principal - com SUM simples
runTest(
    "Query principal - com SUM",
    "SELECT 
        s.id as sacado_id,
        s.empresa as sacado_nome,
        SUM(r.valor_original) as capital_investido
     FROM sacados s
     LEFT JOIN recebiveis r ON r.sacado_id = s.id
     WHERE s.id = :sacado_id
     GROUP BY s.id, s.empresa",
    $test_params
);

// Teste 9: Testar cada subquery individualmente
runTest(
    "Subquery número de operações",
    "SELECT 
        s.id,
        (SELECT COUNT(DISTINCT o2.id) 
         FROM operacoes o2 
         INNER JOIN recebiveis r2 ON r2.operacao_id = o2.id
         WHERE r2.sacado_id = s.id) as num_operacoes
     FROM sacados s 
     WHERE s.id = :sacado_id",
    $test_params
);

// Teste 10: Tentar reproduzir o erro exato com a query original
echo "<div class='test'>\n";
echo "<h3>Teste $test_number: Query Original do Relatório</h3>\n";
echo "<div class='warning'>⚠️ Testando a query exata que está causando o erro...</div>\n";

try {
    // Simular os mesmos filtros e parâmetros
    $data_inicio = '';
    $data_fim = '';
    $sacado_id = '1';
    
    $params = [];
    $whereClauses = [];
    $whereClausesOperacoes = [];
    
    if ($sacado_id) {
        $whereClauses[] = "s.id = :sacado_id";
        $params[':sacado_id'] = $sacado_id;
    }
    
    $whereSQL = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";
    $whereSQL_operacoes = !empty($whereClausesOperacoes) ? "AND " . implode(" AND ", $whereClausesOperacoes) : "";
    
    $sql = "
        SELECT 
            s.id as sacado_id,
            s.empresa as sacado_nome,
            s.documento_principal as sacado_documento,
            s.tipo_pessoa as sacado_tipo,
            
            SUM(r.valor_original) as capital_investido,
            
            COALESCE((
                SELECT SUM(
                    o2.total_lucro_liquido_calc / (
                        SELECT COUNT(DISTINCT r3.sacado_id) 
                        FROM recebiveis r3 
                        WHERE r3.operacao_id = o2.id AND r3.sacado_id IS NOT NULL
                    )
                ) 
                FROM (
                    SELECT DISTINCT o2.id, o2.total_lucro_liquido_calc
                    FROM operacoes o2 
                    INNER JOIN recebiveis r2 ON r2.operacao_id = o2.id
                    WHERE r2.sacado_id = s.id
                    $whereSQL_operacoes
                ) o2
            ), 0) as lucro_estimado
            
        FROM sacados s
        LEFT JOIN recebiveis r ON r.sacado_id = s.id
        LEFT JOIN operacoes o ON r.operacao_id = o.id
        $whereSQL
        GROUP BY s.id, s.empresa, s.documento_principal, s.tipo_pessoa
        HAVING capital_investido > 0
        ORDER BY capital_investido DESC
    ";
    
    echo "<div class='query-box'>" . htmlspecialchars($sql) . "</div>\n";
    echo "<div class='params'><strong>Parâmetros:</strong> " . json_encode($params) . "</div>\n";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div class='success'>✅ Query original executada com sucesso!</div>\n";
    echo "<div>Registros encontrados: " . count($results) . "</div>\n";
    
} catch (PDOException $e) {
    echo "<div class='error'>❌ ERRO NA QUERY ORIGINAL: " . htmlspecialchars($e->getMessage()) . "</div>\n";
    echo "<div class='error'>Código: " . $e->getCode() . "</div>\n";
    echo "<div class='warning'>🎯 <strong>ESTE É O ERRO QUE ESTAMOS PROCURANDO!</strong></div>\n";
}

echo "</div>\n";

// Informações do ambiente
echo "<div class='test'>\n";
echo "<h3>Informações do Ambiente</h3>\n";
echo "<div>Versão do MySQL: ";
try {
    $version = $pdo->query('SELECT VERSION()')->fetchColumn();
    echo htmlspecialchars($version);
} catch (Exception $e) {
    echo "Erro ao obter versão";
}
echo "</div>\n";

echo "<div>Modo SQL: ";
try {
    $mode = $pdo->query('SELECT @@sql_mode')->fetchColumn();
    echo htmlspecialchars($mode);
} catch (Exception $e) {
    echo "Erro ao obter modo SQL";
}
echo "</div>\n";

echo "<div>Charset da conexão: ";
try {
    $charset = $pdo->query('SELECT @@character_set_connection')->fetchColumn();
    echo htmlspecialchars($charset);
} catch (Exception $e) {
    echo "Erro ao obter charset";
}
echo "</div>\n";

echo "</div>\n";

echo "<div class='test warning'>\n";
echo "<h3>🎯 Conclusão</h3>\n";
echo "<p>Se o erro 's.id' apareceu em algum dos testes acima, agora sabemos exatamente onde está o problema!</p>\n";
echo "<p>Se NENHUM teste mostrou o erro, então o problema pode estar:</p>\n";
echo "<ul>\n";
echo "<li>Em uma versão diferente do arquivo online</li>\n";
echo "<li>Em configurações específicas do MySQL do servidor</li>\n";
echo "<li>Em cache de query plans</li>\n";
echo "<li>Em diferenças de estrutura do banco não detectadas</li>\n";
echo "</ul>\n";
echo "</div>\n";

echo "</div>\n</body>\n</html>";
?>