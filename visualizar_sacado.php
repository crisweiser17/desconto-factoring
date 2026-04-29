<?php require_once 'auth_check.php'; ?>
<?php
require_once 'db_connection.php'; // Conexão $pdo

$pageTitle = "Visualizar Sacado";
$sacado = null;
$socios = [];

// Verifica se um ID foi passado via GET
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: listar_sacados.php?status=error&msg=" . urlencode("ID do sacado não fornecido."));
    exit;
}

$sacadoId = (int)$_GET['id'];

try {
    // Buscar dados do sacado
    $stmt = $pdo->prepare("SELECT * FROM sacados WHERE id = :id");
    $stmt->bindParam(':id', $sacadoId, PDO::PARAM_INT);
    $stmt->execute();
    $sacado = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sacado) {
        header("Location: listar_sacados.php?status=error&msg=" . urlencode("Sacado não encontrado."));
        exit;
    }

    // Buscar sócios do sacado
    $stmt_socios = $pdo->prepare("SELECT * FROM sacados_socios WHERE sacado_id = :sacado_id ORDER BY id");
    $stmt_socios->bindParam(':sacado_id', $sacadoId, PDO::PARAM_INT);
    $stmt_socios->execute();
    $socios = $stmt_socios->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erro ao buscar dados do sacado: " . $e->getMessage());
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

function formatCpfOuCnpj($documento) {
    $docLimpo = preg_replace('/\D/', '', $documento);
    if (strlen($docLimpo) === 11) {
        return formatDocumento($docLimpo, 'FISICA');
    }
    if (strlen($docLimpo) === 14) {
        return formatDocumento($docLimpo, 'JURIDICA');
    }
    return !empty($documento) ? htmlspecialchars($documento) : '-';
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

$tipoPessoa = strtoupper($sacado['tipo_pessoa'] ?? '');
$isPessoaFisica = $tipoPessoa === 'FISICA';
$isPessoaJuridica = $tipoPessoa === 'JURIDICA';
$casado = !empty($sacado['casado']);
$whatsappLimpo = preg_replace('/\D/', '', $sacado['whatsapp'] ?? '');
$tituloDadosPrincipais = $isPessoaFisica ? 'Dados Pessoais' : 'Dados da Empresa';
$labelNomeEmpresa = $isPessoaFisica ? 'Nome' : 'Razão Social';
$labelConjuge = $isPessoaFisica ? 'Dados do Cônjuge' : 'Dados do Cônjuge do Representante';
$labelEstadoCivil = $isPessoaFisica ? 'Sacado é Casado(a)?' : 'Representante é Casado(a)?';
$mostrarEndereco = !empty($sacado['cep'])
    || !empty($sacado['logradouro'])
    || !empty($sacado['numero'])
    || !empty($sacado['complemento'])
    || !empty($sacado['bairro'])
    || !empty($sacado['cidade'])
    || !empty($sacado['estado'])
    || !empty($sacado['endereco']);
$mostrarRepresentante = !$isPessoaFisica && (
    !empty($sacado['representante_nome'])
    || !empty($sacado['representante_cpf'])
    || !empty($sacado['representante_rg'])
    || !empty($sacado['representante_estado_civil'])
    || !empty($sacado['representante_profissao'])
    || !empty($sacado['representante_nacionalidade'])
    || !empty($sacado['representante_endereco'])
);
$mostrarConjuge = $casado
    || !empty($sacado['regime_casamento'])
    || !empty($sacado['conjuge_nome'])
    || !empty($sacado['conjuge_cpf'])
    || !empty($sacado['conjuge_rg'])
    || !empty($sacado['conjuge_nacionalidade'])
    || !empty($sacado['conjuge_profissao']);
$mostrarDadosBancarios = !empty($sacado['conta_titular'])
    || !empty($sacado['conta_documento'])
    || !empty($sacado['conta_banco'])
    || !empty($sacado['conta_agencia'])
    || !empty($sacado['conta_numero'])
    || !empty($sacado['conta_tipo'])
    || !empty($sacado['conta_pix']);

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
                <span class="badge bg-primary badge-id">ID: <?php echo $sacado['id']; ?></span>
            </div>
        </div>

        <!-- Dados da Empresa -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-building"></i> <?php echo htmlspecialchars($tituloDadosPrincipais); ?></h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="info-label"><?php echo htmlspecialchars($labelNomeEmpresa); ?></div>
                        <div class="info-value"><?php echo htmlspecialchars($sacado['empresa'] ?? '-'); ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-label"><?php echo $isPessoaFisica ? 'CPF' : 'CNPJ'; ?></div>
                        <div class="info-value"><?php echo formatDocumento($sacado['documento_principal'] ?? '', $tipoPessoa); ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-label">Email</div>
                        <div class="info-value">
                            <?php if (!empty($sacado['email'])): ?>
                                <a href="mailto:<?php echo htmlspecialchars($sacado['email']); ?>"><?php echo htmlspecialchars($sacado['email']); ?></a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-label">Telefone</div>
                        <div class="info-value">
                            <?php if (!empty($sacado['telefone'])): ?>
                                <a href="tel:<?php echo preg_replace('/\D/', '', $sacado['telefone']); ?>"><?php echo formatTelefone($sacado['telefone']); ?></a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-label">WhatsApp</div>
                        <div class="info-value">
                            <?php if (!empty($sacado['whatsapp'])): ?>
                                <a href="https://wa.me/55<?php echo $whatsappLimpo; ?>" target="_blank" rel="noopener noreferrer"><?php echo formatTelefone($sacado['whatsapp']); ?></a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-label">Tipo de Pessoa</div>
                        <div class="info-value">
                            <?php
                            if ($isPessoaFisica) {
                                echo '<span class="badge bg-info">Pessoa Física</span>';
                            } elseif ($isPessoaJuridica) {
                                echo '<span class="badge bg-success">Pessoa Jurídica</span>';
                            } else {
                                echo '-';
                            }
                            ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-label">Porte</div>
                        <div class="info-value"><?php echo formatValorOuPadrao($sacado['porte'] ?? ''); ?></div>
                    </div>

                </div>
            </div>
        </div>

        <!-- Representante -->
        <?php if ($mostrarRepresentante): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-person-badge"></i> Representante</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="info-label">Nome</div>
                        <div class="info-value"><?php echo formatValorOuPadrao($sacado['representante_nome'] ?? ''); ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-label">CPF</div>
                        <div class="info-value">
                            <?php
                            echo !empty($sacado['representante_cpf'])
                                ? formatDocumento($sacado['representante_cpf'], 'FISICA')
                                : '-';
                            ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-label">RG</div>
                        <div class="info-value"><?php echo formatValorOuPadrao($sacado['representante_rg'] ?? ''); ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-label">Estado Civil</div>
                        <div class="info-value"><?php echo formatValorOuPadrao($sacado['representante_estado_civil'] ?? ''); ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-label">Profissão</div>
                        <div class="info-value"><?php echo formatValorOuPadrao($sacado['representante_profissao'] ?? ''); ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-label">Nacionalidade</div>
                        <div class="info-value"><?php echo formatValorOuPadrao($sacado['representante_nacionalidade'] ?? ''); ?></div>
                    </div>
                    <div class="col-12">
                        <div class="info-label">Endereço</div>
                        <div class="info-value"><?php echo !empty($sacado['representante_endereco']) ? nl2br(htmlspecialchars($sacado['representante_endereco'])) : '-'; ?></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Cônjuge -->
        <?php if ($mostrarConjuge): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-heart"></i> <?php echo htmlspecialchars($labelConjuge); ?></h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="info-label"><?php echo htmlspecialchars($labelEstadoCivil); ?></div>
                        <div class="info-value">
                            <?php if ($casado): ?>
                                <span class="badge bg-success">Sim</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Não</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="info-label">Regime de Casamento</div>
                        <div class="info-value"><?php echo formatValorOuPadrao($sacado['regime_casamento'] ?? ''); ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-label">Nome do Cônjuge</div>
                        <div class="info-value"><?php echo formatValorOuPadrao($sacado['conjuge_nome'] ?? ''); ?></div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-label">CPF do Cônjuge</div>
                        <div class="info-value">
                            <?php
                            echo !empty($sacado['conjuge_cpf'])
                                ? formatDocumento($sacado['conjuge_cpf'], 'FISICA')
                                : '-';
                            ?>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-label">RG do Cônjuge</div>
                        <div class="info-value"><?php echo formatValorOuPadrao($sacado['conjuge_rg'] ?? ''); ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-label">Nacionalidade</div>
                        <div class="info-value"><?php echo formatValorOuPadrao($sacado['conjuge_nacionalidade'] ?? ''); ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-label">Profissão</div>
                        <div class="info-value"><?php echo formatValorOuPadrao($sacado['conjuge_profissao'] ?? ''); ?></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Dados Bancários -->
        <?php if ($mostrarDadosBancarios): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-bank"></i> Dados Bancários</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="info-label">Titular da Conta</div>
                        <div class="info-value"><?php echo formatValorOuPadrao($sacado['conta_titular'] ?? ''); ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-label">CPF/CNPJ do Titular</div>
                        <div class="info-value"><?php echo formatCpfOuCnpj($sacado['conta_documento'] ?? ''); ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-label">Banco</div>
                        <div class="info-value"><?php echo formatValorOuPadrao($sacado['conta_banco'] ?? ''); ?></div>
                    </div>
                    <div class="col-md-2">
                        <div class="info-label">Agência</div>
                        <div class="info-value"><?php echo formatValorOuPadrao($sacado['conta_agencia'] ?? ''); ?></div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-label">Conta</div>
                        <div class="info-value"><?php echo formatValorOuPadrao($sacado['conta_numero'] ?? ''); ?></div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-label">Tipo de Conta</div>
                        <div class="info-value"><?php echo formatValorOuPadrao($sacado['conta_tipo'] ?? ''); ?></div>
                    </div>
                    <div class="col-12">
                        <div class="info-label">Chave PIX</div>
                        <div class="info-value"><?php echo formatValorOuPadrao($sacado['conta_pix'] ?? ''); ?></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Endereço -->
        <?php if ($mostrarEndereco): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-geo-alt"></i> Endereço</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="info-label">CEP</div>
                        <div class="info-value"><?php echo formatCEP($sacado['cep'] ?? ''); ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-label">Logradouro</div>
                        <div class="info-value"><?php echo htmlspecialchars($sacado['logradouro'] ?? '-'); ?></div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-label">Número</div>
                        <div class="info-value"><?php echo htmlspecialchars($sacado['numero'] ?? '-'); ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-label">Complemento</div>
                        <div class="info-value"><?php echo htmlspecialchars($sacado['complemento'] ?? '-'); ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-label">Bairro</div>
                        <div class="info-value"><?php echo htmlspecialchars($sacado['bairro'] ?? '-'); ?></div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-label">Cidade</div>
                        <div class="info-value"><?php echo htmlspecialchars($sacado['cidade'] ?? '-'); ?></div>
                    </div>
                    <div class="col-md-1">
                        <div class="info-label">UF</div>
                        <div class="info-value"><?php echo htmlspecialchars($sacado['estado'] ?? '-'); ?></div>
                    </div>
                    <?php if (!empty($sacado['endereco'])): ?>
                    <div class="col-12">
                        <div class="info-label">Observações do Endereço</div>
                        <div class="info-value"><?php echo nl2br(htmlspecialchars($sacado['endereco'])); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Sócios -->
        <?php if (!$isPessoaFisica && !empty($socios)): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-people"></i> Sócios da Empresa</h5>
            </div>
            <div class="card-body">
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
            </div>
        </div>
        <?php endif; ?>

        <!-- Botões de Ação -->
        <div class="row">
            <div class="col-12">
                <a href="form_sacado.php?id=<?php echo $sacado['id']; ?>" class="btn btn-primary">
                    <i class="bi bi-pencil-square"></i> Editar Sacado
                </a>
                <a href="listar_sacados.php" class="btn btn-secondary">
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
