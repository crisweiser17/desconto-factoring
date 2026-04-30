<?php
// update_v2.php
// Script para aplicar as migrações referentes às atualizações de "Empréstimos"
// É idempotente, pode ser rodado múltiplas vezes sem quebrar o banco.

require_once 'db_connection.php';

echo "<h1>Atualização V2: Funcionalidade de Empréstimos</h1>";

try {
    // Inicia a verificação de colunas para a tabela `operacoes`
    $stmtOperacoes = $pdo->query("SHOW COLUMNS FROM `operacoes`");
    $columnsOperacoes = $stmtOperacoes->fetchAll(PDO::FETCH_COLUMN);

    echo "<h3>Tabela: operacoes</h3><ul>";

    // Verifica e adiciona a coluna `tipo_operacao`
    if (!in_array('tipo_operacao', $columnsOperacoes)) {
        $pdo->exec("ALTER TABLE `operacoes` ADD COLUMN `tipo_operacao` ENUM('antecipacao', 'emprestimo') NOT NULL DEFAULT 'antecipacao' AFTER `tipo_pagamento`");
        echo "<li>Coluna <b>tipo_operacao</b> adicionada com sucesso.</li>";
        
        // Atualizar os registros antigos para garantir que estão como 'antecipacao'
        $pdo->exec("UPDATE `operacoes` SET `tipo_operacao` = 'antecipacao' WHERE `tipo_operacao` IS NULL OR `tipo_operacao` = ''");
        echo "<li>Registros antigos atualizados para o tipo 'antecipacao'.</li>";
    } else {
        echo "<li>Coluna <b>tipo_operacao</b> já existe.</li>";
    }

    // Verifica e adiciona a coluna `tem_garantia`
    if (!in_array('tem_garantia', $columnsOperacoes)) {
        $pdo->exec("ALTER TABLE `operacoes` ADD COLUMN `tem_garantia` TINYINT(1) NOT NULL DEFAULT 0 AFTER `tipo_operacao`");
        echo "<li>Coluna <b>tem_garantia</b> adicionada com sucesso.</li>";
    } else {
        echo "<li>Coluna <b>tem_garantia</b> já existe.</li>";
    }

    // Verifica e adiciona a coluna `descricao_garantia`
    if (!in_array('descricao_garantia', $columnsOperacoes)) {
        $pdo->exec("ALTER TABLE `operacoes` ADD COLUMN `descricao_garantia` TEXT DEFAULT NULL AFTER `tem_garantia`");
        echo "<li>Coluna <b>descricao_garantia</b> adicionada com sucesso.</li>";
    } else {
        echo "<li>Coluna <b>descricao_garantia</b> já existe.</li>";
    }
    echo "</ul>";

    // Inicia a verificação para a tabela `recebiveis`
    echo "<h3>Tabela: recebiveis</h3><ul>";
    
    // Modificar a coluna tipo_recebivel para garantir que os enums corretos existam
    $sqlEnumRecebiveis = "ALTER TABLE `recebiveis` MODIFY COLUMN `tipo_recebivel` ENUM('cheque', 'duplicata', 'nota_promissoria', 'boleto', 'fatura', 'nota_fiscal', 'parcela_emprestimo', 'outros') COLLATE utf8mb4_unicode_ci DEFAULT 'duplicata' COMMENT 'Tipo do recebível'";
    $pdo->exec($sqlEnumRecebiveis);
    echo "<li>Coluna <b>tipo_recebivel</b> atualizada para suportar 'parcela_emprestimo'.</li>";
    
    echo "</ul>";
    
    echo "<h2 style='color: green;'>Atualização concluída com sucesso!</h2>";

} catch (PDOException $e) {
    echo "<h2 style='color: red;'>Erro durante a atualização:</h2>";
    echo "<p><strong>Detalhes:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
} catch (Exception $e) {
    echo "<h2 style='color: red;'>Erro geral:</h2>";
    echo "<p><strong>Detalhes:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>