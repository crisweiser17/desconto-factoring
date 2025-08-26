<?php
// listar_arquivos.php
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

/**
 * Função para formatar tamanho de arquivo
 */
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

/**
 * Função para obter ícone baseado no tipo de arquivo
 */
function getFileIcon($extension) {
    $icons = [
        'pdf' => 'bi-file-earmark-pdf',
        'doc' => 'bi-file-earmark-word',
        'docx' => 'bi-file-earmark-word',
        'xls' => 'bi-file-earmark-excel',
        'xlsx' => 'bi-file-earmark-excel',
        'jpg' => 'bi-file-earmark-image',
        'jpeg' => 'bi-file-earmark-image',
        'png' => 'bi-file-earmark-image',
        'gif' => 'bi-file-earmark-image',
        'webp' => 'bi-file-earmark-image',
        'txt' => 'bi-file-earmark-text'
    ];
    
    return $icons[strtolower($extension)] ?? 'bi-file-earmark';
}

try {
    // Verifica se operacao_id foi fornecido
    if (!isset($_GET['operacao_id']) || !is_numeric($_GET['operacao_id'])) {
        throw new Exception('ID da operação é obrigatório e deve ser numérico.');
    }

    $operacao_id = (int)$_GET['operacao_id'];

    // Verifica se a operação existe
    $stmt = $pdo->prepare("SELECT id FROM operacoes WHERE id = ?");
    $stmt->execute([$operacao_id]);
    if (!$stmt->fetchColumn()) {
        throw new Exception('Operação não encontrada.');
    }

    // Busca arquivos da operação
    $stmt = $pdo->prepare("
        SELECT 
            id,
            nome_original,
            nome_arquivo,
            tipo_arquivo,
            extensao,
            tamanho_bytes,
            data_upload,
            usuario_upload,
            descricao
        FROM operacao_arquivos 
        WHERE operacao_id = ? AND ativo = 1 
        ORDER BY data_upload DESC
    ");
    
    $stmt->execute([$operacao_id]);
    $arquivos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formata dados para resposta
    $arquivos_formatados = [];
    foreach ($arquivos as $arquivo) {
        $arquivos_formatados[] = [
            'id' => $arquivo['id'],
            'nome_original' => $arquivo['nome_original'],
            'nome_arquivo' => $arquivo['nome_arquivo'],
            'tipo_arquivo' => $arquivo['tipo_arquivo'],
            'extensao' => $arquivo['extensao'],
            'tamanho_bytes' => $arquivo['tamanho_bytes'],
            'tamanho_formatado' => formatFileSize($arquivo['tamanho_bytes']),
            'data_upload' => $arquivo['data_upload'],
            'data_upload_formatada' => date('d/m/Y H:i', strtotime($arquivo['data_upload'])),
            'usuario_upload' => $arquivo['usuario_upload'],
            'descricao' => $arquivo['descricao'],
            'icone' => getFileIcon($arquivo['extensao']),
            'download_url' => "download_arquivo.php?id=" . $arquivo['id'],
            'pode_visualizar' => in_array(strtolower($arquivo['extensao']), ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'txt'])
        ];
    }

    echo json_encode([
        'success' => true,
        'arquivos' => $arquivos_formatados,
        'total' => count($arquivos_formatados),
        'operacao_id' => $operacao_id
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'arquivos' => [],
        'total' => 0
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Erro no banco de dados: ' . $e->getMessage(),
        'arquivos' => [],
        'total' => 0
    ]);
}
?>