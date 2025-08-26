<?php
// download_arquivo.php

// Inicia a sessão se ainda não foi iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica se o usuário está logado
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(403);
    die('Acesso negado. Faça login para continuar.');
}

// Inclui a conexão com o banco
require_once 'db_connection.php';

/**
 * Função para obter tipo MIME seguro para download
 */
function getSafeMimeType($extension) {
    $mimeTypes = [
        'pdf' => 'application/pdf',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'txt' => 'text/plain'
    ];
    
    return $mimeTypes[strtolower($extension)] ?? 'application/octet-stream';
}

try {
    // Verifica se ID foi fornecido
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        throw new Exception('ID do arquivo é obrigatório e deve ser numérico.');
    }

    $arquivo_id = (int)$_GET['id'];

    // Busca informações do arquivo
    $stmt = $pdo->prepare("
        SELECT 
            oa.nome_original,
            oa.nome_arquivo,
            oa.tipo_arquivo,
            oa.extensao,
            oa.tamanho_bytes,
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
        throw new Exception('Arquivo não encontrado ou foi removido.');
    }

    // Verifica se o arquivo existe fisicamente
    if (!file_exists($arquivo['caminho_arquivo'])) {
        throw new Exception('Arquivo físico não encontrado no servidor.');
    }

    // Verifica se o arquivo é legível
    if (!is_readable($arquivo['caminho_arquivo'])) {
        throw new Exception('Arquivo não pode ser lido. Verifique as permissões.');
    }

    // Determina se deve exibir inline ou forçar download
    $inline_types = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'txt'];
    $disposition = in_array(strtolower($arquivo['extensao']), $inline_types) ? 'inline' : 'attachment';
    
    // Se foi solicitado download forçado
    if (isset($_GET['download']) && $_GET['download'] === '1') {
        $disposition = 'attachment';
    }

    // Limpa qualquer saída anterior
    if (ob_get_level()) {
        ob_end_clean();
    }

    // Define headers para download
    header('Content-Type: ' . getSafeMimeType($arquivo['extensao']));
    header('Content-Length: ' . $arquivo['tamanho_bytes']);
    header('Content-Disposition: ' . $disposition . '; filename="' . addslashes($arquivo['nome_original']) . '"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    header('Expires: 0');

    // Para arquivos grandes, usar readfile com buffer
    if ($arquivo['tamanho_bytes'] > 1024 * 1024) { // > 1MB
        $handle = fopen($arquivo['caminho_arquivo'], 'rb');
        if ($handle === false) {
            throw new Exception('Erro ao abrir arquivo para leitura.');
        }
        
        while (!feof($handle)) {
            echo fread($handle, 8192); // Lê em chunks de 8KB
            flush();
        }
        fclose($handle);
    } else {
        // Para arquivos pequenos, usar readfile diretamente
        readfile($arquivo['caminho_arquivo']);
    }

    // Log do download (opcional)
    try {
        $stmt_log = $pdo->prepare("
            INSERT INTO operacao_arquivos_log (arquivo_id, operacao_id, acao, usuario, data_acao) 
            VALUES (?, ?, 'download', ?, NOW())
        ");
        $usuario = $_SESSION['username'] ?? 'sistema';
        $stmt_log->execute([$arquivo_id, $arquivo['operacao_id'], $usuario]);
    } catch (Exception $e) {
        // Log falhou, mas não impede o download
        error_log("Erro ao registrar log de download: " . $e->getMessage());
    }

    exit;

} catch (Exception $e) {
    http_response_code(404);
    echo "Erro: " . htmlspecialchars($e->getMessage());
    exit;
} catch (PDOException $e) {
    http_response_code(500);
    echo "Erro no banco de dados: " . htmlspecialchars($e->getMessage());
    exit;
}
?>