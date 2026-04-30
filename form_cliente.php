<?php require_once 'auth_check.php'; ?>
<?php
require_once 'db_connection.php'; // Conexão $pdo

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$pageTitle = "Adicionar Novo Cliente";
$cliente = [ // Valores padrão para um novo cliente
    'id' => null,
    'nome' => '',
    'email' => '',
    'telefone' => '',
    'whatsapp' => '',
    'tipo_pessoa' => 'JURIDICA', // Padrão pessoa jurídica
    'porte' => '',
    'documento_principal' => '',
    'empresa' => '',
    'endereco' => '',
    'cep' => '',
    'logradouro' => '',
    'numero' => '',
    'complemento' => '',
    'bairro' => '',
    'cidade' => '',
    'estado' => '',
    'conta_banco' => '',
    'conta_agencia' => '',
    'conta_numero' => '',
    'conta_pix' => '',
    'conta_tipo' => '',
    'conta_titular' => '',
    'conta_documento' => ''
];
$socios = []; // Array de sócios
$editMode = false; // Flag para saber se estamos editando
$alertStatus = $_GET['status'] ?? '';
$alertMessage = trim($_GET['msg'] ?? '');
$flashClienteForm = $_SESSION['cliente_form_flash'] ?? null;

// Verifica se um ID foi passado via GET (para edição)
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $editMode = true;
    $clienteId = (int)$_GET['id'];
    $pageTitle = "Editar Cliente";

    try {
        // Buscar dados do cliente
        $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = :id");
        $stmt->bindParam(':id', $clienteId, PDO::PARAM_INT);
        $stmt->execute();
        $fetchedData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$fetchedData) {
             ob_clean();
             header("Location: listar_clientes.php?status=error&msg=" . urlencode("Cliente não encontrado."));
             exit;
        }
        
        $cliente = array_merge($cliente, $fetchedData);

        // Buscar sócios do cliente
        $stmt_socios = $pdo->prepare("SELECT * FROM clientes_socios WHERE cliente_id = :cliente_id ORDER BY id");
        $stmt_socios->bindParam(':cliente_id', $clienteId, PDO::PARAM_INT);
        $stmt_socios->execute();
        $socios = $stmt_socios->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        die("Erro ao buscar dados do cliente para edição: " . $e->getMessage());
    }
}

$flashAplicaNoContextoAtual = is_array($flashClienteForm)
    && (($editMode && !empty($flashClienteForm['cliente_id']) && (int) $flashClienteForm['cliente_id'] === (int) $cliente['id'])
        || (!$editMode && empty($flashClienteForm['cliente_id'])));

if ($alertStatus === 'error' && $flashAplicaNoContextoAtual) {
    $flashCliente = $flashClienteForm['cliente'] ?? [];
    $flashSocios = $flashClienteForm['socios'] ?? [];

    if (is_array($flashCliente)) {
        $cliente = array_merge($cliente, $flashCliente);
    }

    if (is_array($flashSocios)) {
        $socios = $flashSocios;
    }
}

unset($_SESSION['cliente_form_flash']);

$contaTitularReadonlyValue = $cliente['conta_titular'] ?? '';
$contaDocumentoReadonlyValue = $cliente['conta_documento'] ?? '';

if (($cliente['tipo_pessoa'] ?? 'JURIDICA') === 'JURIDICA') {
    $contaTitularReadonlyValue = $cliente['empresa'] ?? '';
    $contaDocumentoReadonlyValue = $cliente['documento_principal'] ?? '';
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
        .socio-item {
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 1rem;
            margin-bottom: 1rem;
            background-color: #f8f9fa;
        }
        .btn-remove-socio {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
        }
        .socio-item {
            position: relative;
        }
        .loading-cep {
            display: none;
        }
    </style>
</head>
<body>
    <?php require_once 'menu.php'; ?>

    <div class="container-fluid px-3 px-md-4 mt-4">
        <?php if (in_array($alertStatus, ['success', 'error'], true) && $alertMessage !== ''): ?>
            <div class="alert alert-<?php echo $alertStatus === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                <i class="bi <?php echo $alertStatus === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'; ?>"></i>
                <?php echo htmlspecialchars($alertMessage); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form action="salvar_cliente.php" method="post" id="form-cliente">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <div class="d-flex align-items-center gap-3">
                    <h1 class="mb-0">
                        <?php echo htmlspecialchars($pageTitle); ?>
                        <?php if ($editMode && !empty($cliente['empresa'])): ?>
                            <small class="text-muted fs-4">- <?php echo htmlspecialchars($cliente['empresa']); ?></small>
                        <?php endif; ?>
                    </h1>
                    <?php if ($editMode): ?>
                        <span class="badge bg-primary badge-id" style="font-size: 1.1rem; padding: 0.5rem 1rem;">ID: <?php echo $cliente['id']; ?></span>
                    <?php endif; ?>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save-fill"></i> Salvar Cliente
                    </button>
                    <a href="listar_clientes.php" class="btn btn-secondary">
                        Cancelar
                    </a>
                </div>
            </div>

            <?php if ($editMode): ?>
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($cliente['id']); ?>">
            <?php endif; ?>

            <!-- Dados da Empresa -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-building"></i> Dados da Empresa</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="empresa" class="form-label">Nome / Razão Social <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="empresa" name="empresa" value="<?php echo htmlspecialchars($cliente['empresa'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label for="tipo_pessoa_display" class="form-label">Tipo de Pessoa <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="tipo_pessoa_display" value="Pessoa Jurídica" readonly>
                            <input type="hidden" id="tipo_pessoa" name="tipo_pessoa" value="JURIDICA">
                        </div>
                        <div class="col-md-3">
                            <label for="documento_principal" class="form-label">
                                <span id="documento_label">CNPJ</span> <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="documento_principal" name="documento_principal" value="<?php echo htmlspecialchars($cliente['documento_principal'] ?? ''); ?>" required>
                            <div class="invalid-feedback" id="documento_principal-feedback"></div>
                        </div>
                        <div class="col-md-4">
                            <label for="porte" class="form-label">Porte</label>
                            <select class="form-select" id="porte" name="porte">
                                <option value="">Selecione...</option>
                                <option value="MEI" <?php echo ($cliente['porte'] ?? '') == 'MEI' ? 'selected' : ''; ?>>MEI → até R$ 81 mil</option>
                                <option value="ME" <?php echo ($cliente['porte'] ?? '') == 'ME' ? 'selected' : ''; ?>>ME → até R$ 360 mil</option>
                                <option value="EPP" <?php echo ($cliente['porte'] ?? '') == 'EPP' ? 'selected' : ''; ?>>EPP → até R$ 4,8 milhões</option>
                                <option value="MEDIO" <?php echo ($cliente['porte'] ?? '') == 'MEDIO' ? 'selected' : ''; ?>>Médio</option>
                                <option value="GRANDE" <?php echo ($cliente['porte'] ?? '') == 'GRANDE' ? 'selected' : ''; ?>>Grande</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($cliente['email'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="telefone">Telefone</label>
                            <input type="tel" class="form-control" id="telefone" name="telefone"
                                   value="<?php echo htmlspecialchars($cliente['telefone'] ?? ''); ?>"
                                   placeholder="(99) 99999-9999">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="whatsapp">WhatsApp</label>
                            <input type="tel" class="form-control" id="whatsapp" name="whatsapp"
                                   value="<?php echo htmlspecialchars($cliente['whatsapp'] ?? ''); ?>"
                                   placeholder="(99) 99999-9999">
                        </div>
                        <div class="col-12">
                            <label for="anotacoes" class="form-label">Anotações</label>
                            <textarea class="form-control" id="anotacoes" name="anotacoes" rows="3"><?php echo htmlspecialchars($cliente['anotacoes'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sócios -->
            <div class="card mb-4" id="socios_card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-people"></i> Sócios da Empresa</h5>
                    <button type="button" class="btn btn-success btn-sm" id="btn-adicionar-socio">
                        <i class="bi bi-plus-circle"></i> Adicionar Sócio
                    </button>
                </div>
                <div class="card-body">
                    <div id="socios-container">
                        <?php if (!empty($socios)): ?>
                            <?php foreach ($socios as $index => $socio): ?>
                                <div class="socio-item" data-index="<?php echo $index; ?>">
                                    <button type="button" class="btn btn-danger btn-sm btn-remove-socio">
                                        <i class="bi bi-x"></i>
                                    </button>
                                    <div class="row g-3">
                                        <div class="col-md-8">
                                            <label class="form-label">Nome do Sócio <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control socio-nome" name="socios[<?php echo $index; ?>][nome]" value="<?php echo htmlspecialchars($socio['nome']); ?>" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">CPF <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control socio-cpf" name="socios[<?php echo $index; ?>][cpf]" value="<?php echo htmlspecialchars($socio['cpf']); ?>" required>
                                            <div class="invalid-feedback"></div>
                                        </div>
                                    </div>
                                    <?php if (isset($socio['id'])): ?>
                                        <input type="hidden" name="socios[<?php echo $index; ?>][id]" value="<?php echo $socio['id']; ?>">
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div id="no-socios-message" class="text-muted text-center py-3" <?php echo !empty($socios) ? 'style="display: none;"' : ''; ?>>
                        <i class="bi bi-info-circle"></i> Nenhum sócio cadastrado. Clique em "Adicionar Sócio" para incluir.
                    </div>
                </div>
            </div>

            <!-- Dados do Representante -->
            <div class="card mb-4" id="representante_card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-person-badge"></i> Dados do Representante</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-12 mb-2" id="representante_socio_container">
                            <label for="representante_socio_select" class="form-label">Selecionar Sócio como Representante</label>
                            <select class="form-select" id="representante_socio_select">
                                <option value="">Selecione um Sócio...</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="representante_nome" class="form-label">Nome Completo</label>
                            <input type="text" class="form-control bg-light" id="representante_nome" name="representante_nome" value="<?php echo htmlspecialchars($cliente['representante_nome'] ?? ''); ?>" readonly>
                        </div>
                        <div class="col-md-3">
                            <label for="representante_cpf" class="form-label">CPF</label>
                            <input type="text" class="form-control cpf-mask bg-light" id="representante_cpf" name="representante_cpf" value="<?php echo htmlspecialchars($cliente['representante_cpf'] ?? ''); ?>" readonly>
                        </div>
                        <div class="col-12" id="campos_adicionais_representante" style="display: none;">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label for="representante_rg" class="form-label">RG</label>
                                    <input type="text" class="form-control" id="representante_rg" name="representante_rg" value="<?php echo htmlspecialchars($cliente['representante_rg'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="representante_nacionalidade" class="form-label">Nacionalidade</label>
                                    <input type="text" class="form-control" id="representante_nacionalidade" name="representante_nacionalidade" value="<?php echo htmlspecialchars($cliente['representante_nacionalidade'] ?? 'brasileiro(a)'); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="representante_estado_civil" class="form-label">Estado Civil</label>
                                    <select class="form-select" id="representante_estado_civil" name="representante_estado_civil">
                                        <option value="">Selecione...</option>
                                        <option value="Solteiro(a)" <?php echo ($cliente['representante_estado_civil'] ?? '') === 'Solteiro(a)' ? 'selected' : ''; ?>>Solteiro(a)</option>
                                        <option value="Casado(a)" <?php echo ($cliente['representante_estado_civil'] ?? '') === 'Casado(a)' ? 'selected' : ''; ?>>Casado(a)</option>
                                        <option value="Separado(a)" <?php echo ($cliente['representante_estado_civil'] ?? '') === 'Separado(a)' ? 'selected' : ''; ?>>Separado(a)</option>
                                        <option value="Divorciado(a)" <?php echo ($cliente['representante_estado_civil'] ?? '') === 'Divorciado(a)' ? 'selected' : ''; ?>>Divorciado(a)</option>
                                        <option value="Viúvo(a)" <?php echo ($cliente['representante_estado_civil'] ?? '') === 'Viúvo(a)' ? 'selected' : ''; ?>>Viúvo(a)</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="representante_profissao" class="form-label">Profissão</label>
                                    <input type="text" class="form-control" id="representante_profissao" name="representante_profissao" value="<?php echo htmlspecialchars($cliente['representante_profissao'] ?? ''); ?>">
                                </div>
                                <div class="col-12">
                                    <label for="representante_endereco" class="form-label">Endereço Completo</label>
                                    <input type="text" class="form-control" id="representante_endereco" name="representante_endereco" value="<?php echo htmlspecialchars($cliente['representante_endereco'] ?? ''); ?>">
                                </div>
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
                            <label for="cep" class="form-label">CEP</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="cep" name="cep" value="<?php echo htmlspecialchars($cliente['cep'] ?? ''); ?>" placeholder="00000-000">
                                <button class="btn btn-outline-secondary" type="button" id="btn-buscar-cep">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                            <div class="loading-cep">
                                <small class="text-muted"><i class="bi bi-arrow-clockwise"></i> Buscando...</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="logradouro" class="form-label">Logradouro</label>
                            <input type="text" class="form-control" id="logradouro" name="logradouro" value="<?php echo htmlspecialchars($cliente['logradouro'] ?? ''); ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="numero" class="form-label">Número</label>
                            <input type="text" class="form-control" id="numero" name="numero" value="<?php echo htmlspecialchars($cliente['numero'] ?? ''); ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="complemento" class="form-label">Complemento</label>
                            <input type="text" class="form-control" id="complemento" name="complemento" value="<?php echo htmlspecialchars($cliente['complemento'] ?? ''); ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="bairro" class="form-label">Bairro</label>
                            <input type="text" class="form-control" id="bairro" name="bairro" value="<?php echo htmlspecialchars($cliente['bairro'] ?? ''); ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="cidade" class="form-label">Cidade</label>
                            <input type="text" class="form-control" id="cidade" name="cidade" value="<?php echo htmlspecialchars($cliente['cidade'] ?? ''); ?>">
                        </div>
                        <div class="col-md-1">
                            <label for="estado" class="form-label">UF</label>
                            <input type="text" class="form-control" id="estado" name="estado" value="<?php echo htmlspecialchars($cliente['estado'] ?? ''); ?>" maxlength="2">
                        </div>
                        <div class="col-12">
                            <label for="endereco" class="form-label">Observações do Endereço</label>
                            <textarea class="form-control" id="endereco" name="endereco" rows="2"><?php echo htmlspecialchars($cliente['endereco'] ?? ''); ?></textarea>
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
                            <label for="conta_titular" class="form-label">Titular da Conta</label>
                            <input type="text" class="form-control bg-light" id="conta_titular" name="conta_titular" value="<?php echo htmlspecialchars($contaTitularReadonlyValue); ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label for="conta_documento" class="form-label">CPF/CNPJ do Titular</label>
                            <input type="text" class="form-control bg-light" id="conta_documento" name="conta_documento" value="<?php echo htmlspecialchars($contaDocumentoReadonlyValue); ?>" readonly>
                        </div>
                        <div class="col-md-4">
                            <label for="conta_banco" class="form-label">Banco</label>
                            <input type="text" class="form-control" id="conta_banco" name="conta_banco" value="<?php echo htmlspecialchars($cliente['conta_banco'] ?? ''); ?>" placeholder="Ex: Itaú, Bradesco">
                        </div>
                        <div class="col-md-2">
                            <label for="conta_agencia" class="form-label">Agência</label>
                            <input type="text" class="form-control" id="conta_agencia" name="conta_agencia" value="<?php echo htmlspecialchars($cliente['conta_agencia'] ?? ''); ?>" placeholder="0000">
                        </div>
                        <div class="col-md-3">
                            <label for="conta_numero" class="form-label">Conta</label>
                            <input type="text" class="form-control" id="conta_numero" name="conta_numero" value="<?php echo htmlspecialchars($cliente['conta_numero'] ?? ''); ?>" placeholder="00000-0">
                        </div>
                        <div class="col-md-3">
                            <label for="conta_tipo" class="form-label">Tipo de Conta</label>
                            <select class="form-select" id="conta_tipo" name="conta_tipo">
                                <option value="" <?php echo empty($cliente['conta_tipo']) ? 'selected' : ''; ?>>Selecione...</option>
                                <option value="Corrente" <?php echo ($cliente['conta_tipo'] ?? '') === 'Corrente' ? 'selected' : ''; ?>>Conta Corrente</option>
                                <option value="Poupanca" <?php echo ($cliente['conta_tipo'] ?? '') === 'Poupanca' ? 'selected' : ''; ?>>Conta Poupança</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="conta_pix_tipo" class="form-label">Tipo PIX</label>
                            <select class="form-select" id="conta_pix_tipo" name="conta_pix_tipo">
                                <option value="" <?php echo empty($cliente['conta_pix_tipo']) ? 'selected' : ''; ?>>Selecione...</option>
                                <option value="CPF" <?php echo ($cliente['conta_pix_tipo'] ?? '') === 'CPF' ? 'selected' : ''; ?>>CPF</option>
                                <option value="CNPJ" <?php echo ($cliente['conta_pix_tipo'] ?? '') === 'CNPJ' ? 'selected' : ''; ?>>CNPJ</option>
                                <option value="Email" <?php echo ($cliente['conta_pix_tipo'] ?? '') === 'Email' ? 'selected' : ''; ?>>E-mail</option>
                                <option value="Telefone" <?php echo ($cliente['conta_pix_tipo'] ?? '') === 'Telefone' ? 'selected' : ''; ?>>Telefone</option>
                                <option value="Aleatoria" <?php echo ($cliente['conta_pix_tipo'] ?? '') === 'Aleatoria' ? 'selected' : ''; ?>>Chave Aleatória</option>
                            </select>
                        </div>
                        <div class="col-md-9">
                            <label for="conta_pix" class="form-label">Chave PIX</label>
                            <input type="text" class="form-control" id="conta_pix" name="conta_pix" value="<?php echo htmlspecialchars($cliente['conta_pix'] ?? ''); ?>" placeholder="Informe a chave PIX">
                        </div>
                    </div>
                </div>
            </div>



            <!-- Omitindo botoes redundantes do rodape
            <div class="row">
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save-fill"></i> Salvar Cliente
                    </button>
                    <a href="listar_clientes.php" class="btn btn-secondary">
                         Cancelar
                    </a>
                </div>
            </div>
            -->
        </form>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.inputmask/5.0.9/jquery.inputmask.min.js"></script>

    <script>
        // Função para validar e-mail
        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

        // Funções para validar CPF e CNPJ
        function isValidCPF(cpf) {
            cpf = cpf.replace(/[^\d]+/g, '');
            if (cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) return false;
            let soma = 0, resto;
            for (let i = 1; i <= 9; i++) soma += parseInt(cpf.substring(i - 1, i)) * (11 - i);
            resto = (soma * 10) % 11;
            if (resto === 10 || resto === 11) resto = 0;
            if (resto !== parseInt(cpf.substring(9, 10))) return false;
            soma = 0;
            for (let i = 1; i <= 10; i++) soma += parseInt(cpf.substring(i - 1, i)) * (12 - i);
            resto = (soma * 10) % 11;
            if (resto === 10 || resto === 11) resto = 0;
            return resto === parseInt(cpf.substring(10, 11));
        }

        function isValidCNPJ(cnpj) {
            cnpj = cnpj.replace(/[^\d]+/g, '');
            if (cnpj.length !== 14 || /^(\d)\1{13}$/.test(cnpj)) return false;
            let tamanho = cnpj.length - 2;
            let numeros = cnpj.substring(0, tamanho);
            let digitos = cnpj.substring(tamanho);
            let soma = 0, pos = tamanho - 7;
            for (let i = tamanho; i >= 1; i--) {
                soma += numeros.charAt(tamanho - i) * pos--;
                if (pos < 2) pos = 9;
            }
            let resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
            if (resultado !== parseInt(digitos.charAt(0))) return false;
            tamanho += 1;
            numeros = cnpj.substring(0, tamanho);
            soma = 0;
            pos = tamanho - 7;
            for (let i = tamanho; i >= 1; i--) {
                soma += numeros.charAt(tamanho - i) * pos--;
                if (pos < 2) pos = 9;
            }
            resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
            return resultado === parseInt(digitos.charAt(1));
        }

        $(document).ready(function(){
            let socioIndex = <?php echo count($socios); ?>;

            // Função para atualizar máscara do documento principal
            function updateDocumentoMask() {
                const documentoInput = $('#documento_principal');
                const documentoLabel = $('#documento_label');
                
                documentoLabel.text('CNPJ');
                documentoInput.inputmask('remove');
                documentoInput.attr('placeholder', '00.000.000/0000-00');
                documentoInput.inputmask("99.999.999/9999-99", {
                    clearIncomplete: true,
                    placeholder: "_"
                });
                
                // Mostrar e habilitar card de sócios e representante
                $('#socios_card').show();
                $('#socios_card').find('input, button').prop('disabled', false);
                $('#representante_card').show();
                $('#representante_card').find('input, select, button').prop('disabled', false);
                
                // Mostrar campo porte
                $('#porte').closest('div[class^="col-"]').show();
            }

            // Máscaras iniciais
            $('#telefone').inputmask({
                mask: "(99) 9999[9]-9999",
                greedy: false,
                clearIncomplete: true,
                placeholder: "_"
            });

            $('#whatsapp').inputmask({
                mask: "(99) 9999[9]-9999",
                greedy: false,
                clearIncomplete: true,
                placeholder: "_"
            });

            $('#cep').inputmask("99999-999", {
                clearIncomplete: true,
                placeholder: "_"
            });

            
            // Aplicar máscara inicial do documento
            updateDocumentoMask();

            // Aplicar máscara de CPF aos sócios existentes
            $('.socio-cpf').each(function() {
                $(this).inputmask("999.999.999-99", {
                    clearIncomplete: true,
                    placeholder: "_"
                });
            });

            // Event listener para mudança de tipo de pessoa
            $('#tipo_pessoa').on('change', updateDocumentoMask);

            // Buscar CEP via ViaCEP
            $('#btn-buscar-cep').on('click', function(e) {
                e.preventDefault();
                buscarCEP(true);
            });
            
            $('#cep').on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    buscarCEP(true);
                }
            });

            function buscarCEP(mostrarAlerta = false) {
                const cep = $('#cep').val().replace(/\D/g, '');
                
                if (cep.length !== 8) {
                    if (mostrarAlerta) {
                        alert('CEP deve ter 8 dígitos');
                    }
                    return;
                }

                $('.loading-cep').show();
                
                $.ajax({
                    url: `https://viacep.com.br/ws/${cep}/json/`,
                    type: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        $('.loading-cep').hide();
                        
                        if (data.erro) {
                            alert('CEP não encontrado');
                            return;
                        }

                        $('#logradouro').val(data.logradouro || '');
                        $('#bairro').val(data.bairro || '');
                        $('#cidade').val(data.localidade || '');
                        $('#estado').val(data.uf || '');
                        
                        $('#numero').focus();
                    },
                    error: function() {
                        $('.loading-cep').hide();
                        alert('Erro ao buscar CEP. Verifique sua conexão.');
                    }
                });
            }

            // Adicionar sócio
            $('#btn-adicionar-socio').click(function() {
                const socioHtml = `
                    <div class="socio-item" data-index="${socioIndex}">
                        <button type="button" class="btn btn-danger btn-sm btn-remove-socio">
                            <i class="bi bi-x"></i>
                        </button>
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label">Nome do Sócio <span class="text-danger">*</span></label>
                                <input type="text" class="form-control socio-nome" name="socios[${socioIndex}][nome]" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">CPF <span class="text-danger">*</span></label>
                                <input type="text" class="form-control socio-cpf" name="socios[${socioIndex}][cpf]" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>
                `;
                
                $('#socios-container').append(socioHtml);
                $('#no-socios-message').hide();
                
                // Aplicar máscara ao novo campo CPF
                $(`.socio-item[data-index="${socioIndex}"] .socio-cpf`).inputmask("999.999.999-99", {
                    clearIncomplete: true,
                    placeholder: "_"
                });
                
                socioIndex++;
            });

            // Remover sócio
            $(document).on('click', '.btn-remove-socio', function() {
                $(this).closest('.socio-item').remove();
                
                if ($('.socio-item').length === 0) {
                    $('#no-socios-message').show();
                }
                updateDynamicSelects();
            });

            function normalizeRepresentativeName(value) {
                return (value || '').trim().replace(/\s+/g, ' ').toLowerCase();
            }

            function normalizeRepresentativeDocument(value) {
                return (value || '').replace(/\D/g, '');
            }

            function findRepresentativeOptionValue() {
                const nomeAtual = normalizeRepresentativeName($('#representante_nome').val());
                const cpfAtual = normalizeRepresentativeDocument($('#representante_cpf').val());

                if (!nomeAtual || !cpfAtual) {
                    return '';
                }

                let matchedValue = '';

                $('#representante_socio_select option').each(function() {
                    const optionValue = $(this).val();
                    if (!optionValue) {
                        return;
                    }

                    const parts = optionValue.split('|');
                    const optionNome = normalizeRepresentativeName(parts[0] || '');
                    const optionCpf = normalizeRepresentativeDocument(parts[1] || '');

                    if (optionNome === nomeAtual && optionCpf === cpfAtual) {
                        matchedValue = optionValue;
                        return false;
                    }
                });

                return matchedValue;
            }

            function updateRepresentativeDetailsVisibility() {
                const hasRepresentative = Boolean(
                    normalizeRepresentativeName($('#representante_nome').val()) &&
                    normalizeRepresentativeDocument($('#representante_cpf').val())
                );

                $('#campos_adicionais_representante').toggle(hasRepresentative);
            }

            // Lógica para preenchimento dinâmico de representante
            function updateDynamicSelects() {
                let sociosOptions = '';
                $('.socio-item').each(function() {
                    const nome = $(this).find('.socio-nome').val().trim();
                    const cpf = $(this).find('.socio-cpf').val().trim();
                    if (nome || cpf) {
                        sociosOptions += `<option value="${nome}|${cpf}">${nome} (Sócio)</option>`;
                    }
                });

                // Update representante_socio_select
                const repSelect = $('#representante_socio_select');
                const currentRep = repSelect.val();
                repSelect.html('<option value="">Selecione um Sócio...</option>');
                repSelect.append(sociosOptions);

                const hasCurrentRepOption = repSelect.find('option').filter(function() {
                    return $(this).val() === currentRep;
                }).length > 0;

                if (currentRep && hasCurrentRepOption) {
                    repSelect.val(currentRep);
                } else {
                    repSelect.val(findRepresentativeOptionValue());
                }
            }

            // Atualiza selects dinâmicos ao modificar inputs
            $(document).on('input', '.socio-nome, .socio-cpf', updateDynamicSelects);
            updateDynamicSelects();

            function syncContaTitularFields() {
                const tipoPessoa = $('#tipo_pessoa').val();
                if (tipoPessoa === 'JURIDICA') {
                    $('#conta_titular').val($('#empresa').val());
                    $('#conta_documento').val($('#documento_principal').val()).trigger('input');
                }
            }

            // Sincroniza dinamicamente Empresa e Documento com Dados Bancários
            $('#empresa').on('input', syncContaTitularFields);
            $('#documento_principal').on('input', syncContaTitularFields);

            // Sincronização inicial na carga da página
            syncContaTitularFields();

            // Preenche representante ao selecionar sócio
            $('#representante_socio_select').on('change', function() {
                const val = $(this).val();
                if (val) {
                    const parts = val.split('|');
                    const nome = parts[0];
                    const doc = parts[1];
                    $('#representante_nome').val(nome);
                    $('#representante_cpf').val(doc);
                    $('#campos_adicionais_representante').slideDown();
                } else {
                    $('#representante_nome').val('');
                    $('#representante_cpf').val('');
                    $('#campos_adicionais_representante').slideUp();
                    $('#representante_rg, #representante_nacionalidade, #representante_estado_civil, #representante_profissao, #representante_endereco').val('');
                    $('#representante_nacionalidade').val('brasileiro(a)'); // Valor padrão
                }
            });
            
            updateRepresentativeDetailsVisibility();

            // Função para validar email
            function isValidEmail(email) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return emailRegex.test(email);
            }

            // Validação em tempo real para email
            $('#email').on('blur', function() {
                const email = $(this).val().trim();
                if (email && !isValidEmail(email)) {
                    $(this).addClass('is-invalid');
                    if (!$(this).next('.invalid-feedback').length) {
                        $(this).after('<div class="invalid-feedback">Por favor, insira um email válido.</div>');
                    }
                } else {
                    $(this).removeClass('is-invalid');
                    $(this).next('.invalid-feedback').remove();
                }
            });

            // Validação em tempo real do CNPJ/CPF
            $('#documento_principal').on('blur', function() {
                const tipoPessoa = $('#tipo_pessoa').val();
                const documento = $(this).val().replace(/\D/g, '');
                const expectedLength = 14;
                const documentoTipo = 'CNPJ';
                let valid = true;
                if (documento.length !== expectedLength) {
                    valid = false;
                } else if (!isValidCNPJ(documento)) {
                    valid = false;
                }

                if (!valid) {
                    $(this).addClass('is-invalid');
                    $('#documento_principal-feedback').text(`${documentoTipo} inválido`);
                } else {
                    $(this).removeClass('is-invalid');
                    $('#documento_principal-feedback').text('');
                }
            });

            $('.cpf-mask').inputmask("999.999.999-99", {
                clearIncomplete: true,
                placeholder: "_"
            });

            $('#conta_documento').on('input', function() {
                let val = $(this).val().replace(/\D/g, '');
                if (val.length <= 11) {
                    $(this).inputmask("999.999.999-99", { clearIncomplete: false });
                } else {
                    $(this).inputmask("99.999.999/9999-99", { clearIncomplete: false });
                }
            });

            // Validação do formulário
            $('#form-cliente').on('submit', function(e) {
                let isValid = true;
                const tipoPessoa = $('#tipo_pessoa').val();

                // Validar email
                const email = $('#email').val().trim();
                if (email && !isValidEmail(email)) {
                    $('#email').addClass('is-invalid');
                    if (!$('#email').next('.invalid-feedback').length) {
                        $('#email').after('<div class="invalid-feedback">Por favor, insira um email válido.</div>');
                    }
                    isValid = false;
                } else {
                    $('#email').removeClass('is-invalid');
                    $('#email').next('.invalid-feedback').remove();
                }

                // Validar documento principal
                const documento = $('#documento_principal').val().replace(/\D/g, '');
                const expectedLength = 14;
                const documentoTipo = 'CNPJ';
                
                let isDocValid = true;
                if (documento.length !== expectedLength) {
                    isDocValid = false;
                } else if (!isValidCNPJ(documento)) {
                    isDocValid = false;
                }

                if (!isDocValid) {
                    $('#documento_principal').addClass('is-invalid');
                    $('#documento_principal-feedback').text(`${documentoTipo} inválido`);
                    isValid = false;
                } else {
                    $('#documento_principal').removeClass('is-invalid');
                }

                // Validar CPFs dos sócios
                $('.socio-cpf').each(function() {
                    const cpf = $(this).val().replace(/\D/g, '');
                    if (cpf.length !== 11 || !isValidCPF(cpf)) {
                        $(this).addClass('is-invalid');
                        $(this).next('.invalid-feedback').text('CPF inválido');
                        isValid = false;
                    } else {
                        $(this).removeClass('is-invalid');
                    }
                });

                // Validar Representante CPF
                const repCpf = $('#representante_cpf').val().replace(/\D/g, '');
                if (repCpf && (repCpf.length !== 11 || !isValidCPF(repCpf))) {
                    $('#representante_cpf').addClass('is-invalid');
                    isValid = false;
                } else {
                    $('#representante_cpf').removeClass('is-invalid');
                }

                // Validar Conta Documento
                const contaDoc = $('#conta_documento').val().replace(/\D/g, '');
                if (contaDoc) {
                    if (contaDoc.length === 11 && !isValidCPF(contaDoc)) {
                        $('#conta_documento').addClass('is-invalid');
                        isValid = false;
                    } else if (contaDoc.length === 14 && !isValidCNPJ(contaDoc)) {
                        $('#conta_documento').addClass('is-invalid');
                        isValid = false;
                    } else if (contaDoc.length !== 11 && contaDoc.length !== 14) {
                        $('#conta_documento').addClass('is-invalid');
                        isValid = false;
                    } else {
                        $('#conta_documento').removeClass('is-invalid');
                    }
                }

                if (!isValid) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>
