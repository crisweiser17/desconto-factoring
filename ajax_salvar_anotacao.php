<?php
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Usuário não autenticado. Faça login novamente.']);
    exit;
}

require_once 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método não permitido.']);
    exit;
}

// Tenta pegar dados via JSON primeiro
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, TRUE);

if ($input) {
    $operacao_id = isset($input['operacao_id']) ? (int)$input['operacao_id'] : 0;
    $recebivel_id = isset($input['recebivel_id']) && !empty($input['recebivel_id']) ? (int)$input['recebivel_id'] : null;
    $anotacao = isset($input['anotacao']) ? trim($input['anotacao']) : '';
} else {
    // Se não for JSON, pega via $_POST normal
    $operacao_id = isset($_POST['operacao_id']) ? (int)$_POST['operacao_id'] : 0;
    $recebivel_id = isset($_POST['recebivel_id']) && !empty($_POST['recebivel_id']) ? (int)$_POST['recebivel_id'] : null;
    $anotacao = isset($_POST['anotacao']) ? trim($_POST['anotacao']) : '';
}

$usuario_id = $_SESSION['user_id'];

if ($operacao_id <= 0 || empty($anotacao)) {
    echo json_encode(['success' => false, 'error' => 'Dados inválidos. Operação ou anotação não informados.']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO operacao_anotacoes (operacao_id, recebivel_id, usuario_id, anotacao, data_criacao) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$operacao_id, $recebivel_id, $usuario_id, $anotacao]);
    
    echo json_encode(['success' => true, 'message' => 'Anotação salva com sucesso.', 'id' => $pdo->lastInsertId()]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Erro ao salvar anotação no banco de dados.']);
}
