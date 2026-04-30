<?php require_once 'auth_check.php'; ?>
<?php
// excluir_cliente.php
require_once 'db_connection.php'; // Conexão $pdo

// 1. Verifica se o ID foi passado via GET e é um número válido
if (isset($_GET['id']) && filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT)) {
    $clienteIdParaExcluir = (int)$_GET['id'];

    try {
        // ---------------------------------------------------------
        // 2. VERIFICAÇÃO: Checar se existem operações associadas
        // ---------------------------------------------------------
        $sqlCheck = "SELECT 1 -- Seleciona apenas 1 para indicar existência
                     FROM operacoes o
                     WHERE o.cliente_id = :cliente_id
                     LIMIT 1"; // Para assim que encontrar o primeiro

        $stmtCheck = $pdo->prepare($sqlCheck);
        $stmtCheck->bindParam(':cliente_id', $clienteIdParaExcluir, PDO::PARAM_INT);
        $stmtCheck->execute();

        // ---------------------------------------------------------
        // 3. DECISÃO: Excluir ou bloquear?
        // ---------------------------------------------------------
        if ($stmtCheck->fetchColumn()) {
            // Se fetchColumn() retornar 1 (ou qualquer valor true), significa que encontrou operação(ões).
            // Bloqueia a exclusão e redireciona com erro.
            header("Location: listar_clientes.php?status=error&msg=" . urlencode("Cliente ID " . $clienteIdParaExcluir . " não pode ser excluído pois possui operações associadas."));
            exit;
        } else {
            // Nenhuma operação encontrada, pode prosseguir com a exclusão.

            // ---------------------------------------------------------
            // 4. EXECUÇÃO DO DELETE (somente se não houver operações)
            // ---------------------------------------------------------
            $pdo->beginTransaction(); // Opcional, mas bom para DELETE

            // Primeiro excluir sócios associados
            $sqlDeleteSocios = "DELETE FROM clientes_socios WHERE cliente_id = :id";
            $stmtDeleteSocios = $pdo->prepare($sqlDeleteSocios);
            $stmtDeleteSocios->bindParam(':id', $clienteIdParaExcluir, PDO::PARAM_INT);
            $stmtDeleteSocios->execute();

            // Depois excluir o cliente
            $sqlDelete = "DELETE FROM clientes WHERE id = :id";
            $stmtDelete = $pdo->prepare($sqlDelete);
            $stmtDelete->bindParam(':id', $clienteIdParaExcluir, PDO::PARAM_INT);

            if ($stmtDelete->execute()) {
                // Verifica se alguma linha foi realmente deletada
                if ($stmtDelete->rowCount() > 0) {
                    $pdo->commit(); // Confirma a exclusão
                    header("Location: listar_clientes.php?status=success&msg=" . urlencode("Cliente ID " . $clienteIdParaExcluir . " excluído com sucesso."));
                    exit;
                } else {
                    // Nenhuma linha afetada, o ID provavelmente não existia mais
                    $pdo->rollBack(); // Desfaz a transação (embora nada tenha sido feito)
                    header("Location: listar_clientes.php?status=error&msg=" . urlencode("Cliente com ID " . $clienteIdParaExcluir . " não foi encontrado para exclusão."));
                    exit;
                }
            } else {
                 // Erro na execução do DELETE
                 $pdo->rollBack();
                 error_log("Erro ao tentar executar DELETE para cliente ID: " . $clienteIdParaExcluir);
                 header("Location: listar_clientes.php?status=error&msg=" . urlencode("Erro no banco ao tentar excluir o cliente."));
                 exit;
            }
        }

    } catch (PDOException $e) {
        // Erro geral de banco de dados (durante a verificação ou exclusão)
         if ($pdo->inTransaction()) { // Garante rollback se erro ocorreu durante a transação DELETE
            $pdo->rollBack();
         }
        error_log("Erro PDO ao verificar/excluir cliente ID $clienteIdParaExcluir: " . $e->getMessage());
        header("Location: listar_clientes.php?status=error&msg=" . urlencode("Erro no banco de dados: Verifique os logs. [" . $e->getCode() . "]")); // Evita expor $e->getMessage() diretamente
        exit;
    }

} else {
    // Se ID inválido ou não fornecido, redireciona para a lista
    header("Location: listar_clientes.php?status=error&msg=" . urlencode("ID inválido para exclusão."));
    exit;
}
?>