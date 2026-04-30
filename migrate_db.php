<?php
require_once 'db_connection.php';

try {
    $pdo->beginTransaction();
    echo "Iniciando migração...\n";

    // Verifica se a tabela clientes já existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'clientes'");
    if ($stmt->rowCount() == 0) {
        // 1. Renomeia cedentes para clientes
        $pdo->exec("RENAME TABLE `cedentes` TO `clientes`");
        echo "Tabela cedentes renomeada para clientes.\n";

        // 2. Atualiza a FK em operacoes
        // Ignora erros caso a FK não exista exatamente com esse nome
        try {
            $pdo->exec("ALTER TABLE `operacoes` DROP FOREIGN KEY `fk_operacoes_cedente`");
            $pdo->exec("ALTER TABLE `operacoes` ADD CONSTRAINT `fk_operacoes_cliente` FOREIGN KEY (`cedente_id`) REFERENCES `clientes` (`id`)");
            echo "FK em operacoes atualizada.\n";
        } catch (Exception $e) {
            echo "Aviso: Não foi possível atualizar FK em operacoes: " . $e->getMessage() . "\n";
        }
    } else {
        echo "Tabela clientes já existe. Pulando renomeação.\n";
    }

    // 3. Verifica se recebiveis tem FK fk_recebiveis_sacado e remove
    try {
        $pdo->exec("ALTER TABLE `recebiveis` DROP FOREIGN KEY `fk_recebiveis_sacado`");
        echo "FK fk_recebiveis_sacado removida de recebiveis.\n";
    } catch (Exception $e) {
        echo "Aviso: FK fk_recebiveis_sacado não encontrada ou já removida.\n";
    }

    // 4. Migra dados de sacados para clientes
    $stmtSacados = $pdo->query("SELECT * FROM sacados");
    $sacados = $stmtSacados->fetchAll(PDO::FETCH_ASSOC);

    // Pega as colunas de clientes
    $stmtCols = $pdo->query("SHOW COLUMNS FROM clientes");
    $clienteCols = $stmtCols->fetchAll(PDO::FETCH_COLUMN);
    $clienteCols = array_diff($clienteCols, ['id']); // remove id

    $mappings = []; // old_sacado_id => new_cliente_id

    foreach ($sacados as $sacado) {
        $doc = $sacado['documento_principal'];
        
        $stmtCheck = $pdo->prepare("SELECT id FROM clientes WHERE documento_principal = ?");
        $stmtCheck->execute([$doc]);
        $existing = $stmtCheck->fetchColumn();

        if ($existing) {
            $newId = $existing;
            echo "Sacado {$sacado['nome']} (Doc: $doc) já existe como cliente ID $newId.\n";
        } else {
            $insertCols = [];
            $insertVals = [];
            $params = [];
            foreach ($clienteCols as $col) {
                if (array_key_exists($col, $sacado)) {
                    $insertCols[] = "`$col`";
                    $insertVals[] = "?";
                    $params[] = $sacado[$col];
                }
            }
            if (empty($insertCols)) continue;

            $sql = "INSERT INTO clientes (" . implode(',', $insertCols) . ") VALUES (" . implode(',', $insertVals) . ")";
            $stmtInsert = $pdo->prepare($sql);
            $stmtInsert->execute($params);
            $newId = $pdo->lastInsertId();
            echo "Sacado {$sacado['nome']} migrado para cliente ID $newId.\n";
        }
        $mappings[$sacado['id']] = $newId;
    }

    // 5. Atualiza recebiveis com os novos IDs
    foreach ($mappings as $oldId => $newId) {
        $stmtUpdate = $pdo->prepare("UPDATE recebiveis SET sacado_id = ? WHERE sacado_id = ?");
        $stmtUpdate->execute([$newId, $oldId]);
    }
    echo "Tabela recebiveis atualizada com novos IDs.\n";

    // 6. Adiciona nova FK em recebiveis
    try {
        $pdo->exec("ALTER TABLE `recebiveis` ADD CONSTRAINT `fk_recebiveis_cliente` FOREIGN KEY (`sacado_id`) REFERENCES `clientes` (`id`)");
        echo "Nova FK fk_recebiveis_cliente adicionada em recebiveis.\n";
    } catch (Exception $e) {
        echo "Aviso: Não foi possível adicionar nova FK em recebiveis: " . $e->getMessage() . "\n";
    }

    // 7. Remove tabela sacados
    $pdo->exec("DROP TABLE IF EXISTS `sacados`");
    echo "Tabela sacados removida.\n";
    
    // 8. Renomear tabela cedentes_socios para clientes_socios se existir
    $stmt = $pdo->query("SHOW TABLES LIKE 'cedentes_socios'");
    if ($stmt->rowCount() > 0) {
        $pdo->exec("RENAME TABLE `cedentes_socios` TO `clientes_socios`");
        // Update FK if needed
        try {
            $pdo->exec("ALTER TABLE `clientes_socios` DROP FOREIGN KEY `cedentes_socios_ibfk_1`");
            $pdo->exec("ALTER TABLE `clientes_socios` CHANGE `cedente_id` `cliente_id` int(11) NOT NULL");
            $pdo->exec("ALTER TABLE `clientes_socios` ADD CONSTRAINT `fk_clientes_socios_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE");
            echo "Tabela cedentes_socios renomeada e FK atualizada.\n";
        } catch (Exception $e) {
            echo "Aviso: Erro ao atualizar cedentes_socios: " . $e->getMessage() . "\n";
        }
    }

    $pdo->commit();
    echo "Migração concluída com sucesso!\n";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Erro na migração: " . $e->getMessage() . "\n";
}
