<?php
// excluir_operacao.php

// **** PASSO IMPORTANTE: Proteção por Senha ****
// Se você já implementou o sistema de login, adicione esta linha no topo:
require_once 'auth_check.php';
// **********************************************

// --- APENAS PARA DEBUG - REMOVER/COMENTAR EM PRODUÇÃO ---
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
// --- FIM DEBUG ---

require_once 'db_connection.php'; // Conexão $pdo

$operacao_id = null;
$error_message = null;
$success_message = null;

// 1. Validar ID recebido via GET
if (!isset($_GET['id']) || !is_numeric($_GET['id']) || (int)$_GET['id'] <= 0) {
    $error_message = "ID da operação inválido ou não fornecido.";
} else {
    $operacao_id = (int)$_GET['id'];

    // 2. Tentar excluir usando transação para garantir consistência
    try {
        // Inicia a transação
        $pdo->beginTransaction();

        // 2a. PRIMEIRO: Buscar compensações que esta operação criou e reverter status dos recebíveis
        $sql_compensacoes = "SELECT recebivel_compensado_id, valor_compensado FROM compensacoes WHERE operacao_principal_id = :operacao_id";
        $stmt_comp = $pdo->prepare($sql_compensacoes);
        $stmt_comp->bindParam(':operacao_id', $operacao_id, PDO::PARAM_INT);
        $stmt_comp->execute();
        $compensacoes = $stmt_comp->fetchAll(PDO::FETCH_ASSOC);

        // Para cada recebível compensado, atualizar o status
        foreach ($compensacoes as $comp) {
            $recebivel_id = $comp['recebivel_compensado_id'];
            
            // Calcular total compensado APÓS remover esta compensação
            $sql_total_restante = "SELECT COALESCE(SUM(valor_compensado), 0) as total_compensado
                                  FROM compensacoes
                                  WHERE recebivel_compensado_id = :recebivel_id
                                  AND operacao_principal_id != :operacao_id";
            $stmt_total = $pdo->prepare($sql_total_restante);
            $stmt_total->execute([
                ':recebivel_id' => $recebivel_id,
                ':operacao_id' => $operacao_id
            ]);
            $total_restante = $stmt_total->fetchColumn();
            
            // Buscar valor original do recebível
            $sql_recebivel = "SELECT valor_original FROM recebiveis WHERE id = :recebivel_id";
            $stmt_rec_info = $pdo->prepare($sql_recebivel);
            $stmt_rec_info->execute([':recebivel_id' => $recebivel_id]);
            $valor_original = $stmt_rec_info->fetchColumn();
            
            // Determinar novo status
            if ($total_restante == 0) {
                $novo_status = 'Em Aberto';
            } elseif ($total_restante < $valor_original - 0.01) {
                $novo_status = 'Parcialmente Compensado';
            } else {
                $novo_status = 'Compensado';
            }
            
            // Atualizar status do recebível
            $sql_update_status = "UPDATE recebiveis SET status = :status WHERE id = :recebivel_id";
            $stmt_update = $pdo->prepare($sql_update_status);
            $stmt_update->execute([
                ':status' => $novo_status,
                ':recebivel_id' => $recebivel_id
            ]);
        }

        // 2b. Excluir compensações criadas por esta operação
        $sql_delete_compensacoes = "DELETE FROM compensacoes WHERE operacao_principal_id = :operacao_id";
        $stmt_comp_del = $pdo->prepare($sql_delete_compensacoes);
        $stmt_comp_del->bindParam(':operacao_id', $operacao_id, PDO::PARAM_INT);
        $stmt_comp_del->execute();

        // 2c. Excluir os recebíveis associados à operação
        $sql_delete_recebiveis = "DELETE FROM recebiveis WHERE operacao_id = :operacao_id";
        $stmt_rec = $pdo->prepare($sql_delete_recebiveis);
        $stmt_rec->bindParam(':operacao_id', $operacao_id, PDO::PARAM_INT);
        $stmt_rec->execute();

        // 2d. Excluir a operação principal
        $sql_delete_operacao = "DELETE FROM operacoes WHERE id = :id";
        $stmt_op = $pdo->prepare($sql_delete_operacao);
        $stmt_op->bindParam(':id', $operacao_id, PDO::PARAM_INT);
        $stmt_op->execute();

        // Verifica se a operação foi realmente encontrada e excluída
        if ($stmt_op->rowCount() > 0) {
            // Se ambos os deletes funcionaram (ou delete de recebíveis não deu erro)
            // e a operação foi efetivamente deletada, confirma a transação.
            $pdo->commit();
            $success_message = "Operação e seus recebíveis excluídos com sucesso!";
        } else {
            // A operação com o ID fornecido não foi encontrada no banco.
            // Faz rollback por segurança, embora nada deva ter sido alterado na tabela 'operacoes'.
            // Recebíveis (se existissem) foram deletados na etapa anterior antes do rollback.
            // Idealmente, verificar a existência da operação antes de deletar recebíveis seria mais robusto,
            // mas para simplificar, assumimos que o ID vem de um link válido.
            $pdo->rollBack();
            $error_message = "Operação com ID " . htmlspecialchars($operacao_id) . " não encontrada para exclusão.";
        }

    } catch (PDOException $e) {
        // Se qualquer erro ocorrer durante a transação, desfaz tudo (rollback)
        if ($pdo->inTransaction()) { // Verifica se ainda está em transação antes do rollback
             $pdo->rollBack();
        }
        // Em produção, é melhor logar o erro detalhado e mostrar mensagem genérica.
        // error_log("Erro ao excluir operação ID $operacao_id: " . $e->getMessage());
        $error_message = "Erro no banco de dados ao tentar excluir a operação. ";
        // Para debug: $error_message .= "Detalhes: " . $e->getMessage();
    } catch (Exception $e) {
        // Captura outros erros gerais inesperados
         if ($pdo->inTransaction()) {
            $pdo->rollBack();
         }
        // error_log("Erro geral ao excluir operação ID $operacao_id: " . $e->getMessage());
        $error_message = "Ocorreu um erro inesperado durante a exclusão. ";
         // Para debug: $error_message .= "Detalhes: " . $e->getMessage();
    }
}

// 3. Redirecionar de volta para a lista com mensagem de status apropriada
if ($success_message) {
    // Redireciona com status de sucesso
    header("Location: listar_operacoes.php?status=deleted");
    exit;
} else {
    // Se houve erro, $error_message deve estar definido
    if (!$error_message) {
        $error_message = "Ocorreu um erro desconhecido durante a exclusão."; // Mensagem padrão
    }
    // Redireciona com status de erro e a mensagem
    header("Location: listar_operacoes.php?status=error&msg=" . urlencode($error_message));
    exit;
}

// O script não deve chegar aqui, mas por garantia:
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head><title>Excluindo...</title></head>
<body>Redirecionando...</body>
</html>
