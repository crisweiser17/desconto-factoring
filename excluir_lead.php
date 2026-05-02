<?php
require_once 'auth_check.php';
require_once 'db_connection.php';

if (!isset($_GET['id']) || !filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT)) {
    header('Location: listar_leads.php?status=error&msg=' . urlencode('ID inválido.'));
    exit;
}

$leadId = (int)$_GET['id'];

try {
    $stmt = $pdo->prepare('DELETE FROM leads WHERE id = :id');
    $stmt->execute([':id' => $leadId]);
    if ($stmt->rowCount() > 0) {
        header('Location: listar_leads.php?status=success&msg=' . urlencode("Lead #{$leadId} excluído."));
    } else {
        header('Location: listar_leads.php?status=error&msg=' . urlencode('Lead não encontrado.'));
    }
    exit;
} catch (PDOException $e) {
    error_log('Erro excluir_lead: ' . $e->getMessage());
    header('Location: listar_leads.php?status=error&msg=' . urlencode('Erro ao excluir lead.'));
    exit;
}
