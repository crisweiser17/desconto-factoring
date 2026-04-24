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
            
            $stmt = $pdo->prepare("SELECT * FROM distribuicao_lucros WHERE MONTH(data) = ? AND YEAR(data) = ? ORDER BY data DESC");
            $stmt->execute([$mes, $ano]);
            $distribuicoes = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'data' => $distribuicoes]);
            break;
            
        case 'add':
            $socio_nome = $_POST['socio_nome'] ?? '';
            $valor = str_replace(',', '.', str_replace('.', '', $_POST['valor'] ?? '0')); // Format to decimal
            $data = $_POST['data'] ?? '';
            
            if (empty($socio_nome) || empty($valor) || empty($data)) {
                echo json_encode(['success' => false, 'message' => 'Campos obrigatórios não preenchidos.']);
                exit;
            }
            
            $stmt = $pdo->prepare("INSERT INTO distribuicao_lucros (socio_nome, valor, data) VALUES (?, ?, ?)");
            $stmt->execute([$socio_nome, $valor, $data]);
            
            echo json_encode(['success' => true, 'message' => 'Distribuição adicionada com sucesso.']);
            break;
            
        case 'delete':
            $id = $_POST['id'] ?? '';
            
            if (empty($id)) {
                echo json_encode(['success' => false, 'message' => 'ID não informado.']);
                exit;
            }
            
            $stmt = $pdo->prepare("DELETE FROM distribuicao_lucros WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode(['success' => true, 'message' => 'Distribuição excluída com sucesso.']);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Ação inválida.']);
            break;
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
}
