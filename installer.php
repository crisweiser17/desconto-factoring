<?php
// installer.php - Script para reinstalar o banco de dados no servidor online
// ATENÇÃO: Este script irá DELETAR completamente o banco atual e recriar do zero!

set_time_limit(300); // 5 minutos de timeout
ini_set('memory_limit', '512M');

// Configurações do banco (usando as mesmas do db_connection_o.php)
$db_host = 'localhost';
$db_name = 'rfqkezvjge';
$db_user = 'rfqkezvjge';
$db_pass = 'Tgbyhn123@';
$charset = 'utf8mb4';

echo "<!DOCTYPE html>\n<html>\n<head>\n<title>Installer - Factor System</title>\n<style>\nbody { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }\n.container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }\n.step { margin: 10px 0; padding: 10px; border-left: 4px solid #007cba; background: #f8f9fa; }\n.success { border-left-color: #28a745; background: #d4edda; }\n.error { border-left-color: #dc3545; background: #f8d7da; }\n.warning { border-left-color: #ffc107; background: #fff3cd; }\ncode { background: #e9ecef; padding: 2px 4px; border-radius: 3px; }\n</style>\n</head>\n<body>\n<div class='container'>\n<h1>🚀 Factor System Database Installer</h1>\n";

try {
    echo "<div class='step'>📋 <strong>Passo 1:</strong> Conectando ao MySQL...</div>\n";
    
    // Conectar ao MySQL (sem especificar database)
    $dsn_server = "mysql:host=$db_host;port=3306;charset=$charset";
    $pdo_server = new PDO($dsn_server, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "<div class='step success'>✅ Conectado ao MySQL com sucesso!</div>\n";
    
    echo "<div class='step warning'>⚠️ <strong>Passo 2:</strong> ATENÇÃO! Deletando banco existente...</div>\n";
    
    // Dropar banco se existir
    $pdo_server->exec("DROP DATABASE IF EXISTS `$db_name`");
    echo "<div class='step'>🗑️ Banco '$db_name' removido (se existia)</div>\n";
    
    // Criar novo banco
    $pdo_server->exec("CREATE DATABASE `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<div class='step success'>✅ Novo banco '$db_name' criado com sucesso!</div>\n";
    
    echo "<div class='step'>📊 <strong>Passo 3:</strong> Conectando ao novo banco...</div>\n";
    
    // Conectar ao banco específico
    $dsn = "mysql:host=$db_host;port=3306;dbname=$db_name;charset=$charset";
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "<div class='step success'>✅ Conectado ao banco '$db_name'!</div>\n";
    
    echo "<div class='step'>📥 <strong>Passo 4:</strong> Importando estrutura e dados...</div>\n";
    
    // Ler o arquivo SQL local
    $sql_file = __DIR__ . '/banco_dump.sql.s';
    
    if (!file_exists($sql_file)) {
        throw new Exception("Arquivo SQL não encontrado: $sql_file");
    }
    
    $sql_content = file_get_contents($sql_file);
    
    if ($sql_content === false) {
        throw new Exception("Erro ao ler o arquivo SQL");
    }
    
    echo "<div class='step'>📄 Arquivo SQL carregado (" . number_format(strlen($sql_content)) . " caracteres)</div>\n";
    
    // Dividir em statements individuais
    $statements = array_filter(
        array_map('trim', explode(';', $sql_content)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^\s*(--|\/\*|#)/', $stmt);
        }
    );
    
    echo "<div class='step'>🔧 Executando " . count($statements) . " comandos SQL...</div>\n";
    
    $executed = 0;
    $errors = 0;
    
    foreach ($statements as $statement) {
        try {
            $pdo->exec($statement);
            $executed++;
            
            // Mostrar progresso a cada 10 comandos
            if ($executed % 10 == 0) {
                echo "<div class='step'>⏳ Executados: $executed comandos...</div>\n";
                flush();
            }
        } catch (PDOException $e) {
            $errors++;
            echo "<div class='step error'>❌ Erro no comando $executed: " . htmlspecialchars($e->getMessage()) . "</div>\n";
            
            // Se houver muitos erros, parar
            if ($errors > 5) {
                throw new Exception("Muitos erros durante a importação. Processo interrompido.");
            }
        }
    }
    
    echo "<div class='step success'>✅ Importação concluída! Executados: $executed comandos, Erros: $errors</div>\n";
    
    echo "<div class='step'>🔍 <strong>Passo 5:</strong> Verificando estrutura...</div>\n";
    
    // Verificar tabelas criadas
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "<div class='step'>📋 Tabelas criadas: " . implode(', ', $tables) . "</div>\n";
    
    // Verificar tabela sacados especificamente
    if (in_array('sacados', $tables)) {
        $sacados_count = $pdo->query("SELECT COUNT(*) FROM sacados")->fetchColumn();
        echo "<div class='step success'>✅ Tabela 'sacados' criada com $sacados_count registros</div>\n";
        
        // Verificar se tem a coluna id
        $columns = $pdo->query("DESCRIBE sacados")->fetchAll();
        $has_id = false;
        foreach ($columns as $col) {
            if ($col['Field'] === 'id') {
                $has_id = true;
                break;
            }
        }
        
        if ($has_id) {
            echo "<div class='step success'>✅ Coluna 'id' encontrada na tabela sacados</div>\n";
        } else {
            echo "<div class='step error'>❌ Coluna 'id' NÃO encontrada na tabela sacados!</div>\n";
        }
    } else {
        echo "<div class='step error'>❌ Tabela 'sacados' não foi criada!</div>\n";
    }
    
    echo "<div class='step'>🔧 <strong>Passo 6:</strong> Criando arquivo de conexão...</div>\n";
    
    // Criar o arquivo db_connection.php com as credenciais corretas
    $db_connection_content = "<?php\n// db_connection.php - Gerado automaticamente pelo installer\n\n";
    $db_connection_content .= "\$db_host = '$db_host';\n";
    $db_connection_content .= "\$db_name = '$db_name';\n";
    $db_connection_content .= "\$db_user = '$db_user';\n";
    $db_connection_content .= "\$db_pass = '$db_pass';\n";
    $db_connection_content .= "\$charset = '$charset';\n\n";
    $db_connection_content .= "// Opções do PDO\n";
    $db_connection_content .= "\$options = [\n";
    $db_connection_content .= "    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,\n";
    $db_connection_content .= "    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,\n";
    $db_connection_content .= "    PDO::ATTR_EMULATE_PREPARES   => false,\n";
    $db_connection_content .= "];\n\n";
    $db_connection_content .= "// Data Source Name (DSN)\n";
    $db_connection_content .= "\$dsn = \"mysql:host=\$db_host;port=3306;dbname=\$db_name;charset=\$charset\";\n\n";
    $db_connection_content .= "try {\n";
    $db_connection_content .= "    \$pdo = new PDO(\$dsn, \$db_user, \$db_pass, \$options);\n";
    $db_connection_content .= "} catch (\\PDOException \$e) {\n";
    $db_connection_content .= "    error_log(\"Erro de Conexão BD: \" . \$e->getMessage());\n";
    $db_connection_content .= "    die(\"Erro ao conectar com o banco de dados. Tente novamente mais tarde.\");\n";
    $db_connection_content .= "}\n";
    $db_connection_content .= "?>";
    
    file_put_contents(__DIR__ . '/db_connection.php', $db_connection_content);
    echo "<div class='step success'>✅ Arquivo 'db_connection.php' criado com sucesso!</div>\n";
    
    echo "<div class='step success'>🎉 <strong>INSTALAÇÃO CONCLUÍDA COM SUCESSO!</strong></div>\n";
    echo "<div class='step'>📝 <strong>Próximos passos:</strong></div>\n";
    echo "<div class='step'>1. ✅ Banco de dados reinstalado</div>\n";
    echo "<div class='step'>2. ✅ Arquivo db_connection.php atualizado</div>\n";
    echo "<div class='step'>3. 🔄 Teste o relatório de sacados agora</div>\n";
    echo "<div class='step'>4. 🗑️ Você pode deletar este arquivo installer.php após confirmar que tudo funciona</div>\n";
    
    echo "<div class='step warning'>⚠️ <strong>IMPORTANTE:</strong> O erro 's.id' deve estar resolvido agora!</div>\n";
    
} catch (Exception $e) {
    echo "<div class='step error'>❌ <strong>ERRO FATAL:</strong> " . htmlspecialchars($e->getMessage()) . "</div>\n";
    echo "<div class='step error'>🔧 Verifique as credenciais do banco e tente novamente.</div>\n";
}

echo "</div>\n</body>\n</html>";
?>