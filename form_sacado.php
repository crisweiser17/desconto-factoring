<?php require_once 'auth_check.php'; ?>
<?php
require_once 'db_connection.php'; // Conexão $pdo

$pageTitle = "Adicionar Novo Sacado";
$sacado = [ // Valores padrão para um novo sacado
    'id' => null,
    'nome' => '',
    'email' => '',
    'telefone' => '',
    'tipo_pessoa' => 'JURIDICA', // Sempre pessoa jurídica
    'documento_principal' => '', // CNPJ
    'empresa' => '',
    'endereco' => '',
    'cep' => '',
    'logradouro' => '',
    'numero' => '',
    'complemento' => '',
    'bairro' => '',
    'cidade' => '',
    'estado' => '',
    'whatsapp' => '',
    'casado' => 0,
    'regime_casamento' => '',
    'conjuge_nome' => '',
    'conjuge_cpf' => '',
    'conjuge_rg' => '',
    'conjuge_nacionalidade' => '',
    'conjuge_profissao' => '',
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

// Verifica se um ID foi passado via GET (para edição)
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $editMode = true;
    $sacadoId = (int)$_GET['id'];
    $pageTitle = "Editar Sacado";

    try {
        // Buscar dados do sacado
        $stmt = $pdo->prepare("SELECT * FROM sacados WHERE id = :id");
        $stmt->bindParam(':id', $sacadoId, PDO::PARAM_INT);
        $stmt->execute();
        $fetchedData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$fetchedData) {
             ob_clean();
             header("Location: listar_sacados.php?status=error&msg=" . urlencode("Sacado não encontrado."));
             exit;
        }
        
        $sacado = array_merge($sacado, $fetchedData);

        // Buscar sócios do sacado
        $stmt_socios = $pdo->prepare("SELECT * FROM sacados_socios WHERE sacado_id = :sacado_id ORDER BY id");
        $stmt_socios->bindParam(':sacado_id', $sacadoId, PDO::PARAM_INT);
        $stmt_socios->execute();
        $socios = $stmt_socios->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        die("Erro ao buscar dados do sacado para edição: " . $e->getMessage());
    }
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
        <h1 class="mb-4"><?php echo htmlspecialchars($pageTitle); ?></h1>

        <form action="salvar_sacado.php" method="post" id="form-sacado">
            <?php if ($editMode): ?>
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($sacado['id']); ?>">
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
                            <input type="text" class="form-control" id="empresa" name="empresa" value="<?php echo htmlspecialchars($sacado['empresa'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label for="tipo_pessoa" class="form-label">Tipo de Pessoa <span class="text-danger">*</span></label>
                            <select class="form-select" id="tipo_pessoa" name="tipo_pessoa" required>
                                <option value="JURIDICA" <?php echo ($sacado['tipo_pessoa'] ?? 'JURIDICA') == 'JURIDICA' ? 'selected' : ''; ?>>Pessoa Jurídica</option>
                                <option value="FISICA" <?php echo ($sacado['tipo_pessoa'] ?? '') == 'FISICA' ? 'selected' : ''; ?>>Pessoa Física</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="documento_principal" class="form-label">
                                <span id="documento_label">CNPJ</span> <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="documento_principal" name="documento_principal" value="<?php echo htmlspecialchars($sacado['documento_principal'] ?? ''); ?>" required>
                            <div class="invalid-feedback" id="documento_principal-feedback"></div>
                        </div>
                        <div class="col-md-3">
                            <label for="porte" class="form-label">Porte</label>
                            <select class="form-select" id="porte" name="porte">
                                <option value="">Selecione...</option>
                                <option value="MEI" <?php echo ($sacado['porte'] ?? '') == 'MEI' ? 'selected' : ''; ?>>MEI</option>
                                <option value="ME" <?php echo ($sacado['porte'] ?? '') == 'ME' ? 'selected' : ''; ?>>ME</option>
                                <option value="EPP" <?php echo ($sacado['porte'] ?? '') == 'EPP' ? 'selected' : ''; ?>>EPP</option>
                                <option value="MEDIO" <?php echo ($sacado['porte'] ?? '') == 'MEDIO' ? 'selected' : ''; ?>>Médio</option>
                                <option value="GRANDE" <?php echo ($sacado['porte'] ?? '') == 'GRANDE' ? 'selected' : ''; ?>>Grande</option>
                                <option value="PF" <?php echo ($sacado['porte'] ?? '') == 'PF' ? 'selected' : ''; ?>>Pessoa Física</option>
                            </select>
                        </div>
                        <div class="col-md-3 mt-4 pt-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="possui_cnpj_mei" name="possui_cnpj_mei" value="1" <?php echo !empty($sacado['possui_cnpj_mei']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="possui_cnpj_mei">
                                    Possui CNPJ MEI
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($sacado['email'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="telefone">Telefone</label>
                            <input type="tel" class="form-control" id="telefone" name="telefone"
                                   value="<?php echo htmlspecialchars($sacado['telefone'] ?? ''); ?>"
                                   placeholder="(99) 99999-9999">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="whatsapp">WhatsApp</label>
                            <input type="tel" class="form-control" id="whatsapp" name="whatsapp"
                                   value="<?php echo htmlspecialchars($sacado['whatsapp'] ?? ''); ?>"
                                   placeholder="(99) 99999-9999">
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
                                <input type="text" class="form-control" id="cep" name="cep" value="<?php echo htmlspecialchars($sacado['cep'] ?? ''); ?>" placeholder="00000-000">
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
                            <input type="text" class="form-control" id="logradouro" name="logradouro" value="<?php echo htmlspecialchars($sacado['logradouro'] ?? ''); ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="numero" class="form-label">Número</label>
                            <input type="text" class="form-control" id="numero" name="numero" value="<?php echo htmlspecialchars($sacado['numero'] ?? ''); ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="complemento" class="form-label">Complemento</label>
                            <input type="text" class="form-control" id="complemento" name="complemento" value="<?php echo htmlspecialchars($sacado['complemento'] ?? ''); ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="bairro" class="form-label">Bairro</label>
                            <input type="text" class="form-control" id="bairro" name="bairro" value="<?php echo htmlspecialchars($sacado['bairro'] ?? ''); ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="cidade" class="form-label">Cidade</label>
                            <input type="text" class="form-control" id="cidade" name="cidade" value="<?php echo htmlspecialchars($sacado['cidade'] ?? ''); ?>">
                        </div>
                        <div class="col-md-1">
                            <label for="estado" class="form-label">UF</label>
                            <input type="text" class="form-control" id="estado" name="estado" value="<?php echo htmlspecialchars($sacado['estado'] ?? ''); ?>" maxlength="2">
                        </div>
                        <div class="col-12">
                            <label for="endereco" class="form-label">Observações do Endereço</label>
                            <textarea class="form-control" id="endereco" name="endereco" rows="2"><?php echo htmlspecialchars($sacado['endereco'] ?? ''); ?></textarea>
                        </div>
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
                        <div class="col-md-6">
                            <label for="representante_nome" class="form-label">Nome Completo</label>
                            <input type="text" class="form-control" id="representante_nome" name="representante_nome" value="<?php echo htmlspecialchars($sacado['representante_nome'] ?? ''); ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="representante_cpf" class="form-label">CPF</label>
                            <input type="text" class="form-control cpf-mask" id="representante_cpf" name="representante_cpf" value="<?php echo htmlspecialchars($sacado['representante_cpf'] ?? ''); ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="representante_rg" class="form-label">RG</label>
                            <input type="text" class="form-control" id="representante_rg" name="representante_rg" value="<?php echo htmlspecialchars($sacado['representante_rg'] ?? ''); ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="representante_nacionalidade" class="form-label">Nacionalidade</label>
                            <input type="text" class="form-control" id="representante_nacionalidade" name="representante_nacionalidade" value="<?php echo htmlspecialchars($sacado['representante_nacionalidade'] ?? 'brasileiro(a)'); ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="representante_estado_civil" class="form-label">Estado Civil</label>
                            <input type="text" class="form-control" id="representante_estado_civil" name="representante_estado_civil" value="<?php echo htmlspecialchars($sacado['representante_estado_civil'] ?? ''); ?>" placeholder="Ex: Solteiro(a)">
                        </div>
                        <div class="col-md-4">
                            <label for="representante_profissao" class="form-label">Profissão</label>
                            <input type="text" class="form-control" id="representante_profissao" name="representante_profissao" value="<?php echo htmlspecialchars($sacado['representante_profissao'] ?? ''); ?>">
                        </div>
                        <div class="col-12">
                            <label for="representante_endereco" class="form-label">Endereço Completo</label>
                            <input type="text" class="form-control" id="representante_endereco" name="representante_endereco" value="<?php echo htmlspecialchars($sacado['representante_endereco'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dados do Cônjuge -->
            <div class="card mb-4" id="card-conjuge">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-person-heart"></i> Dados do Cônjuge</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-12 mb-2">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="casado" name="casado" value="1" <?php echo !empty($sacado['casado']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="casado">Sacado/Representante é Casado(a)?</label>
                            </div>
                        </div>
                    </div>
                    <div class="row g-3" id="campos-conjuge" style="<?php echo !empty($sacado['casado']) ? '' : 'display: none;'; ?>">
                        <div class="col-md-6">
                            <label for="conjuge_nome" class="form-label">Nome do Cônjuge</label>
                            <input type="text" class="form-control" id="conjuge_nome" name="conjuge_nome" value="<?php echo htmlspecialchars($sacado['conjuge_nome'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="regime_casamento" class="form-label">Regime de Casamento</label>
                            <input type="text" class="form-control" id="regime_casamento" name="regime_casamento" value="<?php echo htmlspecialchars($sacado['regime_casamento'] ?? ''); ?>" placeholder="Ex: Comunhão Parcial de Bens">
                        </div>
                        <div class="col-md-3">
                            <label for="conjuge_cpf" class="form-label">CPF do Cônjuge</label>
                            <input type="text" class="form-control" id="conjuge_cpf" name="conjuge_cpf" value="<?php echo htmlspecialchars($sacado['conjuge_cpf'] ?? ''); ?>" placeholder="000.000.000-00">
                        </div>
                        <div class="col-md-3">
                            <label for="conjuge_rg" class="form-label">RG do Cônjuge</label>
                            <input type="text" class="form-control" id="conjuge_rg" name="conjuge_rg" value="<?php echo htmlspecialchars($sacado['conjuge_rg'] ?? ''); ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="conjuge_nacionalidade" class="form-label">Nacionalidade</label>
                            <input type="text" class="form-control" id="conjuge_nacionalidade" name="conjuge_nacionalidade" value="<?php echo htmlspecialchars($sacado['conjuge_nacionalidade'] ?? ''); ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="conjuge_profissao" class="form-label">Profissão</label>
                            <input type="text" class="form-control" id="conjuge_profissao" name="conjuge_profissao" value="<?php echo htmlspecialchars($sacado['conjuge_profissao'] ?? ''); ?>">
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
                            <input type="text" class="form-control" id="conta_titular" name="conta_titular" value="<?php echo htmlspecialchars($sacado['conta_titular'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="conta_documento" class="form-label">CPF/CNPJ do Titular</label>
                            <input type="text" class="form-control" id="conta_documento" name="conta_documento" value="<?php echo htmlspecialchars($sacado['conta_documento'] ?? ''); ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="conta_banco" class="form-label">Banco</label>
                            <input type="text" class="form-control" id="conta_banco" name="conta_banco" value="<?php echo htmlspecialchars($sacado['conta_banco'] ?? ''); ?>" placeholder="Ex: Itaú, Bradesco">
                        </div>
                        <div class="col-md-2">
                            <label for="conta_agencia" class="form-label">Agência</label>
                            <input type="text" class="form-control" id="conta_agencia" name="conta_agencia" value="<?php echo htmlspecialchars($sacado['conta_agencia'] ?? ''); ?>" placeholder="0000">
                        </div>
                        <div class="col-md-3">
                            <label for="conta_numero" class="form-label">Conta</label>
                            <input type="text" class="form-control" id="conta_numero" name="conta_numero" value="<?php echo htmlspecialchars($sacado['conta_numero'] ?? ''); ?>" placeholder="00000-0">
                        </div>
                        <div class="col-md-3">
                            <label for="conta_tipo" class="form-label">Tipo de Conta</label>
                            <select class="form-select" id="conta_tipo" name="conta_tipo">
                                <option value="" <?php echo empty($sacado['conta_tipo']) ? 'selected' : ''; ?>>Selecione...</option>
                                <option value="Corrente" <?php echo ($sacado['conta_tipo'] ?? '') === 'Corrente' ? 'selected' : ''; ?>>Conta Corrente</option>
                                <option value="Poupanca" <?php echo ($sacado['conta_tipo'] ?? '') === 'Poupanca' ? 'selected' : ''; ?>>Conta Poupança</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label for="conta_pix" class="form-label">Chave PIX</label>
                            <input type="text" class="form-control" id="conta_pix" name="conta_pix" value="<?php echo htmlspecialchars($sacado['conta_pix'] ?? ''); ?>" placeholder="CPF, CNPJ, E-mail, Telefone ou Chave Aleatória">
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

            <!-- Botões -->
            <div class="row">
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save-fill"></i> Salvar Sacado
                    </button>
                    <a href="listar_sacados.php" class="btn btn-secondary">
                         Cancelar
                    </a>
                </div>
            </div>
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

        $(document).ready(function(){
            let socioIndex = <?php echo count($socios); ?>;

            // Função para atualizar máscara do documento principal
            function updateDocumentoMask() {
                const tipoPessoa = $('#tipo_pessoa').val();
                const documentoInput = $('#documento_principal');
                const documentoLabel = $('#documento_label');
                
                // Remove máscara anterior
                documentoInput.inputmask('remove');
                
                if (tipoPessoa === 'FISICA') {
                    documentoLabel.text('CPF');
                    documentoInput.attr('placeholder', '000.000.000-00');
                    documentoInput.inputmask("999.999.999-99", {
                        clearIncomplete: true,
                        placeholder: "_"
                    });
                    
                    // Esconder e desabilitar card de sócios
                    $('#socios_card').hide();
                    $('#socios_card').find('input, button').prop('disabled', true);
                } else {
                    documentoLabel.text('CNPJ');
                    documentoInput.attr('placeholder', '00.000.000/0000-00');
                    documentoInput.inputmask("99.999.999/9999-99", {
                        clearIncomplete: true,
                        placeholder: "_"
                    });
                    
                    // Mostrar e habilitar card de sócios
                    $('#socios_card').show();
                    $('#socios_card').find('input, button').prop('disabled', false);
                }
            }

            // Máscaras iniciais
            $('#telefone').inputmask({
                mask: ["(99) 9999-9999", "(99) 99999-9999"],
                greedy: false,
                clearIncomplete: true,
                placeholder: "_"
            });

            $('#whatsapp').inputmask({
                mask: ["(99) 9999-9999", "(99) 99999-9999"],
                greedy: false,
                clearIncomplete: true,
                placeholder: "_"
            });

            $('#conjuge_cpf').inputmask("999.999.999-99", {
                clearIncomplete: true,
                placeholder: "_"
            });

            // Toggle para campos do cônjuge
            $('#casado').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#campos-conjuge').slideDown();
                } else {
                    $('#campos-conjuge').slideUp();
                }
            });

            // Aplicar máscara inicial do documento
            updateDocumentoMask();

            $('#cep').inputmask("99999-999", {
                clearIncomplete: true,
                placeholder: "_"
            });

            // Aplicar máscara de CPF aos sócios existentes
            $('.socio-cpf').each(function() {
                $(this).inputmask("999.999.999-99", {
                    clearIncomplete: true,
                    placeholder: "_"
                });
            });

            // Event listener para mudança de tipo de pessoa
            $('#tipo_pessoa').on('change', updateDocumentoMask);

            // Validação em tempo real do e-mail
            $('#email').on('blur', function() {
                const email = $(this).val().trim();
                if (email && !isValidEmail(email)) {
                    $(this).addClass('is-invalid');
                    if (!$(this).next('.invalid-feedback').length) {
                        $(this).after('<div class="invalid-feedback">Por favor, insira um e-mail válido</div>');
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
                const expectedLength = tipoPessoa === 'FISICA' ? 11 : 14;
                const documentoTipo = tipoPessoa === 'FISICA' ? 'CPF' : 'CNPJ';

                if (documento && documento.length !== expectedLength) {
                    $(this).addClass('is-invalid');
                    $('#documento_principal-feedback').text(`${documentoTipo} deve ter ${expectedLength} dígitos`);
                } else {
                    $(this).removeClass('is-invalid');
                    $('#documento_principal-feedback').text('');
                }
            });

            // Buscar CEP via ViaCEP - apenas no botão e Enter
            $('#btn-buscar-cep').on('click', function(e) {
                e.preventDefault();
                buscarCEP(true); // true = mostrar alerta se inválido
            });
            
            $('#cep').on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    buscarCEP(true); // true = mostrar alerta se inválido
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
                        
                        // Focar no campo número
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
            });

            // Validação do formulário
            $('#form-sacado').on('submit', function(e) {
                let isValid = true;
                const tipoPessoa = $('#tipo_pessoa').val();

                // Validar documento principal
                const documento = $('#documento_principal').val().replace(/\D/g, '');
                const expectedLength = tipoPessoa === 'FISICA' ? 11 : 14;
                const documentoTipo = tipoPessoa === 'FISICA' ? 'CPF' : 'CNPJ';
                
                if (documento.length !== expectedLength) {
                    $('#documento_principal').addClass('is-invalid');
                    $('#documento_principal-feedback').text(`${documentoTipo} deve ter ${expectedLength} dígitos`);
                    isValid = false;
                } else {
                    $('#documento_principal').removeClass('is-invalid');
                }

                // Validar e-mail
                const email = $('#email').val().trim();
                if (email && !isValidEmail(email)) {
                    $('#email').addClass('is-invalid');
                    if (!$('#email').next('.invalid-feedback').length) {
                        $('#email').after('<div class="invalid-feedback">Por favor, insira um e-mail válido</div>');
                    }
                    isValid = false;
                } else {
                    $('#email').removeClass('is-invalid');
                    $('#email').next('.invalid-feedback').remove();
                }

                // Validar CPFs dos sócios
                $('.socio-cpf').each(function() {
                    const cpf = $(this).val().replace(/\D/g, '');
                    if (cpf.length !== 11) {
                        $(this).addClass('is-invalid');
                        $(this).next('.invalid-feedback').text('CPF deve ter 11 dígitos');
                        isValid = false;
                    } else {
                        $(this).removeClass('is-invalid');
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>
