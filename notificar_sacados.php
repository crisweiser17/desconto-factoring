<?php
// notificar_sacados.php
require_once 'auth_check.php';
require_once 'db_connection.php';
require_once 'funcoes_email.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método inválido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$operacao_id = isset($input['operacao_id']) ? (int)$input['operacao_id'] : 0;

if (!$operacao_id) {
    echo json_encode(['success' => false, 'error' => 'ID da operação não informado']);
    exit;
}

try {
    // 1. Buscar a operação e o cedente
    $sql_op = "SELECT o.id, o.data_operacao, c.nome as cedente_nome, c.documento_principal as cedente_cnpj
               FROM operacoes o
               JOIN cedentes c ON o.cedente_id = c.id
               WHERE o.id = :operacao_id";
    $stmt_op = $pdo->prepare($sql_op);
    $stmt_op->bindParam(':operacao_id', $operacao_id, PDO::PARAM_INT);
    $stmt_op->execute();
    $operacao = $stmt_op->fetch(PDO::FETCH_ASSOC);

    if (!$operacao) {
        echo json_encode(['success' => false, 'error' => 'Operação não encontrada']);
        exit;
    }

    // 2. Buscar recebíveis agrupados por sacado
    $sql_rec = "SELECT r.id as recebivel_id, r.valor_original, r.data_vencimento,
                       s.id as sacado_id, s.nome as sacado_nome, s.documento_principal as sacado_cnpj, s.email as sacado_email
                FROM recebiveis r
                JOIN sacados s ON r.sacado_id = s.id
                WHERE r.operacao_id = :operacao_id";
    $stmt_rec = $pdo->prepare($sql_rec);
    $stmt_rec->bindParam(':operacao_id', $operacao_id, PDO::PARAM_INT);
    $stmt_rec->execute();
    $recebiveis = $stmt_rec->fetchAll(PDO::FETCH_ASSOC);

    if (empty($recebiveis)) {
        echo json_encode(['success' => false, 'error' => 'Nenhum recebível/sacado encontrado nesta operação']);
        exit;
    }

    // Agrupar por sacado
    $sacados = [];
    foreach ($recebiveis as $rec) {
        $sid = $rec['sacado_id'];
        if (!isset($sacados[$sid])) {
            $sacados[$sid] = [
                'nome' => $rec['sacado_nome'],
                'cnpj' => $rec['sacado_cnpj'],
                'email' => $rec['sacado_email'],
                'titulos' => [],
                'total' => 0
            ];
        }
        $sacados[$sid]['titulos'][] = $rec;
        $sacados[$sid]['total'] += $rec['valor_original'];
    }

    // 3. Obter configurações (API Key, Template, etc)
    $configPath = __DIR__ . '/config.json';
    $config = [];
    if (file_exists($configPath)) {
        $config = json_decode(file_get_contents($configPath), true);
    }
    
    $api_key = $config['resend_api_key'] ?? '';
    $from_email = $config['resend_from_email'] ?? '';
    $from_name = $config['resend_from_name'] ?? 'Notificações';
    $template_raw = $config['email_template'] ?? '';
    $subject_raw = $config['email_subject'] ?? 'Notificação de Cessão de Crédito - Op #[BORDERO_NUMERO]';
    $cc_email = $config['resend_cc_email'] ?? '';
    $bcc_email = $config['resend_bcc_email'] ?? '';

    if (empty($api_key) || empty($from_email) || empty($template_raw)) {
        echo json_encode(['success' => false, 'error' => 'Configurações de e-mail (Resend) incompletas no painel de configurações.']);
        exit;
    }

    require_once 'lib/Parsedown.php'; // Se existir. Como não temos certeza, usaremos nl2br ou Markdown simples.
    // O sistema pode não ter um parser Markdown. Vamos fazer uma conversão simples de HTML ou enviar em plain/text.
    // Melhor enviar como HTML com formatação básica.

    $resultados = [];
    $sucessos = 0;
    $falhas = 0;

    foreach ($sacados as $sid => $sacado) {
        if (empty($sacado['email'])) {
            $resultados[] = ['sacado' => $sacado['nome'], 'status' => 'falha', 'motivo' => 'Sem e-mail cadastrado'];
            $falhas++;
            continue;
        }

        // Construir tabela de títulos HTML
        $tabela_html = '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse; width: 100%; max-width: 600px;">
            <tr style="background-color: #f2f2f2;">
                <th>Duplicata/ID</th>
                <th>Valor (R$)</th>
                <th>Vencimento</th>
            </tr>';
        
        foreach ($sacado['titulos'] as $t) {
            $vencimento = date('d/m/Y', strtotime($t['data_vencimento']));
            $valor = number_format($t['valor_original'], 2, ',', '.');
            $tabela_html .= "<tr>
                <td style='text-align:center;'>{$t['recebivel_id']}</td>
                <td style='text-align:right;'>R$ {$valor}</td>
                <td style='text-align:center;'>{$vencimento}</td>
            </tr>";
        }
        $tabela_html .= '</table>';

        // O template agora é salvo em HTML puro pelo Quill.js, não precisamos mais usar nl2br e htmlspecialchars
        // Apenas vamos substituir as variáveis
        $html_body = $template_raw;
        
        // Substituir variáveis (os dados do banco precisam de htmlspecialchars para evitar XSS)
        $html_body = str_replace('[CEDENTE_NOME]', htmlspecialchars($operacao['cedente_nome']), $html_body);
        $html_body = str_replace('[CEDENTE_CNPJ]', htmlspecialchars($operacao['cedente_cnpj']), $html_body);
        $html_body = str_replace('[SACADO_NOME]', htmlspecialchars($sacado['nome']), $html_body);
        $html_body = str_replace('[SACADO_CNPJ]', htmlspecialchars($sacado['cnpj']), $html_body);
        $html_body = str_replace('[BORDERO_NUMERO]', htmlspecialchars($operacao['id']), $html_body);
        $html_body = str_replace('[BORDERO_DATA]', date('d/m/Y', strtotime($operacao['data_operacao'])), $html_body);
        $html_body = str_replace('[BORDERO_VALOR]', 'R$ ' . number_format($sacado['total'], 2, ',', '.'), $html_body);
        $html_body = str_replace('[TABELA_TITULOS]', $tabela_html, $html_body); // Inserimos HTML cru no final
        $html_body = str_replace('[CIDADE_DATA]', htmlspecialchars('Data: ' . date('d/m/Y')), $html_body);

        // Substituir variáveis no assunto
        $assunto = $subject_raw;
        $assunto = str_replace('[CEDENTE_NOME]', $operacao['cedente_nome'], $assunto);
        $assunto = str_replace('[CEDENTE_CNPJ]', $operacao['cedente_cnpj'], $assunto);
        $assunto = str_replace('[SACADO_NOME]', $sacado['nome'], $assunto);
        $assunto = str_replace('[SACADO_CNPJ]', $sacado['cnpj'], $assunto);
        $assunto = str_replace('[BORDERO_NUMERO]', $operacao['id'], $assunto);
        $assunto = str_replace('[BORDERO_DATA]', date('d/m/Y', strtotime($operacao['data_operacao'])), $assunto);
        $assunto = str_replace('[BORDERO_VALOR]', 'R$ ' . number_format($sacado['total'], 2, ',', '.'), $assunto);

        // Enviar
        $res = enviar_email_resend($sacado['email'], $assunto, $html_body, $api_key, $from_email, $cc_email, $bcc_email, $from_name);
        
        if ($res['success']) {
            $resultados[] = ['sacado' => $sacado['nome'], 'status' => 'sucesso'];
            $sucessos++;
        } else {
            $resultados[] = ['sacado' => $sacado['nome'], 'status' => 'falha', 'motivo' => $res['error']];
            $falhas++;
        }
    }

    echo json_encode([
        'success' => true,
        'mensagem' => "Envio concluído: $sucessos sucesso(s), $falhas falha(s).",
        'resultados' => $resultados
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
