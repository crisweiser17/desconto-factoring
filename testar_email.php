<?php
// testar_email.php
require_once 'auth_check.php';
require_once 'funcoes_email.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método inválido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$to = $input['email'] ?? '';
$api_key = $input['api_key'] ?? '';
$from_email = $input['from_email'] ?? '';

if (empty($to) || empty($api_key) || empty($from_email)) {
    echo json_encode(['success' => false, 'error' => 'Dados incompletos.']);
    exit;
}

$subject = 'Teste de Integração - Sistema Factoring';
$body = '<h1>Teste Bem Sucedido!</h1>
<p>Se você está recebendo este e-mail, significa que a integração do sistema Factoring com a API do Resend está funcionando perfeitamente.</p>
<p>Data do teste: ' . date('d/m/Y H:i:s') . '</p>';

$result = enviar_email_resend($to, $subject, $body, $api_key, $from_email);

echo json_encode($result);
