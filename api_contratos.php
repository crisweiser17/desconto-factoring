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

function gerarContrato($pdo, $operacao_id) {
    // Pegar dados do POST
    $natureza = $_POST['natureza'] ?? '';
    $porte_cliente = $_POST['porte_cliente'] ?? '';
    $tem_garantia = $_POST['tem_garantia'] ?? 'sem_veiculo_sem_avalista';
    
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

    // Fetch operacao details
    $stmt = $pdo->prepare("SELECT * FROM operacoes WHERE id = ?");
    $stmt->execute([$operacao_id]);
    $operacao = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$operacao) {
        throw new Exception("Operação não encontrada.");
    }

    // Identificar de onde vem os dados do Devedor/Cedente (Cedente para Antecipação, Sacado para Empréstimo)
    if (($operacao['tipo_operacao'] ?? 'antecipacao') === 'emprestimo') {
        // Para Empréstimo, o Devedor é o Sacado
        $stmtSacado = $pdo->prepare("
            SELECT s.id as sacado_id, s.nome as cedente_nome, s.documento_principal as cedente_documento_principal, s.cpf as cedente_cpf, s.cnpj as cedente_cnpj, s.endereco as cedente_endereco, 
                   s.cidade as cedente_cidade, s.estado as cedente_estado, s.tipo_pessoa, s.possui_cnpj_mei, s.empresa,
                   s.representante_nome, s.representante_cpf, s.representante_rg, s.representante_estado_civil, s.representante_profissao, s.representante_nacionalidade, s.representante_endereco,
                   s.casado, s.conjuge_nome, s.conjuge_cpf, s.conta_banco, s.conta_agencia, s.conta_numero, s.conta_tipo, s.conta_pix, s.email, s.whatsapp
            FROM recebiveis r
            JOIN sacados s ON r.sacado_id = s.id
            WHERE r.operacao_id = ?
            LIMIT 1
        ");
        $stmtSacado->execute([$operacao_id]);
        $sacado = $stmtSacado->fetch(PDO::FETCH_ASSOC);
        if ($sacado) {
            $operacao = array_merge($operacao, $sacado);
        }
    } else {
        // Para Antecipação, o Devedor/Cedente é o Cedente
        $stmtCedente = $pdo->prepare("
            SELECT c.nome as cedente_nome, c.documento_principal as cedente_documento_principal, c.cpf as cedente_cpf, c.cnpj as cedente_cnpj, c.endereco as cedente_endereco, 
                   c.cidade as cedente_cidade, c.estado as cedente_estado, c.tipo_pessoa, c.possui_cnpj_mei, c.empresa,
                   c.representante_nome, c.representante_cpf, c.representante_rg, c.representante_estado_civil, c.representante_profissao, c.representante_nacionalidade, c.representante_endereco,
                   c.casado, c.conjuge_nome, c.conjuge_cpf, c.conta_banco, c.conta_agencia, c.conta_numero, c.conta_tipo, c.conta_pix, c.email, c.whatsapp
            FROM cedentes c
            WHERE c.id = ?
        ");
        $stmtCedente->execute([$operacao['cedente_id']]);
        $cedente = $stmtCedente->fetch(PDO::FETCH_ASSOC);
        if ($cedente) {
            $operacao = array_merge($operacao, $cedente);
        }
    }
    
    // Regra 2: Validação de Tomador
    if ($natureza === 'EMPRESTIMO') {
        if ($operacao['tipo_pessoa'] === 'PF' && empty($operacao['possui_cnpj_mei'])) {
            throw new Exception("A ACM ESC não pode realizar operações de mútuo com pessoa física sem CNPJ. Oriente o cliente a abrir MEI ou converta esta operação para Desconto de Título.");
        }
        if (!in_array($porte_cliente, ['MEI', 'ME', 'EPP'])) {
            throw new Exception("LC 167/2019 restringe operações da ESC a MEI, Microempresas e Empresas de Pequeno Porte.");
        }
    } else if ($natureza === 'DESCONTO') {
        // Validar títulos (sacado distinto do cedente)
        $stmtTitulos = $pdo->prepare("SELECT COUNT(*) FROM recebiveis WHERE operacao_id = ? AND sacado_id != (SELECT id FROM sacados WHERE empresa = ? OR empresa = ? LIMIT 1)");
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
    $total_presente_calc = (float)($operacao['total_presente_calc'] ?? 0);
    $taxa_mensal = (float)($operacao['taxa_mensal'] ?? 0);
    $veiculo_valor_avaliacao = (float)($veiculo_valor_avaliacao ?? 0);

    // Carregar recebíveis da operação
    $stmtTitulosList = $pdo->prepare("
        SELECT r.*, s.nome as sacado_nome, s.documento_principal as sacado_documento 
        FROM recebiveis r 
        LEFT JOIN sacados s ON r.sacado_id = s.id 
        WHERE r.operacao_id = ?
    ");
    $stmtTitulosList->execute([$operacao_id]);
    $titulos = $stmtTitulosList->fetchAll(PDO::FETCH_ASSOC);

    // Formatar títulos
    $titulos_formatados = [];
    $total_face_bordero = 0;
    $total_juros_bordero = 0;
    $total_liquido_bordero = 0;
    
    $ordem = 1;
    foreach ($titulos as $t) {
        $valor_face = (float)$t['valor_face'];
        $valor_liquido = (float)$t['valor_liquido'];
        $juros = $valor_face - $valor_liquido;

        $total_face_bordero += $valor_face;
        $total_juros_bordero += $juros;
        $total_liquido_bordero += $valor_liquido;

        $titulos_formatados[] = [
            'ordem' => str_pad($ordem++, 2, '0', STR_PAD_LEFT),
            'numero' => $t['numero_documento'] ?: $t['id'],
            'tipo' => $t['tipo_recebivel'] ?? 'DUPLICATA',
            'sacado_nome' => $t['sacado_nome'],
            'sacado_documento' => $t['sacado_documento'],
            'data_emissao' => !empty($t['data_emissao']) ? date('d/m/Y', strtotime($t['data_emissao'])) : date('d/m/Y'),
            'data_vencimento' => date('d/m/Y', strtotime($t['data_vencimento'])),
            'valor_face' => number_format($valor_face, 2, ',', '.'),
            'valor_liquido' => number_format($valor_liquido, 2, ',', '.'),
            'valor_presente' => number_format($valor_liquido, 2, ',', '.'),
            'juros' => number_format($juros, 2, ',', '.')
        ];
    }

    // Carregar configuração
    $config_file = __DIR__ . '/config.json';
    $config = file_exists($config_file) ? json_decode(file_get_contents($config_file), true) : [];

    // Preparar Data para Mustache
    $data = [
        'titulos' => $titulos_formatados,
        'credor' => [
            'representante' => [
                'nome' => 'Cris Weiser', // Placeholder, ideal buscar da conf da ESC
                'nacionalidade' => 'brasileiro(a)',
                'estado_civil' => 'casado(a)',
                'rg' => '12.345.678-9',
                'cpf' => '123.456.789-00'
            ],
            'conta' => [
                'banco' => $config['conta_banco'] ?? '',
                'agencia' => $config['conta_agencia'] ?? '',
                'numero' => $config['conta_numero'] ?? '',
                'tipo' => $config['conta_tipo'] ?? '',
                'pix' => $config['conta_pix'] ?? '',
                'titular' => $config['conta_titular'] ?? '',
                'documento' => $config['conta_documento'] ?? ''
            ],
            'email' => 'contato@acmempresa.com.br',
            'whatsapp' => '(19) 99999-9999'
        ],
        'cedente' => [
            'pessoa_juridica' => in_array($operacao['tipo_pessoa'], ['PJ', 'JURIDICA']),
            'razao_social' => !empty($operacao['empresa']) ? $operacao['empresa'] : ($operacao['cedente_nome'] ?? ''),
            'descricao_juridica' => 'pessoa jurídica de direito privado',
            'cnpj' => !empty($operacao['cedente_cnpj']) ? $operacao['cedente_cnpj'] : (!empty($operacao['cedente_cpf']) ? $operacao['cedente_cpf'] : ($operacao['cedente_documento_principal'] ?? '')),
            'nome_completo' => $operacao['cedente_nome'] ?? '',
            'nacionalidade' => 'brasileiro(a)',
            'estado_civil' => 'Solteiro(a)',
            'profissao' => 'Empresário',
            'rg' => '-',
            'cpf' => !empty($operacao['cedente_cpf']) ? $operacao['cedente_cpf'] : (!empty($operacao['cedente_cnpj']) ? $operacao['cedente_cnpj'] : ($operacao['cedente_documento_principal'] ?? '')),
            'endereco_completo' => ($operacao['cedente_endereco'] ?? '') . ', ' . ($operacao['cedente_cidade'] ?? '') . ' - ' . ($operacao['cedente_estado'] ?? ''),
            'email' => $operacao['email'] ?? '',
            'whatsapp' => $operacao['whatsapp'] ?? '',
            'casado' => !empty($operacao['casado']) && $operacao['casado'] == 1,
            'conjuge' => [
                'nome' => $operacao['conjuge_nome'] ?? '',
                'cpf' => $operacao['conjuge_cpf'] ?? ''
            ],
            'conta' => [
                'banco' => $operacao['conta_banco'] ?? '',
                'agencia' => $operacao['conta_agencia'] ?? '',
                'numero' => $operacao['conta_numero'] ?? '',
                'tipo' => $operacao['conta_tipo'] ?? '',
                'pix' => $operacao['conta_pix'] ?? ''
            ],
            'representante' => [
                'nome' => !empty($operacao['representante_nome']) ? $operacao['representante_nome'] : ($operacao['cedente_nome'] ?? ''),
                'nacionalidade' => !empty($operacao['representante_nacionalidade']) ? $operacao['representante_nacionalidade'] : 'brasileiro(a)',
                'estado_civil' => !empty($operacao['representante_estado_civil']) ? $operacao['representante_estado_civil'] : 'Solteiro(a)',
                'profissao' => !empty($operacao['representante_profissao']) ? $operacao['representante_profissao'] : 'Empresário',
                'rg' => !empty($operacao['representante_rg']) ? $operacao['representante_rg'] : '-',
                'cpf' => !empty($operacao['representante_cpf']) ? $operacao['representante_cpf'] : ($operacao['cedente_cpf'] ?? ''),
                'endereco' => !empty($operacao['representante_endereco']) ? $operacao['representante_endereco'] : ($operacao['cedente_endereco'] ?? '')
            ]
        ],
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
            'total_titulos' => count($titulos),
            'taxa_desagio' => number_format($taxa_mensal * 100, 2, ',', '.'),
            'total_desagio' => number_format($total_original_calc - $total_presente_calc, 2, ',', '.'),
            'prazo_medio' => number_format($operacao['media_dias_pond_calc'] ?? 0, 0, '', ''),
            'tarifas' => number_format(($operacao['iof_total_calc'] ?? 0) + ($operacao['tarifas'] ?? 0), 2, ',', '.'),
            'valor_liquido_extenso' => $extenso->converter($total_liquido_pago_calc),
            'forma_pagamento' => 'Transferência Bancária (PIX)'
        ],
        'devedor' => [
            'razao_social' => !empty($operacao['empresa']) ? $operacao['empresa'] : ($operacao['cedente_nome'] ?? ''),
            'descricao_juridica' => 'pessoa jurídica de direito privado',
            'cnpj' => !empty($operacao['cedente_cnpj']) ? $operacao['cedente_cnpj'] : (!empty($operacao['cedente_cpf']) ? $operacao['cedente_cpf'] : ($operacao['cedente_documento_principal'] ?? '')),
            'porte' => $porte_cliente,
            'endereco_completo' => ($operacao['cedente_endereco'] ?? '') . ', ' . ($operacao['cedente_cidade'] ?? '') . ' - ' . ($operacao['cedente_estado'] ?? ''),
            'email' => $operacao['email'] ?? '',
            'whatsapp' => $operacao['whatsapp'] ?? '',
            'casado' => !empty($operacao['casado']) && $operacao['casado'] == 1,
            'conjuge' => [
                'nome' => $operacao['conjuge_nome'] ?? '',
                'cpf' => $operacao['conjuge_cpf'] ?? ''
            ],
            'conta' => [
                'banco' => $operacao['conta_banco'] ?? '',
                'agencia' => $operacao['conta_agencia'] ?? '',
                'numero' => $operacao['conta_numero'] ?? '',
                'tipo' => $operacao['conta_tipo'] ?? '',
                'pix' => $operacao['conta_pix'] ?? ''
            ],
            'representante' => [
                'nome' => !empty($operacao['representante_nome']) ? $operacao['representante_nome'] : ($operacao['cedente_nome'] ?? ''),
                'nacionalidade' => !empty($operacao['representante_nacionalidade']) ? $operacao['representante_nacionalidade'] : 'brasileiro(a)',
                'estado_civil' => !empty($operacao['representante_estado_civil']) ? $operacao['representante_estado_civil'] : 'Solteiro(a)',
                'profissao' => !empty($operacao['representante_profissao']) ? $operacao['representante_profissao'] : 'Empresário',
                'rg' => !empty($operacao['representante_rg']) ? $operacao['representante_rg'] : '-',
                'cpf' => !empty($operacao['representante_cpf']) ? $operacao['representante_cpf'] : ($operacao['cedente_cpf'] ?? ''),
                'endereco' => !empty($operacao['representante_endereco']) ? $operacao['representante_endereco'] : ($operacao['cedente_endereco'] ?? '')
            ]
        ],
        'avalista' => [
            'nome' => $avalista_nome,
            'nacionalidade' => $avalista_nacionalidade,
            'estado_civil' => $avalista_estado_civil,
            'profissao' => $avalista_profissao,
            'rg' => $avalista_rg,
            'cpf' => $avalista_cpf,
            'endereco_completo' => $avalista_endereco,
            'email' => $avalista_email,
            'whatsapp' => $avalista_whatsapp,
            'casado' => in_array($avalista_estado_civil, ['Casado(a)', 'União Estável']),
            'regime_casamento' => $avalista_regime_casamento,
            'conjuge' => [
                'nome' => $avalista_conjuge_nome,
                'cpf' => $avalista_conjuge_cpf
            ]
        ],
        'operacao' => [
            'id' => $operacao_id,
            'local' => 'Piracicaba/SP',
            'data_extenso' => $data_atual,
            'valor_principal' => number_format($total_liquido_pago_calc, 2, ',', '.'),
            'valor_principal_extenso' => $extenso->converter($total_liquido_pago_calc),
            'forma_liberacao' => 'Transferência Bancária (PIX)',
            'comprovante_liberacao' => '',
            'valor_total_devido' => number_format($total_original_calc, 2, ',', '.'),
            'valor_total_devido_extenso' => $extenso->converter($total_original_calc),
            'num_parcelas' => '1',
            'num_parcelas_extenso' => 'uma',
            'periodicidade' => 'única',
            'valor_parcela' => number_format($total_original_calc, 2, ',', '.'),
            'valor_parcela_extenso' => $extenso->converter($total_original_calc),
            'data_primeiro_vencimento' => date('d/m/Y', strtotime('+30 days')), // Simples placeholder
            'forma_pagamento' => 'Transferência Bancária (PIX)',
            'taxa_juros_mensal' => number_format($taxa_mensal * 100, 2, ',', '.'),
            'taxa_juros_mensal_extenso' => $extenso->converter($taxa_mensal * 100),
            'taxa_juros_anual' => number_format((pow(1 + $taxa_mensal, 12) - 1) * 100, 2, ',', '.'),
            'taxa_juros_anual_extenso' => $extenso->converter((pow(1 + $taxa_mensal, 12) - 1) * 100),
            'cet' => number_format($taxa_mensal * 100 + 0.1, 2, ',', '.'),
            'total_juros' => number_format($total_original_calc - $total_liquido_pago_calc, 2, ',', '.'),
            'sistema_amortizacao' => 'Pagamento Único no Vencimento',
            'num_vias' => '2',
            'num_vias_extenso' => 'duas'
        ],
        'veiculo' => [
            'marca' => $veiculo_marca,
            'modelo' => $veiculo_modelo,
            'ano_fab' => $veiculo_ano_fab,
            'ano_mod' => $veiculo_ano_mod,
            'cor' => $veiculo_cor,
            'combustivel' => 'Flex',
            'placa' => $veiculo_placa,
            'renavam' => $veiculo_renavam,
            'chassi' => $veiculo_chassi,
            'municipio_registro' => $veiculo_municipio_registro,
            'uf' => $veiculo_uf,
            'valor_avaliacao' => number_format($veiculo_valor_avaliacao, 2, ',', '.'),
            'valor_avaliacao_extenso' => $extenso->converter($veiculo_valor_avaliacao)
        ],
        'testemunhas' => [
            ['nome' => 'Testemunha 1', 'cpf' => '000.000.000-00'],
            ['nome' => 'Testemunha 2', 'cpf' => '111.111.111-11']
        ],
        'cronograma' => [
            [
                'numero' => 1,
                'data_vencimento' => date('d/m/Y', strtotime('+30 days')),
                'valor_parcela' => number_format($total_original_calc, 2, ',', '.'),
                'valor_amortizacao' => number_format($total_liquido_pago_calc, 2, ',', '.'),
                'valor_juros' => number_format($total_original_calc - $total_liquido_pago_calc, 2, ',', '.'),
                'saldo_devedor' => '0,00'
            ]
        ]
    ];

    // Determinar o template a usar
    $tem_veiculo = ($tem_garantia === 'com_veiculo_com_avalista' || $tem_garantia === 'com_veiculo_sem_avalista');
    $tem_avalista = ($tem_garantia === 'com_veiculo_com_avalista' || $tem_garantia === 'sem_veiculo_com_avalista');

    if ($natureza === 'EMPRESTIMO') {
        $templatePath = '_contratos/02_template_contrato_mutuo.md';
    } else {
        if ($tem_veiculo && $tem_avalista) {
            $templatePath = '_contratos/contrato_1_com_veiculo_com_avalista.md';
        } elseif (!$tem_veiculo && !$tem_avalista) {
            $templatePath = '_contratos/contrato_2_sem_veiculo_sem_avalista.md';
        } elseif ($tem_veiculo && !$tem_avalista) {
            $templatePath = '_contratos/contrato_3_com_veiculo_sem_avalista.md';
        } else { // !$tem_veiculo && $tem_avalista
            $templatePath = '_contratos/contrato_4_sem_veiculo_com_avalista.md';
        }

        // Fallback legado caso os novos não existam
        if (!file_exists($templatePath)) {
            if (file_exists('_contratos/03_template_cessao_bordero.md')) {
                $templatePath = '_contratos/03_template_cessao_bordero.md';
            } else {
                $templatePath = '_contratos/template.md';
            }
        }
    }
    
    if (!file_exists($templatePath)) {
        throw new Exception("Template não encontrado: " . $templatePath);
    }

    $markdownTemplate = file_get_contents($templatePath);

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
    $mpdf->SetFooter('ACM Empresa Simples de Crédito LTDA||Página {PAGENO} de {nbpg}');
    
    $mpdf->WriteHTML($html);
    
    // Save to disk
    $filename = $operacao_id . '_' . $natureza . '_' . date('Ymd') . '.pdf';
    
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
                    $stmtUpdateSac = $pdo->prepare("UPDATE sacados SET porte = ? WHERE id = ?");
                    $stmtUpdateSac->execute([$porte_cliente, $operacao['sacado_id']]);
                }
            } else {
                if (!empty($operacao['cedente_id'])) {
                    $stmtUpdateCed = $pdo->prepare("UPDATE cedentes SET porte = ? WHERE id = ?");
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
                    operation_id, marca, modelo, ano_fab, ano_mod, cor, placa, renavam, valor_avaliacao, chassi, municipio_registro, uf
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
