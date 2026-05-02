<?php require_once 'auth_check.php'; ?>
<?php
require_once 'db_connection.php'; // Conexão $pdo

$pageTitle = "Visualizar Cliente";
$cliente = null;
$socios = [];

// Verifica se um ID foi passado via GET
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: listar_clientes.php?status=error&msg=" . urlencode("ID do cliente não fornecido."));
    exit;
}

$clienteId = (int)$_GET['id'];

try {
    // Buscar dados do cliente
    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = :id");
    $stmt->bindParam(':id', $clienteId, PDO::PARAM_INT);
    $stmt->execute();
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cliente) {
        header("Location: listar_clientes.php?status=error&msg=" . urlencode("Cliente não encontrado."));
        exit;
    }

    // Buscar sócios do cliente
    $stmt_socios = $pdo->prepare("SELECT * FROM clientes_socios WHERE cliente_id = :cliente_id ORDER BY id");
    $stmt_socios->bindParam(':cliente_id', $clienteId, PDO::PARAM_INT);
    $stmt_socios->execute();
    $socios = $stmt_socios->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erro ao buscar dados do cliente: " . $e->getMessage());
}

// Função para formatar CPF/CNPJ
function formatDocumento($documento) {
    $docLimpo = preg_replace('/\D/', '', $documento);
    if (empty($docLimpo)) {
        return '-';
    }
    if (strlen($docLimpo) === 11) {
        return substr($docLimpo, 0, 3) . '.' .
               substr($docLimpo, 3, 3) . '.' .
               substr($docLimpo, 6, 3) . '-' .
               substr($docLimpo, 9, 2);
    } elseif (strlen($docLimpo) === 14) {
        return substr($docLimpo, 0, 2) . '.' .
               substr($docLimpo, 2, 3) . '.' .
               substr($docLimpo, 5, 3) . '/' .
               substr($docLimpo, 8, 4) . '-' .
               substr($docLimpo, 12, 2);
    }
    return htmlspecialchars($documento);
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

function formatValorOuPadrao($valor, $padrao = '-') {
    return !empty($valor) ? htmlspecialchars($valor) : $padrao;
}

function formatTextoMultilinhaOuPadrao($valor, $padrao = '-') {
    return !empty($valor) ? nl2br(htmlspecialchars($valor)) : $padrao;
}

$tituloDadosPrincipais = 'Dados da Empresa';
$labelNomeEmpresa = 'Razão Social';
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
            <div class="d-flex align-items-center gap-3">
                <h1 class="mb-0">
                    <i class="bi bi-eye-fill me-2"></i><?php echo htmlspecialchars($pageTitle); ?>
                    <?php if (!empty($cliente['empresa'])): ?>
                        <small class="text-muted fs-4">- <?php echo htmlspecialchars($cliente['empresa']); ?></small>
                    <?php endif; ?>
                </h1>
                <span class="badge bg-primary badge-id">ID: <?php echo $cliente['id']; ?></span>
            </div>
            <div class="d-flex gap-2">
                <a href="form_cliente.php?id=<?php echo $cliente['id']; ?>" class="btn btn-primary">
                    <i class="bi bi-pencil-square"></i> Editar
                </a>
                <button type="button" class="btn btn-info text-white" onclick="window.print()">
                    <i class="bi bi-printer"></i> Imprimir
                </button>
                <a href="listar_clientes.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Voltar à Lista
                </a>
            </div>
        </div>

        <!-- Dados Principais -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-building"></i> <?php echo htmlspecialchars($tituloDadosPrincipais); ?></h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="info-label"><?php echo htmlspecialchars($labelNomeEmpresa); ?></div>
                        <div class="info-value"><?php echo htmlspecialchars($cliente['empresa'] ?? '-'); ?></div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-label">Porte</div>
                        <div class="info-value">
                            <?php
                            $porte = $cliente['porte'] ?? '';
                            if ($porte === 'MEDIO') {
                                echo 'Médio';
                            } elseif ($porte === 'GRANDE') {
                                echo 'Grande';
                            } elseif (!empty($porte)) {
                                echo htmlspecialchars($porte);
                            } else {
                                echo '-';
                            }
                            ?>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-label">CNPJ</div>
                        <div class="info-value"><?php echo formatDocumento($cliente['documento_principal'] ?? ''); ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-label">Email</div>
                        <div class="info-value">
                            <?php if (!empty($cliente['email'])): ?>
                                <a href="mailto:<?php echo htmlspecialchars($cliente['email']); ?>"><?php echo htmlspecialchars($cliente['email']); ?></a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-label">Telefone</div>
                        <div class="info-value">
                            <?php if (!empty($cliente['telefone'])): ?>
                                <a href="tel:<?php echo preg_replace('/\D/', '', $cliente['telefone']); ?>"><?php echo formatTelefone($cliente['telefone']); ?></a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-label">WhatsApp</div>
                        <div class="info-value">
                            <?php if (!empty($cliente['whatsapp'])): ?>
                                <a href="https://wa.me/55<?php echo preg_replace('/\D/', '', $cliente['whatsapp']); ?>" target="_blank" rel="noopener noreferrer"><?php echo formatTelefone($cliente['whatsapp']); ?></a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-label">Tipo de Pessoa</div>
                        <div class="info-value">
                            <?php echo '<span class="badge bg-primary">Pessoa Jurídica</span>'; ?>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="info-label">Anotações</div>
                        <div class="info-value"><?php echo formatTextoMultilinhaOuPadrao($cliente['anotacoes'] ?? ''); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Representante -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-person-badge"></i> Representante</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="info-label">Nome</div>
                        <div class="info-value"><?php echo formatValorOuPadrao($cliente['representante_nome'] ?? ''); ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-label">CPF</div>
                        <div class="info-value">
                            <?php
                            echo !empty($cliente['representante_cpf'])
                                ? formatDocumento($cliente['representante_cpf'])
                                : '-';
                            ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-label">RG</div>
                        <div class="info-value"><?php echo formatValorOuPadrao($cliente['representante_rg'] ?? ''); ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-label">Estado Civil</div>
                        <div class="info-value"><?php echo formatValorOuPadrao($cliente['representante_estado_civil'] ?? ''); ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-label">Profissão</div>
                        <div class="info-value"><?php echo formatValorOuPadrao($cliente['representante_profissao'] ?? ''); ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-label">Nacionalidade</div>
                        <div class="info-value"><?php echo formatValorOuPadrao($cliente['representante_nacionalidade'] ?? ''); ?></div>
                    </div>
                    <div class="col-12">
                        <div class="info-label">Endereço</div>
                        <div class="info-value"><?php echo formatTextoMultilinhaOuPadrao($cliente['representante_endereco'] ?? ''); ?></div>
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
                        <div class="info-value"><?php echo formatCEP($cliente['cep'] ?? ''); ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-label">Logradouro</div>
                        <div class="info-value"><?php echo htmlspecialchars($cliente['logradouro'] ?? '-'); ?></div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-label">Número</div>
                        <div class="info-value"><?php echo htmlspecialchars($cliente['numero'] ?? '-'); ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-label">Complemento</div>
                        <div class="info-value"><?php echo htmlspecialchars($cliente['complemento'] ?? '-'); ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-label">Bairro</div>
                        <div class="info-value"><?php echo htmlspecialchars($cliente['bairro'] ?? '-'); ?></div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-label">Cidade</div>
                        <div class="info-value"><?php echo htmlspecialchars($cliente['cidade'] ?? '-'); ?></div>
                    </div>
                    <div class="col-md-1">
                        <div class="info-label">UF</div>
                        <div class="info-value"><?php echo htmlspecialchars($cliente['estado'] ?? '-'); ?></div>
                    </div>
                    <div class="col-12">
                        <div class="info-label">Observações do Endereço</div>
                        <div class="info-value"><?php echo formatTextoMultilinhaOuPadrao($cliente['endereco'] ?? ''); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dados Bancários -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-bank"></i> Dados Bancários</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="info-label">Titular da Conta</div>
                        <div class="info-value"><?php echo formatValorOuPadrao($cliente['conta_titular'] ?? ''); ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-label">CPF/CNPJ do Titular</div>
                        <div class="info-value"><?php echo formatDocumento($cliente['conta_documento'] ?? ''); ?></div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-label">Banco</div>
                        <div class="info-value"><?php echo formatValorOuPadrao($cliente['conta_banco'] ?? ''); ?></div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-label">Agência</div>
                        <div class="info-value"><?php echo formatValorOuPadrao($cliente['conta_agencia'] ?? ''); ?></div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-label">Conta</div>
                        <div class="info-value"><?php echo formatValorOuPadrao($cliente['conta_numero'] ?? ''); ?></div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-label">Tipo de Conta</div>
                        <div class="info-value"><?php echo formatValorOuPadrao($cliente['conta_tipo'] ?? ''); ?></div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-label">Tipo PIX</div>
                        <div class="info-value"><?php echo formatValorOuPadrao($cliente['conta_pix_tipo'] ?? ''); ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-label">Chave PIX</div>
                        <div class="info-value"><?php echo formatValorOuPadrao($cliente['conta_pix'] ?? ''); ?></div>
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
                                    <div class="info-value"><?php echo formatDocumento($socio['cpf']); ?></div>
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

        <!-- Omitindo botoes redundantes do rodape
        <div class="row">
            <div class="col-12">
                <a href="form_cliente.php?id=<?php echo $cliente['id']; ?>" class="btn btn-primary">
                    <i class="bi bi-pencil-square"></i> Editar Cliente
                </a>
                <a href="listar_clientes.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Voltar à Lista
                </a>
                <button type="button" class="btn btn-info" onclick="window.print()">
                    <i class="bi bi-printer"></i> Imprimir
                </button>
            </div>
        </div>
        -->

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <style media="print">
        .btn, .navbar {
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
