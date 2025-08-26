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
            "default_taxa_mensal" => 5.00,
            "iof_adicional_rate" => 0.0038,
            "iof_diaria_rate" => 0.000082
        ];
        file_put_contents($filePath, json_encode($defaultConfig, JSON_PRETTY_PRINT));
        return $defaultConfig;
    }
    $configContent = file_get_contents($filePath);
    return json_decode($configContent, true);
}

// Lidar com o envio do formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newDefaultTaxaMensal = isset($_POST['default_taxa_mensal']) ? (float)$_POST['default_taxa_mensal'] : null;
    $newIofAdicionalRate = isset($_POST['iof_adicional_rate']) ? (float)$_POST['iof_adicional_rate'] : null;
    $newIofDiariaRate = isset($_POST['iof_diaria_rate']) ? (float)$_POST['iof_diaria_rate'] : null;

    if ($newDefaultTaxaMensal !== null && $newIofAdicionalRate !== null && $newIofDiariaRate !== null &&
        $newDefaultTaxaMensal >= 0 && $newIofAdicionalRate >= 0 && $newIofDiariaRate >= 0) {

        $config = [
            "default_taxa_mensal" => $newDefaultTaxaMensal,
            "iof_adicional_rate" => $newIofAdicionalRate,
            "iof_diaria_rate" => $newIofDiariaRate
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
</head>
<body>
    <?php require_once 'menu.php'; ?>

    <div class="container mt-4">
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
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Salvar Configurações</button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
