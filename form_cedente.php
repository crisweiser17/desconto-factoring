<?php require_once 'auth_check.php'; ?>
<?php
require_once 'db_connection.php'; // Conexão $pdo

$pageTitle = "Adicionar Novo Cedente";
$cedente = [ // Valores padrão para um novo cedente
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
    $cedenteId = (int)$_GET['id'];
    $pageTitle = "Editar Cedente";

    try {
        // Buscar dados do cedente
        $stmt = $pdo->prepare("SELECT * FROM cedentes WHERE id = :id");
        $stmt->bindParam(':id', $cedenteId, PDO::PARAM_INT);
        $stmt->execute();
        $fetchedData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$fetchedData) {
             ob_clean();
             header("Location: listar_cedentes.php?status=error&msg=" . urlencode("Cedente não encontrado."));
             exit;
        }
        
        $cedente = array_merge($cedente, $fetchedData);

        // Buscar sócios do cedente
        $stmt_socios = $pdo->prepare("SELECT * FROM cedentes_socios WHERE cedente_id = :cedente_id ORDER BY id");
        $stmt_socios->bindParam(':cedente_id', $cedenteId, PDO::PARAM_INT);
        $stmt_socios->execute();
        $socios = $stmt_socios->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        die("Erro ao buscar dados do cedente para edição: " . $e->getMessage());
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

        <form action="salvar_cedente.php" method="post" id="form-cedente">
            <?php if ($editMode): ?>
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($cedente['id']); ?>">
            <?php endif; ?>

            <!-- Dados da Empresa -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-building"></i> Dados da Empresa</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="empresa" class="form-label">Razão Social <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="empresa" name="empresa" value="<?php echo htmlspecialchars($cedente['empresa'] ?? ''); ?>" required>
                        </div>
                        <input type="hidden" id="tipo_pessoa" name="tipo_pessoa" value="JURIDICA">
                        <div class="col-md-3">
                            <label for="porte" class="form-label">Porte <span class="text-danger">*</span></label>
                            <select class="form-select" id="porte" name="porte" required>
                                <option value="" <?php echo empty($cedente['porte']) ? 'selected' : ''; ?>>Selecione...</option>
                                <option value="MEI" <?php echo ($cedente['porte'] ?? '') == 'MEI' ? 'selected' : ''; ?>>MEI → até R$ 81 mil</option>
                                <option value="ME" <?php echo ($cedente['porte'] ?? '') == 'ME' ? 'selected' : ''; ?>>ME → até R$ 360 mil</option>
                                <option value="EPP" <?php echo ($cedente['porte'] ?? '') == 'EPP' ? 'selected' : ''; ?>>EPP → até R$ 4,8 milhões</option>
                                <option value="MEDIO" <?php echo ($cedente['porte'] ?? '') == 'MEDIO' ? 'selected' : ''; ?>>Médio</option>
                                <option value="GRANDE" <?php echo ($cedente['porte'] ?? '') == 'GRANDE' ? 'selected' : ''; ?>>Grande</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="documento_principal" class="form-label">
                                <span id="documento_label">CNPJ</span> <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="documento_principal" name="documento_principal" value="<?php echo htmlspecialchars($cedente['documento_principal'] ?? ''); ?>" required>
                            <div class="invalid-feedback" id="documento_principal-feedback"></div>
                        </div>
                        <div class="col-md-4">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($cedente['email'] ?? ''); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="telefone">Telefone</label>
                            <input type="tel" class="form-control" id="telefone" name="telefone"
                                   value="<?php echo htmlspecialchars($cedente['telefone'] ?? ''); ?>"
                                   placeholder="(99) 99999-9999">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="whatsapp">WhatsApp</label>
                            <input type="tel" class="form-control" id="whatsapp" name="whatsapp"
                                   value="<?php echo htmlspecialchars($cedente['whatsapp'] ?? ''); ?>"
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
                                <input type="text" class="form-control" id="cep" name="cep" value="<?php echo htmlspecialchars($cedente['cep'] ?? ''); ?>" placeholder="00000-000">
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
                            <input type="text" class="form-control" id="logradouro" name="logradouro" value="<?php echo htmlspecialchars($cedente['logradouro'] ?? ''); ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="numero" class="form-label">Número</label>
                            <input type="text" class="form-control" id="numero" name="numero" value="<?php echo htmlspecialchars($cedente['numero'] ?? ''); ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="complemento" class="form-label">Complemento</label>
                            <input type="text" class="form-control" id="complemento" name="complemento" value="<?php echo htmlspecialchars($cedente['complemento'] ?? ''); ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="bairro" class="form-label">Bairro</label>
                            <input type="text" class="form-control" id="bairro" name="bairro" value="<?php echo htmlspecialchars($cedente['bairro'] ?? ''); ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="cidade" class="form-label">Cidade</label>
                            <input type="text" class="form-control" id="cidade" name="cidade" value="<?php echo htmlspecialchars($cedente['cidade'] ?? ''); ?>">
                        </div>
                        <div class="col-md-1">
                            <label for="estado" class="form-label">UF</label>
                            <input type="text" class="form-control" id="estado" name="estado" value="<?php echo htmlspecialchars($cedente['estado'] ?? ''); ?>" maxlength="2">
                        </div>
                        <div class="col-12">
                            <label for="endereco" class="form-label">Observações do Endereço</label>
                            <textarea class="form-control" id="endereco" name="endereco" rows="2"><?php echo htmlspecialchars($cedente['endereco'] ?? ''); ?></textarea>
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
                                <input class="form-check-input" type="checkbox" id="casado" name="casado" value="1" <?php echo !empty($cedente['casado']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="casado">Cedente/Representante é Casado(a)?</label>
                            </div>
                        </div>
                    </div>
                    <div class="row g-3" id="campos-conjuge" style="<?php echo !empty($cedente['casado']) ? '' : 'display: none;'; ?>">
                        <div class="col-md-6">
                            <label for="conjuge_nome" class="form-label">Nome do Cônjuge</label>
                            <input type="text" class="form-control" id="conjuge_nome" name="conjuge_nome" value="<?php echo htmlspecialchars($cedente['conjuge_nome'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="regime_casamento" class="form-label">Regime de Casamento</label>
                            <input type="text" class="form-control" id="regime_casamento" name="regime_casamento" value="<?php echo htmlspecialchars($cedente['regime_casamento'] ?? ''); ?>" placeholder="Ex: Comunhão Parcial de Bens">
                        </div>
                        <div class="col-md-3">
                            <label for="conjuge_cpf" class="form-label">CPF do Cônjuge</label>
                            <input type="text" class="form-control" id="conjuge_cpf" name="conjuge_cpf" value="<?php echo htmlspecialchars($cedente['conjuge_cpf'] ?? ''); ?>" placeholder="000.000.000-00">
                        </div>
                        <div class="col-md-3">
                            <label for="conjuge_rg" class="form-label">RG do Cônjuge</label>
                            <input type="text" class="form-control" id="conjuge_rg" name="conjuge_rg" value="<?php echo htmlspecialchars($cedente['conjuge_rg'] ?? ''); ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="conjuge_nacionalidade" class="form-label">Nacionalidade</label>
                            <input type="text" class="form-control" id="conjuge_nacionalidade" name="conjuge_nacionalidade" value="<?php echo htmlspecialchars($cedente['conjuge_nacionalidade'] ?? ''); ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="conjuge_profissao" class="form-label">Profissão</label>
                            <input type="text" class="form-control" id="conjuge_profissao" name="conjuge_profissao" value="<?php echo htmlspecialchars($cedente['conjuge_profissao'] ?? ''); ?>">
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
                            <input type="text" class="form-control" id="conta_titular" name="conta_titular" value="<?php echo htmlspecialchars($cedente['conta_titular'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="conta_documento" class="form-label">CPF/CNPJ do Titular</label>
                            <input type="text" class="form-control" id="conta_documento" name="conta_documento" value="<?php echo htmlspecialchars($cedente['conta_documento'] ?? ''); ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="conta_banco" class="form-label">Banco</label>
                            <input type="text" class="form-control" id="conta_banco" name="conta_banco" value="<?php echo htmlspecialchars($cedente['conta_banco'] ?? ''); ?>" placeholder="Ex: Itaú, Bradesco">
                        </div>
                        <div class="col-md-2">
                            <label for="conta_agencia" class="form-label">Agência</label>
                            <input type="text" class="form-control" id="conta_agencia" name="conta_agencia" value="<?php echo htmlspecialchars($cedente['conta_agencia'] ?? ''); ?>" placeholder="0000">
                        </div>
                        <div class="col-md-3">
                            <label for="conta_numero" class="form-label">Conta</label>
                            <input type="text" class="form-control" id="conta_numero" name="conta_numero" value="<?php echo htmlspecialchars($cedente['conta_numero'] ?? ''); ?>" placeholder="00000-0">
                        </div>
                        <div class="col-md-3">
                            <label for="conta_tipo" class="form-label">Tipo de Conta</label>
                            <select class="form-select" id="conta_tipo" name="conta_tipo">
                                <option value="" <?php echo empty($cedente['conta_tipo']) ? 'selected' : ''; ?>>Selecione...</option>
                                <option value="Corrente" <?php echo ($cedente['conta_tipo'] ?? '') === 'Corrente' ? 'selected' : ''; ?>>Conta Corrente</option>
                                <option value="Poupanca" <?php echo ($cedente['conta_tipo'] ?? '') === 'Poupanca' ? 'selected' : ''; ?>>Conta Poupança</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label for="conta_pix" class="form-label">Chave PIX</label>
                            <input type="text" class="form-control" id="conta_pix" name="conta_pix" value="<?php echo htmlspecialchars($cedente['conta_pix'] ?? ''); ?>" placeholder="CPF, CNPJ, E-mail, Telefone ou Chave Aleatória">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sócios -->
            <div class="card mb-4">
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
                        <i class="bi bi-save-fill"></i> Salvar Cedente
                    </button>
                    <a href="listar_cedentes.php" class="btn btn-secondary">
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
                } else {
                    documentoLabel.text('CNPJ');
                    documentoInput.attr('placeholder', '00.000.000/0000-00');
                    documentoInput.inputmask("99.999.999/9999-99", {
                        clearIncomplete: true,
                        placeholder: "_"
                    });
                }
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

            $('#conjuge_cpf').inputmask("999.999.999-99", {
                clearIncomplete: true,
                placeholder: "_"
            });

            $('#cep').inputmask("99999-999", {
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
            });

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
                const expectedLength = tipoPessoa === 'FISICA' ? 11 : 14;
                const documentoTipo = tipoPessoa === 'FISICA' ? 'CPF' : 'CNPJ';

                let valid = true;
                if (documento.length !== expectedLength) {
                    valid = false;
                } else if (tipoPessoa === 'FISICA' && !isValidCPF(documento)) {
                    valid = false;
                } else if (tipoPessoa === 'JURIDICA' && !isValidCNPJ(documento)) {
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
            $('#form-cedente').on('submit', function(e) {
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
                const expectedLength = tipoPessoa === 'FISICA' ? 11 : 14;
                const documentoTipo = tipoPessoa === 'FISICA' ? 'CPF' : 'CNPJ';
                
                let isDocValid = true;
                if (documento.length !== expectedLength) {
                    isDocValid = false;
                } else if (tipoPessoa === 'FISICA' && !isValidCPF(documento)) {
                    isDocValid = false;
                } else if (tipoPessoa === 'JURIDICA' && !isValidCNPJ(documento)) {
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

                // Validar Cônjuge CPF
                if ($('#casado').is(':checked')) {
                    const conjugeCpf = $('#conjuge_cpf').val().replace(/\D/g, '');
                    if (conjugeCpf && (conjugeCpf.length !== 11 || !isValidCPF(conjugeCpf))) {
                        $('#conjuge_cpf').addClass('is-invalid');
                        isValid = false;
                    } else {
                        $('#conjuge_cpf').removeClass('is-invalid');
                    }
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