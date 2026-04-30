<?php
require_once 'db_connection.php';

try {
    echo "Iniciando migração de sócios...\n";

    // Verifica se a tabela sacados_socios existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'sacados_socios'");
    if ($stmt->rowCount() > 0) {
        // Migra dados de sacados_socios para cedentes_socios
        $stmtSacadosSocios = $pdo->query("SELECT * FROM sacados_socios");
        $sacadosSocios = $stmtSacadosSocios->fetchAll(PDO::FETCH_ASSOC);

        // Nós temos o mapeamento de sacado_id para cliente_id? 
        // Vamos recriar o mapeamento a partir do CPF/CNPJ ou documento_principal
        $stmtSacados = $pdo->query("SELECT id, documento_principal FROM sacados");
        $sacados = $stmtSacados->fetchAll(PDO::FETCH_ASSOC);
        
        $mappings = [];
        foreach ($sacados as $s) {
            $stmtCheck = $pdo->prepare("SELECT id FROM clientes WHERE documento_principal = ?");
            $stmtCheck->execute([$s['documento_principal']]);
            $clienteId = $stmtCheck->fetchColumn();
            if ($clienteId) {
                $mappings[$s['id']] = $clienteId;
            }
        }

        foreach ($sacadosSocios as $socio) {
            $clienteId = $mappings[$socio['sacado_id']] ?? null;
            if ($clienteId) {
                $stmtInsert = $pdo->prepare("INSERT INTO cedentes_socios (cedente_id, nome, cpf, data_cadastro) VALUES (?, ?, ?, ?)");
                $stmtInsert->execute([$clienteId, $socio['nome'], $socio['cpf'], $socio['data_cadastro']]);
                echo "Sócio {$socio['nome']} migrado para cliente ID {$clienteId}.\n";
            }
        }

        // Remove a tabela sacados_socios
        $pdo->exec("DROP TABLE `sacados_socios`");
        echo "Tabela sacados_socios removida.\n";
        
        // Remove a tabela sacados
        $pdo->exec("DROP TABLE `sacados`");
        echo "Tabela sacados removida.\n";
    }

    // Renomeia cedentes_socios para clientes_socios
    $stmt = $pdo->query("SHOW TABLES LIKE 'cedentes_socios'");
    if ($stmt->rowCount() > 0) {
        $pdo->exec("RENAME TABLE `cedentes_socios` TO `clientes_socios`");
        
        // Atualiza a coluna e FK
        try {
            $pdo->exec("ALTER TABLE `clientes_socios` DROP FOREIGN KEY `fk_cedentes_socios_cedente`");
        } catch (Exception $e) {}
        
        $pdo->exec("ALTER TABLE `clientes_socios` CHANGE `cedente_id` `cliente_id` int(11) DEFAULT NULL");
        $pdo->exec("ALTER TABLE `clientes_socios` ADD CONSTRAINT `fk_clientes_socios_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE");
        echo "Tabela cedentes_socios renomeada para clientes_socios e FK atualizada.\n";
    }

    echo "Migração de sócios concluída com sucesso!\n";

} catch (Exception $e) {
    echo "Erro na migração: " . $e->getMessage() . "\n";
}
