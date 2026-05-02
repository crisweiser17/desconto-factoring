<?php
// gera_analise_interna_pdf.php

// Proteção - Adicione se implementou o login
require_once 'auth_check.php';

// Habilitar exibição de erros APENAS para depuração - REMOVA ou comente em produção!
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

// 1. Incluir dependências
require('fpdf/fpdf.php');
require_once 'db_connection.php';

// 2. Constantes e Funções Auxiliares Globais
define('IOF_ADICIONAL_RATE', 0.0038);
define('IOF_DIARIA_RATE', 0.000082);
function pdfFormatCurrency($value) { return 'R$ ' . number_format($value ?? 0, 2, ',', '.') . ''; }
function pdfFormatPercent($value) { return number_format(($value ?? 0) * 100, 2, ',', '.') . '%'; }
function pdfFormatDate($dateStr) { if(empty($dateStr)) return ''; try { return (new DateTime($dateStr))->format('d/m/Y'); } catch (Exception $e){ return $dateStr; } }
function pdfFormatSimNao($value) { return $value ? 'Sim' : 'Não'; } // Nova função para Sim/Não

// Função para converter UTF-8 para ISO-8859-1 (FPDF compatibility)
function pdfText($text) {
    if (empty($text)) return '';
    // Converter UTF-8 para ISO-8859-1 para compatibilidade com FPDF, substituindo caracteres não suportados
    return iconv('UTF-8', 'ISO-8859-1//IGNORE', $text);
}

// 3. Definição da Classe PDF personalizada
class PDF extends FPDF {
    public $companyName = '';
    public $companyDoc = '';
    public $documentTitle = 'Análise Interna da Operação';
    public $documentSubtitle = '';

    function setBranding($companyName, $companyDoc, $documentTitle, $documentSubtitle = '') {
        $this->companyName = $companyName;
        $this->companyDoc = $companyDoc;
        $this->documentTitle = $documentTitle;
        $this->documentSubtitle = $documentSubtitle;
    }

    function Header() {
        $this->SetFont('Arial','B',9);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(95, 5, pdfText($this->companyName), 0, 0, 'L');
        $this->SetFont('Arial','B',16);
        $this->SetTextColor(31, 58, 95);
        $this->Cell(0, 5, pdfText($this->documentTitle), 0, 1, 'R');

        $this->SetFont('Arial','',8);
        $this->SetTextColor(120, 120, 120);
        $this->Cell(95, 4, pdfText($this->companyDoc), 0, 0, 'L');
        $this->Cell(0, 4, pdfText($this->documentSubtitle), 0, 1, 'R');

        $this->Ln(2);
        $this->SetDrawColor(31, 58, 95);
        $this->SetLineWidth(0.5);
        $this->Line(15, $this->GetY(), 195, $this->GetY());
        $this->SetLineWidth(0.2);
        $this->SetDrawColor(0, 0, 0);
        $this->SetTextColor(0, 0, 0);
        $this->Ln(6);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetDrawColor(200, 210, 220);
        $this->Line(15, $this->GetY(), 195, $this->GetY());
        $this->SetDrawColor(0, 0, 0);
        $this->Ln(2);
        $this->SetFont('Arial','I',8);
        $this->SetTextColor(120, 120, 120);
        $this->Cell(95, 5, pdfText('Gerado em ' . date('d/m/Y H:i')), 0, 0, 'L');
        $this->Cell(0, 5, pdfText('Página ') . $this->PageNo() . '/{nb}', 0, 0, 'R');
        $this->SetTextColor(0, 0, 0);
    }

    function SectionTitle($label) {
        $this->Ln(1);
        $this->SetFont('Arial','B',11);
        $this->SetTextColor(31, 58, 95);
        $this->Cell(0, 6, pdfText($label), 0, 1, 'L');
        $y = $this->GetY();
        $this->SetDrawColor(31, 58, 95);
        $this->SetLineWidth(0.4);
        $this->Line(15, $y, 195, $y);
        $this->SetLineWidth(0.2);
        $this->SetDrawColor(0, 0, 0);
        $this->SetTextColor(0, 0, 0);
        $this->Ln(3);
        $this->SetFont('Arial','',10);
    }

    function ParameterLine($key, $value) {
        $this->SetFont('Arial','',10);
        $this->SetTextColor(90, 90, 90);
        $this->Cell(85, 6, pdfText($key), 0, 0, 'L');
        $this->SetFont('Arial','B',10);
        $this->SetTextColor(0, 0, 0);
        $this->Cell(0, 6, pdfText($value), 0, 1, 'L');
    }

    function HighlightBox($label, $value, $type = 'info') {
        switch ($type) {
            case 'success':
                $bg = [220, 240, 220]; $fg = [30, 100, 30]; break;
            case 'warning':
                $bg = [255, 246, 205]; $fg = [140, 90, 10]; break;
            default:
                $bg = [232, 238, 245]; $fg = [31, 58, 95];
        }
        $this->SetFillColor($bg[0], $bg[1], $bg[2]);
        $this->SetTextColor($fg[0], $fg[1], $fg[2]);
        $this->SetFont('Arial','B',10);
        $this->Cell(110, 10, '  ' . pdfText($label), 0, 0, 'L', true);
        $this->SetFont('Arial','B',12);
        $this->Cell(70, 10, pdfText($value) . '  ', 0, 1, 'R', true);
        $this->SetTextColor(0, 0, 0);
        $this->SetFillColor(255, 255, 255);
        $this->Ln(2);
    }

    function BasicTable($header, $data) {
        $widths = [30, 24, 14, 30, 24, 36, 22]; // total 180

        // Header
        $this->SetFillColor(31, 58, 95);
        $this->SetTextColor(255, 255, 255);
        $this->SetDrawColor(31, 58, 95);
        $this->SetFont('Arial','B',8);
        for ($i = 0; $i < count($header); $i++) {
            $this->Cell($widths[$i], 8, pdfText($header[$i]), 1, 0, 'C', true);
        }
        $this->Ln();

        // Body com zebra striping
        $this->SetFont('Arial','',8);
        $this->SetTextColor(0, 0, 0);
        $this->SetDrawColor(220, 225, 232);
        $fill = false;
        $totalOriginal = 0; $totalPresente = 0; $totalIof = 0; $totalLiquido = 0;
        foreach ($data as $row) {
            if ($fill) $this->SetFillColor(247, 249, 252);
            else $this->SetFillColor(255, 255, 255);
            $this->Cell($widths[0], 6, pdfFormatCurrency($row['original']), 'B', 0, 'R', true);
            $this->Cell($widths[1], 6, pdfFormatDate($row['vencimento']), 'B', 0, 'C', true);
            $this->Cell($widths[2], 6, $row['dias'], 'B', 0, 'C', true);
            $this->Cell($widths[3], 6, pdfFormatCurrency($row['presente']), 'B', 0, 'R', true);
            $this->Cell($widths[4], 6, pdfFormatCurrency($row['iof']), 'B', 0, 'R', true);
            $this->Cell($widths[5], 6, pdfFormatCurrency($row['liquido']), 'B', 0, 'R', true);
            $this->Cell($widths[6], 6, pdfText($row['status']), 'B', 0, 'C', true);
            $this->Ln();
            $totalOriginal += $row['original'];
            $totalPresente += $row['presente'];
            $totalIof += $row['iof'];
            $totalLiquido += $row['liquido'];
            $fill = !$fill;
        }

        // Linha de totais
        $this->SetFont('Arial','B',8);
        $this->SetFillColor(232, 238, 245);
        $this->SetTextColor(31, 58, 95);
        $this->SetDrawColor(31, 58, 95);
        $this->Cell($widths[0], 7, pdfFormatCurrency($totalOriginal), 'T', 0, 'R', true);
        $this->Cell($widths[1] + $widths[2], 7, pdfText('Totais'), 'T', 0, 'C', true);
        $this->Cell($widths[3], 7, pdfFormatCurrency($totalPresente), 'T', 0, 'R', true);
        $this->Cell($widths[4], 7, pdfFormatCurrency($totalIof), 'T', 0, 'R', true);
        $this->Cell($widths[5], 7, pdfFormatCurrency($totalLiquido), 'T', 0, 'R', true);
        $this->Cell($widths[6], 7, '', 'T', 0, 'C', true);
        $this->Ln(9);
        $this->SetTextColor(0, 0, 0);
        $this->SetDrawColor(0, 0, 0);
        $this->SetFillColor(255, 255, 255);
    }
}

// 4. Receber e Validar dados (simulação ou operação real)
$operacao_id = isset($_GET['id']) ? filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) : (isset($_POST['operacao_id']) ? filter_input(INPUT_POST, 'operacao_id', FILTER_VALIDATE_INT) : null);
$chartImageData = isset($_POST['chartImageData']) ? $_POST['chartImageData'] : null; // Recebe a imagem do gráfico via POST
$isSimulacao = false;
$recebiveis_db = [];
$operacao = null;
$error = null;

// Verificar se é simulação (dados POST) ou operação real (operacao_id POST)
if (isset($_POST['titulo_valor']) && is_array($_POST['titulo_valor']) && !empty($_POST['titulo_valor'])) {
    // MODO SIMULAÇÃO - Dados vindos do formulário
    $isSimulacao = true;
    
    // Extrair dados do POST
    $cedente_id = isset($_POST['cedente_id']) ? filter_input(INPUT_POST, 'cedente_id', FILTER_VALIDATE_INT) : null;
    $taxaMensal = isset($_POST['taxaMensal']) ? (float) $_POST['taxaMensal'] / 100 : 0;
    $dataOperacaoStr = isset($_POST['data_operacao']) ? $_POST['data_operacao'] : date('Y-m-d');
    $incorreCustoIOF = isset($_POST['incorreIOF']) ? $_POST['incorreIOF'] === 'Sim' : false;
    $cobrarIOFCliente = isset($_POST['cobrarIOF']) ? $_POST['cobrarIOF'] === 'Sim' : false;
    $notas = isset($_POST['notas']) ? trim($_POST['notas']) : '';
    $valoresOriginais = $_POST['titulo_valor'];
    $datasVencimento = isset($_POST['titulo_data']) && is_array($_POST['titulo_data']) ? $_POST['titulo_data'] : [];
    
    // Buscar nome do Cedente
    $cedenteNome = 'N/D';
    if ($cedente_id && $cedente_id > 0) {
        try {
            $stmtSacado = $pdo->prepare("SELECT empresa FROM clientes WHERE id = :id");
            $stmtSacado->bindParam(':id', $cedente_id, PDO::PARAM_INT);
            $stmtSacado->execute();
            $resultSacado = $stmtSacado->fetch(PDO::FETCH_ASSOC);
            if ($resultSacado) {
                $cedenteNome = $resultSacado['empresa'];
            } else {
                $cedenteNome = 'ID '.$cedente_id.' não encontrado';
            }
        } catch (PDOException $e) {
            error_log("Erro PDF Análise Cedente: " . $e->getMessage());
            $cedenteNome = 'Erro BD';
        }
    } else {
        $cedenteNome = 'Não informado';
    }
    
    // Criar operação simulada
    $operacao = [
        'id' => 'SIMULAÇÃO',
        'cedente_nome' => $cedenteNome,
        'data_operacao' => $dataOperacaoStr,
        'taxa_mensal' => $taxaMensal,
        'tipo_pagamento' => $_POST['tipoPagamento'] ?? 'direto',
        'incorre_custo_iof' => $incorreCustoIOF,
        'cobrar_iof_cliente' => $cobrarIOFCliente,
        'notas' => $notas,
        'valor_total_compensacao' => 0
    ];
    
    // Criar recebiveis simulados
    $count = min(count($valoresOriginais), count($datasVencimento));
    for ($i = 0; $i < $count; $i++) {
        if (!empty($valoresOriginais[$i]) && !empty($datasVencimento[$i]) && (float)$valoresOriginais[$i] > 0) {
            $recebiveis_db[] = [
                'id' => 'SIM_' . ($i + 1),
                'valor_original' => (float) $valoresOriginais[$i],
                'data_vencimento' => $datasVencimento[$i],
                'status' => 'Em Aberto'
            ];
        }
    }
    
    if (empty($recebiveis_db)) {
        $error = "Nenhum título válido fornecido para a simulação.";
    }
    
} elseif ($operacao_id && $operacao_id > 0) {
    // MODO OPERAÇÃO REAL - ID da operação via POST
    try {
        // Buscar dados da operação com todos os campos calculados
        $stmt_op = $pdo->prepare("SELECT o.*, COALESCE(s.empresa, s.nome, (SELECT COALESCE(sac.empresa, sac.nome) FROM recebiveis r2 JOIN clientes sac ON r2.sacado_id = sac.id WHERE r2.operacao_id = o.id LIMIT 1)) AS cedente_nome FROM operacoes o LEFT JOIN clientes s ON o.cedente_id = s.id WHERE o.id = :id");
        $stmt_op->bindParam(':id', $operacao_id, PDO::PARAM_INT);
        $stmt_op->execute();
        $operacao = $stmt_op->fetch(PDO::FETCH_ASSOC);

        if (!$operacao) {
            $error = "Operação com ID " . $operacao_id . " não encontrada.";
        } else {
            // Buscar recebíveis associados à operação para detalhamento
            $stmt_rec = $pdo->prepare("SELECT * FROM recebiveis WHERE operacao_id = :operacao_id ORDER BY data_vencimento ASC");
            $stmt_rec->bindParam(':operacao_id', $operacao_id, PDO::PARAM_INT);
            $stmt_rec->execute();
            $recebiveis_db = $stmt_rec->fetchAll(PDO::FETCH_ASSOC);

            if (empty($recebiveis_db)) {
                $error = "Nenhum recebível encontrado para a operação ID " . $operacao_id . ".";
            }
        }

    } catch (PDOException $e) {
        $error = "Erro ao buscar dados do banco de dados: " . htmlspecialchars($e->getMessage());
    }
} else {
    $error = "Nenhum dado válido fornecido. Use operacao_id ou dados de simulação.";
}

// 5. Extrair Dados da Operação
$cedenteNome = $operacao['cedente_nome'] ?? 'N/D';
$taxaMensal = (float)($operacao['taxa_mensal'] ?? 0);
$dataOperacaoStr = $operacao['data_operacao'] ?? date('Y-m-d H:i:s');
$cobrarIOFCliente = filter_var($operacao['cobrar_iof_cliente'] ?? false, FILTER_VALIDATE_BOOLEAN);
$incorreCustoIOF = filter_var($operacao['incorre_custo_iof'] ?? false, FILTER_VALIDATE_BOOLEAN);
$notas = $operacao['notas'] ?? '';

// 6. Usar Função Centralizada para Calcular (igual ao recibo do cliente)
require_once 'funcoes_calculo_central.php';

// Preparar títulos no formato esperado pelas funções centralizadas
$titulos_para_calculo = [];
foreach ($recebiveis_db as $r) {
    $titulos_para_calculo[] = [
        'valor' => (float)$r['valor_original'],
        'data_vencimento' => $r['data_vencimento']
    ];
}

// Usar função centralizada para calcular totais (IGUAL ao recibo do cliente)
$totais_calculados = null;
if (!empty($titulos_para_calculo)) {
    $totais_calculados = calcularTotaisOperacao(
        $titulos_para_calculo,
        $operacao['data_operacao'],
        $taxaMensal,
        $cobrarIOFCliente,
        [] // Compensações serão processadas separadamente
    );
}

// Usar valores calculados ou do banco (priorizar banco se disponível)
$totalOriginal = $totais_calculados ? $totais_calculados['total_original'] : 0;
$totalPresente = (!empty($operacao['total_presente_calc']) && $operacao['total_presente_calc'] > 0) ? (float)$operacao['total_presente_calc'] : ($totais_calculados ? $totais_calculados['total_presente'] : 0);
$totalIOF = (!empty($operacao['iof_total_calc']) && $operacao['iof_total_calc'] > 0) ? (float)$operacao['iof_total_calc'] : ($totais_calculados ? $totais_calculados['total_iof'] : 0);
$totalLiquidoPago = (!empty($operacao['total_liquido_pago_calc']) && $operacao['total_liquido_pago_calc'] > 0) ? (float)$operacao['total_liquido_pago_calc'] : ($totais_calculados ? $totais_calculados['total_liquido_pago'] : 0);
$totalLucroLiquido = (!empty($operacao['total_lucro_liquido_calc']) && $operacao['total_lucro_liquido_calc'] > 0) ? (float)$operacao['total_lucro_liquido_calc'] : ($totais_calculados ? $totais_calculados['total_lucro_liquido'] : 0);
$mediaPonderadaDiasNumerico = (!empty($operacao['media_dias_pond_calc']) && $operacao['media_dias_pond_calc'] > 0) ? (int)$operacao['media_dias_pond_calc'] : ($totais_calculados ? (int)$totais_calculados['media_dias'] : 0);

// Verificar compensação detalhada (IGUAL ao recibo do cliente)
$compensacao = null;
$custo_antecipacao_total = 0;
if (!empty($operacao['valor_total_compensacao']) && $operacao['valor_total_compensacao'] > 0) {
    try {
        $sql_comp = "SELECT SUM(valor_compensado) as total_compensado, SUM(valor_presente_compensacao) as total_presente
                    FROM compensacoes
                    WHERE operacao_principal_id = :operacao_id";
        $stmt_comp = $pdo->prepare($sql_comp);
        $stmt_comp->bindParam(':operacao_id', $operacao_id, PDO::PARAM_INT);
        $stmt_comp->execute();
        $resultado_comp = $stmt_comp->fetch(PDO::FETCH_ASSOC);
        
        if ($resultado_comp && $resultado_comp['total_compensado'] > 0) {
            $custo_antecipacao_total = $resultado_comp['total_compensado'] - $resultado_comp['total_presente'];
            $compensacao = [
                'temCompensacao' => true,
                'valorTotal' => (float)$operacao['valor_total_compensacao'],
                'custoAntecipacao' => $custo_antecipacao_total
            ];
        }
    } catch (Exception $e) {
        // Em caso de erro, manter compensação como null
    }
}

// Calcular percentuais baseados nos valores finais
$totalLucroPercentual = ($totalOriginal > 0) ? ($totalLucroLiquido / $totalOriginal) : 0;
$retornoMensalDecimal = 0;

// Retorno Mensal baseado nos dados finais
if ($totalLiquidoPago > 0 && $mediaPonderadaDiasNumerico > 0) {
    $taxaPeriodo = $totalLucroLiquido / $totalLiquidoPago;
    $base = 1 + $taxaPeriodo;
    $expoente = 30.0 / $mediaPonderadaDiasNumerico;
    if ($base >= 0) {
        $retornoMensalDecimal = pow($base, $expoente) - 1;
    } else {
        $retornoMensalDecimal = -1;
    }
}

// 7. Preparar Detalhamento dos Recebíveis (apenas para exibição na tabela)
$calculatedTitles = [];
if (!empty($recebiveis_db)) {
    $dataOperacao = null;
    try {
        $dataOperacao = new DateTime($dataOperacaoStr);
        $dataOperacao->setTime(0, 0, 0);
    } catch (Exception $e) {
        $error = 'Data da operação inválida no banco: ' . htmlspecialchars($dataOperacaoStr);
    }
    
    if (!$error) {
        foreach ($recebiveis_db as $rec) {
            if (!empty($rec['valor_original']) && !empty($rec['data_vencimento']) && (float)$rec['valor_original'] > 0) {
                $valorOriginalTitulo = (float) $rec['valor_original'];
                $dataVencimentoStrTitulo = $rec['data_vencimento'];
                $statusTitulo = $rec['status'];
                
                // Calcular dias para exibição
                $dataVencimentoTitulo = new DateTime($dataVencimentoStrTitulo);
                $dataVencimentoTitulo->setTime(0, 0, 0);
                $dias = 0;
                if ($dataVencimentoTitulo >= $dataOperacao) {
                    $interval = $dataOperacao->diff($dataVencimentoTitulo);
                    $dias = $interval->days;
                }
                
                // Calcular valor presente para exibição
                $valorPresenteTitulo = $valorOriginalTitulo;
                if ($dias > 0 && (1 + $taxaMensal) > 1e-9) {
                    $valorPresenteTitulo = $valorOriginalTitulo / pow(1 + $taxaMensal, $dias / 30.0);
                }
                
                // IOF calculado sobre o valor original do título (0,38%)
                $iofTitulo = $valorOriginalTitulo * 0.0038;
                
                // Valor líquido pago (valor presente menos IOF se cobrado do cliente)
                $iofDescontadoDoCliente = $cobrarIOFCliente ? $iofTitulo : 0;
                $valorLiquidoPagoTitulo = max(0, $valorPresenteTitulo - $iofDescontadoDoCliente);
                
                $calculatedTitles[] = [
                    'original' => $valorOriginalTitulo,
                    'vencimento' => $dataVencimentoStrTitulo,
                    'dias' => $dias,
                    'presente' => $valorPresenteTitulo,
                    'iof' => $iofTitulo,
                    'liquido' => $valorLiquidoPagoTitulo,
                    'status' => $statusTitulo
                ];
            }
        }
    }
}

// Validações básicas (taxa zero é permitida — operação sem juros)
if (!$error && $taxaMensal < 0) { $error = 'Taxa mensal da operação não pode ser negativa.'; }
if (!$error && empty($calculatedTitles)) { $error = 'Nenhum recebível válido encontrado para esta operação.'; }

// 7. Processar e Salvar Imagem do Gráfico (copiado do export_pdf.php)
$chartImagePath = null;
$tempImageCleanupNeeded = false;
if (!$error && $chartImageData && strpos($chartImageData, 'data:image/png;base64,') === 0) {
    $imageData = base64_decode(str_replace('data:image/png;base64,', '', $chartImageData));
    if ($imageData) {
        $tempDir = sys_get_temp_dir();
        if (!@is_writable($tempDir)) {
            $tempDir = __DIR__; // Tenta o diretório atual como fallback
        }
        if (@is_writable($tempDir)) {
            $tempImageFilename = rtrim($tempDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'chart_' . uniqid() . '.png';
            if (file_put_contents($tempImageFilename, $imageData)) {
                $chartImagePath = $tempImageFilename;
                $tempImageCleanupNeeded = true;
            } else {
                $error = ($error ?? '') . ' Erro: Falha ao salvar imagem temporária no disco.';
            }
        } else {
            $error = ($error ?? '') . ' Erro: Diretório temporário não gravável.';
        }
    } else {
        $error = ($error ?? '') . ' Erro: Falha ao decodificar dados da imagem.';
    }
}


// 8. Lidar com erros antes de gerar PDF
if ($error) {
    header("Content-Type: text/plain; charset=utf-8");
    die("Erro ao gerar Análise Interna: " . htmlspecialchars($error));
}

// 9. Gerar o PDF
// Carregar dados da empresa para branding do cabeçalho
$brandingConfig = [];
$brandingPath = __DIR__ . '/config.json';
if (file_exists($brandingPath)) {
    $brandingConfig = json_decode(file_get_contents($brandingPath), true) ?: [];
}
$empresaNome = $brandingConfig['empresa_razao_social'] ?? ($brandingConfig['conta_titular'] ?? '');
$empresaDoc = $brandingConfig['empresa_documento'] ?? ($brandingConfig['conta_documento'] ?? '');

$pdf = new PDF('P','mm','A4');
$pdf->AliasNbPages();
$pdf->SetAutoPageBreak(true, 20);
$pdf->SetLeftMargin(15);
$pdf->SetRightMargin(15);
$pdf->SetTopMargin(12);

$subtituloDoc = $isSimulacao
    ? 'Simulação • ' . pdfFormatDate($dataOperacaoStr)
    : 'Operação #' . ($operacao_id ?? '—') . ' • ' . pdfFormatDate($dataOperacaoStr);
$pdf->setBranding($empresaNome, $empresaDoc, 'Análise Interna da Operação', $subtituloDoc);

$pdf->AddPage();
$pdf->SetFont('Arial','',10);

// Adicionar Conteúdo
$pdf->SectionTitle('Informações da Operação');
$pdf->ParameterLine('ID da Operação', $operacao['id']);
$pdf->ParameterLine('Cedente', $cedenteNome);
$pdf->ParameterLine('Data da Operação', pdfFormatDate($dataOperacaoStr));
$pdf->ParameterLine('Taxa Nominal', pdfFormatPercent($taxaMensal) . ' ao mês');
// Tipo de Pagamento com nomenclatura completa
$tipoPagamento = $operacao['tipo_pagamento'] ?? 'direto';
switch($tipoPagamento) {
    case 'direto':
        $tipoPagamentoTexto = 'Pagamento Direto (Notificação ao Sacado)';
        break;
    case 'escrow':
        $tipoPagamentoTexto = 'Pagamento via Conta Escrow (Conta Vinculada)';
        break;
    case 'indireto':
        $tipoPagamentoTexto = 'Pagamento Indireto (via Cedente)';
        break;

    default:
        $tipoPagamentoTexto = $tipoPagamento;
}
$pdf->ParameterLine('Tipo de Pagamento', $tipoPagamentoTexto);
$pdf->ParameterLine('Incorre Custo de IOF', pdfFormatSimNao($incorreCustoIOF)); // Nova linha de informação
$pdf->Ln(5);

// Notas
if (!empty($notas)) {
    $pdf->SectionTitle('Observações da Operação');
    $pdf->MultiCell(0, 5, pdfText($notas));
    $pdf->Ln(5);
}

// Detalhamento Títulos
$pdf->SectionTitle('Detalhamento dos Títulos');
$header = ['Vl Original', 'Vencimento', 'Dias', 'Vl Presente', 'IOF Calc.', 'Vl Líquido Pgo.', 'Status'];
$pdf->BasicTable($header, $calculatedTitles);

// Resultados Totais
$pdf->SectionTitle('Resultados Calculados da Operação');
$pdf->ParameterLine('Média Ponderada de Dias', round($mediaPonderadaDiasNumerico) . ' dias');
$pdf->ParameterLine('Total Valor Original dos Recebíveis', pdfFormatCurrency($totalOriginal));

// Adicionar informações de compensação se aplicável (igual ao recibo do cliente)
if (!empty($compensacao) && $compensacao['temCompensacao']) {
    $pdf->ParameterLine('Abatimento (Compensação)', pdfFormatCurrency(-$compensacao['valorTotal']));
}

// DESTACAR O CRÉDITO OFERECIDO AO CLIENTE (Custo da Antecipação)
// Se há compensação, usar o custo da antecipação da compensação, senão ZERO (sem antecipação)
$custoAntecipacao = (!empty($compensacao) && isset($compensacao['custoAntecipacao']))
    ? $compensacao['custoAntecipacao']
    : 0; // CORREÇÃO: Zero quando não há antecipação de recebíveis

$pdf->HighlightBox('Crédito Oferecido ao Cliente', pdfFormatCurrency($custoAntecipacao), 'warning');

$pdf->ParameterLine('Total Valor Presente (Base de Cálculo)', pdfFormatCurrency($totalPresente));
$pdf->ParameterLine('Total IOF Calculado', pdfFormatCurrency($totalIOF));
$pdf->ParameterLine('Total Líquido Pago ao Cliente', pdfFormatCurrency($totalLiquidoPago));
$pdf->Ln(2);

// DESTACAR O LUCRO CORRETO DA OPERAÇÃO
$pdf->HighlightBox('Lucro Líquido da Operação', pdfFormatCurrency($totalLucroLiquido), 'success');

// Usar ParameterLine para consistência no espaçamento
$pdf->ParameterLine('Margem Total (% / Original)', pdfFormatPercent($totalLucroPercentual));
$pdf->ParameterLine('Retorno Mensal (% / Líq. Pago)', ($retornoMensalDecimal == -1 ? 'N/A' : pdfFormatPercent($retornoMensalDecimal)));
$pdf->Ln(3);

// Seção de Compensação Detalhada (se aplicável) - SEM duplicar o valor total
if (!empty($compensacao) && $compensacao['temCompensacao']) {
    $pdf->SectionTitle('Detalhamento da Compensação/Offset');
    
    // Buscar detalhes da compensação do banco de dados
    try {
        $sql_detalhes = "SELECT c.*, r.data_vencimento
                        FROM compensacoes c
                        LEFT JOIN recebiveis r ON c.recebivel_compensado_id = r.id
                        WHERE c.operacao_principal_id = :operacao_id";
        $stmt_detalhes = $pdo->prepare($sql_detalhes);
        $stmt_detalhes->bindParam(':operacao_id', $operacao_id, PDO::PARAM_INT);
        $stmt_detalhes->execute();
        $detalhes_compensacao = $stmt_detalhes->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($detalhes_compensacao)) {
            $pdf->SetFont('Arial','B',9);
            $pdf->Cell(0, 6, pdfText('Recebíveis Utilizados na Compensação:'), 0, 1, 'L');
            $pdf->Ln(3);
            
            foreach ($detalhes_compensacao as $detalhe) {
                $pdf->SetFont('Arial','',8);
                $pdf->Cell(40, 6, pdfText('Recebível ID: ' . $detalhe['recebivel_compensado_id']), 0, 0, 'L');
                $pdf->Cell(50, 6, pdfText('Valor: ' . pdfFormatCurrency($detalhe['valor_compensado'])), 0, 0, 'L');
                $pdf->Cell(0, 6, pdfText('Venc.: ' . pdfFormatDate($detalhe['data_vencimento'])), 0, 1, 'L');
                $pdf->Ln(1); // Pequeno espaço entre cada item
            }
            $pdf->Ln(3);
        }
    } catch (Exception $e) {
        // Em caso de erro, apenas mostrar uma mensagem simples
        $pdf->SetFont('Arial','',8);
        $pdf->Cell(0, 6, pdfText('Detalhes da compensação não disponíveis.'), 0, 1, 'L');
        $pdf->Ln(3);
    }
    
    // Explicação do impacto
    $pdf->SetFont('Arial','I',8);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->MultiCell(0, 4, pdfText('* A compensação reduz o valor original dos recebíveis, impactando diretamente o cálculo do crédito oferecido ao cliente.'));
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln(2); // Reduzido de 5 para 2
}

// Adicionar nota sobre fonte dos dados
$pdf->SetFont('Arial','I',8);
$pdf->SetTextColor(100, 100, 100);
$pdf->MultiCell(0, 4, pdfText('* Valores extraídos diretamente do banco de dados (centro de verdade do sistema)'));
$pdf->SetTextColor(0, 0, 0);
$pdf->Ln(2); // Reduzido de 5 para 2

// Inserir Imagem do Gráfico (copiado do export_pdf.php)
if ($chartImagePath && file_exists($chartImagePath)) {
    try {
        $maxWidth=180;$maxHeight=180;
        list($imgWidth,$imgHeight)=@getimagesize($chartImagePath);
        $newWidth=0;$newHeight=0;
        if($imgWidth&&$imgHeight){
            $ratio=$imgWidth/$imgHeight;
            $newWidth=$maxWidth;$newHeight=$newWidth/$ratio;
            if($newHeight>$maxHeight){
                $newHeight=$maxHeight;$newWidth=$newHeight*$ratio;
            }
        }else{
            throw new Exception("Dimensões da imagem inválidas ou arquivo corrompido.");
        }
        $altTotalNec=11+$newHeight+5; // Altura necessária para título do gráfico + gráfico + margem
        $yAtual=$pdf->GetY();
        $mInfDef=20; // Margem inferior definida
        $espRest=$pdf->GetPageHeight()-$yAtual-$mInfDef; // Espaço restante na página

        if($espRest<$altTotalNec){
            $pdf->AddPage('P','A4'); // Adiciona nova página se não houver espaço suficiente
        }
        $pdf->SectionTitle('Gráfico - Fluxo de Caixa dos Vencimentos');
        $xPos=(210-$newWidth)/2; // Centraliza a imagem
        $yPos=$pdf->GetY(); // Posição Y atual após o título
        $pdf->Image($chartImagePath,$xPos,$yPos,$newWidth,$newHeight,'PNG');
    } catch (Exception $imgE){
        $yAErr=$pdf->GetY();$mIErr=20;$eRErr=$pdf->GetPageHeight()-$yAErr-$mIErr;
        if($eRErr<15){$pdf->AddPage('P','A4');}
        $pdf->Ln(5);$pdf->SetFont('Arial','I',9);$pdf->SetTextColor(255,0,0);
        $pdf->MultiCell(0,5,pdfText('Erro ao inserir gráfico: '.$imgE->getMessage()));
        $pdf->SetTextColor(0);
    }
}
elseif ($chartImageData && $error) {
    // Exibe erro do gráfico se o cálculo falhou (apenas se chartImageData existia)
    $yAErr=$pdf->GetY();$mIErr=20;$eRErr=$pdf->GetPageHeight()-$yAErr-$mIErr;
    if($eRErr<20){$pdf->AddPage('P','A4');}
    $pdf->SectionTitle('Gráfico - Fluxo de Caixa');$pdf->Ln(5);
    $pdf->SetFont('Arial','I',9);$pdf->SetTextColor(255,0,0);
    $pdf->MultiCell(0,5,pdfText('Gráfico não incluído devido a erro no cálculo: '.htmlspecialchars(str_replace('Erro: ','',$error))));
    $pdf->SetTextColor(0);
}


// 10. Output PDF
if (ob_get_level()) ob_end_clean(); // Limpa qualquer output buffer pendente

// Definir nome do arquivo baseado no tipo (simulação ou operação real)
$isSimulacao = isset($isSimulacao) ? $isSimulacao : false;
if ($isSimulacao) {
    // Para simulação: simulacao_[nome_do_sacado]_analise
    $cedenteNome = $operacao['cedente_nome'] ?? 'N_A';
    $nomeArquivo = 'simulacao_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower($cedenteNome)) . '_analise_' . date('Ymd') . '.pdf';
} else {
    // Para operação real: analise_[ID]
    $nomeArquivo = 'analise_' . ($operacao_id ?? 'avulso') . '_' . date('Ymd') . '.pdf';
}

$pdf->Output('D', $nomeArquivo, true);

// 11. Limpeza Imagem Temp (copiado do export_pdf.php)
// Usar register_shutdown_function para garantir que o arquivo temporário seja excluído
if ($tempImageCleanupNeeded && $chartImagePath && file_exists($chartImagePath)) {
    register_shutdown_function(function($filename) {
        @unlink($filename);
    }, $chartImagePath);
}

exit;
?>
