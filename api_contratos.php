<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);
session_start();
require_once 'db_connection.php';
require_once 'vendor/autoload.php';

use WGenial\NumeroPorExtenso\NumeroPorExtenso;

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Não autorizado.']);
    exit;
}

$action = $_REQUEST['action'] ?? '';
$operacao_id = $_REQUEST['operacao_id'] ?? null;

if (!$operacao_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID da operação é obrigatório.']);
    exit;
}

try {
    switch ($action) {
        case 'listar':
            listarDocumentos($pdo, $operacao_id);
            break;
        case 'gerar':
            gerarContrato($pdo, $operacao_id);
            break;
        case 'upload':
            uploadContrato($pdo, $operacao_id);
            break;
        case 'delete':
            deleteContrato($pdo, $operacao_id);
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Ação inválida.']);
            break;
    }
} catch (Throwable $e) {
    error_log("Erro na api_contratos.php: " . $e->getMessage() . " na linha " . $e->getLine());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor: ' . $e->getMessage()]);
}

// === Funções ===

function listarDocumentos($pdo, $operacao_id) {
    $stmt = $pdo->prepare("SELECT * FROM operacao_documentos WHERE operacao_id = ? ORDER BY data_geracao DESC");
    $stmt->execute([$operacao_id]);
    $documentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmtStatus = $pdo->prepare("SELECT status_contrato FROM operacoes WHERE id = ?");
    $stmtStatus->execute([$operacao_id]);
    $status_contrato = $stmtStatus->fetchColumn();

    echo json_encode([
        'success' => true,
        'status_contrato' => $status_contrato,
        'documentos' => $documentos
    ]);
}

function parseBooleanContratoFlag($value) {
    if (is_bool($value)) {
        return $value;
    }

    if ($value === null) {
        return false;
    }

    $normalized = strtolower(trim((string) $value));
    return in_array($normalized, ['1', 'sim', 'true', 'on', 'yes'], true);
}

function normalizarCampoContrato($value, $default = '') {
    if ($value === null) {
        return $default;
    }

    $normalized = trim((string) $value);
    return $normalized !== '' ? $normalized : $default;
}

function montarEnderecoContrato(array $operacao) {
    $logradouro = normalizarCampoContrato($operacao['cedente_logradouro'] ?? '');
    $numero = normalizarCampoContrato($operacao['cedente_numero'] ?? '');
    $complemento = normalizarCampoContrato($operacao['cedente_complemento'] ?? '');
    $bairro = normalizarCampoContrato($operacao['cedente_bairro'] ?? '');
    $cidade = normalizarCampoContrato($operacao['cedente_cidade'] ?? '');
    $estado = normalizarCampoContrato($operacao['cedente_estado'] ?? '');
    $cep = normalizarCampoContrato($operacao['cedente_cep'] ?? '');
    
    // Se logradouro foi preenchido, montar endereço completo estruturado
    if ($logradouro !== '') {
        $endereco = $logradouro;
        if ($numero !== '') $endereco .= ', ' . $numero;
        else $endereco .= ', S/N';
        
        if ($complemento !== '') $endereco .= ' - ' . $complemento;
        if ($bairro !== '') $endereco .= ', ' . $bairro;
        if ($cidade !== '') $endereco .= ', ' . $cidade;
        if ($estado !== '') $endereco .= ' - ' . $estado;
        if ($cep !== '') $endereco .= ', CEP: ' . $cep;
        
        return $endereco;
    }

    // Fallback: usar o campo de texto livre se as partes estiverem vazias
    $endereco_livre = normalizarCampoContrato($operacao['cedente_endereco'] ?? '');
    
    if ($endereco_livre !== '' && $cidade !== '' && $estado !== '') {
        return $endereco_livre . ', ' . $cidade . ' - ' . $estado;
    }

    $partes = array_values(array_filter([$endereco_livre, $cidade, $estado], static function ($parte) {
        return $parte !== '';
    }));

    return implode(' - ', $partes);
}

function montarParteContrato(array $operacao, $porteCliente = '') {
    $tipoPessoa = strtoupper((string) ($operacao['tipo_pessoa'] ?? ''));
    $pessoaJuridica = in_array($tipoPessoa, ['PJ', 'JURIDICA'], true);
    $documentoPrincipal = normalizarCampoContrato($operacao['cedente_documento_principal'] ?? $operacao['sacado_documento_principal'] ?? '');
    $cpf = normalizarCampoContrato($operacao['cedente_cpf'] ?? $operacao['sacado_cpf'] ?? '', $documentoPrincipal);
    $cnpj = normalizarCampoContrato($operacao['cedente_cnpj'] ?? $operacao['sacado_cnpj'] ?? '', $documentoPrincipal);
    $nomeCompleto = normalizarCampoContrato($operacao['cedente_nome'] ?? $operacao['sacado_nome'] ?? '');
    $razaoSocial = normalizarCampoContrato($operacao['empresa'] ?? '', $nomeCompleto);
    $nomeExibicao = $pessoaJuridica ? $razaoSocial : $nomeCompleto;
    $documento = $pessoaJuridica ? $cnpj : $cpf;
    $enderecoCompleto = montarEnderecoContrato($operacao);
    $representanteNome = $pessoaJuridica
        ? normalizarCampoContrato($operacao['representante_nome'] ?? '')
        : normalizarCampoContrato($operacao['representante_nome'] ?? '', $nomeCompleto);
    $representanteCpf = $pessoaJuridica
        ? normalizarCampoContrato($operacao['representante_cpf'] ?? '')
        : normalizarCampoContrato($operacao['representante_cpf'] ?? '', $cpf);
    $representanteRg = $pessoaJuridica
        ? normalizarCampoContrato($operacao['representante_rg'] ?? '')
        : normalizarCampoContrato($operacao['representante_rg'] ?? '', '-');
    $representanteEndereco = $pessoaJuridica
        ? normalizarCampoContrato($operacao['representante_endereco'] ?? '')
        : normalizarCampoContrato($operacao['representante_endereco'] ?? '', $enderecoCompleto);

    return [
        'pessoa_juridica' => $pessoaJuridica,
        'razao_social' => normalizarCampoContrato($razaoSocial, '[NÃO INFORMADO]'),
        'descricao_juridica' => 'pessoa jurídica de direito privado',
        'cnpj' => normalizarCampoContrato($cnpj, '[NÃO INFORMADO]'),
        'nome_completo' => normalizarCampoContrato($nomeCompleto, '[NÃO INFORMADO]'),
        'nacionalidade' => normalizarCampoContrato($operacao['representante_nacionalidade'] ?? '', 'brasileiro(a)'),
        'estado_civil' => normalizarCampoContrato($operacao['representante_estado_civil'] ?? '', 'Solteiro(a)'),
        'profissao' => normalizarCampoContrato($operacao['representante_profissao'] ?? '', 'Empresário'),
        'rg' => normalizarCampoContrato($operacao['representante_rg'] ?? '', '-'),
        'cpf' => normalizarCampoContrato($cpf, '[NÃO INFORMADO]'),
        'documento' => normalizarCampoContrato($documento, '[NÃO INFORMADO]'),
        'documento_label' => $pessoaJuridica ? 'CNPJ' : 'CPF',
        'nome_exibicao' => normalizarCampoContrato($nomeExibicao, '[NÃO INFORMADO]'),
        'porte' => normalizarCampoContrato($porteCliente, normalizarCampoContrato($operacao['porte'] ?? '', 'ME')),
        'endereco_completo' => normalizarCampoContrato($enderecoCompleto, '[NÃO INFORMADO]'),
        'email' => normalizarCampoContrato($operacao['email'] ?? '', 'Não informado'),
        'whatsapp' => normalizarCampoContrato($operacao['whatsapp'] ?? '', 'Não informado'),
        'casado' => in_array(
            normalizarCampoContrato($operacao['representante_estado_civil'] ?? '', 'Solteiro(a)'),
            ['Casado(a)'],
            true
        ),
        'conjuge' => [
            'nome' => '',
            'cpf' => ''
        ],
        'conta' => [
            'banco' => normalizarCampoContrato($operacao['conta_banco'] ?? '', '[NÃO INFORMADO]'),
            'agencia' => normalizarCampoContrato($operacao['conta_agencia'] ?? '', '[NÃO INFORMADO]'),
            'numero' => normalizarCampoContrato($operacao['conta_numero'] ?? '', '[NÃO INFORMADO]'),
            'tipo' => normalizarCampoContrato($operacao['conta_tipo'] ?? '', '[NÃO INFORMADO]'),
            'pix' => normalizarCampoContrato($operacao['conta_pix'] ?? '', '[NÃO INFORMADO]'),
            'titular' => normalizarCampoContrato($nomeExibicao, '[NÃO INFORMADO]'),
            'documento' => normalizarCampoContrato($documento, '[NÃO INFORMADO]')
        ],
        'representante' => [
            'nome' => normalizarCampoContrato($representanteNome, '[NÃO INFORMADO]'),
            'nacionalidade' => normalizarCampoContrato($operacao['representante_nacionalidade'] ?? '', 'brasileiro(a)'),
            'estado_civil' => normalizarCampoContrato($operacao['representante_estado_civil'] ?? '', 'Solteiro(a)'),
            'profissao' => normalizarCampoContrato($operacao['representante_profissao'] ?? '', 'Empresário'),
            'rg' => normalizarCampoContrato($representanteRg, '[NÃO INFORMADO]'),
            'cpf' => normalizarCampoContrato($representanteCpf, '[NÃO INFORMADO]'),
            'endereco' => normalizarCampoContrato($representanteEndereco, '[NÃO INFORMADO]')
        ]
    ];
}

function validarDadosBancariosCedenteAntecipacao(array $cedente, array $conta) {
    $camposObrigatorios = [
        'banco' => 'Banco',
        'agencia' => 'Agência',
        'numero' => 'Conta',
        'tipo' => 'Tipo de conta'
    ];

    $camposFaltantes = [];
    foreach ($camposObrigatorios as $chave => $label) {
        if (normalizarCampoContrato($conta[$chave] ?? '') === '') {
            $camposFaltantes[] = $label;
        }
    }

    if ($camposFaltantes === []) {
        return;
    }

    $nomeCedente = normalizarCampoContrato(
        $cedente['nome_exibicao'] ?? '',
        normalizarCampoContrato($cedente['razao_social'] ?? '', normalizarCampoContrato($cedente['nome_completo'] ?? '', 'informado na operação'))
    );

    throw new Exception(
        'Não foi possível gerar o contrato de antecipação porque faltam dados bancários obrigatórios do cedente ' .
        $nomeCedente .
        ': ' .
        implode(', ', $camposFaltantes) .
        '. Atualize o cadastro do cedente e tente novamente.'
    );
}

function gerarContrato($pdo, $operacao_id) {
    // Pegar dados do POST
    $natureza = $_POST['natureza'] ?? '';
    $porte_cliente = $_POST['porte_cliente'] ?? '';
    $tem_garantia_legado = $_POST['tem_garantia'] ?? null;
    $tem_garantia_real_flag = $_POST['tem_garantia_real'] ?? null;
    $tem_avalista_flag = $_POST['tem_avalista'] ?? null;
    $tipo_garantia = normalizarCampoContrato($_POST['tipo_garantia'] ?? 'veiculo', 'veiculo');
    $conjuge_assina_flag = $_POST['conjuge_assina'] ?? null;
    $conjuge_assina = parseBooleanContratoFlag($conjuge_assina_flag);

    $tem_veiculo = null;
    $tem_bem_movel = false;
    $tem_avalista = null;

    if ($tem_garantia_real_flag !== null) {
        $tem_veiculo = parseBooleanContratoFlag($tem_garantia_real_flag);
    }

    if ($tem_avalista_flag !== null) {
        $tem_avalista = parseBooleanContratoFlag($tem_avalista_flag);
    }

    if ($tem_veiculo === null || $tem_avalista === null) {
        $tem_veiculo_legado = in_array($tem_garantia_legado, ['com_veiculo_com_avalista', 'com_veiculo_sem_avalista'], true);
        $tem_avalista_legado = in_array($tem_garantia_legado, ['com_veiculo_com_avalista', 'sem_veiculo_com_avalista'], true);

        if ($tem_veiculo === null) {
            $tem_veiculo = $tem_veiculo_legado;
        }

        if ($tem_avalista === null) {
            $tem_avalista = $tem_avalista_legado;
        }
    }

    if ($natureza !== 'EMPRESTIMO') {
        $tem_veiculo = false;
        $tem_bem_movel = false;
        $tem_avalista = false;
        $tipo_garantia = '';
    } else {
        if (!in_array($tipo_garantia, ['veiculo', 'bem_movel'], true)) {
            $tipo_garantia = 'veiculo';
        }

        if ($tipo_garantia === 'bem_movel' && parseBooleanContratoFlag($tem_garantia_real_flag)) {
            $tem_bem_movel = true;
            $tem_veiculo = false;
        } else {
            $tem_bem_movel = false;
        }
    }
    
    // Avalista
    $avalista_nome = $_POST['avalista_nome'] ?? '';
    $avalista_cpf = $_POST['avalista_cpf'] ?? '';
    $avalista_rg = $_POST['avalista_rg'] ?? '';
    $avalista_nacionalidade = $_POST['avalista_nacionalidade'] ?? '';
    $avalista_estado_civil = $_POST['avalista_estado_civil'] ?? '';
    $avalista_profissao = $_POST['avalista_profissao'] ?? '';
    $avalista_endereco = $_POST['avalista_endereco'] ?? '';
    $avalista_regime_casamento = $_POST['avalista_regime_casamento'] ?? '';
    $avalista_conjuge_nome = $_POST['avalista_conjuge_nome'] ?? '';
    $avalista_conjuge_cpf = $_POST['avalista_conjuge_cpf'] ?? '';
    $avalista_email = $_POST['avalista_email'] ?? '';
    $avalista_whatsapp = $_POST['avalista_whatsapp'] ?? '';

    // Bem móvel
    $bem_tipo = $_POST['bem_tipo'] ?? '';
    $bem_descricao_detalhada = $_POST['bem_descricao_detalhada'] ?? '';
    $bem_identificadores = $_POST['bem_identificadores'] ?? '';
    $bem_local_guarda = $_POST['bem_local_guarda'] ?? '';
    $bem_documentos_origem = $_POST['bem_documentos_origem'] ?? '';
    $bem_valor_avaliacao = !empty($_POST['bem_valor_avaliacao']) ? (float)$_POST['bem_valor_avaliacao'] : 0;
    
    // Veiculo
    $veiculo_marca = $_POST['veiculo_marca'] ?? '';
    $veiculo_modelo = $_POST['veiculo_modelo'] ?? '';
    $veiculo_ano_fab = !empty($_POST['veiculo_ano_fab']) ? (int)$_POST['veiculo_ano_fab'] : null;
    $veiculo_ano_mod = !empty($_POST['veiculo_ano_mod']) ? (int)$_POST['veiculo_ano_mod'] : null;
    $veiculo_cor = $_POST['veiculo_cor'] ?? '';
    $veiculo_placa = $_POST['veiculo_placa'] ?? '';
    $veiculo_renavam = $_POST['veiculo_renavam'] ?? '';
    $veiculo_valor_avaliacao = $_POST['veiculo_valor_avaliacao'] ?? 0;
    $veiculo_chassi = $_POST['veiculo_chassi'] ?? '';
    $veiculo_municipio_registro = $_POST['veiculo_municipio_registro'] ?? '';
    $veiculo_uf = $_POST['veiculo_uf'] ?? '';

    if ($tem_avalista) {
        if (trim($avalista_nome) === '' || trim($avalista_cpf) === '') {
            throw new Exception("Informe pelo menos nome completo e CPF do avalista para gerar o contrato com avalista.");
        }

        $avalista_nacionalidade = normalizarCampoContrato($avalista_nacionalidade, 'brasileiro(a)');
        $avalista_estado_civil = normalizarCampoContrato($avalista_estado_civil, 'Solteiro(a)');
        $avalista_profissao = normalizarCampoContrato($avalista_profissao, 'não informado');
        $avalista_rg = normalizarCampoContrato($avalista_rg, 'não informado');
        $avalista_endereco = normalizarCampoContrato($avalista_endereco, 'não informado');
    }

    if ($tem_bem_movel) {
        if (
            trim($bem_tipo) === '' ||
            trim($bem_descricao_detalhada) === '' ||
            trim($bem_identificadores) === '' ||
            trim($bem_local_guarda) === '' ||
            $bem_valor_avaliacao <= 0
        ) {
            throw new Exception("Preencha os dados principais do bem móvel oferecido em garantia.");
        }
    }

    // Fetch operacao details
    $stmt = $pdo->prepare("SELECT * FROM operacoes WHERE id = ?");
    $stmt->execute([$operacao_id]);
    $operacao = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$operacao) {
        throw new Exception("Operação não encontrada.");
    }

    // Identificar de onde vem os dados do Devedor/Cedente (Cedente para Antecipação, Sacado para Empréstimo)
    if (($operacao['tipo_operacao'] ?? 'antecipacao') === 'emprestimo') {
        // Para Empréstimo, o Devedor é o Cliente (cedente_id)
        $stmtSacado = $pdo->prepare("
            SELECT c.nome as sacado_nome, c.documento_principal as sacado_documento_principal, c.cpf as sacado_cpf, c.cnpj as sacado_cnpj, c.endereco as sacado_endereco,
                   c.cidade as cedente_cidade, c.estado as cedente_estado, c.tipo_pessoa, c.empresa,
                   c.representante_nome, c.representante_cpf, c.representante_rg, c.representante_estado_civil, c.representante_profissao, c.representante_nacionalidade, c.representante_endereco,
                   c.conta_banco, c.conta_agencia, c.conta_numero, c.conta_tipo, c.conta_pix, c.email, c.whatsapp,
                   c.logradouro as cedente_logradouro, c.numero as cedente_numero, c.complemento as cedente_complemento, c.bairro as cedente_bairro, c.cep as cedente_cep
            FROM clientes c
            JOIN operacoes o ON c.id = o.cedente_id
            WHERE o.id = ?
            LIMIT 1
        ");
        $stmtSacado->execute([$operacao_id]);
        $sacado = $stmtSacado->fetch(PDO::FETCH_ASSOC);
        if ($sacado) {
            $operacao = array_merge($operacao, $sacado);
        }
    } else {
        // Para Antecipação, o Devedor/Cedente é o Cedente
        if (empty($operacao['cedente_id'])) {
            throw new Exception("Não foi possível gerar o contrato de antecipação porque a operação não possui cedente vinculado.");
        }

        $stmtCedente = $pdo->prepare("
            SELECT c.nome as cedente_nome, c.documento_principal as cedente_documento_principal, c.cpf as cedente_cpf, c.cnpj as cedente_cnpj, c.endereco as cedente_endereco, 
                   c.cidade as cedente_cidade, c.estado as cedente_estado, c.tipo_pessoa, c.empresa,
                   c.representante_nome, c.representante_cpf, c.representante_rg, c.representante_estado_civil, c.representante_profissao, c.representante_nacionalidade, c.representante_endereco,
                   c.conta_banco, c.conta_agencia, c.conta_numero, c.conta_tipo, c.conta_pix, c.email, c.whatsapp,
                   c.logradouro as cedente_logradouro, c.numero as cedente_numero, c.complemento as cedente_complemento, c.bairro as cedente_bairro, c.cep as cedente_cep
            FROM clientes c
            WHERE c.id = ?
        ");
        $stmtCedente->execute([$operacao['cedente_id']]);
        $cedente = $stmtCedente->fetch(PDO::FETCH_ASSOC);
        if (!$cedente) {
            throw new Exception("Não foi possível localizar o cadastro do cedente vinculado a esta operação.");
        }

        $operacao = array_merge($operacao, $cedente);
    }
    
    // Regra 2: Validação de Tomador
    if ($natureza === 'EMPRESTIMO') {
        $tipoPessoa = strtoupper((string) ($operacao['tipo_pessoa'] ?? ''));
        if (!in_array($tipoPessoa, ['PJ', 'JURIDICA'], true)) {
            throw new Exception("A ACM ESC não pode realizar operações de mútuo com pessoa física. Apenas pessoas jurídicas (MEI, ME, EPP) são permitidas.");
        }
        if (!in_array($porte_cliente, ['MEI', 'ME', 'EPP'])) {
            throw new Exception("LC 167/2019 restringe operações da ESC a MEI, Microempresas e Empresas de Pequeno Porte.");
        }
    } else if ($natureza === 'DESCONTO') {
        // Validar títulos (sacado distinto do cedente)
        $stmtTitulos = $pdo->prepare("SELECT COUNT(*) FROM recebiveis WHERE operacao_id = ? AND sacado_id != (SELECT id FROM clientes WHERE empresa = ? OR empresa = ? LIMIT 1)");
        $stmtTitulos->execute([$operacao_id, $operacao['empresa'], $operacao['cedente_nome']]);
        $titulosValidos = $stmtTitulos->fetchColumn();
        
        // Simples verificação, em produção pode ser mais robusta
        $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM recebiveis WHERE operacao_id = ?");
        $stmtCount->execute([$operacao_id]);
        $totalTitulos = $stmtCount->fetchColumn();
        
        if ($totalTitulos == 0) {
            throw new Exception("Operação de Desconto precisa ter pelo menos 1 título.");
        }
    }

    $extenso = new NumeroPorExtenso();

    $meses = [
        1 => 'janeiro', 2 => 'fevereiro', 3 => 'março', 4 => 'abril', 5 => 'maio', 6 => 'junho',
        7 => 'julho', 8 => 'agosto', 9 => 'setembro', 10 => 'outubro', 11 => 'novembro', 12 => 'dezembro'
    ];
    $data_atual = date('d') . ' de ' . $meses[(int)date('m')] . ' de ' . date('Y');

    $total_liquido_pago_calc = (float)($operacao['total_liquido_pago_calc'] ?? 0);
    $total_original_calc = (float)($operacao['total_original_calc'] ?? 0);
    $taxa_mensal = (float)($operacao['taxa_mensal'] ?? 0);
    $veiculo_valor_avaliacao = (float)($veiculo_valor_avaliacao ?? 0);

    // Carregar recebíveis da operação
    $stmtTitulosList = $pdo->prepare("
        SELECT r.*, s.nome as sacado_nome, s.documento_principal as sacado_documento 
        FROM recebiveis r 
        LEFT JOIN clientes s ON r.sacado_id = s.id 
        WHERE r.operacao_id = ?
    ");
    $stmtTitulosList->execute([$operacao_id]);
    $titulos = $stmtTitulosList->fetchAll(PDO::FETCH_ASSOC);

    // Formatar títulos
    $titulos_formatados = [];
    $total_face_bordero = 0;
    $total_juros_bordero = 0;
    $total_liquido_bordero = 0;
    $tipo_operacao_normalizado = strtolower((string)($operacao['tipo_operacao'] ?? 'antecipacao'));
    $is_emprestimo_operacao = $tipo_operacao_normalizado === 'emprestimo';
    
    $ordem = 1;
    foreach ($titulos as $t) {
        // `valor_original` + `valor_presente_calc` são os campos canônicos dos recebíveis atuais.
        // Mantemos fallback para colunas legadas para preservar contratos de desconto antigos.
        $valor_face = (float)($t['valor_original'] ?? $t['valor_face'] ?? 0);
        $numero_titulo = trim((string)($t['numero_documento'] ?? $t['numero_titulo'] ?? ''));

        if ($numero_titulo === '') {
            $numero_titulo = (string)($t['id'] ?? '');
        }

        if ($is_emprestimo_operacao) {
            $valor_presente = (float)($t['valor_presente_calc'] ?? $t['valor_liquido_calc'] ?? $t['valor_presente'] ?? $t['valor_liquido'] ?? 0);
        } else {
            $valor_presente = (float)($t['valor_liquido_calc'] ?? $t['valor_presente_calc'] ?? $t['valor_liquido'] ?? $t['valor_presente'] ?? 0);
        }

        $juros = $valor_face - $valor_presente;

        $total_face_bordero += $valor_face;
        $total_juros_bordero += $juros;
        $total_liquido_bordero += $valor_presente;

        $titulos_formatados[] = [
            'ordem' => str_pad($ordem++, 2, '0', STR_PAD_LEFT),
            'numero' => $numero_titulo,
            'tipo' => $t['tipo_recebivel'] ?? 'DUPLICATA',
            'sacado_nome' => $t['sacado_nome'],
            'sacado_documento' => $t['sacado_documento'],
            'data_emissao' => !empty($t['data_emissao']) ? date('d/m/Y', strtotime($t['data_emissao'])) : date('d/m/Y'),
            'data_vencimento' => date('d/m/Y', strtotime($t['data_vencimento'])),
            'valor_face' => number_format($valor_face, 2, ',', '.'),
            'valor_liquido' => number_format($valor_presente, 2, ',', '.'),
            'valor_presente' => number_format($valor_presente, 2, ',', '.'),
            'juros' => number_format($juros, 2, ',', '.')
        ];
    }

    $total_desagio_bordero = $total_face_bordero - $total_liquido_bordero;

    // Construir cronograma dinamicamente
    $cronograma = [];
    $saldo_devedor_atual = $total_face_bordero;
    $data_primeiro_vencimento = null;
    $data_vencimento_ultima_parcela = null;

    foreach ($titulos_formatados as $i => $t) {
        $valor_parcela_num = (float) str_replace(['.', ','], ['', '.'], $t['valor_face']);
        $valor_amortizacao_num = (float) str_replace(['.', ','], ['', '.'], $t['valor_liquido']);
        $valor_juros_num = (float) str_replace(['.', ','], ['', '.'], $t['juros']);
        
        $saldo_devedor_atual -= $valor_parcela_num;
        if ($saldo_devedor_atual < 0.01) $saldo_devedor_atual = 0; // Prevenir imprecisão de float

        if ($i === 0) {
            $data_primeiro_vencimento = $t['data_vencimento'];
        }
        
        $data_vencimento_ultima_parcela = $t['data_vencimento'];

        $cronograma[] = [
            'numero' => $i + 1,
            'data_vencimento' => $t['data_vencimento'],
            'valor_parcela' => number_format($valor_parcela_num, 2, ',', '.'),
            'valor_amortizacao' => number_format($valor_amortizacao_num, 2, ',', '.'),
            'valor_juros' => number_format($valor_juros_num, 2, ',', '.'),
            'saldo_devedor' => number_format($saldo_devedor_atual, 2, ',', '.')
        ];
    }

    $data_vencimento_extenso = '';
    if ($data_vencimento_ultima_parcela) {
        $parts = explode('/', $data_vencimento_ultima_parcela);
        if (count($parts) === 3) {
            $dia = (int)$parts[0];
            $mes = (int)$parts[1];
            $ano = (int)$parts[2];
            $dia_extenso = trim(str_replace([' reais', ' real'], '', $extenso->converter($dia)));
            if ($dia === 1) {
                $dia_extenso = 'primeiro';
            }
            $ano_extenso = trim(str_replace([' reais', ' real'], '', $extenso->converter($ano)));
            $data_vencimento_extenso = $dia_extenso . ' de ' . $meses[$mes] . ' de ' . $ano_extenso;
        }
    }

    $num_parcelas_count = count($titulos_formatados);
    $periodicidade = $num_parcelas_count === 1 ? 'única' : 'variável';
    $valor_parcela_texto = $num_parcelas_count === 1 ? number_format($total_face_bordero, 2, ',', '.') : 'Variável (conforme cronograma)';
    $valor_parcela_extenso_texto = $num_parcelas_count === 1 ? $extenso->converter($total_face_bordero) : 'Variável';

    // Carregar configuração
    $config_file = __DIR__ . '/config.json';
    $config = file_exists($config_file) ? json_decode(file_get_contents($config_file), true) : [];
    $credorRazaoSocial = normalizarCampoContrato(
        $config['empresa_razao_social'] ?? '',
        normalizarCampoContrato($config['conta_titular'] ?? '', normalizarCampoContrato($config['app_name'] ?? ''))
    );
    $credorDocumento = normalizarCampoContrato(
        $config['empresa_documento'] ?? '',
        normalizarCampoContrato($config['conta_documento'] ?? '')
    );
    $credorContaTitular = normalizarCampoContrato($config['conta_titular'] ?? '', $credorRazaoSocial);
    $credorContaDocumento = normalizarCampoContrato($config['conta_documento'] ?? '', $credorDocumento);
    $parteContrato = montarParteContrato($operacao, $porte_cliente);
    $tarifas_operacionais = (float)($operacao['iof_total_calc'] ?? 0) + (float)($operacao['tarifas'] ?? 0);
    $resumoFinanceiroAntecipacao = [
        'total_titulos' => count($titulos),
        'total_face' => number_format($total_face_bordero, 2, ',', '.'),
        'taxa_desagio_mensal' => number_format($taxa_mensal * 100, 2, ',', '.'),
        'taxa_desagio_mensal_extenso' => $extenso->converter($taxa_mensal * 100),
        'prazo_medio' => number_format($operacao['media_dias_pond_calc'] ?? 0, 0, '', ''),
        'total_desagio' => number_format($total_desagio_bordero, 2, ',', '.'),
        'tarifas' => number_format($tarifas_operacionais, 2, ',', '.'),
        'possui_tarifas' => abs($tarifas_operacionais) > 0.00001,
        'valor_liquido' => number_format($total_liquido_bordero, 2, ',', '.'),
        'valor_liquido_extenso' => $extenso->converter($total_liquido_bordero),
        'forma_pagamento' => 'Transferência Bancária (PIX)'
    ];

    if (!$is_emprestimo_operacao) {
        validarDadosBancariosCedenteAntecipacao($parteContrato, $parteContrato['conta'] ?? []);
    }
    
    // Adicionar propriedade conjuge_assina
    $parteContrato['conjuge_assina'] = $conjuge_assina;

    // Preparar Data para Mustache
    $data = [
        'titulos' => $titulos_formatados,
        'credor' => [
            'razao_social' => normalizarCampoContrato($credorRazaoSocial, '[NÃO INFORMADO]'),
            'documento' => normalizarCampoContrato($credorDocumento, '[NÃO INFORMADO]'),
            'representante' => [
                'nome' => normalizarCampoContrato($config['empresa_representante_nome'] ?? '', '[NÃO INFORMADO]'),
                'nacionalidade' => 'brasileiro(a)',
                'estado_civil' => 'casado(a)',
                'rg' => '-',
                'cpf' => normalizarCampoContrato($config['empresa_representante_cpf'] ?? '', '[NÃO INFORMADO]')
            ],
            'conta' => [
                'banco' => normalizarCampoContrato($config['conta_banco'] ?? '', '[NÃO INFORMADO]'),
                'agencia' => normalizarCampoContrato($config['conta_agencia'] ?? '', '[NÃO INFORMADO]'),
                'numero' => normalizarCampoContrato($config['conta_numero'] ?? '', '[NÃO INFORMADO]'),
                'tipo' => normalizarCampoContrato($config['conta_tipo'] ?? '', '[NÃO INFORMADO]'),
                'pix' => normalizarCampoContrato($config['conta_pix'] ?? '', '[NÃO INFORMADO]'),
                'titular' => normalizarCampoContrato($credorContaTitular, '[NÃO INFORMADO]'),
                'documento' => normalizarCampoContrato($credorContaDocumento, '[NÃO INFORMADO]')
            ],
            'endereco_completo' => normalizarCampoContrato($config['empresa_endereco'] ?? '', '[NÃO INFORMADO]'),
            'email' => normalizarCampoContrato($config['empresa_email'] ?? '', 'Não informado'),
            'whatsapp' => normalizarCampoContrato($config['empresa_whatsapp'] ?? '', 'Não informado')
        ],
        'cedente' => $parteContrato,
        'contrato_mae' => [
            'id' => $operacao_id,
            'local' => 'Piracicaba/SP',
            'data_extenso' => $data_atual
        ],
        'bordero' => [
            'id' => $operacao_id,
            'local' => 'Piracicaba/SP',
            'data_extenso' => $data_atual,
            'total_face' => number_format($total_face_bordero, 2, ',', '.'),
            'total_juros' => number_format($total_juros_bordero, 2, ',', '.'),
            'total_liquido' => number_format($total_liquido_bordero, 2, ',', '.'),
            'valor_liquido' => number_format($total_liquido_bordero, 2, ',', '.'),
            'total_titulos' => count($titulos),
            'taxa_desagio' => number_format($taxa_mensal * 100, 2, ',', '.'),
            'total_desagio' => number_format($total_desagio_bordero, 2, ',', '.'),
            'prazo_medio' => number_format($operacao['media_dias_pond_calc'] ?? 0, 0, '', ''),
            'tarifas' => number_format($tarifas_operacionais, 2, ',', '.'),
            'valor_liquido_extenso' => $extenso->converter($total_liquido_bordero),
            'forma_pagamento' => 'Transferência Bancária (PIX)'
        ],
        'devedor' => $parteContrato,
        'tem_avalista' => $tem_avalista,
        'avalista' => $tem_avalista ? [
            'nome' => normalizarCampoContrato($avalista_nome, '[NÃO INFORMADO]'),
            'nacionalidade' => normalizarCampoContrato($avalista_nacionalidade, '[NÃO INFORMADO]'),
            'estado_civil' => normalizarCampoContrato($avalista_estado_civil, '[NÃO INFORMADO]'),
            'profissao' => normalizarCampoContrato($avalista_profissao, '[NÃO INFORMADO]'),
            'rg' => normalizarCampoContrato($avalista_rg, '[NÃO INFORMADO]'),
            'cpf' => normalizarCampoContrato($avalista_cpf, '[NÃO INFORMADO]'),
            'endereco_completo' => normalizarCampoContrato($avalista_endereco, '[NÃO INFORMADO]'),
            'email' => normalizarCampoContrato($avalista_email ?? '', 'Não informado'),
            'whatsapp' => normalizarCampoContrato($avalista_whatsapp ?? '', 'Não informado'),
            'casado' => in_array($avalista_estado_civil, ['Casado(a)', 'União Estável']),
            'regime_casamento' => normalizarCampoContrato($avalista_regime_casamento, '[NÃO INFORMADO]'),
            'conjuge' => [
                'nome' => normalizarCampoContrato($avalista_conjuge_nome, '[NÃO INFORMADO]'),
                'cpf' => normalizarCampoContrato($avalista_conjuge_cpf, '[NÃO INFORMADO]')
            ]
        ] : null,
        'operacao' => [
            'id' => $operacao_id,
            'local' => 'Piracicaba/SP',
            'data_extenso' => $data_atual,
            'valor_principal' => number_format($total_liquido_pago_calc, 2, ',', '.'),
            'valor_principal_extenso' => $extenso->converter($total_liquido_pago_calc),
            'forma_liberacao' => 'Transferência Bancária (PIX)',
            'valor_total_devido' => number_format($total_face_bordero, 2, ',', '.'),
            'valor_total_devido_extenso' => $extenso->converter($total_face_bordero),
            'num_parcelas' => $num_parcelas_count,
            'num_parcelas_extenso' => $extenso->converter($num_parcelas_count),
            'periodicidade' => $periodicidade,
            'valor_parcela' => $valor_parcela_texto,
            'valor_parcela_extenso' => $valor_parcela_extenso_texto,
            'data_primeiro_vencimento' => $data_primeiro_vencimento ?? date('d/m/Y'),
            'forma_pagamento' => 'Transferência Bancária (PIX)',
            'taxa_juros_mensal' => number_format($taxa_mensal * 100, 2, ',', '.'),
            'taxa_juros_mensal_extenso' => $extenso->converter($taxa_mensal * 100),
            'taxa_juros_anual' => number_format((pow(1 + $taxa_mensal, 12) - 1) * 100, 2, ',', '.'),
            'taxa_juros_anual_extenso' => $extenso->converter((pow(1 + $taxa_mensal, 12) - 1) * 100),
            'cet' => number_format($taxa_mensal * 100 + 0.1, 2, ',', '.'),
            'taxa_juros_atraso' => number_format($config['taxa_juros_atraso'] ?? 1.00, 2, ',', '.'),
            'taxa_juros_atraso_extenso' => $extenso->converter($config['taxa_juros_atraso'] ?? 1.00),
            'taxa_multa_atraso' => number_format($config['taxa_multa_atraso'] ?? 2.00, 2, ',', '.'),
            'taxa_multa_atraso_extenso' => $extenso->converter($config['taxa_multa_atraso'] ?? 2.00),
            'total_juros' => number_format($total_face_bordero - $total_liquido_pago_calc, 2, ',', '.'),
            'sistema_amortizacao' => $num_parcelas_count === 1 ? 'Pagamento Único no Vencimento' : 'Pagamento Variável',
            'num_vias' => '2',
            'num_vias_extenso' => 'duas'
        ] + $resumoFinanceiroAntecipacao,
        'veiculo' => [
            'marca' => $veiculo_marca,
            'modelo' => $veiculo_modelo,
            'ano_fab' => $veiculo_ano_fab,
            'ano_mod' => $veiculo_ano_mod,
            'cor' => $veiculo_cor,
            'placa' => $veiculo_placa,
            'renavam' => $veiculo_renavam,
            'chassi' => $veiculo_chassi,
            'municipio_emplacamento' => $veiculo_municipio_registro,
            'uf' => $veiculo_uf,
            'municipio_uf_emplacamento' => trim($veiculo_municipio_registro) !== '' && trim($veiculo_uf) !== ''
                ? trim($veiculo_municipio_registro) . '/' . trim($veiculo_uf)
                : (trim($veiculo_municipio_registro) ?: trim($veiculo_uf)),
            'valor_avaliacao' => number_format($veiculo_valor_avaliacao, 2, ',', '.'),
            'valor_avaliacao_extenso' => $extenso->converter($veiculo_valor_avaliacao)
        ],
        'bem' => [
            'tipo' => $bem_tipo,
            'descricao_detalhada' => $bem_descricao_detalhada,
            'identificadores' => $bem_identificadores,
            'local_guarda' => $bem_local_guarda,
            'documentos_origem' => $bem_documentos_origem,
            'valor_avaliacao' => number_format($bem_valor_avaliacao, 2, ',', '.'),
            'valor_avaliacao_extenso' => $extenso->converter($bem_valor_avaliacao)
        ],
        'testemunhas' => [
            ['nome' => '________________________________________________', 'cpf' => '_______________________'],
            ['nome' => '________________________________________________', 'cpf' => '_______________________']
        ],
        'cronograma' => $cronograma,
        'np' => [
            'numero' => '01/01',
            'vencimento' => $data_vencimento_ultima_parcela,
            'data_vencimento_extenso' => $data_vencimento_extenso
        ]
    ];

    // Determinar o template a usar
    if ($natureza === 'EMPRESTIMO') {
        if ($tem_bem_movel && $tem_avalista) {
            $templatePath = '_contratos/02f_template_mutuo_com_garantia_bem_e_aval.md';
        } elseif ($tem_bem_movel && !$tem_avalista) {
            $templatePath = '_contratos/02e_template_mutuo_com_garantia_bem.md';
        } elseif ($tem_veiculo && $tem_avalista) {
            $templatePath = '_contratos/02d_template_mutuo_com_garantia_e_aval.md';
        } elseif (!$tem_veiculo && !$tem_avalista) {
            $templatePath = '_contratos/02a_template_mutuo_simples.md';
        } elseif ($tem_veiculo && !$tem_avalista) {
            $templatePath = '_contratos/02c_template_mutuo_com_garantia.md';
        } else {
            $templatePath = '_contratos/02b_template_mutuo_com_aval.md';
        }

        if (!file_exists($templatePath)) {
            $templatePath = '_contratos/template.md';
        }
    } else {
        $templatePath = file_exists('_contratos/01_template_antecipacao_recebiveis.md')
            ? '_contratos/01_template_antecipacao_recebiveis.md'
            : '_contratos/template.md';
    }
    
    if (!file_exists($templatePath)) {
        throw new Exception("Template não encontrado: " . $templatePath);
    }

    $markdownTemplate = file_get_contents($templatePath);
    $markdownTemplate = preg_replace('/^.*?\n---\n+/s', '', $markdownTemplate, 1);

    if ($natureza === 'EMPRESTIMO') {
        $npTemplatePath = '_contratos/03_template_nota_promissoria.md';
        if (file_exists($npTemplatePath)) {
            $npTemplate = file_get_contents($npTemplatePath);
            $npTemplate = preg_replace('/^.*?\n---\n+/s', '', $npTemplate, 1);
            $markdownTemplate .= "\n\n<div style=\"page-break-before: always;\"></div>\n\n" . $npTemplate;
        }
    }

    // Render with Mustache
    $m = new Mustache_Engine;
    $markdownContent = $m->render($markdownTemplate, $data);

    // Parse Markdown to HTML
    $parsedown = new Parsedown();
    $htmlContent = $parsedown->text($markdownContent);

    // Add styling
    $html = "
    <style>
        body { font-family: 'Times New Roman', serif; line-height: 1.5; font-size: 14px; text-align: justify; }
        h1, h2, h3 { text-align: center; font-weight: bold; margin-top: 20px; }
        p { margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #000; padding: 5px; text-align: center; }
        
        /* Estilos específicos da Nota Promissória */
        .np-header { 
            text-align: center; 
            font-size: 20pt; 
            font-weight: bold; 
            letter-spacing: 3px; 
            margin-bottom: 20px; 
            border-bottom: 2px solid #000; 
            padding-bottom: 10px; 
        }
        
        .np-valor { 
            text-align: center; 
            font-size: 16pt; 
            font-weight: bold; 
            margin: 20px 0; 
            padding: 10px; 
            border: 2px solid #000; 
        }
        
        .np-texto { 
            text-align: justify; 
            text-indent: 2cm; 
            margin: 15px 0; 
        }
        
        .np-assinatura { 
            margin-top: 60px; 
            text-align: center; 
        }
        
        .np-linha-assinatura {
            border-top: 1px solid #000;
            width: 70%;
            margin: 0 auto 5px auto;
        }

        /* Bloco de assinatura — não pode quebrar entre páginas */
        .signature-block {
            page-break-inside: avoid;
            margin: 30px 0 12px 0;
            text-align: center;
        }
        .signature-block .sig-role {
            font-weight: bold;
            text-align: center;
            margin: 0;
            text-transform: uppercase;
        }
        .signature-block .sig-space {
            height: 60px;
        }
        .signature-block .sig-line {
            border-top: 1px solid #000;
            width: 70%;
            margin: 0 auto 6px auto;
        }
        .signature-block .sig-name {
            text-align: center;
            margin: 0;
            line-height: 1.4;
        }
    </style>
    " . $htmlContent;

    // Ensure mpdf temp directory exists
    $mpdfTempDir = __DIR__ . '/uploads/mpdf_temp';
    if (!file_exists($mpdfTempDir)) {
        mkdir($mpdfTempDir, 0777, true);
    }

    // Generate PDF with mPDF
    $mpdf = new \Mpdf\Mpdf([
        'tempDir' => $mpdfTempDir,
        'margin_left' => 20,
        'margin_right' => 20,
        'margin_top' => 20,
        'margin_bottom' => 20,
    ]);
    
    // Configurar rodapé com numeração de páginas
    $mpdf->SetFooter($credorRazaoSocial . '||Página {PAGENO} de {nbpg}');
    
    $mpdf->WriteHTML($html);
    
    // Save to disk
    $filename = $operacao_id . '_' . $natureza . '_' . date('Ymd_His') . '.pdf';
    
    // Diretorio especifico da operacao
    $uploadFileDir = 'uploads/contratos/' . $operacao_id . '/';
    if (!file_exists($uploadFileDir)) {
        mkdir($uploadFileDir, 0777, true);
    }
    
    $filepath = $uploadFileDir . $filename;
    $mpdf->Output(__DIR__ . '/' . $filepath, \Mpdf\Output\Destination::FILE);

    // Update Database
    $pdo->beginTransaction();
    try {
        // Atualizar operacao com dados
        $stmtUpdateOp = $pdo->prepare("UPDATE operacoes SET natureza = ?, status_contrato = 'aguardando_assinatura' WHERE id = ?");
        $stmtUpdateOp->execute([$natureza, $operacao_id]);
        
        // Atualizar porte do devedor (Cedente ou Sacado) se foi alterado
        if (!empty($porte_cliente)) {
            if (($operacao['tipo_operacao'] ?? 'antecipacao') === 'emprestimo') {
                if (!empty($operacao['sacado_id'])) {
                    $stmtUpdateSac = $pdo->prepare("UPDATE clientes SET porte = ? WHERE id = ?");
                    $stmtUpdateSac->execute([$porte_cliente, $operacao['sacado_id']]);
                }
            } else {
                if (!empty($operacao['cedente_id'])) {
                    $stmtUpdateCed = $pdo->prepare("UPDATE clientes SET porte = ? WHERE id = ?");
                    $stmtUpdateCed->execute([$porte_cliente, $operacao['cedente_id']]);
                }
            }
        }

        // Limpar registros antigos
        $stmtDelAv = $pdo->prepare("DELETE FROM operation_guarantors WHERE operation_id = ?");
        $stmtDelAv->execute([$operacao_id]);
        
        $stmtDelVe = $pdo->prepare("DELETE FROM operation_vehicles WHERE operation_id = ?");
        $stmtDelVe->execute([$operacao_id]);

        // Save Veiculo
        if ($tem_veiculo) {
            $stmt = $pdo->prepare("
                INSERT INTO operation_vehicles (
                    operation_id, marca, modelo, ano_fab, ano_mod, cor, placa, renavam, valor_avaliacao, chassi, municipio_emplacamento, uf
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $operacao_id, $veiculo_marca, $veiculo_modelo, $veiculo_ano_fab, $veiculo_ano_mod,
                $veiculo_cor, $veiculo_placa, $veiculo_renavam, $veiculo_valor_avaliacao, $veiculo_chassi, $veiculo_municipio_registro, $veiculo_uf
            ]);
        }
            
        // Save Avalista
        if ($tem_avalista) {
            $stmt = $pdo->prepare("
                INSERT INTO operation_guarantors (
                    operation_id, nome, cpf, rg, nacionalidade, estado_civil, profissao, endereco,
                    casado, regime_casamento, conjuge_nome, conjuge_cpf, tipo, email, whatsapp
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'AVALISTA', ?, ?)
            ");
            $stmt->execute([
                $operacao_id, $avalista_nome, $avalista_cpf, $avalista_rg, $avalista_nacionalidade,
                $avalista_estado_civil, $avalista_profissao, $avalista_endereco,
                in_array($avalista_estado_civil, ['Casado(a)', 'União Estável']) ? 1 : 0,
                $avalista_regime_casamento, $avalista_conjuge_nome, $avalista_conjuge_cpf,
                $avalista_email, $avalista_whatsapp
            ]);
        }

        $stmtInsert = $pdo->prepare("INSERT INTO operacao_documentos (operacao_id, nome_arquivo, caminho_arquivo, is_assinado) VALUES (?, ?, ?, 0)");
        $stmtInsert->execute([$operacao_id, $filename, $filepath]);
        
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Contrato gerado com sucesso.',
        'file' => $filepath
    ]);
}

function uploadContrato($pdo, $operacao_id) {
    if (!isset($_FILES['contrato_assinado']) || $_FILES['contrato_assinado']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Erro no upload do arquivo.");
    }

    $fileTmpPath = $_FILES['contrato_assinado']['tmp_name'];
    $fileName = $_FILES['contrato_assinado']['name'];
    $fileSize = $_FILES['contrato_assinado']['size'];
    $fileType = $_FILES['contrato_assinado']['type'];
    
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if ($fileExtension !== 'pdf') {
        throw new Exception("Apenas arquivos PDF são permitidos.");
    }

    $newFileName = 'contrato_assinado_' . $operacao_id . '_' . time() . '.pdf';
    $uploadFileDir = 'uploads/contratos_assinados/';
    if (!file_exists($uploadFileDir)) {
        mkdir($uploadFileDir, 0777, true);
    }
    
    $dest_path = $uploadFileDir . $newFileName;

    if(move_uploaded_file($fileTmpPath, __DIR__ . '/' . $dest_path)) {
        $pdo->beginTransaction();
        try {
            $stmtUpdate = $pdo->prepare("UPDATE operacoes SET status_contrato = 'assinado' WHERE id = ?");
            $stmtUpdate->execute([$operacao_id]);

            $stmtInsert = $pdo->prepare("INSERT INTO operacao_documentos (operacao_id, nome_arquivo, caminho_arquivo, is_assinado) VALUES (?, ?, ?, 1)");
            $stmtInsert->execute([$operacao_id, $newFileName, $dest_path]);
            
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }

        echo json_encode([
            'success' => true,
            'message' => 'Contrato assinado enviado com sucesso.',
            'file' => $dest_path
        ]);
    } else {
        throw new Exception("Ocorreu um erro ao mover o arquivo enviado.");
    }
}

function deleteContrato($pdo, $operacao_id) {
    $documento_id = $_POST['documento_id'] ?? null;

    if (!$documento_id) {
        throw new Exception("ID do documento não fornecido.");
    }

    // Buscar o arquivo no banco de dados para obter o caminho
    $stmt = $pdo->prepare("SELECT caminho_arquivo FROM operacao_documentos WHERE id = ? AND operacao_id = ?");
    $stmt->execute([$documento_id, $operacao_id]);
    $caminho = $stmt->fetchColumn();

    if (!$caminho) {
        throw new Exception("Documento não encontrado na operação.");
    }

    $pdo->beginTransaction();
    try {
        // Excluir o registro da tabela
        $stmtDelete = $pdo->prepare("DELETE FROM operacao_documentos WHERE id = ?");
        $stmtDelete->execute([$documento_id]);

        // Apagar o arquivo físico se existir
        $caminhoCompleto = __DIR__ . '/' . $caminho;
        if (file_exists($caminhoCompleto)) {
            unlink($caminhoCompleto);
        }

        // Recalcular o status do contrato na operação
        // Checar quais documentos restaram
        $stmtRestantes = $pdo->prepare("SELECT is_assinado FROM operacao_documentos WHERE operacao_id = ?");
        $stmtRestantes->execute([$operacao_id]);
        $documentos_restantes = $stmtRestantes->fetchAll(PDO::FETCH_ASSOC);

        $novo_status = 'pendente'; // Padrão se não houver mais nenhum
        
        if (!empty($documentos_restantes)) {
            $tem_assinado = false;
            foreach ($documentos_restantes as $doc) {
                if ($doc['is_assinado'] == 1) {
                    $tem_assinado = true;
                    break;
                }
            }
            
            if ($tem_assinado) {
                $novo_status = 'assinado';
            } else {
                $novo_status = 'aguardando_assinatura';
            }
        }

        $stmtUpdateStatus = $pdo->prepare("UPDATE operacoes SET status_contrato = ? WHERE id = ?");
        $stmtUpdateStatus->execute([$novo_status, $operacao_id]);

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Contrato apagado com sucesso.',
            'novo_status' => $novo_status
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}
