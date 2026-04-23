<?php
// upload_arquivos.php
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

// Configurações de upload
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('MAX_FILES_PER_OPERATION', 20);
define('UPLOAD_DIR', __DIR__ . '/uploads/operacoes/');

// Tipos de arquivo permitidos
$allowed_types = [
    'application/pdf',
    'image/jpeg',
    'image/png', 
    'image/gif',
    'image/webp',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'text/plain'
];

// Extensões permitidas
$allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'doc', 'docx', 'xls', 'xlsx', 'txt'];

/**
 * Função para validar e sanitizar nome de arquivo
 */
function sanitizeFileName($filename) {
    // Remove caracteres especiais e espaços
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
    // Remove múltiplos underscores consecutivos
    $filename = preg_replace('/_+/', '_', $filename);
    // Remove underscores no início e fim
    $filename = trim($filename, '_');
    return $filename;
}

/**
 * Função para gerar nome único de arquivo
 */
function generateUniqueFileName($originalName, $operacaoId) {
    $pathInfo = pathinfo($originalName);
    $extension = strtolower($pathInfo['extension'] ?? '');
    $baseName = sanitizeFileName($pathInfo['filename'] ?? 'arquivo');
    
    $timestamp = date('Y-m-d_H-i-s');
    $randomString = substr(md5(uniqid(rand(), true)), 0, 8);
    
    return "op{$operacaoId}_{$timestamp}_{$randomString}_{$baseName}.{$extension}";
}

/**
 * Função para criar diretório se não existir
 */
function createDirectoryIfNotExists($path) {
    if (!is_dir($path)) {
        if (!mkdir($path, 0755, true)) {
            throw new Exception("Erro ao criar diretório: {$path}");
        }
    }
    return true;
}

/**
 * Função para verificar se operação existe
 */
function operacaoExists($operacaoId, $pdo) {
    try {
        $stmt = $pdo->prepare("SELECT id FROM operacoes WHERE id = ?");
        $stmt->execute([$operacaoId]);
        return $stmt->fetchColumn() !== false;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Função para contar arquivos existentes da operação
 */
function countExistingFiles($operacaoId, $pdo) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM operacao_arquivos WHERE operacao_id = ? AND ativo = 1");
        $stmt->execute([$operacaoId]);
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

// Processa o upload
try {
    // Verifica se é uma requisição POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método não permitido.');
    }

    // Verifica se operacao_id foi fornecido
    if (!isset($_POST['operacao_id']) || !is_numeric($_POST['operacao_id'])) {
        throw new Exception('ID da operação é obrigatório e deve ser numérico.');
    }

    $operacao_id = (int)$_POST['operacao_id'];

    // Verifica se a operação existe
    if (!operacaoExists($operacao_id, $pdo)) {
        throw new Exception('Operação não encontrada.');
    }

    // Verifica se arquivos foram enviados
    if (!isset($_FILES['arquivos']) || empty($_FILES['arquivos']['name'][0])) {
        throw new Exception('Nenhum arquivo foi enviado.');
    }

    // Conta arquivos existentes
    $existingFiles = countExistingFiles($operacao_id, $pdo);
    $newFilesCount = count($_FILES['arquivos']['name']);

    // Verifica limite de arquivos
    if (($existingFiles + $newFilesCount) > MAX_FILES_PER_OPERATION) {
        throw new Exception("Limite de " . MAX_FILES_PER_OPERATION . " arquivos por operação excedido. Arquivos existentes: {$existingFiles}");
    }

    // Cria diretório da operação
    $operationDir = UPLOAD_DIR . $operacao_id . '/';
    createDirectoryIfNotExists($operationDir);

    $uploadedFiles = [];
    $errors = [];

    // Processa cada arquivo
    for ($i = 0; $i < $newFilesCount; $i++) {
        try {
            // Verifica se houve erro no upload
            if ($_FILES['arquivos']['error'][$i] !== UPLOAD_ERR_OK) {
                $errors[] = "Erro no upload do arquivo " . ($_FILES['arquivos']['name'][$i] ?? "#{$i}") . ": " . $_FILES['arquivos']['error'][$i];
                continue;
            }

            $originalName = $_FILES['arquivos']['name'][$i];
            $tmpName = $_FILES['arquivos']['tmp_name'][$i];
            $fileSize = $_FILES['arquivos']['size'][$i];
            $fileType = $_FILES['arquivos']['type'][$i];

            // Validações
            if ($fileSize > MAX_FILE_SIZE) {
                $errors[] = "Arquivo '{$originalName}' excede o tamanho máximo de " . (MAX_FILE_SIZE / 1024 / 1024) . "MB";
                continue;
            }

            // Verifica extensão
            $pathInfo = pathinfo($originalName);
            $extension = strtolower($pathInfo['extension'] ?? '');
            
            if (!in_array($extension, $allowed_extensions)) {
                $errors[] = "Tipo de arquivo '{$originalName}' não permitido. Extensões aceitas: " . implode(', ', $allowed_extensions);
                continue;
            }

            // Verifica tipo MIME (dupla verificação)
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $detectedType = $finfo->file($tmpName);

            if (!in_array($detectedType, $allowed_types)) {
                $errors[] = "Tipo MIME do arquivo '{$originalName}' não permitido: {$detectedType}";
                continue;
            }

            // Gera nome único
            $uniqueFileName = generateUniqueFileName($originalName, $operacao_id);
            $filePath = $operationDir . $uniqueFileName;

            // Move arquivo
            if (!move_uploaded_file($tmpName, $filePath)) {
                $errors[] = "Erro ao mover arquivo '{$originalName}' para o destino";
                continue;
            }

            // Salva no banco de dados
            $stmt = $pdo->prepare("
                INSERT INTO operacao_arquivos 
                (operacao_id, nome_original, nome_arquivo, tipo_arquivo, extensao, tamanho_bytes, caminho_arquivo, usuario_upload, descricao) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $descricao = $_POST['descricao'][$i] ?? null;
            $usuario = $_SESSION['username'] ?? 'sistema';

            $stmt->execute([
                $operacao_id,
                $originalName,
                $uniqueFileName,
                $detectedType,
                $extension,
                $fileSize,
                $filePath,
                $usuario,
                $descricao
            ]);

            $uploadedFiles[] = [
                'id' => $pdo->lastInsertId(),
                'nome_original' => $originalName,
                'nome_arquivo' => $uniqueFileName,
                'tamanho' => $fileSize,
                'tipo' => $detectedType
            ];

        } catch (Exception $e) {
            $errors[] = "Erro ao processar arquivo '{$originalName}': " . $e->getMessage();
        }
    }

    // Resposta
    $response = [
        'success' => !empty($uploadedFiles),
        'uploaded_files' => $uploadedFiles,
        'errors' => $errors,
        'total_uploaded' => count($uploadedFiles),
        'total_errors' => count($errors)
    ];

    if (!empty($uploadedFiles)) {
        $response['message'] = count($uploadedFiles) . " arquivo(s) enviado(s) com sucesso.";
    }

    if (!empty($errors)) {
        $response['message'] = (isset($response['message']) ? $response['message'] . ' ' : '') . 
                              count($errors) . " erro(s) encontrado(s).";
    }

    echo json_encode($response);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'uploaded_files' => [],
        'errors' => [$e->getMessage()]
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Erro no banco de dados: ' . $e->getMessage(),
        'uploaded_files' => [],
        'errors' => ['Erro no banco de dados']
    ]);
}
?>