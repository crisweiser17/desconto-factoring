<?php
require_once 'auth_check.php';
require_once 'db_connection.php';

if (!isset($_GET['id']) || !filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT)) {
    header('Location: listar_leads.php?status=error&msg=' . urlencode('ID inválido.'));
    exit;
}

$leadId = (int)$_GET['id'];

try {
    $stmt = $pdo->prepare('SELECT * FROM leads WHERE id = :id');
    $stmt->execute([':id' => $leadId]);
    $lead = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$lead) {
        header('Location: listar_leads.php?status=error&msg=' . urlencode('Lead não encontrado.'));
        exit;
    }

    if (!empty($lead['cliente_id'])) {
        // já convertido — vai direto pra edição do cliente
        header('Location: form_cliente.php?id=' . (int)$lead['cliente_id'] . '&status=success&msg=' . urlencode('Lead já convertido. Complete os dados do cliente.'));
        exit;
    }

    $pdo->beginTransaction();

    $insCliente = $pdo->prepare("INSERT INTO clientes (nome, empresa, telefone, whatsapp, tipo_pessoa)
                                 VALUES (:nome, :empresa, :telefone, :whatsapp, 'JURIDICA')");
    $insCliente->execute([
        ':nome' => $lead['nome_contato'],
        ':empresa' => $lead['empresa'],
        ':telefone' => $lead['telefone'] ?: null,
        ':whatsapp' => $lead['telefone'] ?: null,
    ]);
    $clienteId = (int)$pdo->lastInsertId();

    $estagioAnterior = $lead['estagio'];
    $updLead = $pdo->prepare("UPDATE leads SET cliente_id = :cliente_id, estagio = 'convertido' WHERE id = :id");
    $updLead->execute([':cliente_id' => $clienteId, ':id' => $leadId]);

    $hist = $pdo->prepare('INSERT INTO leads_historico (lead_id, estagio_de, estagio_para, usuario_id, observacao) VALUES (:lead_id, :de, :para, :usuario, :obs)');
    $hist->execute([
        ':lead_id' => $leadId,
        ':de' => $estagioAnterior,
        ':para' => 'convertido',
        ':usuario' => $_SESSION['user_id'] ?? null,
        ':obs' => "Convertido em cliente #{$clienteId}",
    ]);

    $pdo->commit();

    header('Location: form_cliente.php?id=' . $clienteId . '&status=success&msg=' . urlencode('Lead convertido em cliente. Complete os dados restantes (CNPJ, endereço, sócios).'));
    exit;
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Erro converter_lead: ' . $e->getMessage());
    header('Location: listar_leads.php?status=error&msg=' . urlencode('Erro ao converter lead: ' . $e->getMessage()));
    exit;
}
