<?php require_once 'auth_check.php'; ?>
<?php
// excluir_sacado.php
require_once 'db_connection.php'; // Conexão $pdo

// 1. Verifica se o ID foi passado via GET e é um número válido
if (isset($_GET['id']) && filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT)) {
    $sacadoIdParaExcluir = (int)$_GET['id'];

    try {
        // ---------------------------------------------------------
        // 2. VERIFICAÇÃO: Checar se existem recebíveis associados
        // ---------------------------------------------------------
        // O sacado_id agora está na própria tabela de recebiveis
        $sqlCheck = "SELECT 1 -- Seleciona apenas 1 para indicar existência
                     FROM recebiveis r
                     WHERE r.sacado_id = :sacado_id
                     LIMIT 1"; // Para assim que encontrar o primeiro

        $stmtCheck = $pdo->prepare($sqlCheck);
        $stmtCheck->bindParam(':sacado_id', $sacadoIdParaExcluir, PDO::PARAM_INT);
        $stmtCheck->execute();

        // ---------------------------------------------------------
        // 3. DECISÃO: Excluir ou bloquear?
        // ---------------------------------------------------------
        if ($stmtCheck->fetchColumn()) {
            // Se fetchColumn() retornar 1 (ou qualquer valor true), significa que encontrou recebível(is).
            // Bloqueia a exclusão e redireciona com erro.
            header("Location: listar_sacados.php?status=error&msg=" . urlencode("Sacado ID " . $sacadoIdParaExcluir . " não pode ser excluído pois possui recebíveis associados."));
            exit;
        } else {
            // Nenhum recebível encontrado, pode prosseguir com a exclusão.

            // ---------------------------------------------------------
            // 4. EXECUÇÃO DO DELETE (somente se não houver recebíveis)
            // ---------------------------------------------------------
            $pdo->beginTransaction(); // Opcional, mas bom para DELETE

            $sqlDelete = "DELETE FROM sacados WHERE id = :id";
            $stmtDelete = $pdo->prepare($sqlDelete);
            $stmtDelete->bindParam(':id', $sacadoIdParaExcluir, PDO::PARAM_INT);

            if ($stmtDelete->execute()) {
                // Verifica se alguma linha foi realmente deletada
                if ($stmtDelete->rowCount() > 0) {
                    $pdo->commit(); // Confirma a exclusão
                    header("Location: listar_sacados.php?status=success&msg=" . urlencode("Sacado ID " . $sacadoIdParaExcluir . " excluído com sucesso."));
                    exit;
                } else {
                    // Nenhuma linha afetada, o ID provavelmente não existia mais
                    $pdo->rollBack(); // Desfaz a transação (embora nada tenha sido feito)
                    header("Location: listar_sacados.php?status=error&msg=" . urlencode("Sacado com ID " . $sacadoIdParaExcluir . " não foi encontrado para exclusão."));
                    exit;
                }
            } else {
                 // Erro na execução do DELETE
                 $pdo->rollBack();
                 error_log("Erro ao tentar executar DELETE para sacado ID: " . $sacadoIdParaExcluir);
                 header("Location: listar_sacados.php?status=error&msg=" . urlencode("Erro no banco ao tentar excluir o sacado."));
                 exit;
            }
        }

    } catch (PDOException $e) {
        // Erro geral de banco de dados (durante a verificação ou exclusão)
         if ($pdo->inTransaction()) { // Garante rollback se erro ocorreu durante a transação DELETE
            $pdo->rollBack();
         }
        error_log("Erro PDO ao verificar/excluir sacado ID $sacadoIdParaExcluir: " . $e->getMessage());
        header("Location: listar_sacados.php?status=error&msg=" . urlencode("Erro no banco de dados: Verifique os logs. [" . $e->getCode() . "]")); // Evita expor $e->getMessage() diretamente
        exit;
    }

} else {
    // Se ID inválido ou não fornecido, redireciona para a lista
    header("Location: listar_sacados.php?status=error&msg=" . urlencode("ID inválido para exclusão."));
    exit;
}
?>
