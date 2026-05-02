<?php
require_once 'auth_check.php';
require_once 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: listar_leads.php');
    exit;
}

$leadId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$motivo = trim($_POST['motivo_perda'] ?? '');

if ($leadId <= 0) {
    header('Location: listar_leads.php?status=error&msg=' . urlencode('ID inválido.'));
    exit;
}
if ($motivo === '') {
    header("Location: listar_leads.php?status=error&msg=" . urlencode('Informe o motivo da perda.'));
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('SELECT estagio FROM leads WHERE id = :id');
    $stmt->execute([':id' => $leadId]);
    $estagioAnterior = $stmt->fetchColumn();
    if ($estagioAnterior === false) {
        $pdo->rollBack();
        header('Location: listar_leads.php?status=error&msg=' . urlencode('Lead não encontrado.'));
        exit;
    }

    $upd = $pdo->prepare("UPDATE leads SET estagio = 'perdido', motivo_perda = :motivo WHERE id = :id");
    $upd->execute([':motivo' => $motivo, ':id' => $leadId]);

    $hist = $pdo->prepare('INSERT INTO leads_historico (lead_id, estagio_de, estagio_para, usuario_id, observacao) VALUES (:lead_id, :de, :para, :usuario, :obs)');
    $hist->execute([
        ':lead_id' => $leadId,
        ':de' => $estagioAnterior,
        ':para' => 'perdido',
        ':usuario' => $_SESSION['user_id'] ?? null,
        ':obs' => mb_substr($motivo, 0, 255),
    ]);

    $pdo->commit();
    header('Location: listar_leads.php?status=success&msg=' . urlencode("Lead #{$leadId} arquivado como perdido."));
    exit;
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Erro arquivar_lead: ' . $e->getMessage());
    header('Location: listar_leads.php?status=error&msg=' . urlencode('Erro ao arquivar lead.'));
    exit;
}
