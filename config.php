<?php
require_once 'auth_check.php'; // Proteção de acesso
require_once 'menu.php'; // Inclui o menu

$configFilePath = __DIR__ . '/config.json';
$message = '';
$messageType = '';

// Função para ler o arquivo de configuração
function readConfig($filePath) {
    if (!file_exists($filePath)) {
        // Criar arquivo padrão se não existir
        $defaultConfig = [
            "app_name" => "Factoring",
            "app_version" => "5.2 de abril de 2026",
            "default_taxa_mensal" => 5.00,
            "iof_adicional_rate" => 0.0038,
            "iof_diaria_rate" => 0.000082,
            "resend_api_key" => "",
            "resend_from_name" => "Notificações",
            "resend_from_email" => "notificacoes@seudominio.com",
            "email_subject" => "Notificação de Cessão de Crédito - Borderô #[BORDERO_NUMERO]",
            "email_template" => "## **NOTIFICAÇÃO DE CESSÃO DE CRÉDITO**\n\n**Cedente:** [CEDENTE_NOME] / [CEDENTE_CNPJ]\n**Cessionário:** SUA EMPRESA FACTORING / 00.000.000/0001-00\n**Sacado (Devedor):** [SACADO_NOME] / [SACADO_CNPJ]\n\n---\n\n**Assunto: Cessão de Crédito – Art. 290 do Código Civil**\n\nPrezado(a),\n\nInformamos que os créditos representados pelas duplicatas abaixo foram **cedidos** ao Cessionário acima identificado, por meio de operação de desconto.\n\nNos termos do Art. 290 do Código Civil, esta notificação torna a cessão eficaz perante V.Sa.\n\n---\n\n### **Borderô**\n\nNº: [BORDERO_NUMERO]\nData: [BORDERO_DATA]\nValor Total: [BORDERO_VALOR]\n\n---\n\n### **Títulos Cedidos**\n\n[TABELA_TITULOS]\n\n---\n\n### **Pagamento**\n\nA partir do recebimento desta, **os pagamentos deverão ser feitos exclusivamente ao Cessionário**:\n\nBanco: SEU BANCO\nAgência: 0000\nConta: 00000-0\nFavorecido: SUA EMPRESA FACTORING\nCNPJ: 00.000.000/0001-00\nPIX: sua-chave-pix\n\n---\n\n### **Importante**\n\n* Pagamento ao Cedente após esta notificação **não terá efeito liberatório**.\n* A obrigação de pagamento permanece válida independentemente de concordância com a cessão.\n\n---\n\n**Local e Data:** [CIDADE_DATA]\n\n**Cedente:** _________________________\n\n**Cessionário:** _________________________"
        ];
        file_put_contents($filePath, json_encode($defaultConfig, JSON_PRETTY_PRINT));
        return $defaultConfig;
    }
    $configContent = file_get_contents($filePath);
    $config = json_decode($configContent, true);
    
    // Garantir que os campos de email existam mesmo em configs antigas
    if (!isset($config['app_name'])) $config['app_name'] = 'Factoring';
    if (!isset($config['app_version'])) $config['app_version'] = '5.2 de abril de 2026';
    if (!isset($config['resend_api_key'])) $config['resend_api_key'] = '';
    if (!isset($config['resend_from_name'])) $config['resend_from_name'] = 'Notificações';
    if (!isset($config['resend_from_email'])) $config['resend_from_email'] = 'notificacoes@seudominio.com';
    if (!isset($config['resend_cc_email'])) $config['resend_cc_email'] = '';
    if (!isset($config['resend_bcc_email'])) $config['resend_bcc_email'] = '';
    if (!isset($config['email_subject'])) $config['email_subject'] = "Notificação de Cessão de Crédito - Borderô #[BORDERO_NUMERO]";
    if (!isset($config['email_template'])) $config['email_template'] = "<h2><strong>NOTIFICAÇÃO DE CESSÃO DE CRÉDITO</strong></h2><p><strong>Cedente:</strong> [CEDENTE_NOME] / [CEDENTE_CNPJ]<br><strong>Cessionário:</strong> SUA EMPRESA FACTORING / 00.000.000/0001-00<br><strong>Sacado (Devedor):</strong> [SACADO_NOME] / [SACADO_CNPJ]</p><hr><p><strong>Assunto: Cessão de Crédito – Art. 290 do Código Civil</strong></p><p>Prezado(a),</p><p>Informamos que os créditos representados pelas duplicatas abaixo foram <strong>cedidos</strong> ao Cessionário acima identificado, por meio de operação de desconto.</p><p>Nos termos do Art. 290 do Código Civil, esta notificação torna a cessão eficaz perante V.Sa.</p><hr><h3><strong>Borderô</strong></h3><p>Nº: [BORDERO_NUMERO]<br>Data: [BORDERO_DATA]<br>Valor Total: [BORDERO_VALOR]</p><hr><h3><strong>Títulos Cedidos</strong></h3><p>[TABELA_TITULOS]</p><hr><h3><strong>Pagamento</strong></h3><p>A partir do recebimento desta, <strong>os pagamentos deverão ser feitos exclusivamente ao Cessionário</strong>:</p><p>Banco: SEU BANCO<br>Agência: 0000<br>Conta: 00000-0<br>Favorecido: SUA EMPRESA FACTORING<br>CNPJ: 00.000.000/0001-00<br>PIX: sua-chave-pix</p><hr><h3><strong>Importante</strong></h3><ul><li>Pagamento ao Cedente após esta notificação <strong>não terá efeito liberatório</strong>.</li><li>A obrigação de pagamento permanece válida independentemente de concordância com a cessão.</li></ul><hr><p><strong>Local e Data:</strong> [CIDADE_DATA]</p><p><strong>Cedente:</strong> _________________________</p><p><strong>Cessionário:</strong> _________________________</p>";
    
    // Fallbacks para Dados Bancários
    if (!isset($config['conta_titular'])) $config['conta_titular'] = '';
    if (!isset($config['conta_documento'])) $config['conta_documento'] = '';
    if (!isset($config['conta_banco'])) $config['conta_banco'] = '';
    if (!isset($config['conta_agencia'])) $config['conta_agencia'] = '';
    if (!isset($config['conta_numero'])) $config['conta_numero'] = '';
    if (!isset($config['conta_tipo'])) $config['conta_tipo'] = '';
    if (!isset($config['conta_pix'])) $config['conta_pix'] = '';
    
    // Fallbacks para Dados da Empresa
    if (!isset($config['empresa_representante_nome'])) $config['empresa_representante_nome'] = '';
    if (!isset($config['empresa_representante_cpf'])) $config['empresa_representante_cpf'] = '';
    if (!isset($config['empresa_endereco'])) $config['empresa_endereco'] = '';
    if (!isset($config['empresa_email'])) $config['empresa_email'] = '';
    if (!isset($config['empresa_whatsapp'])) $config['empresa_whatsapp'] = '';
    
    return $config;
}

// Lidar com o envio do formulário de configurações gerais
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_config') {
    $newAppName = $_POST['app_name'] ?? 'Factoring';
    $newAppVersion = '5.2 de abril de 2026';
    $newDefaultTaxaMensal = isset($_POST['default_taxa_mensal']) ? (float)$_POST['default_taxa_mensal'] : null;
    $newIofAdicionalRate = isset($_POST['iof_adicional_rate']) ? (float)$_POST['iof_adicional_rate'] : null;
    $newIofDiariaRate = isset($_POST['iof_diaria_rate']) ? (float)$_POST['iof_diaria_rate'] : null;
    $newResendApiKey = $_POST['resend_api_key'] ?? '';
    $newResendFromName = $_POST['resend_from_name'] ?? 'Notificações';
    $newResendFromEmail = $_POST['resend_from_email'] ?? '';
    $newResendCcEmail = $_POST['resend_cc_email'] ?? '';
    $newResendBccEmail = $_POST['resend_bcc_email'] ?? '';
    $newEmailSubject = $_POST['email_subject'] ?? '';
    $newEmailTemplate = $_POST['email_template'] ?? '';

    // Dados Bancários
    $newContaTitular = $_POST['conta_titular'] ?? '';
    $newContaDocumento = $_POST['conta_documento'] ?? '';
    $newContaBanco = $_POST['conta_banco'] ?? '';
    $newContaAgencia = $_POST['conta_agencia'] ?? '';
    $newContaNumero = $_POST['conta_numero'] ?? '';
    $newContaTipo = $_POST['conta_tipo'] ?? '';
    $newContaPix = $_POST['conta_pix'] ?? '';

    // Dados da Empresa
    $newEmpresaRepresentanteNome = $_POST['empresa_representante_nome'] ?? '';
    $newEmpresaRepresentanteCpf = $_POST['empresa_representante_cpf'] ?? '';
    $newEmpresaEndereco = $_POST['empresa_endereco'] ?? '';
    $newEmpresaEmail = $_POST['empresa_email'] ?? '';
    $newEmpresaWhatsapp = $_POST['empresa_whatsapp'] ?? '';

    if ($newDefaultTaxaMensal !== null && $newIofAdicionalRate !== null && $newIofDiariaRate !== null &&
        $newDefaultTaxaMensal >= 0 && $newIofAdicionalRate >= 0 && $newIofDiariaRate >= 0) {

        $config = [
            "app_name" => $newAppName,
            "app_version" => $newAppVersion,
            "default_taxa_mensal" => $newDefaultTaxaMensal,
            "iof_adicional_rate" => $newIofAdicionalRate,
            "iof_diaria_rate" => $newIofDiariaRate,
            "resend_api_key" => $newResendApiKey,
            "resend_from_name" => $newResendFromName,
            "resend_from_email" => $newResendFromEmail,
            "resend_cc_email" => $newResendCcEmail,
            "resend_bcc_email" => $newResendBccEmail,
            "email_subject" => $newEmailSubject,
            "email_template" => $newEmailTemplate,
            "conta_titular" => $newContaTitular,
            "conta_documento" => $newContaDocumento,
            "conta_banco" => $newContaBanco,
            "conta_agencia" => $newContaAgencia,
            "conta_numero" => $newContaNumero,
            "conta_tipo" => $newContaTipo,
            "conta_pix" => $newContaPix,
            "empresa_representante_nome" => $newEmpresaRepresentanteNome,
            "empresa_representante_cpf" => $newEmpresaRepresentanteCpf,
            "empresa_endereco" => $newEmpresaEndereco,
            "empresa_email" => $newEmpresaEmail,
            "empresa_whatsapp" => $newEmpresaWhatsapp
        ];

        if (file_put_contents($configFilePath, json_encode($config, JSON_PRETTY_PRINT))) {
            $message = "Configurações salvas com sucesso!";
            $messageType = "success";
        } else {
            $message = "Erro ao salvar as configurações. Verifique as permissões do arquivo config.json.";
            $messageType = "danger";
        }
    } else {
        $message = "Valores inválidos. Verifique se os campos estão preenchidos corretamente e são números positivos.";
        $messageType = "danger";
    }
}

    // Função para apagar diretório recursivamente
    function deleteDir($dirPath) {
        if (!is_dir($dirPath)) {
            return false;
        }
        if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
            $dirPath .= '/';
        }
        $files = glob($dirPath . '*', GLOB_MARK);
        foreach ($files as $file) {
            if (is_dir($file)) {
                deleteDir($file);
            } else {
                unlink($file);
            }
        }
        return rmdir($dirPath);
    }

    // Lidar com o Reset do Sistema
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_system') {
        $confirmacao = isset($_POST['confirmacao']) ? trim($_POST['confirmacao']) : '';
        
        if ($confirmacao === 'CONFIRMAR') {
            require_once 'db_connection.php'; // Usa a conexão existente
            
            try {
                // Desativa a checagem de chave estrangeira para poder truncar
                $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
                
                // Limpa as tabelas principais
                // Nota: TRUNCATE no MySQL causa commit implícito, então não usamos beginTransaction() aqui
                $tabelas = [
                    'compensacoes',
                    'operacao_arquivos',
                    'recebiveis',
                    'operacoes',
                    'sacados',
                    'cedentes_socios',
                    'cedentes'
                ];
                
                foreach ($tabelas as $tabela) {
                    $pdo->exec("TRUNCATE TABLE `$tabela`");
                }
                
                // Reativa a checagem de chave estrangeira
                $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
                
                // Apaga os arquivos físicos
                $uploadDir = __DIR__ . '/uploads/operacoes/';
                if (is_dir($uploadDir)) {
                    $files = glob($uploadDir . '*');
                    foreach ($files as $file) {
                        if (is_dir($file)) {
                            deleteDir($file);
                        }
                    }
                }
                
                $message = "SISTEMA RESETADO COM SUCESSO! Todos os dados e arquivos de operações foram apagados.";
                $messageType = "success";
                
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                // Reativa caso tenha falhado no meio
                $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
                $message = "Erro ao resetar o banco de dados: " . $e->getMessage();
                $messageType = "danger";
            }
        } else {
            $message = "A palavra de confirmação não confere. O sistema NÃO foi resetado.";
            $messageType = "danger";
        }
    }

// Carregar as configurações atuais para exibir no formulário
$currentConfig = readConfig($configFilePath);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações do Sistema</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Quill.js CSS -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <style>
        .ql-editor { font-family: Arial, sans-serif; font-size: 14px; }
        .var-btn { margin-bottom: 5px; font-family: monospace; font-size: 0.85em !important; }
    </style>
</head>
<body>
    <?php require_once 'menu.php'; ?>

    <div class="container-fluid px-3 px-md-4 mt-4">
        <h1 class="mb-4">Configurações do Sistema</h1>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                Gerenciar Taxas e Padrões
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="save_config">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="app_name" class="form-label">Nome do Aplicativo:</label>
                            <input type="text" class="form-control" id="app_name" name="app_name" value="<?php echo htmlspecialchars($currentConfig['app_name'] ?? 'Factoring'); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="app_version" class="form-label">Versão do Aplicativo:</label>
                            <input type="text" class="form-control" id="app_version" name="app_version" value="<?php echo htmlspecialchars($currentConfig['app_version'] ?? '5.2 de abril de 2026'); ?>" readonly>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="default_taxa_mensal" class="form-label">Taxa de Juros Padrão (% a.m.):</label>
                        <div class="input-group">
                            <input type="number" step="0.01" min="0" class="form-control" id="default_taxa_mensal" name="default_taxa_mensal" value="<?php echo htmlspecialchars(number_format($currentConfig['default_taxa_mensal'] ?? 0, 2, '.', '')); ?>" required>
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="iof_adicional_rate" class="form-label">Taxa de IOF Adicional (decimal, ex: 0.0038 para 0.38%):</label>
                        <input type="number" step="0.000001" min="0" class="form-control" id="iof_adicional_rate" name="iof_adicional_rate" value="<?php echo htmlspecialchars(number_format($currentConfig['iof_adicional_rate'] ?? 0, 6, '.', '')); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="iof_diaria_rate" class="form-label">Taxa de IOF Diária (decimal, ex: 0.000082 para 0.0082%):</label>
                        <input type="number" step="0.000001" min="0" class="form-control" id="iof_diaria_rate" name="iof_diaria_rate" value="<?php echo htmlspecialchars(number_format($currentConfig['iof_diaria_rate'] ?? 0, 8, '.', '')); ?>" required>
                    </div>

                    <hr class="my-4">
                    <h5 class="mb-3"><i class="bi bi-building"></i> Dados da Empresa</h5>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="empresa_representante_nome" class="form-label">Nome do Representante Legal:</label>
                            <input type="text" class="form-control" id="empresa_representante_nome" name="empresa_representante_nome" value="<?php echo htmlspecialchars($currentConfig['empresa_representante_nome'] ?? ''); ?>" placeholder="João da Silva">
                        </div>
                        <div class="col-md-6">
                            <label for="empresa_representante_cpf" class="form-label">CPF do Representante:</label>
                            <input type="text" class="form-control" id="empresa_representante_cpf" name="empresa_representante_cpf" value="<?php echo htmlspecialchars($currentConfig['empresa_representante_cpf'] ?? ''); ?>" placeholder="000.000.000-00">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="empresa_endereco" class="form-label">Endereço Completo:</label>
                        <input type="text" class="form-control" id="empresa_endereco" name="empresa_endereco" value="<?php echo htmlspecialchars($currentConfig['empresa_endereco'] ?? ''); ?>" placeholder="Rua Exemplo, 123, Bairro, Cidade - UF, CEP">
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="empresa_email" class="form-label">E-mail de Contato:</label>
                            <input type="email" class="form-control" id="empresa_email" name="empresa_email" value="<?php echo htmlspecialchars($currentConfig['empresa_email'] ?? ''); ?>" placeholder="contato@suaempresa.com.br">
                        </div>
                        <div class="col-md-6">
                            <label for="empresa_whatsapp" class="form-label">WhatsApp de Contato:</label>
                            <input type="text" class="form-control" id="empresa_whatsapp" name="empresa_whatsapp" value="<?php echo htmlspecialchars($currentConfig['empresa_whatsapp'] ?? ''); ?>" placeholder="(00) 00000-0000">
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    <h5 class="mb-3"><i class="bi bi-bank"></i> Dados Bancários de Recebimento</h5>
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="conta_titular" class="form-label">Titular (Favorecido):</label>
                            <input type="text" class="form-control" id="conta_titular" name="conta_titular" value="<?php echo htmlspecialchars($currentConfig['conta_titular'] ?? ''); ?>" placeholder="Sua Empresa Factoring">
                        </div>
                        <div class="col-md-6">
                            <label for="conta_documento" class="form-label">CNPJ/CPF do Titular:</label>
                            <input type="text" class="form-control" id="conta_documento" name="conta_documento" value="<?php echo htmlspecialchars($currentConfig['conta_documento'] ?? ''); ?>" placeholder="00.000.000/0001-00">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label for="conta_banco" class="form-label">Banco:</label>
                            <input type="text" class="form-control" id="conta_banco" name="conta_banco" value="<?php echo htmlspecialchars($currentConfig['conta_banco'] ?? ''); ?>" placeholder="Ex: Banco do Brasil">
                        </div>
                        <div class="col-md-3">
                            <label for="conta_agencia" class="form-label">Agência:</label>
                            <input type="text" class="form-control" id="conta_agencia" name="conta_agencia" value="<?php echo htmlspecialchars($currentConfig['conta_agencia'] ?? ''); ?>" placeholder="0000">
                        </div>
                        <div class="col-md-3">
                            <label for="conta_numero" class="form-label">Número da Conta:</label>
                            <input type="text" class="form-control" id="conta_numero" name="conta_numero" value="<?php echo htmlspecialchars($currentConfig['conta_numero'] ?? ''); ?>" placeholder="00000-0">
                        </div>
                        <div class="col-md-3">
                            <label for="conta_tipo" class="form-label">Tipo de Conta:</label>
                            <select class="form-select" id="conta_tipo" name="conta_tipo">
                                <option value="" <?php echo ($currentConfig['conta_tipo'] ?? '') == '' ? 'selected' : ''; ?>>Selecione...</option>
                                <option value="Corrente" <?php echo ($currentConfig['conta_tipo'] ?? '') == 'Corrente' ? 'selected' : ''; ?>>Corrente</option>
                                <option value="Poupança" <?php echo ($currentConfig['conta_tipo'] ?? '') == 'Poupança' ? 'selected' : ''; ?>>Poupança</option>
                                <option value="Pagamento" <?php echo ($currentConfig['conta_tipo'] ?? '') == 'Pagamento' ? 'selected' : ''; ?>>Pagamento</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-12">
                            <label for="conta_pix" class="form-label">Chave PIX:</label>
                            <input type="text" class="form-control" id="conta_pix" name="conta_pix" value="<?php echo htmlspecialchars($currentConfig['conta_pix'] ?? ''); ?>" placeholder="sua-chave-pix">
                        </div>
                    </div>

                    <hr class="my-4">
                    <h5 class="mb-3"><i class="bi bi-envelope"></i> Configurações de E-mail (Resend)</h5>
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label for="resend_api_key" class="form-label">Resend API Key:</label>
                            <input type="password" class="form-control" id="resend_api_key" name="resend_api_key" value="<?php echo htmlspecialchars($currentConfig['resend_api_key'] ?? ''); ?>" placeholder="re_...">
                            <small class="text-muted">Chave de API gerada no painel do Resend.com</small>
                        </div>
                        <div class="col-md-4">
                            <label for="resend_from_name" class="form-label">Nome do Remetente:</label>
                            <input type="text" class="form-control" id="resend_from_name" name="resend_from_name" value="<?php echo htmlspecialchars($currentConfig['resend_from_name'] ?? 'Notificações'); ?>" placeholder="Ex: Sistema Factoring">
                            <small class="text-muted">Nome que aparecerá no e-mail recebido</small>
                        </div>
                        <div class="col-md-4">
                            <label for="resend_from_email" class="form-label">E-mail do Remetente:</label>
                            <input type="email" class="form-control" id="resend_from_email" name="resend_from_email" value="<?php echo htmlspecialchars($currentConfig['resend_from_email'] ?? ''); ?>" placeholder="exemplo@seudominio.com">
                            <small class="text-muted">Deve ser um domínio verificado no Resend</small>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="resend_cc_email" class="form-label">Cópia (CC):</label>
                            <input type="text" class="form-control" id="resend_cc_email" name="resend_cc_email" value="<?php echo htmlspecialchars($currentConfig['resend_cc_email'] ?? ''); ?>">
                            <small class="text-muted">E-mails separados por vírgula que receberão cópia das notificações.</small>
                        </div>
                        <div class="col-md-6">
                            <label for="resend_bcc_email" class="form-label">Cópia Oculta (BCC):</label>
                            <input type="text" class="form-control" id="resend_bcc_email" name="resend_bcc_email" value="<?php echo htmlspecialchars($currentConfig['resend_bcc_email'] ?? ''); ?>">
                            <small class="text-muted">E-mails separados por vírgula que receberão cópia oculta.</small>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email_subject" class="form-label">Assunto do E-mail:</label>
                        <input type="text" class="form-control" id="email_subject" name="email_subject" value="<?php echo htmlspecialchars($currentConfig['email_subject'] ?? 'Notificação de Cessão de Crédito - Borderô #[BORDERO_NUMERO]'); ?>" required>
                        <small class="text-muted">Você pode usar as variáveis abaixo também no assunto (ex: [BORDERO_NUMERO], [CEDENTE_NOME]).</small>
                    </div>

                    <div class="mb-3">
                        <label for="email_template" class="form-label">Template de E-mail (Notificação de Sacado):</label>
                        <div class="mb-2">
                            <span class="text-muted" style="font-size: 0.9em;">Clique nas variáveis abaixo para copiar e colar no assunto ou template:</span><br>
                            <div class="btn-group flex-wrap mt-1" role="group" aria-label="Variáveis">
                                <button type="button" class="btn btn-outline-secondary btn-sm var-btn" data-var="[CEDENTE_NOME]">[CEDENTE_NOME]</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm var-btn" data-var="[CEDENTE_CNPJ]">[CEDENTE_CNPJ]</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm var-btn" data-var="[SACADO_NOME]">[SACADO_NOME]</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm var-btn" data-var="[SACADO_CNPJ]">[SACADO_CNPJ]</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm var-btn" data-var="[BORDERO_NUMERO]">[BORDERO_NUMERO]</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm var-btn" data-var="[BORDERO_DATA]">[BORDERO_DATA]</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm var-btn" data-var="[BORDERO_VALOR]">[BORDERO_VALOR]</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm var-btn" data-var="[TABELA_TITULOS]">[TABELA_TITULOS]</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm var-btn" data-var="[CIDADE_DATA]">[CIDADE_DATA]</button>
                            </div>
                        </div>
                        <!-- Container do Quill -->
                        <div id="editor-container" style="height: 300px; background-color: #fff;"></div>
                        <!-- Textarea oculta que vai enviar o conteúdo pro PHP -->
                        <textarea id="email_template" name="email_template" style="display:none;"><?php echo htmlspecialchars($currentConfig['email_template'] ?? ''); ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary" id="btnSalvarConfig"><i class="bi bi-save"></i> Salvar Configurações</button>
                    <button type="button" class="btn btn-outline-info ms-2" id="btnTestarEmail" data-bs-toggle="modal" data-bs-target="#testEmailModal"><i class="bi bi-send"></i> Testar Disparo</button>
                </form>
            </div>
        </div>
        <div class="card mt-4 border-danger">
            <div class="card-header bg-danger text-white">
                <i class="bi bi-exclamation-triangle-fill"></i> Zona de Perigo (Danger Zone)
            </div>
            <div class="card-body">
                <p class="card-text text-danger">
                    <strong>Atenção:</strong> Esta ação irá apagar <strong>TODOS</strong> os dados do sistema, incluindo Cedentes, Sacados, Operações, Recebíveis, Compensações e todos os Arquivos (PDFs) anexados. As configurações de taxas e os usuários do sistema (login) serão mantidos.
                </p>
                <p class="card-text text-danger">Esta ação <strong>NÃO pode ser desfeita</strong>.</p>
                
                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#resetModal">
                    <i class="bi bi-trash3-fill"></i> Resetar Sistema Completo
                </button>
            </div>
        </div>

    </div>

    <!-- Modal de Confirmação de Reset -->
    <div class="modal fade" id="resetModal" tabindex="-1" aria-labelledby="resetModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content border-danger">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="resetModalLabel">Confirmar Reset do Sistema</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Você está prestes a <strong>APAGAR TODOS OS DADOS</strong> do sistema.</p>
                    <p>Para prosseguir, digite a palavra <strong>CONFIRMAR</strong> (em maiúsculas) no campo abaixo:</p>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="reset_system">
                        <div class="mb-3">
                            <input type="text" class="form-control" name="confirmacao" id="confirmacao" required autocomplete="off" placeholder="Digite CONFIRMAR">
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-danger" id="btnReset" disabled>Efetuar Limpeza Total</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Teste de E-mail -->
    <div class="modal fade" id="testEmailModal" tabindex="-1" aria-labelledby="testEmailModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="testEmailModalLabel">Testar Disparo de E-mail</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Digite um e-mail de destino para testar as configurações atuais do Resend.</p>
                    <div class="mb-3">
                        <input type="email" class="form-control" id="test_email_dest" placeholder="seu-email@exemplo.com" required>
                    </div>
                    <div id="testEmailResult" class="mt-2"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-primary" id="btnEnviarTeste">Enviar E-mail de Teste</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Quill.js JS -->
    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar Quill editor
            var quill = new Quill('#editor-container', {
                theme: 'snow',
                modules: {
                    toolbar: [
                        [{ 'header': [1, 2, 3, false] }],
                        ['bold', 'italic', 'underline', 'strike'],
                        [{ 'color': [] }, { 'background': [] }],
                        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                        ['clean']
                    ]
                }
            });
            
            // Carregar conteúdo existente no editor
            const textarea = document.getElementById('email_template');
            quill.clipboard.dangerouslyPasteHTML(textarea.value);
            
            // Sincronizar editor com textarea no submit do formulário
            document.getElementById('btnSalvarConfig').addEventListener('click', function() {
                textarea.value = quill.root.innerHTML;
            });
            
            // Botões de variáveis
            document.querySelectorAll('.var-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const variable = this.getAttribute('data-var');
                    const range = quill.getSelection(true); // Retorna seleção atual ou final do texto
                    quill.insertText(range.index, variable, 'bold', true);
                    quill.setSelection(range.index + variable.length);
                });
            });

            // Habilitar botão de reset apenas quando digitar CONFIRMAR
            const inputConfirmacao = document.getElementById('confirmacao');
            const btnReset = document.getElementById('btnReset');
            
            inputConfirmacao.addEventListener('input', function() {
                if (this.value === 'CONFIRMAR') {
                    btnReset.disabled = false;
                } else {
                    btnReset.disabled = true;
                }
            });
            
            // Limpar campo ao fechar o modal
            const resetModal = document.getElementById('resetModal');
            resetModal.addEventListener('hidden.bs.modal', function () {
                inputConfirmacao.value = '';
                btnReset.disabled = true;
            });
            
            // Teste de e-mail
            const btnEnviarTeste = document.getElementById('btnEnviarTeste');
            if (btnEnviarTeste) {
                btnEnviarTeste.addEventListener('click', async function() {
                    const email = document.getElementById('test_email_dest').value;
                    const resend_api_key = document.getElementById('resend_api_key').value;
                    const resend_from_email = document.getElementById('resend_from_email').value;
                    const resultDiv = document.getElementById('testEmailResult');
                    
                    if (!email || !resend_api_key || !resend_from_email) {
                        resultDiv.innerHTML = '<div class="alert alert-warning py-1">Preencha os campos de API Key, Remetente e o e-mail de destino.</div>';
                        return;
                    }
                    
                    btnEnviarTeste.disabled = true;
                    btnEnviarTeste.textContent = 'Enviando...';
                    resultDiv.innerHTML = '';
                    
                    try {
                        const response = await fetch('testar_email.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                email: email,
                                api_key: resend_api_key,
                                from_email: resend_from_email
                            })
                        });
                        
                        const data = await response.json();
                        if (data.success) {
                            resultDiv.innerHTML = '<div class="alert alert-success py-1">E-mail enviado com sucesso! Verifique a caixa de entrada.</div>';
                        } else {
                            resultDiv.innerHTML = `<div class="alert alert-danger py-1">Erro: ${data.error}</div>`;
                        }
                    } catch (e) {
                        resultDiv.innerHTML = `<div class="alert alert-danger py-1">Erro na requisição: ${e.message}</div>`;
                    } finally {
                        btnEnviarTeste.disabled = false;
                        btnEnviarTeste.textContent = 'Enviar E-mail de Teste';
                    }
                });
            }
        });
    </script>
</body>
</html>
