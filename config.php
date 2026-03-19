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

// Lidar com o envio do formulário de configurações gerais
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_config') {
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
                $pdo->beginTransaction();
                
                // Desativa a checagem de chave estrangeira para poder truncar
                $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
                
                // Limpa as tabelas principais
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
                
                $pdo->commit();
                
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
                    <input type="hidden" name="action" value="save_config">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Habilitar botão de reset apenas quando digitar CONFIRMAR
        document.addEventListener('DOMContentLoaded', function() {
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
        });
    </script>
</body>
</html>
