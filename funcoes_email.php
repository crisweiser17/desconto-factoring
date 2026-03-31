<?php
// funcoes_email.php
// Funções para envio de e-mails usando a API REST do Resend

function enviar_email_resend($to, $subject, $html_body, $api_key = null, $from_email = null, $cc = null, $bcc = null) {
    // Se as credenciais não forem passadas diretamente, buscar do config.json
    if (!$api_key || !$from_email) {
        $configPath = __DIR__ . '/config.json';
        if (file_exists($configPath)) {
            $config = json_decode(file_get_contents($configPath), true);
            $api_key = $api_key ?: ($config['resend_api_key'] ?? '');
            $from_email = $from_email ?: ($config['resend_from_email'] ?? '');
        }
    }

    if (empty($api_key) || empty($from_email)) {
        return ['success' => false, 'error' => 'API Key ou E-mail Remetente não configurados.'];
    }

    $data = [
        'from' => 'Sistema Factoring <' . $from_email . '>',
        'to' => is_array($to) ? $to : [$to],
        'subject' => $subject,
        'html' => nl2br($html_body) // Caso seja enviado texto plano com quebras de linha
    ];

    if (!empty($cc)) {
        if (is_string($cc)) {
            // Separar por vírgula e limpar espaços
            $cc = array_map('trim', explode(',', $cc));
        }
        $data['cc'] = $cc;
    }

    if (!empty($bcc)) {
        if (is_string($bcc)) {
            $bcc = array_map('trim', explode(',', $bcc));
        }
        $data['bcc'] = $bcc;
    }

    $ch = curl_init('https://api.resend.com/emails');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $api_key,
        'Content-Type: application/json'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return ['success' => false, 'error' => 'Erro cURL: ' . $error];
    }

    $responseData = json_decode($response, true);

    if ($httpCode >= 200 && $httpCode < 300) {
        return ['success' => true, 'id' => $responseData['id'] ?? null];
    } else {
        return ['success' => false, 'error' => $responseData['message'] ?? 'Erro desconhecido da API Resend (' . $httpCode . ')'];
    }
}
