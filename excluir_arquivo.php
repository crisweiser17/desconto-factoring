<?php
// excluir_arquivo.php
header('Content-Type: application/json');

// Inicia a sessão se ainda não foi iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica se o usuário está logado
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Usuário não autenticado. Faça login novamente.']);
    exit;
}

// Inclui a conexão com o banco
require_once 'db_connection.php';

try {
    // Verifica se é uma requisição POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método não permitido.');
    }

    // Verifica se ID foi fornecido
    if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
        throw new Exception('ID do arquivo é obrigatório e deve ser numérico.');
    }

    $arquivo_id = (int)$_POST['id'];

    // Busca informações do arquivo
    $stmt = $pdo->prepare("
        SELECT 
            oa.nome_original,
            oa.nome_arquivo,
            oa.caminho_arquivo,
            oa.operacao_id,
            o.id as operacao_existe
        FROM operacao_arquivos oa
        INNER JOIN operacoes o ON oa.operacao_id = o.id
        WHERE oa.id = ? AND oa.ativo = 1
    ");
    
    $stmt->execute([$arquivo_id]);
    $arquivo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$arquivo) {
        throw new Exception('Arquivo não encontrado ou já foi removido.');
    }

    // Inicia transação
    $pdo->beginTransaction();

    try {
        // Marca arquivo como inativo no banco (soft delete)
        $stmt_delete = $pdo->prepare("
            UPDATE operacao_arquivos 
            SET ativo = 0, 
                data_exclusao = NOW(),
                usuario_exclusao = ?
            WHERE id = ?
        ");
        
        $usuario = $_SESSION['username'] ?? 'sistema';
        $stmt_delete->execute([$usuario, $arquivo_id]);

        // Tenta reconstruir o caminho relativo atual
        $upload_dir = __DIR__ . '/uploads/operacoes/';
        $caminho_reconstruido = $upload_dir . $arquivo['operacao_id'] . '/' . $arquivo['nome_arquivo'];
        
        $caminho_final = '';
        if (file_exists($caminho_reconstruido)) {
            $caminho_final = $caminho_reconstruido;
        } elseif (file_exists($arquivo['caminho_arquivo'])) {
            $caminho_final = $arquivo['caminho_arquivo'];
        }

        // Tenta remover arquivo físico (opcional - pode manter para auditoria)
        $arquivo_removido = false;
        if (!empty($caminho_final) && file_exists($caminho_final)) {
            if (unlink($caminho_final)) {
                $arquivo_removido = true;
            }
        } else {
            // Arquivo físico já não existe
            $arquivo_removido = true;
        }

        // Log da exclusão (opcional)
        try {
            $stmt_log = $pdo->prepare("
                INSERT INTO operacao_arquivos_log (arquivo_id, operacao_id, acao, usuario, data_acao, observacoes) 
                VALUES (?, ?, 'exclusao', ?, NOW(), ?)
            ");
            $observacoes = $arquivo_removido ? 'Arquivo físico removido' : 'Arquivo físico não encontrado ou não pôde ser removido';
            $stmt_log->execute([$arquivo_id, $arquivo['operacao_id'], $usuario, $observacoes]);
        } catch (Exception $e) {
            // Log falhou, mas não impede a exclusão
            error_log("Erro ao registrar log de exclusão: " . $e->getMessage());
        }

        // Confirma transação
        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Arquivo excluído com sucesso.',
            'arquivo_fisico_removido' => $arquivo_removido
        ]);

    } catch (Exception $e) {
        // Desfaz transação em caso de erro
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Erro no banco de dados: ' . $e->getMessage()
    ]);
}
?>