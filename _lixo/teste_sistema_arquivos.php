<?php
// teste_sistema_arquivos.php
require_once 'auth_check.php';
require_once 'db_connection.php';

$message = '';
$messageType = '';

// Verificar se a tabela existe
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'operacao_arquivos'");
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        $message = "⚠️ Tabela 'operacao_arquivos' não encontrada. Execute o script SQL primeiro.";
        $messageType = 'warning';
    } else {
        $message = "✅ Tabela 'operacao_arquivos' encontrada.";
        $messageType = 'success';
    }
} catch (PDOException $e) {
    $message = "❌ Erro ao verificar tabela: " . $e->getMessage();
    $messageType = 'danger';
}

// Verificar diretório de uploads
$uploadDir = __DIR__ . '/uploads/operacoes/';
$dirExists = is_dir($uploadDir);
$dirWritable = is_writable($uploadDir);

// Buscar operações para teste
$operacoes = [];
try {
    $stmt = $pdo->query("SELECT id, cedente_id, data_operacao FROM operacoes ORDER BY id DESC LIMIT 5");
    $operacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Ignorar erro se não houver operações
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste do Sistema de Arquivos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
    <?php require_once 'menu.php'; ?>

    <div class="container mt-4">
        <h1 class="mb-4">Teste do Sistema de Upload de Arquivos</h1>

        <!-- Status do Sistema -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Status do Sistema</h5>
            </div>
            <div class="card-body">
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-6">
                        <h6>Banco de Dados</h6>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Tabela operacao_arquivos</span>
                                <span class="badge bg-<?php echo $tableExists ? 'success' : 'danger'; ?>">
                                    <?php echo $tableExists ? 'Existe' : 'Não existe'; ?>
                                </span>
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6>Sistema de Arquivos</h6>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Diretório uploads/operacoes/</span>
                                <span class="badge bg-<?php echo $dirExists ? 'success' : 'danger'; ?>">
                                    <?php echo $dirExists ? 'Existe' : 'Não existe'; ?>
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Permissão de escrita</span>
                                <span class="badge bg-<?php echo $dirWritable ? 'success' : 'danger'; ?>">
                                    <?php echo $dirWritable ? 'Sim' : 'Não'; ?>
                                </span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Arquivos PHP -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Arquivos do Sistema</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php
                    $arquivos_sistema = [
                        'criar_tabela_arquivos.sql' => 'Script SQL para criar tabela',
                        'upload_arquivos.php' => 'Endpoint para upload',
                        'listar_arquivos.php' => 'Endpoint para listar arquivos',
                        'download_arquivo.php' => 'Endpoint para download',
                        'excluir_arquivo.php' => 'Endpoint para exclusão'
                    ];
                    
                    foreach ($arquivos_sistema as $arquivo => $descricao):
                        $existe = file_exists(__DIR__ . '/' . $arquivo);
                    ?>
                    <div class="col-md-6 mb-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong><?php echo $arquivo; ?></strong><br>
                                <small class="text-muted"><?php echo $descricao; ?></small>
                            </div>
                            <span class="badge bg-<?php echo $existe ? 'success' : 'danger'; ?>">
                                <?php echo $existe ? 'OK' : 'Faltando'; ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Teste de Upload -->
        <?php if ($tableExists && !empty($operacoes)): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Teste de Upload</h5>
            </div>
            <div class="card-body">
                <form id="testeUploadForm" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-4">
                            <label for="operacao_teste" class="form-label">Operação para Teste</label>
                            <select id="operacao_teste" name="operacao_id" class="form-select" required>
                                <option value="">Selecione uma operação</option>
                                <?php foreach ($operacoes as $op): ?>
                                    <option value="<?php echo $op['id']; ?>">
                                        Operação #<?php echo $op['id']; ?> - <?php echo date('d/m/Y', strtotime($op['data_operacao'])); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="arquivos_teste" class="form-label">Arquivo de Teste</label>
                            <input type="file" class="form-control" id="arquivos_teste" name="arquivos[]" 
                                   accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.doc,.docx,.xls,.xlsx,.txt">
                        </div>
                        <div class="col-md-4">
                            <label for="descricao_teste" class="form-label">Descrição</label>
                            <input type="text" class="form-control" id="descricao_teste" 
                                   placeholder="Arquivo de teste" value="Teste do sistema de upload">
                        </div>
                    </div>
                    <div class="mt-3">
                        <button type="button" id="testarUploadBtn" class="btn btn-primary">
                            <i class="bi bi-cloud-upload"></i> Testar Upload
                        </button>
                        <button type="button" id="listarArquivosBtn" class="btn btn-secondary" disabled>
                            <i class="bi bi-list"></i> Listar Arquivos
                        </button>
                    </div>
                </form>
                
                <div id="resultado-teste" class="mt-3"></div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Instruções -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Instruções de Instalação</h5>
            </div>
            <div class="card-body">
                <ol>
                    <li><strong>Execute o script SQL:</strong> Importe o arquivo <code>criar_tabela_arquivos.sql</code> no seu banco de dados.</li>
                    <li><strong>Crie o diretório:</strong> Certifique-se de que o diretório <code>uploads/operacoes/</code> existe e tem permissão de escrita.</li>
                    <li><strong>Teste o upload:</strong> Use o formulário acima para testar o upload de um arquivo.</li>
                    <li><strong>Verifique a integração:</strong> Acesse uma operação existente em "Detalhes da Operação" para ver a seção de arquivos.</li>
                </ol>
                
                <div class="alert alert-info mt-3">
                    <strong>Comandos úteis:</strong><br>
                    <code>mkdir -p uploads/operacoes</code><br>
                    <code>chmod 755 uploads/operacoes</code>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const testarUploadBtn = document.getElementById('testarUploadBtn');
            const listarArquivosBtn = document.getElementById('listarArquivosBtn');
            const operacaoSelect = document.getElementById('operacao_teste');
            const resultadoDiv = document.getElementById('resultado-teste');

            // Habilitar botão de listar quando operação for selecionada
            operacaoSelect.addEventListener('change', function() {
                listarArquivosBtn.disabled = !this.value;
            });

            // Teste de upload
            testarUploadBtn.addEventListener('click', async function() {
                const formData = new FormData();
                const operacaoId = operacaoSelect.value;
                const arquivoInput = document.getElementById('arquivos_teste');
                const descricao = document.getElementById('descricao_teste').value;

                if (!operacaoId) {
                    alert('Selecione uma operação para teste.');
                    return;
                }

                if (!arquivoInput.files.length) {
                    alert('Selecione um arquivo para teste.');
                    return;
                }

                formData.append('operacao_id', operacaoId);
                formData.append('arquivos[]', arquivoInput.files[0]);
                formData.append('descricao[]', descricao);

                const originalText = this.innerHTML;
                this.disabled = true;
                this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Testando...';

                try {
                    const response = await fetch('upload_arquivos.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    if (result.success) {
                        resultadoDiv.innerHTML = `
                            <div class="alert alert-success">
                                <strong>✅ Teste bem-sucedido!</strong><br>
                                ${result.message}<br>
                                <small>Arquivo enviado: ${result.uploaded_files[0]?.nome_original}</small>
                            </div>
                        `;
                    } else {
                        resultadoDiv.innerHTML = `
                            <div class="alert alert-danger">
                                <strong>❌ Teste falhou!</strong><br>
                                ${result.error}<br>
                                ${result.errors ? '<small>' + result.errors.join('<br>') + '</small>' : ''}
                            </div>
                        `;
                    }
                } catch (error) {
                    resultadoDiv.innerHTML = `
                        <div class="alert alert-danger">
                            <strong>❌ Erro de comunicação!</strong><br>
                            ${error.message}
                        </div>
                    `;
                } finally {
                    this.disabled = false;
                    this.innerHTML = originalText;
                }
            });

            // Listar arquivos
            listarArquivosBtn.addEventListener('click', async function() {
                const operacaoId = operacaoSelect.value;
                
                if (!operacaoId) {
                    alert('Selecione uma operação.');
                    return;
                }

                try {
                    const response = await fetch(`listar_arquivos.php?operacao_id=${operacaoId}`);
                    const result = await response.json();

                    if (result.success) {
                        if (result.arquivos.length === 0) {
                            resultadoDiv.innerHTML = `
                                <div class="alert alert-info">
                                    <strong>ℹ️ Nenhum arquivo encontrado</strong><br>
                                    A operação #${operacaoId} não possui arquivos anexados.
                                </div>
                            `;
                        } else {
                            let arquivosHtml = '<div class="alert alert-success"><strong>📁 Arquivos encontrados:</strong><ul class="mt-2 mb-0">';
                            result.arquivos.forEach(arquivo => {
                                arquivosHtml += `<li>${arquivo.nome_original} (${arquivo.tamanho_formatado}) - ${arquivo.data_upload_formatada}</li>`;
                            });
                            arquivosHtml += '</ul></div>';
                            resultadoDiv.innerHTML = arquivosHtml;
                        }
                    } else {
                        resultadoDiv.innerHTML = `
                            <div class="alert alert-danger">
                                <strong>❌ Erro ao listar arquivos!</strong><br>
                                ${result.error}
                            </div>
                        `;
                    }
                } catch (error) {
                    resultadoDiv.innerHTML = `
                        <div class="alert alert-danger">
                            <strong>❌ Erro de comunicação!</strong><br>
                            ${error.message}
                        </div>
                    `;
                }
            });
        });
    </script>
</body>
</html>