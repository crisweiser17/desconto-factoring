<?php
session_start();
require_once 'db_connection.php';

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Não autorizado.']);
    exit;
}

$action = $_REQUEST['action'] ?? '';

try {
    switch ($action) {
        case 'list':
            $mes = $_GET['mes'] ?? date('m');
            $ano = $_GET['ano'] ?? date('Y');
            
            $stmt = $pdo->prepare("SELECT * FROM despesas WHERE MONTH(data_despesa) = ? AND YEAR(data_despesa) = ? ORDER BY data_despesa DESC");
            $stmt->execute([$mes, $ano]);
            $despesas = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'data' => $despesas]);
            break;
            
        case 'add':
            $titulo = $_POST['titulo'] ?? '';
            $descricao = $_POST['descricao'] ?? '';
            $valor = str_replace(',', '.', str_replace('.', '', $_POST['valor'] ?? '0')); // Format to decimal
            $data_despesa = $_POST['data_despesa'] ?? '';
            
            if (empty($titulo) || empty($valor) || empty($data_despesa)) {
                echo json_encode(['success' => false, 'message' => 'Campos obrigatórios não preenchidos.']);
                exit;
            }
            
            $stmt = $pdo->prepare("INSERT INTO despesas (titulo, descricao, valor, data_despesa) VALUES (?, ?, ?, ?)");
            $stmt->execute([$titulo, $descricao, $valor, $data_despesa]);
            
            echo json_encode(['success' => true, 'message' => 'Despesa adicionada com sucesso.']);
            break;
            
        case 'delete':
            $id = $_POST['id'] ?? '';
            
            if (empty($id)) {
                echo json_encode(['success' => false, 'message' => 'ID não informado.']);
                exit;
            }
            
            $stmt = $pdo->prepare("DELETE FROM despesas WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode(['success' => true, 'message' => 'Despesa excluída com sucesso.']);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Ação inválida.']);
            break;
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
}
