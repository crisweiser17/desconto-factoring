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

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID de anotação inválido.']);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM operacao_anotacoes WHERE id = ?");
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Anotação excluída com sucesso.']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Anotação não encontrada ou já excluída.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Erro ao excluir anotação no banco de dados.']);
}
