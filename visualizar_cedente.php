<?php require_once 'auth_check.php'; ?>
<?php
require_once 'db_connection.php'; // Conexão $pdo

$pageTitle = "Visualizar Cedente";
$cedente = null;
$socios = [];

// Verifica se um ID foi passado via GET
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: listar_cedentes.php?status=error&msg=" . urlencode("ID do cedente não fornecido."));
    exit;
}

$cedenteId = (int)$_GET['id'];

try {
    // Buscar dados do cedente
    $stmt = $pdo->prepare("SELECT * FROM cedentes WHERE id = :id");
    $stmt->bindParam(':id', $cedenteId, PDO::PARAM_INT);
    $stmt->execute();
    $cedente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cedente) {
        header("Location: listar_cedentes.php?status=error&msg=" . urlencode("Cedente não encontrado."));
        exit;
    }

    // Buscar sócios do cedente
    $stmt_socios = $pdo->prepare("SELECT * FROM cedentes_socios WHERE cedente_id = :cedente_id ORDER BY id");
    $stmt_socios->bindParam(':cedente_id', $cedenteId, PDO::PARAM_INT);
    $stmt_socios->execute();
    $socios = $stmt_socios->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erro ao buscar dados do cedente: " . $e->getMessage());
}

// Função para formatar CPF/CNPJ
function formatDocumento($documento, $tipo_pessoa) {
    $docLimpo = preg_replace('/\D/', '', $documento);
    if (empty($docLimpo)) {
        return '-';
    }
    if ($tipo_pessoa == 'FISICA') {
        if (strlen($docLimpo) == 11) {
            return substr($docLimpo, 0, 3) . '.' .
                   substr($docLimpo, 3, 3) . '.' .
                   substr($docLimpo, 6, 3) . '-' .
                   substr($docLimpo, 9, 2);
        }
    } elseif ($tipo_pessoa == 'JURIDICA') {
        if (strlen($docLimpo) == 14) {
            return substr($docLimpo, 0, 2) . '.' .
                   substr($docLimpo, 2, 3) . '.' .
                   substr($docLimpo, 5, 3) . '/' .
                   substr($docLimpo, 8, 4) . '-' .
                   substr($docLimpo, 12, 2);
        }
    }
    return $documento;
}

// Função para formatar telefone
function formatTelefone($telefone) {
    $tel = preg_replace('/\D/', '', $telefone);
    if (strlen($tel) == 11) {
        return '(' . substr($tel, 0, 2) . ') ' . substr($tel, 2, 5) . '-' . substr($tel, 7, 4);
    } elseif (strlen($tel) == 10) {
        return '(' . substr($tel, 0, 2) . ') ' . substr($tel, 2, 4) . '-' . substr($tel, 6, 4);
    }
    return $telefone;
}

// Função para formatar CEP
function formatCEP($cep) {
    $cepLimpo = preg_replace('/\D/', '', $cep);
    if (strlen($cepLimpo) == 8) {
        return substr($cepLimpo, 0, 5) . '-' . substr($cepLimpo, 5, 3);
    }
    return $cep;
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .info-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.25rem;
        }
        .info-value {
            color: #212529;
            margin-bottom: 1rem;
            padding: 0.5rem;
            background-color: #f8f9fa;
            border-radius: 0.375rem;
            border: 1px solid #e9ecef;
        }
        .socio-item {
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 1rem;
            margin-bottom: 1rem;
            background-color: #f8f9fa;
        }
        .badge-id {
            font-size: 1.1rem;
            padding: 0.5rem 1rem;
        }
    </style>
</head>
<body>
    <?php require_once 'menu.php'; ?>

    <div class="container-fluid px-3 px-md-4 mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <h1><i class="bi bi-eye-fill me-2"></i><?php echo htmlspecialchars($pageTitle); ?></h1>
            <div>
                <span class="badge bg-primary badge-id">ID: <?php echo $cedente['id']; ?></span>
            </div>
        </div>

        <!-- Dados da Empresa -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-building"></i> Dados da Empresa</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="info-label">Razão Social</div>
                        <div class="info-value"><?php echo htmlspecialchars($cedente['empresa'] ?? '-'); ?></div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-label">Tipo de Pessoa</div>
                        <div class="info-value">
                            <?php
                            if ($cedente['tipo_pessoa'] == 'FISICA') {
                                echo '<span class="badge bg-info">Pessoa Física</span>';
                            } elseif ($cedente['tipo_pessoa'] == 'JURIDICA') {
                                echo '<span class="badge bg-success">Pessoa Jurídica</span>';
                            } else {
                                echo '-';
                            }
                            ?>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-label"><?php echo $cedente['tipo_pessoa'] == 'FISICA' ? 'CPF' : 'CNPJ'; ?></div>
                        <div class="info-value"><?php echo formatDocumento($cedente['documento_principal'] ?? '', $cedente['tipo_pessoa'] ?? ''); ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-label">Email</div>
                        <div class="info-value">
                            <?php if (!empty($cedente['email'])): ?>
                                <a href="mailto:<?php echo htmlspecialchars($cedente['email']); ?>"><?php echo htmlspecialchars($cedente['email']); ?></a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-label">Telefone</div>
                        <div class="info-value">
                            <?php if (!empty($cedente['telefone'])): ?>
                                <a href="tel:<?php echo preg_replace('/\D/', '', $cedente['telefone']); ?>"><?php echo formatTelefone($cedente['telefone']); ?></a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Endereço -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-geo-alt"></i> Endereço</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="info-label">CEP</div>
                        <div class="info-value"><?php echo formatCEP($cedente['cep'] ?? ''); ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-label">Logradouro</div>
                        <div class="info-value"><?php echo htmlspecialchars($cedente['logradouro'] ?? '-'); ?></div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-label">Número</div>
                        <div class="info-value"><?php echo htmlspecialchars($cedente['numero'] ?? '-'); ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-label">Complemento</div>
                        <div class="info-value"><?php echo htmlspecialchars($cedente['complemento'] ?? '-'); ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-label">Bairro</div>
                        <div class="info-value"><?php echo htmlspecialchars($cedente['bairro'] ?? '-'); ?></div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-label">Cidade</div>
                        <div class="info-value"><?php echo htmlspecialchars($cedente['cidade'] ?? '-'); ?></div>
                    </div>
                    <div class="col-md-1">
                        <div class="info-label">UF</div>
                        <div class="info-value"><?php echo htmlspecialchars($cedente['estado'] ?? '-'); ?></div>
                    </div>
                    <?php if (!empty($cedente['endereco'])): ?>
                    <div class="col-12">
                        <div class="info-label">Observações do Endereço</div>
                        <div class="info-value"><?php echo nl2br(htmlspecialchars($cedente['endereco'])); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Dados Bancários -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-bank me-2"></i>Dados Bancários</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="info-label">Banco</div>
                            <div class="info-value"><?php echo htmlspecialchars($cedente['banco'] ?: 'Não informado'); ?></div>
                        </div>
                        <div class="col-md-2">
                            <div class="info-label">Agência</div>
                            <div class="info-value"><?php echo htmlspecialchars($cedente['agencia'] ?: 'Não informado'); ?></div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-label">Conta</div>
                            <div class="info-value"><?php echo htmlspecialchars($cedente['conta'] ?: 'Não informado'); ?></div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-label">Tipo de Conta</div>
                            <div class="info-value"><?php echo htmlspecialchars($cedente['tipo_conta'] ?: 'Não informado'); ?></div>
                        </div>
                        <div class="col-md-12">
                            <div class="info-label">Chave PIX</div>
                            <div class="info-value"><?php echo htmlspecialchars($cedente['chave_pix'] ?: 'Não informada'); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sócios -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-people"></i> Sócios da Empresa</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($socios)): ?>
                    <div class="row">
                        <?php foreach ($socios as $index => $socio): ?>
                            <div class="col-md-6 mb-3">
                                <div class="socio-item">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="mb-0"><i class="bi bi-person-fill"></i> Sócio <?php echo $index + 1; ?></h6>
                                        <span class="badge bg-secondary">ID: <?php echo $socio['id']; ?></span>
                                    </div>
                                    <div class="info-label">Nome</div>
                                    <div class="info-value"><?php echo htmlspecialchars($socio['nome']); ?></div>
                                    <div class="info-label">CPF</div>
                                    <div class="info-value"><?php echo formatDocumento($socio['cpf'], 'FISICA'); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-muted text-center py-3">
                        <i class="bi bi-info-circle"></i> Nenhum sócio cadastrado para esta empresa.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Botões de Ação -->
        <div class="row">
            <div class="col-12">
                <a href="form_cedente.php?id=<?php echo $cedente['id']; ?>" class="btn btn-primary">
                    <i class="bi bi-pencil-square"></i> Editar Cedente
                </a>
                <a href="listar_cedentes.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Voltar à Lista
                </a>
                <button type="button" class="btn btn-info" onclick="window.print()">
                    <i class="bi bi-printer"></i> Imprimir
                </button>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <style media="print">
        .btn, .navbar, .container .row:last-child {
            display: none !important;
        }
        .card {
            border: 1px solid #000 !important;
            break-inside: avoid;
        }
        .info-value {
            background-color: transparent !important;
            border: none !important;
        }
    </style>
</body>
</html>