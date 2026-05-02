<?php
require_once 'auth_check.php';
require_once 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: listar_leads.php');
    exit;
}

$leadId = isset($_POST['id']) && is_numeric($_POST['id']) ? (int)$_POST['id'] : null;
$empresa = trim($_POST['empresa'] ?? '');
$nomeContato = trim($_POST['nome_contato'] ?? '');
$telefone = trim($_POST['telefone'] ?? '');
$origem = $_POST['origem'] ?? 'receptivo';
$estagio = $_POST['estagio'] ?? 'novo';
$responsavelId = !empty($_POST['responsavel_id']) ? (int)$_POST['responsavel_id'] : null;
$dataVisita = trim($_POST['data_visita_agendada'] ?? '');
$observacoes = trim($_POST['observacoes'] ?? '');

$origensValidas = ['receptivo', 'ativo'];
$estagiosValidos = ['novo', 'visita_agendada', 'visita_feita', 'aprovado', 'perdido', 'convertido'];

if ($empresa === '' || $nomeContato === '') {
    $redirect = $leadId ? "form_lead.php?id={$leadId}" : 'form_lead.php';
    header("Location: {$redirect}&status=error&msg=" . urlencode('Empresa e nome do contato são obrigatórios.'));
    exit;
}
if (!in_array($origem, $origensValidas, true)) $origem = 'receptivo';
if (!in_array($estagio, $estagiosValidos, true)) $estagio = 'novo';

$dataVisitaSql = null;
if ($dataVisita !== '') {
    $dt = DateTime::createFromFormat('Y-m-d\TH:i', $dataVisita) ?: DateTime::createFromFormat('Y-m-d H:i:s', $dataVisita);
    if ($dt) $dataVisitaSql = $dt->format('Y-m-d H:i:s');
}

try {
    $pdo->beginTransaction();

    if ($leadId) {
        $stmtAtual = $pdo->prepare('SELECT estagio FROM leads WHERE id = :id');
        $stmtAtual->execute([':id' => $leadId]);
        $estagioAnterior = $stmtAtual->fetchColumn();
        if ($estagioAnterior === false) {
            $pdo->rollBack();
            header('Location: listar_leads.php?status=error&msg=' . urlencode('Lead não encontrado.'));
            exit;
        }

        $sql = "UPDATE leads SET
                    empresa = :empresa,
                    nome_contato = :nome_contato,
                    telefone = :telefone,
                    origem = :origem,
                    estagio = :estagio,
                    responsavel_id = :responsavel_id,
                    data_visita_agendada = :data_visita,
                    observacoes = :observacoes
                WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':empresa' => $empresa,
            ':nome_contato' => $nomeContato,
            ':telefone' => $telefone,
            ':origem' => $origem,
            ':estagio' => $estagio,
            ':responsavel_id' => $responsavelId,
            ':data_visita' => $dataVisitaSql,
            ':observacoes' => $observacoes,
            ':id' => $leadId,
        ]);

        if ($estagioAnterior !== $estagio) {
            $stmtHist = $pdo->prepare('INSERT INTO leads_historico (lead_id, estagio_de, estagio_para, usuario_id) VALUES (:lead_id, :de, :para, :usuario)');
            $stmtHist->execute([
                ':lead_id' => $leadId,
                ':de' => $estagioAnterior,
                ':para' => $estagio,
                ':usuario' => $_SESSION['user_id'] ?? null,
            ]);
        }
        $msg = 'Lead atualizado com sucesso.';
    } else {
        $sql = "INSERT INTO leads (empresa, nome_contato, telefone, origem, estagio, responsavel_id, data_visita_agendada, observacoes)
                VALUES (:empresa, :nome_contato, :telefone, :origem, :estagio, :responsavel_id, :data_visita, :observacoes)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':empresa' => $empresa,
            ':nome_contato' => $nomeContato,
            ':telefone' => $telefone,
            ':origem' => $origem,
            ':estagio' => $estagio,
            ':responsavel_id' => $responsavelId,
            ':data_visita' => $dataVisitaSql,
            ':observacoes' => $observacoes,
        ]);
        $leadId = (int)$pdo->lastInsertId();

        $stmtHist = $pdo->prepare('INSERT INTO leads_historico (lead_id, estagio_de, estagio_para, usuario_id, observacao) VALUES (:lead_id, NULL, :para, :usuario, :obs)');
        $stmtHist->execute([
            ':lead_id' => $leadId,
            ':para' => $estagio,
            ':usuario' => $_SESSION['user_id'] ?? null,
            ':obs' => 'Lead criado',
        ]);
        $msg = 'Lead cadastrado com sucesso.';
    }

    $pdo->commit();
    header('Location: listar_leads.php?status=success&msg=' . urlencode($msg));
    exit;
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Erro salvar_lead: ' . $e->getMessage());
    $redirect = $leadId ? "form_lead.php?id={$leadId}" : 'form_lead.php';
    header("Location: {$redirect}&status=error&msg=" . urlencode('Erro ao salvar lead.'));
    exit;
}
