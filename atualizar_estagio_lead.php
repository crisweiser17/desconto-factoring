<?php
require_once 'auth_check.php';
require_once 'db_connection.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método não permitido']);
    exit;
}

$leadId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$novoEstagio = $_POST['estagio'] ?? '';
$estagiosKanban = ['novo', 'visita_agendada', 'visita_feita', 'aprovado'];

if ($leadId <= 0 || !in_array($novoEstagio, $estagiosKanban, true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Parâmetros inválidos']);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('SELECT estagio FROM leads WHERE id = :id');
    $stmt->execute([':id' => $leadId]);
    $estagioAnterior = $stmt->fetchColumn();
    if ($estagioAnterior === false) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'Lead não encontrado']);
        exit;
    }

    if ($estagioAnterior === $novoEstagio) {
        $pdo->commit();
        echo json_encode(['ok' => true, 'unchanged' => true]);
        exit;
    }

    $upd = $pdo->prepare('UPDATE leads SET estagio = :estagio WHERE id = :id');
    $upd->execute([':estagio' => $novoEstagio, ':id' => $leadId]);

    $hist = $pdo->prepare('INSERT INTO leads_historico (lead_id, estagio_de, estagio_para, usuario_id) VALUES (:lead_id, :de, :para, :usuario)');
    $hist->execute([
        ':lead_id' => $leadId,
        ':de' => $estagioAnterior,
        ':para' => $novoEstagio,
        ':usuario' => $_SESSION['user_id'] ?? null,
    ]);

    $pdo->commit();
    echo json_encode(['ok' => true, 'estagio_de' => $estagioAnterior, 'estagio_para' => $novoEstagio]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Erro atualizar_estagio_lead: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Erro no banco']);
}
