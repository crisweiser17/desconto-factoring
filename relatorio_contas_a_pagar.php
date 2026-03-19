<?php require_once 'auth_check.php'; ?>
<?php
require_once 'db_connection.php';
require_once 'functions.php';

// Configurações
$items_per_page_options = [25, 50, 100];
$items_per_page = isset($_GET['per_page']) && in_array((int)$_GET['per_page'], $items_per_page_options) ? (int)$_GET['per_page'] : 50;
setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'portuguese');

// Parâmetros de filtro
$filtro_periodo = isset($_GET['periodo']) ? $_GET['periodo'] : 'proximos_365';
$data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : '';
$data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : '';
$cedente_id = isset($_GET['cedente_id']) ? (int)$_GET['cedente_id'] : 0;
$tipos_pagamento = isset($_GET['tipos_pagamento']) ? $_GET['tipos_pagamento'] : ['direto', 'indireto']; // Por padrão, exclui escrow
$exportar = isset($_GET['exportar']) ? $_GET['exportar'] : '';

// Garantir que tipos_pagamento seja sempre um array
if (!is_array($tipos_pagamento)) {
    $tipos_pagamento = [$tipos_pagamento];
}

// Calcular datas baseado no período selecionado
if ($filtro_periodo !== 'personalizado') {
    $hoje = new DateTime();
    $data_inicio = $hoje->format('Y-m-d');
    
    switch ($filtro_periodo) {
        case 'proximos_7':
            $data_fim = $hoje->modify('+7 days')->format('Y-m-d');
            break;
        case 'proximos_15':
            $data_fim = $hoje->modify('+15 days')->format('Y-m-d');
            break;
        case 'proximos_30':
            $data_fim = $hoje->modify('+30 days')->format('Y-m-d');
            break;
        case 'proximos_365':
        default:
            $data_fim = $hoje->modify('+365 days')->format('Y-m-d');
            break;
    }
}

// Validar datas personalizadas
if ($filtro_periodo === 'personalizado') {
    if (!$data_inicio || !DateTime::createFromFormat('Y-m-d', $data_inicio)) {
        $data_inicio = date('Y-m-d');
    }
    if (!$data_fim || !DateTime::createFromFormat('Y-m-d', $data_fim)) {
        $data_fim = date('Y-m-d', strtotime('+30 days'));
    }
}

// Formatar datas para exibição
$data_inicio_formatada = DateTime::createFromFormat('Y-m-d', $data_inicio)->format('d/m/Y');
$data_fim_formatada = DateTime::createFromFormat('Y-m-d', $data_fim)->format('d/m/Y');

// Buscar nome do cedente selecionado
$cedente_selecionado = '';
if ($cedente_id > 0) {
    try {
        $stmt_cedente = $pdo->prepare("SELECT empresa FROM cedentes WHERE id = :id");
        $stmt_cedente->bindValue(':id', $cedente_id);
        $stmt_cedente->execute();
        $cedente_data = $stmt_cedente->fetch(PDO::FETCH_ASSOC);
        if ($cedente_data) {
            $cedente_selecionado = $cedente_data['empresa'];
        }
    } catch (PDOException $e) {
        // Ignorar erro
    }
}

// Buscar cedentes para o filtro
try {
    $stmt_cedentes = $pdo->query("SELECT id, empresa FROM cedentes ORDER BY empresa ASC");
    $cedentes = $stmt_cedentes->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $cedentes = [];
    $error_message = "Erro ao buscar cedentes: " . $e->getMessage();
}

// Construir query para buscar recebíveis
$whereClauses = [];
$params = [];

// Filtro de data
$whereClauses[] = "r.data_vencimento >= :data_inicio";
$whereClauses[] = "r.data_vencimento <= :data_fim";
$params[':data_inicio'] = $data_inicio;
$params[':data_fim'] = $data_fim;

// Excluir recebíveis tipo cheque
$whereClauses[] = "(r.tipo_recebivel IS NULL OR r.tipo_recebivel != 'cheque')";

// Filtro por tipos de pagamento selecionados
if (!empty($tipos_pagamento)) {
    $placeholders = [];
    foreach ($tipos_pagamento as $index => $tipo) {
        $placeholder = ":tipo_pagamento_$index";
        $placeholders[] = $placeholder;
        $params[$placeholder] = $tipo;
    }
    $whereClauses[] = "o.tipo_pagamento IN (" . implode(',', $placeholders) . ")";
}

// Filtro por cedente
if ($cedente_id > 0) {
    $whereClauses[] = "o.cedente_id = :cedente_id";
    $params[':cedente_id'] = $cedente_id;
}

// Apenas recebíveis em aberto
$whereClauses[] = "r.status = 'Em Aberto'";

$whereSQL = implode(' AND ', $whereClauses);

// Query principal
$sql = "SELECT 
    r.id,
    r.operacao_id,
    r.valor_original,
    r.data_vencimento,
    r.tipo_recebivel,
    o.data_operacao,
    o.tipo_pagamento,
    c.id as cedente_id,
    c.empresa as cedente_nome,
    c.email as cedente_email,
    c.telefone as cedente_telefone,
    s.empresa as sacado_nome
FROM recebiveis r
INNER JOIN operacoes o ON r.operacao_id = o.id
INNER JOIN cedentes c ON o.cedente_id = c.id
LEFT JOIN sacados s ON r.sacado_id = s.id
WHERE $whereSQL
ORDER BY c.empresa ASC, r.data_vencimento ASC";

try {
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $recebiveis = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $recebiveis = [];
    $error_message = "Erro ao buscar recebíveis: " . $e->getMessage();
}

// Agrupar por cedente
$recebiveis_por_cedente = [];
$total_geral = 0;

foreach ($recebiveis as $recebivel) {
    $cedente_id = $recebivel['cedente_id'];
    if (!isset($recebiveis_por_cedente[$cedente_id])) {
        $recebiveis_por_cedente[$cedente_id] = [
            'cedente' => [
                'id' => $recebivel['cedente_id'],
                'nome' => $recebivel['cedente_nome'],
                'email' => $recebivel['cedente_email'],
                'telefone' => $recebivel['cedente_telefone']
            ],
            'recebiveis' => [],
            'total' => 0
        ];
    }
    
    $recebiveis_por_cedente[$cedente_id]['recebiveis'][] = $recebivel;
    $recebiveis_por_cedente[$cedente_id]['total'] += $recebivel['valor_original'];
    $total_geral += $recebivel['valor_original'];
}

// Calcular totais para o PDF
$total_recebiveis = count($recebiveis);
$valor_total = $total_geral;

// Buscar cheques cedidos por cedente (para mostrar responsabilidade de ressarcimento)
$cheques_por_cedente = [];
$total_cheques = 0;

// Query para buscar cheques no mesmo período
$whereClauses_cheques = [];
$params_cheques = [];

// Filtro de data para cheques
$whereClauses_cheques[] = "r.data_vencimento >= :data_inicio";
$whereClauses_cheques[] = "r.data_vencimento <= :data_fim";
$params_cheques[':data_inicio'] = $data_inicio;
$params_cheques[':data_fim'] = $data_fim;

// Apenas cheques
$whereClauses_cheques[] = "r.tipo_recebivel = 'cheque'";

// Filtro por cedente se especificado
if ($cedente_id > 0) {
    $whereClauses_cheques[] = "o.cedente_id = :cedente_id";
    $params_cheques[':cedente_id'] = $cedente_id;
}

// Apenas cheques em aberto
$whereClauses_cheques[] = "r.status = 'Em Aberto'";

$whereSQL_cheques = implode(' AND ', $whereClauses_cheques);

// Query para buscar cheques
$sql_cheques = "SELECT 
    r.id,
    r.operacao_id,
    r.valor_original,
    r.data_vencimento,
    r.tipo_recebivel,
    o.data_operacao,
    o.tipo_pagamento,
    c.id as cedente_id,
    c.empresa as cedente_nome,
    c.email as cedente_email,
    c.telefone as cedente_telefone,
    s.empresa as sacado_nome
FROM recebiveis r
INNER JOIN operacoes o ON r.operacao_id = o.id
INNER JOIN cedentes c ON o.cedente_id = c.id
LEFT JOIN sacados s ON r.sacado_id = s.id
WHERE $whereSQL_cheques
ORDER BY c.empresa ASC, r.data_vencimento ASC";

try {
    $stmt_cheques = $pdo->prepare($sql_cheques);
    foreach ($params_cheques as $key => $value) {
        $stmt_cheques->bindValue($key, $value);
    }
    $stmt_cheques->execute();
    $cheques = $stmt_cheques->fetchAll(PDO::FETCH_ASSOC);
    
    // Agrupar cheques por cedente
    foreach ($cheques as $cheque) {
        $cedente_id_cheque = $cheque['cedente_id'];
        if (!isset($cheques_por_cedente[$cedente_id_cheque])) {
            $cheques_por_cedente[$cedente_id_cheque] = [
                'cedente' => [
                    'id' => $cheque['cedente_id'],
                    'nome' => $cheque['cedente_nome'],
                    'email' => $cheque['cedente_email'],
                    'telefone' => $cheque['cedente_telefone']
                ],
                'cheques' => [],
                'total' => 0
            ];
        }
        
        $cheques_por_cedente[$cedente_id_cheque]['cheques'][] = $cheque;
        $cheques_por_cedente[$cedente_id_cheque]['total'] += $cheque['valor_original'];
        $total_cheques += $cheque['valor_original'];
    }
} catch (PDOException $e) {
    $cheques = [];
    $error_message_cheques = "Erro ao buscar cheques: " . $e->getMessage();
}

// Função para formatar período
function formatarPeriodo($periodo, $data_inicio, $data_fim) {
    switch ($periodo) {
        case 'proximos_7':
            return 'Próximos 7 dias';
        case 'proximos_15':
            return 'Próximos 15 dias';
        case 'proximos_30':
            return 'Próximos 30 dias';
        case 'personalizado':
            return 'De ' . date('d/m/Y', strtotime($data_inicio)) . ' até ' . date('d/m/Y', strtotime($data_fim));
        default:
            return 'Período não definido';
    }
}

// Processar exportação
if ($exportar === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="contas_a_pagar_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // BOM para UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Cabeçalho
    fputcsv($output, [
        'Cedente',
        'Email',
        'Telefone',
        'Operação',
        'ID Recebível',
        'Vencimento',
        'Valor',
        'Sacado',
        'Tipo Recebível'
    ], ';');
    
    // Dados - Recebíveis
    foreach ($recebiveis_por_cedente as $grupo) {
        foreach ($grupo['recebiveis'] as $recebivel) {
            fputcsv($output, [
                $grupo['cedente']['nome'],
                $grupo['cedente']['email'],
                $grupo['cedente']['telefone'],
                $recebivel['operacao_id'],
                $recebivel['id'],
                date('d/m/Y', strtotime($recebivel['data_vencimento'])),
                number_format($recebivel['valor_original'], 2, ',', '.'),
                $recebivel['sacado_nome'] ?: 'N/A',
                $recebivel['tipo_recebivel'] ?: 'N/A'
            ], ';');
        }
    }
    
    // Adicionar linha em branco
    fputcsv($output, [''], ';');
    
    // Cabeçalho para cheques
    fputcsv($output, [
        'CHEQUES CEDIDOS (Responsabilidade de Ressarcimento)',
        '',
        '',
        '',
        '',
        '',
        '',
        ''
    ], ';');
    
    fputcsv($output, [
        'Cedente',
        'Email',
        'Telefone',
        'Operação',
        'ID Recebível',
        'Vencimento',
        'Valor',
        'Sacado',
        'Tipo'
    ], ';');
    
    // Dados - Cheques
    foreach ($cheques_por_cedente as $grupo) {
        foreach ($grupo['cheques'] as $cheque) {
            fputcsv($output, [
                $grupo['cedente']['nome'],
                $grupo['cedente']['email'],
                $grupo['cedente']['telefone'],
                $cheque['operacao_id'],
                $cheque['id'],
                date('d/m/Y', strtotime($cheque['data_vencimento'])),
                number_format($cheque['valor_original'], 2, ',', '.'),
                $cheque['sacado_nome'] ?: 'N/A',
                'Cheque'
            ], ';');
        }
    }
    
    fclose($output);
    exit;
}

if ($exportar === 'pdf') {
    require('fpdf/fpdf.php');
    
    // Classe PDF personalizada
    class PDF extends FPDF {
        function Header() {
            $this->SetFont('Arial','B',14);
            $this->Cell(0,10,mb_convert_encoding('Relatório de Contas a Pagar', 'ISO-8859-1', 'UTF-8'),0,1,'C');
            $this->Ln(5);
        }
        
        function Footer() {
            $this->SetY(-15);
            $this->SetFont('Arial','I',8);
            $this->Cell(0,10,mb_convert_encoding('Página ', 'ISO-8859-1', 'UTF-8').$this->PageNo().'/{nb}',0,0,'C');
        }
        
        function SectionTitle($title) {
            $this->SetFont('Arial','B',12);
            $this->Cell(0,8,mb_convert_encoding($title, 'ISO-8859-1', 'UTF-8'),0,1,'L');
            $this->Ln(2);
        }
        
        function ParameterLine($label, $value) {
            $this->SetFont('Arial','B',10);
            $this->Cell(60, 6, mb_convert_encoding($label . ': ', 'ISO-8859-1', 'UTF-8'), 0, 0, 'L');
            $this->SetFont('Arial','',10);
            $this->Cell(0, 6, mb_convert_encoding($value ?: '', 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');
        }
    }
    
    // Criar PDF
    $pdf = new PDF('P','mm','A4');
    $pdf->AliasNbPages();
    $pdf->SetAutoPageBreak(true, 20);
    $pdf->AddPage();
    $pdf->SetFont('Arial','',10);
    $pdf->SetLeftMargin(15);
    $pdf->SetRightMargin(15);
    
    // Título e parâmetros
    $pdf->SectionTitle('Parâmetros do Relatório');
    $pdf->ParameterLine('Período', $data_inicio_formatada . ' a ' . $data_fim_formatada);
    if ($cedente_selecionado) {
        $pdf->ParameterLine('Cedente Selecionado', $cedente_selecionado);
    }
    $pdf->ParameterLine('Data de Geração', date('d/m/Y H:i:s'));
    $pdf->Ln(5);
    
    // Resumo
    $pdf->SectionTitle('Resumo');
    $pdf->ParameterLine('Total de Cedentes', count($recebiveis_por_cedente));
    $pdf->ParameterLine('Total de Recebíveis', $total_recebiveis);
    $pdf->ParameterLine('Valor Total', 'R$ ' . number_format($valor_total, 2, ',', '.'));
    $pdf->Ln(5);
    
    // Dados por cedente
    foreach ($recebiveis_por_cedente as $grupo) {
        $pdf->SectionTitle('Cedente: ' . $grupo['cedente']['nome']);
        $pdf->ParameterLine('Email', $grupo['cedente']['email'] ?: 'N/A');
        $pdf->ParameterLine('Telefone', $grupo['cedente']['telefone'] ?: 'N/A');
        $pdf->ParameterLine('Quantidade de Recebíveis', count($grupo['recebiveis']));
        $pdf->ParameterLine('Valor Total do Cedente', 'R$ ' . number_format($grupo['total'], 2, ',', '.'));
        $pdf->Ln(3);
        
        // Tabela de recebíveis
        $pdf->SetFont('Arial','B',8);
        $pdf->Cell(18, 6, mb_convert_encoding('Op.', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
        $pdf->Cell(18, 6, mb_convert_encoding('ID Rec.', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
        $pdf->Cell(22, 6, mb_convert_encoding('Vencimento', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
        $pdf->Cell(25, 6, mb_convert_encoding('Valor', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
        $pdf->Cell(40, 6, mb_convert_encoding('Sacado', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
        $pdf->Cell(22, 6, mb_convert_encoding('Tipo', 'ISO-8859-1', 'UTF-8'), 1, 1, 'C');
        
        $pdf->SetFont('Arial','',8);
        foreach ($grupo['recebiveis'] as $recebivel) {
            $pdf->Cell(18, 5, $recebivel['operacao_id'], 1, 0, 'C');
            $pdf->Cell(18, 5, $recebivel['id'], 1, 0, 'C');
            $pdf->Cell(22, 5, date('d/m/Y', strtotime($recebivel['data_vencimento'])), 1, 0, 'C');
            $pdf->Cell(25, 5, 'R$ ' . number_format($recebivel['valor_original'], 2, ',', '.'), 1, 0, 'R');
            $pdf->Cell(40, 5, mb_convert_encoding(substr($recebivel['sacado_nome'] ?: 'N/A', 0, 20), 'ISO-8859-1', 'UTF-8'), 1, 0, 'L');
            $pdf->Cell(22, 5, mb_convert_encoding($recebivel['tipo_recebivel'] ?: 'N/A', 'ISO-8859-1', 'UTF-8'), 1, 1, 'C');
        }
        $pdf->Ln(5);
    }
    
    // Seção de Cheques Cedidos
    if (!empty($cheques_por_cedente)) {
        $pdf->AddPage();
        $pdf->SectionTitle('Cheques Cedidos - Responsabilidade de Ressarcimento');
        $pdf->SetFont('Arial','',9);
        $pdf->Cell(0, 6, mb_convert_encoding('Os cheques listados abaixo nunca serão pagos pelo cedente, mas ele é responsável', 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');
        $pdf->Cell(0, 6, mb_convert_encoding('por ressarcir caso o cheque retorne.', 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');
        $pdf->Ln(5);
        
        // Resumo de cheques
        $pdf->ParameterLine('Total de Cheques', count($cheques));
        $pdf->ParameterLine('Valor Total em Cheques', 'R$ ' . number_format($total_cheques, 2, ',', '.'));
        $pdf->Ln(5);
        
        // Dados de cheques por cedente
        foreach ($cheques_por_cedente as $grupo) {
            $pdf->SectionTitle('Cedente: ' . $grupo['cedente']['nome']);
            $pdf->ParameterLine('Quantidade de Cheques', count($grupo['cheques']));
            $pdf->ParameterLine('Valor Total em Cheques', 'R$ ' . number_format($grupo['total'], 2, ',', '.'));
            $pdf->Ln(3);
            
            // Tabela de cheques
            $pdf->SetFont('Arial','B',8);
            $pdf->Cell(18, 6, mb_convert_encoding('Op.', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
            $pdf->Cell(18, 6, mb_convert_encoding('ID Rec.', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
            $pdf->Cell(22, 6, mb_convert_encoding('Vencimento', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
            $pdf->Cell(25, 6, mb_convert_encoding('Valor', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
            $pdf->Cell(40, 6, mb_convert_encoding('Sacado', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
            $pdf->Cell(22, 6, mb_convert_encoding('Tipo', 'ISO-8859-1', 'UTF-8'), 1, 1, 'C');
            
            $pdf->SetFont('Arial','',8);
            foreach ($grupo['cheques'] as $cheque) {
                $pdf->Cell(18, 5, $cheque['operacao_id'], 1, 0, 'C');
                $pdf->Cell(18, 5, $cheque['id'], 1, 0, 'C');
                $pdf->Cell(22, 5, date('d/m/Y', strtotime($cheque['data_vencimento'])), 1, 0, 'C');
                $pdf->Cell(25, 5, 'R$ ' . number_format($cheque['valor_original'], 2, ',', '.'), 1, 0, 'R');
                $pdf->Cell(40, 5, mb_convert_encoding(substr($cheque['sacado_nome'] ?: 'N/A', 0, 20), 'ISO-8859-1', 'UTF-8'), 1, 0, 'L');
                $pdf->Cell(22, 5, mb_convert_encoding('Cheque', 'ISO-8859-1', 'UTF-8'), 1, 1, 'C');
            }
            $pdf->Ln(5);
        }
    }
    
    // Output PDF
    if (ob_get_level()) ob_end_clean();
    $pdf->Output('D', 'contas_a_pagar_' . date('Y-m-d') . '.pdf', true);
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Contas a Pagar - Factoring</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .periodo-badge {
            font-size: 0.9em;
        }
        .cedente-card {
            border-left: 4px solid #0d6efd;
        }
        .valor-destaque {
            font-weight: bold;
            color: #198754;
        }
        @media print {
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <?php include 'menu.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2><i class="bi bi-calendar-check me-2"></i>Relatório de Contas a Pagar</h2>
                        <p class="text-muted mb-0">
                            <span class="badge bg-primary periodo-badge">
                                <?php echo formatarPeriodo($filtro_periodo, $data_inicio, $data_fim); ?>
                            </span>
                            <?php if ($cedente_id > 0): ?>
                                <span class="badge bg-secondary ms-2">
                                    <?php 
                                    $cedente_selecionado = array_filter($cedentes, function($c) use ($cedente_id) {
                                        return $c['id'] == $cedente_id;
                                    });
                                    echo reset($cedente_selecionado)['empresa'] ?? 'Cedente não encontrado';
                                    ?>
                                </span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="no-print">
                        <div class="btn-group" role="group">
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['exportar' => 'csv'])); ?>" 
                               class="btn btn-success btn-sm">
                                <i class="bi bi-file-earmark-spreadsheet me-1"></i>Exportar CSV
                            </a>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['exportar' => 'pdf'])); ?>" 
                               class="btn btn-danger btn-sm">
                                <i class="bi bi-file-earmark-pdf me-1"></i>Exportar PDF
                            </a>
                            <button onclick="window.print()" class="btn btn-secondary btn-sm">
                                <i class="bi bi-printer me-1"></i>Imprimir
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="card mb-4 no-print">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label for="periodo" class="form-label">Período</label>
                                <select name="periodo" id="periodo" class="form-select" onchange="toggleCustomDates()">
                                    <option value="proximos_7" <?php echo $filtro_periodo === 'proximos_7' ? 'selected' : ''; ?>>Próximos 7 dias</option>
                                    <option value="proximos_15" <?php echo $filtro_periodo === 'proximos_15' ? 'selected' : ''; ?>>Próximos 15 dias</option>
                                    <option value="proximos_30" <?php echo $filtro_periodo === 'proximos_30' ? 'selected' : ''; ?>>Próximos 30 dias</option>
                                    <option value="proximos_365" <?php echo $filtro_periodo === 'proximos_365' ? 'selected' : ''; ?>>Próximo ano</option>
                                    <option value="personalizado" <?php echo $filtro_periodo === 'personalizado' ? 'selected' : ''; ?>>Período personalizado</option>
                                </select>
                            </div>
                            
                            <div class="col-md-2" id="data_inicio_div" style="<?php echo $filtro_periodo !== 'personalizado' ? 'display: none;' : ''; ?>">
                                <label for="data_inicio" class="form-label">Data Início</label>
                                <input type="date" name="data_inicio" id="data_inicio" class="form-select" value="<?php echo htmlspecialchars($data_inicio); ?>">
                            </div>
                            
                            <div class="col-md-2" id="data_fim_div" style="<?php echo $filtro_periodo !== 'personalizado' ? 'display: none;' : ''; ?>">
                                <label for="data_fim" class="form-label">Data Fim</label>
                                <input type="date" name="data_fim" id="data_fim" class="form-select" value="<?php echo htmlspecialchars($data_fim); ?>">
                            </div>
                            
                            <div class="col-md-3">
                                <label for="cedente_id" class="form-label">Cedente</label>
                                <select name="cedente_id" id="cedente_id" class="form-select">
                                    <option value="0">Todos os cedentes</option>
                                    <?php foreach ($cedentes as $cedente): ?>
                                        <option value="<?php echo $cedente['id']; ?>" 
                                                <?php echo $cedente_id == $cedente['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cedente['empresa']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label for="tipos_pagamento" class="form-label">Tipos de Pagamento</label>
                                <select name="tipos_pagamento[]" id="tipos_pagamento" class="form-select" multiple>
                                    <option value="direto" <?php echo in_array('direto', $tipos_pagamento) ? 'selected' : ''; ?>>Direto</option>
                                    <option value="indireto" <?php echo in_array('indireto', $tipos_pagamento) ? 'selected' : ''; ?>>Indireto</option>
                                    <option value="escrow" <?php echo in_array('escrow', $tipos_pagamento) ? 'selected' : ''; ?>>Escrow</option>
                                </select>
                                <div class="form-text">Segure Ctrl/Cmd para selecionar múltiplos tipos</div>
                            </div>
                            
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-search me-1"></i>Filtrar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Resumo -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title"><i class="bi bi-building me-2"></i>Cedentes</h5>
                                <h3 class="mb-0"><?php echo count($recebiveis_por_cedente); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h5 class="card-title"><i class="bi bi-list-check me-2"></i>Recebíveis</h5>
                                <h3 class="mb-0"><?php echo count($recebiveis); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title"><i class="bi bi-currency-dollar me-2"></i>Total</h5>
                                <h3 class="mb-0">R$ <?php echo number_format($total_geral, 2, ',', '.'); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <h5 class="card-title"><i class="bi bi-check2-square me-2"></i>Cheques</h5>
                                <h3 class="mb-0"><?php echo count($cheques); ?></h3>
                                <small>R$ <?php echo number_format($total_cheques, 2, ',', '.'); ?></small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Dados por Cedente -->
                <?php if (empty($recebiveis_por_cedente)): ?>
                    <div class="alert alert-info text-center">
                        <i class="bi bi-info-circle me-2"></i>
                        Nenhum recebível encontrado para o período selecionado.
                    </div>
                <?php else: ?>
                    <?php foreach ($recebiveis_por_cedente as $grupo): ?>
                        <div class="card mb-4 cedente-card">
                            <div class="card-header bg-light">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <h5 class="mb-0">
                                            <i class="bi bi-building me-2"></i>
                                            <?php echo htmlspecialchars($grupo['cedente']['nome']); ?>
                                        </h5>
                                        <small class="text-muted">
                                            <?php if ($grupo['cedente']['email']): ?>
                                                <i class="bi bi-envelope me-1"></i><?php echo htmlspecialchars($grupo['cedente']['email']); ?>
                                            <?php endif; ?>
                                            <?php if ($grupo['cedente']['telefone']): ?>
                                                <i class="bi bi-telephone ms-3 me-1"></i><?php echo htmlspecialchars($grupo['cedente']['telefone']); ?>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <span class="badge bg-primary fs-6">
                                            <?php echo count($grupo['recebiveis']); ?> recebível(is)
                                        </span>
                                        <div class="valor-destaque fs-5">
                                            R$ <?php echo number_format($grupo['total'], 2, ',', '.'); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Operação</th>
                                                <th>ID Recebível</th>
                                                <th>Vencimento</th>
                                                <th>Valor</th>
                                                <th>Sacado</th>
                                                <th>Tipo de Pagamento</th>
                                                <th>Tipo de Recebível</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($grupo['recebiveis'] as $recebivel): ?>
                                                <tr>
                                                    <td>
                                                        <a href="detalhes_operacao.php?id=<?php echo $recebivel['operacao_id']; ?>" 
                                                           class="text-decoration-none">
                                                            #<?php echo $recebivel['operacao_id']; ?>
                                                        </a>
                                                    </td>
                                                    <td>
                                                        <?php echo $recebivel['id']; ?>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                        $vencimento = new DateTime($recebivel['data_vencimento']);
                                                        $hoje = new DateTime();
                                                        $diff = $hoje->diff($vencimento);
                                                        $dias = $vencimento >= $hoje ? $diff->days : -$diff->days;
                                                        
                                                        $classe_vencimento = '';
                                                        if ($dias < 0) {
                                                            $classe_vencimento = 'text-danger';
                                                        } elseif ($dias <= 7) {
                                                            $classe_vencimento = 'text-warning';
                                                        }
                                                        ?>
                                                        <span class="<?php echo $classe_vencimento; ?>">
                                                            <?php echo $vencimento->format('d/m/Y'); ?>
                                                            <small>(<?php echo $dias >= 0 ? '+' : ''; ?><?php echo $dias; ?> dias)</small>
                                                        </span>
                                                    </td>
                                                    <td class="valor-destaque">
                                                        R$ <?php echo number_format($recebivel['valor_original'], 2, ',', '.'); ?>
                                                    </td>
                                                    <td>
                                                        <?php echo htmlspecialchars($recebivel['sacado_nome'] ?: 'N/A'); ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-info">
                                                            <?php echo htmlspecialchars(ucfirst($recebivel['tipo_pagamento'] ?: 'N/A')); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-secondary">
                                                            <?php echo htmlspecialchars($recebivel['tipo_recebivel'] ?: 'N/A'); ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <!-- Seção de Cheques Cedidos -->
                <?php if (!empty($cheques_por_cedente)): ?>
                    <div class="row mt-5">
                        <div class="col-12">
                            <div class="alert alert-warning">
                                <h5 class="alert-heading"><i class="bi bi-exclamation-triangle me-2"></i>Cheques Cedidos - Responsabilidade de Ressarcimento</h5>
                                <p class="mb-0">Os cheques listados abaixo <strong>nunca serão pagos pelo cedente</strong>, mas ele é <strong>responsável por ressarcir</strong> caso o cheque retorne.</p>
                            </div>
                        </div>
                    </div>
                    
                    <?php foreach ($cheques_por_cedente as $grupo): ?>
                        <div class="card mb-4" style="border-left: 4px solid #ffc107;">
                            <div class="card-header" style="background-color: #fff3cd;">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <h5 class="mb-0">
                                            <i class="bi bi-check2-square me-2 text-warning"></i>
                                            <?php echo htmlspecialchars($grupo['cedente']['nome']); ?> - Cheques Cedidos
                                        </h5>
                                        <small class="text-muted">
                                            <?php if ($grupo['cedente']['email']): ?>
                                                <i class="bi bi-envelope me-1"></i><?php echo htmlspecialchars($grupo['cedente']['email']); ?>
                                            <?php endif; ?>
                                            <?php if ($grupo['cedente']['telefone']): ?>
                                                <i class="bi bi-telephone ms-3 me-1"></i><?php echo htmlspecialchars($grupo['cedente']['telefone']); ?>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <span class="badge bg-warning text-dark fs-6">
                                            <?php echo count($grupo['cheques']); ?> cheque(s)
                                        </span>
                                        <div class="valor-destaque fs-5 text-warning">
                                            R$ <?php echo number_format($grupo['total'], 2, ',', '.'); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead class="table-warning">
                                            <tr>
                                                <th>Operação</th>
                                                <th>ID Recebível</th>
                                                <th>Vencimento</th>
                                                <th>Valor</th>
                                                <th>Sacado</th>
                                                <th>Tipo de Pagamento</th>
                                                <th>Tipo de Recebível</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($grupo['cheques'] as $cheque): ?>
                                                <tr>
                                                    <td>
                                                        <a href="detalhes_operacao.php?id=<?php echo $cheque['operacao_id']; ?>" 
                                                           class="text-decoration-none">
                                                            #<?php echo $cheque['operacao_id']; ?>
                                                        </a>
                                                    </td>
                                                    <td>
                                                        <?php echo $cheque['id']; ?>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                        $vencimento = new DateTime($cheque['data_vencimento']);
                                                        $hoje = new DateTime();
                                                        $diff = $hoje->diff($vencimento);
                                                        $dias = $vencimento >= $hoje ? $diff->days : -$diff->days;
                                                        
                                                        $classe_vencimento = '';
                                                        if ($dias < 0) {
                                                            $classe_vencimento = 'text-danger';
                                                        } elseif ($dias <= 7) {
                                                            $classe_vencimento = 'text-warning';
                                                        }
                                                        ?>
                                                        <span class="<?php echo $classe_vencimento; ?>">
                                                            <?php echo $vencimento->format('d/m/Y'); ?>
                                                            <small>(<?php echo $dias >= 0 ? '+' : ''; ?><?php echo $dias; ?> dias)</small>
                                                        </span>
                                                    </td>
                                                    <td class="valor-destaque text-warning">
                                                        R$ <?php echo number_format($cheque['valor_original'], 2, ',', '.'); ?>
                                                    </td>
                                                    <td>
                                                        <?php echo htmlspecialchars($cheque['sacado_nome'] ?: 'N/A'); ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-info">
                                                            <?php echo htmlspecialchars(ucfirst($cheque['tipo_pagamento'] ?: 'N/A')); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-warning text-dark">
                                                            Cheque
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleCustomDates() {
            const periodo = document.getElementById('periodo').value;
            const dataInicioDiv = document.getElementById('data_inicio_div');
            const dataFimDiv = document.getElementById('data_fim_div');
            
            if (periodo === 'personalizado') {
                dataInicioDiv.style.display = 'block';
                dataFimDiv.style.display = 'block';
            } else {
                dataInicioDiv.style.display = 'none';
                dataFimDiv.style.display = 'none';
            }
        }
        
        // Mostrar mensagens de erro se houver
        <?php if (isset($error_message)): ?>
            alert('<?php echo addslashes($error_message); ?>');
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])): ?>
            alert('<?php echo addslashes($_GET['error']); ?>');
        <?php endif; ?>
    </script>
</body>
</html>