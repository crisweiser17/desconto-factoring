<?php
// export_pdf_cliente.php - Versão simplificada para o cliente

// Proteção removida para simulações - não requer autenticação
// require_once 'auth_check.php';

// Habilitar exibição de erros APENAS para depuração - REMOVA ou comente em produção!
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

// 1. Incluir dependências
require('fpdf/fpdf.php'); // Verifique se este caminho está correto
require_once 'db_connection.php'; // Conexão $pdo

// 2. Constantes e Funções Auxiliares Globais
define('IOF_ADICIONAL_RATE', 0.0038);
define('IOF_DIARIA_RATE', 0.000082);
function pdfFormatCurrency($value) { return 'R$ ' . number_format($value ?? 0, 2, ',', '.'); }
// *** CORREÇÃO: Função pdfFormatPercent DESCOMENTADA ***
function pdfFormatPercent($value) { return number_format(($value ?? 0) * 100, 2, ',', '.') . '%'; }
// *** FIM CORREÇÃO ***
function pdfFormatDate($dateStr) { if(empty($dateStr)) return ''; try { return (new DateTime($dateStr))->format('d/m/Y'); } catch (Exception $e){ return $dateStr; } }

// Modern replacement for deprecated utf8_decode()
function pdfEncodeText($text) {
    if (empty($text)) return '';
    // Convert UTF-8 to ISO-8859-1 (Latin-1) for FPDF compatibility
    return mb_convert_encoding($text, 'ISO-8859-1', 'UTF-8');
}

// 3. Definição da Classe PDF personalizada
class PDF_Cliente extends FPDF {
    public $tituloPdf = 'Resumo da Operação de Desconto';
    public $isEmprestimo = false;

    function Header() { $this->SetFont('Arial','B',14); $this->Cell(0,10,pdfEncodeText($this->tituloPdf),0,1,'C'); $this->Ln(5); }
    function Footer() { $this->SetY(-15); $this->SetFont('Arial','I',8); $this->Cell(0,10,pdfEncodeText('Página ').$this->PageNo().'/{nb}',0,0,'C'); }
    function SectionTitle($label) { $this->SetFont('Arial','B',11); $this->SetFillColor(230,230,230); $this->Cell(0,7,pdfEncodeText($label),0,1,'L',true); $this->Ln(4); $this->SetFont('Arial','',10); }
    function ParameterLine($key, $value) { $this->SetFont('Arial','B',10); $this->Cell(60, 6, pdfEncodeText($key.': '), 0, 0, 'L'); $this->SetFont('Arial','',10); $this->MultiCell(0, 6, pdfEncodeText($value), 0, 'L'); $this->Ln(1); }
    // Tabela ajustada para cliente (incluindo sacado)
    function BasicTable($header_cliente, $data) {
        $this->SetFillColor(230,230,230); $this->SetFont('Arial','B',8);
        if ($this->isEmprestimo) {
            $widths = [45, 35, 70, 20]; // Sem coluna Liquido
        } else {
            $widths = [35, 30, 40, 15, 50]; // Original
        }
        
        for($i=0; $i<count($header_cliente); $i++) { $this->Cell($widths[$i], 7, pdfEncodeText($header_cliente[$i]), 1, 0, 'C', true); }
        $this->Ln(); $this->SetFont('Arial','',8); $this->SetFillColor(255);
        foreach($data as $row){
            $this->Cell($widths[0], 6, pdfFormatCurrency($row['original']), 'LR', 0, 'R');
            $this->Cell($widths[1], 6, pdfFormatDate($row['vencimento']), 'LR', 0, 'C');
            if ($this->isEmprestimo) {
                $this->Cell($widths[2], 6, pdfEncodeText(substr($row['sacado'] ?? 'N/D', 0, 30)), 'LR', 0, 'L');
                $this->Cell($widths[3], 6, $row['dias'], 'LR', 0, 'C');
            } else {
                $this->Cell($widths[2], 6, pdfEncodeText(substr($row['sacado'] ?? 'N/D', 0, 18)), 'LR', 0, 'L');
                $this->Cell($widths[3], 6, $row['dias'], 'LR', 0, 'C');
                $this->Cell($widths[4], 6, pdfFormatCurrency($row['liquido']), 'LR', 0, 'R');
            }
            $this->Ln();
        }
        $this->Cell(array_sum($widths), 0, '', 'T'); $this->Ln(5);
    }
}

// 4. Receber Dados POST (Mantido)
$cedente_id = isset($_POST['cedente_id']) ? filter_input(INPUT_POST, 'cedente_id', FILTER_VALIDATE_INT) : null;
$tomador_id = isset($_POST['tomador_id']) ? filter_input(INPUT_POST, 'tomador_id', FILTER_VALIDATE_INT) : null;
$tipoOperacao = isset($_POST['tipoOperacao']) ? trim($_POST['tipoOperacao']) : 'antecipacao';
$valorEmprestimo = isset($_POST['valor_emprestimo']) ? (float)$_POST['valor_emprestimo'] : null;

$taxaMensal = isset($_POST['taxaMensal']) ? (float) $_POST['taxaMensal'] / 100 : 0;
$dataOperacaoStr = isset($_POST['data_operacao']) ? $_POST['data_operacao'] : date('Y-m-d');
$incorreIOF = isset($_POST['incorreIOF']) ? $_POST['incorreIOF'] === 'Sim' : false;
$cobrarIOF = isset($_POST['cobrarIOF']) ? $_POST['cobrarIOF'] === 'Sim' : false;
$temGarantia = isset($_POST['tem_garantia']) ? (int)$_POST['tem_garantia'] : 0;
$descricaoGarantia = isset($_POST['descricao_garantia']) ? trim($_POST['descricao_garantia']) : '';

// Se for empréstimo, ignora IOF e troca cedente_id pelo tomador_id
if ($tipoOperacao === 'emprestimo') {
    $incorreIOF = false;
    $cobrarIOF = false;
    $cedente_id = $tomador_id;
}

$notas = isset($_POST['notas']) ? trim($_POST['notas']) : '';
$valoresOriginais = isset($_POST['titulo_valor']) && is_array($_POST['titulo_valor']) ? $_POST['titulo_valor'] : [];
$datasVencimento = isset($_POST['titulo_data']) && is_array($_POST['titulo_data']) ? $_POST['titulo_data'] : [];
$sacadosIds = isset($_POST['titulo_sacado']) && is_array($_POST['titulo_sacado']) ? $_POST['titulo_sacado'] : [];

// 4.5 Buscar nome do Cedente/Tomador (Mantido)
$cedenteNome = 'N/D'; 
if ($cedente_id && $cedente_id > 0) { 
    try { 
        if ($tipoOperacao === 'emprestimo') {
            $stmtSacado = $pdo->prepare("SELECT empresa FROM clientes WHERE id = :id"); 
        } else {
            $stmtSacado = $pdo->prepare("SELECT empresa FROM clientes WHERE id = :id"); 
        }
        $stmtSacado->bindParam(':id', $cedente_id, PDO::PARAM_INT); 
        $stmtSacado->execute(); 
        $resultSacado = $stmtSacado->fetch(PDO::FETCH_ASSOC); 
        if ($resultSacado) { 
            $cedenteNome = $resultSacado['empresa']; 
        } else { 
            $cedenteNome = 'ID '.$cedente_id.' não encontrado'; 
        } 
    } catch (PDOException $e) { 
        error_log("Erro PDF Cliente Cedente: " . $e->getMessage()); 
        $cedenteNome = 'Erro BD'; 
    } 
} else { 
    $cedenteNome = 'Não informado'; 
}

// 4.6 Buscar nomes dos Sacados
$sacadosNomes = [];
if (!empty($sacadosIds)) {
    try {
        $sacadosIdsLimpos = array_filter($sacadosIds, function($id) { return !empty($id) && is_numeric($id); });
        if (!empty($sacadosIdsLimpos)) {
            $placeholders = str_repeat('?,', count($sacadosIdsLimpos) - 1) . '?';
            $stmtSacados = $pdo->prepare("SELECT id, empresa FROM clientes WHERE id IN ($placeholders)");
            $stmtSacados->execute($sacadosIdsLimpos);
            $resultSacados = $stmtSacados->fetchAll(PDO::FETCH_ASSOC);
            foreach ($resultSacados as $sacado) {
                $sacadosNomes[$sacado['id']] = $sacado['empresa'];
            }
        }
    } catch (PDOException $e) {
        error_log("Erro PDF Cliente Sacados: " . $e->getMessage());
    }
}

// 5. Combinar Títulos (incluindo sacados)
$titulos = []; $count = min(count($valoresOriginais), count($datasVencimento), count($sacadosIds)); for ($i = 0; $i < $count; $i++) { if (!empty($valoresOriginais[$i]) && !empty($datasVencimento[$i]) && (float)$valoresOriginais[$i] > 0) { $sacadoId = !empty($sacadosIds[$i]) ? $sacadosIds[$i] : null; $sacadoNome = $sacadoId && isset($sacadosNomes[$sacadoId]) ? $sacadosNomes[$sacadoId] : 'Não informado'; $titulos[] = [ 'valorOriginal' => (float) $valoresOriginais[$i], 'dataVencimento' => $datasVencimento[$i], 'sacadoId' => $sacadoId, 'sacadoNome' => $sacadoNome ]; } }

// 6. Recalcular (Lógica mantida)
$totalOriginal = 0; $totalLiquidoPago = 0;
$weightedDaysNumerator = 0; $totalWeightForDays = 0; $mediaPonderadaDiasNumerico = 0;
$calculatedTitles = []; $error = null; $dataOperacao = null;

if ($taxaMensal <= 0) { $error = 'Taxa mensal deve ser maior que zero.'; }
elseif (empty($titulos)) { $error = 'Nenhum título válido fornecido.'; }
else { try { $dataOperacao = new DateTime($dataOperacaoStr); $dataOperacao->setTime(0,0,0); } catch (Exception $e) { $error = 'Data op inválida'; } }

if (!$error) {
    try {
        $titulosTemp = [];
        $totalPresente = 0;
        
        // Primeiro loop: calcular valores presentes e acumular totais
        foreach ($titulos as $titulo) {
            $valorOriginalTitulo = $titulo['valorOriginal']; $dataVencimentoStrTitulo = $titulo['dataVencimento'];
            try { $dataVencimentoTitulo = new DateTime($dataVencimentoStrTitulo); $dataVencimentoTitulo->setTime(0,0,0); } catch (Exception $e) { $error = 'Data venc inválida'; break; }
            $dias = 0; if ($dataVencimentoTitulo < $dataOperacao) { $error = "Venc<Op"; break; } elseif ($dataVencimentoTitulo >= $dataOperacao) { $interval = $dataOperacao->diff($dataVencimentoTitulo); $dias = $interval->days; }
            $valorPresenteTitulo = $valorOriginalTitulo; if ($dias > 0 && (1 + $taxaMensal) > 1e-9) { $valorPresenteTitulo = $valorOriginalTitulo / pow(1 + $taxaMensal, $dias / 30.0); } elseif ($dias > 0) { $error = $error ?? 'Taxa inválida.'; $valorPresenteTitulo = 0; }

            if ($error) break;

            // Acumula totais primeiro (sem IOF ainda)
            $totalOriginal += $valorOriginalTitulo;
            $totalPresente += $valorPresenteTitulo;
            $weightedDaysNumerator += $dias * $valorOriginalTitulo;
            $totalWeightForDays += $valorOriginalTitulo;

            // Armazena dados temporários
            $titulosTemp[] = [
                'valorOriginal' => $valorOriginalTitulo,
                'valorPresente' => $valorPresenteTitulo,
                'dataVencimento' => $dataVencimentoStrTitulo,
                'dias' => $dias
            ];
        }
        
        // LÓGICA CORRIGIDA: Calcular IOF sobre o valor original de cada título
        if (!$error && !empty($titulosTemp)) {
            $totalIOF = 0;
            
            foreach ($titulosTemp as $tituloData) {
                $valorOriginalTitulo = $tituloData['valorOriginal'];
                $valorPresenteTitulo = $tituloData['valorPresente'];
                $dataVencimentoStrTitulo = $tituloData['dataVencimento'];
                $dias = $tituloData['dias'];
                
                // IOF calculado sobre o valor original do título (0,38%)
                $iofTitulo = $valorOriginalTitulo * 0.0038;
                
                // Acumula IOF total
                $totalIOF += $iofTitulo;
                
                // IOF que é descontado do valor que sua empresa PAGA ao cliente
                $iofDescontadoDoCliente = $cobrarIOF ? $iofTitulo : 0;
                
                // Valor Líquido que sua empresa PAGA ao cliente por este título
                $valorLiquidoPagoTitulo = max(0, $valorPresenteTitulo - $iofDescontadoDoCliente);
                
                // Acumula totais finais
                $totalLiquidoPago += $valorLiquidoPagoTitulo;
                
                // Armazena dados para tabela PDF (incluindo sacado)
                $tituloOriginal = null;
                foreach ($titulos as $tit) {
                    if ($tit['valorOriginal'] == $valorOriginalTitulo && $tit['dataVencimento'] == $dataVencimentoStrTitulo) {
                        $tituloOriginal = $tit;
                        break;
                    }
                }
                $calculatedTitles[] = [
                    'original' => $valorOriginalTitulo,
                    'vencimento' => $dataVencimentoStrTitulo,
                    'dias' => $dias,
                    'liquido' => $valorLiquidoPagoTitulo,
                    'sacado' => $tituloOriginal ? $tituloOriginal['sacadoNome'] : 'N/D'
                ];
            }
        }
        if (!$error && $totalWeightForDays > 0) { $mediaPonderadaDiasNumerico = $weightedDaysNumerator / $totalWeightForDays; }
    } catch (Exception $e) { $error = 'Erro processar títulos: ' . $e->getMessage(); }
}

// 7. Processar Imagem Gráfico - REMOVIDO

// 8. Lidar com erros antes de gerar PDF
if ($error) {
    // Mostra erro mais detalhado se display_errors estiver ativo
    header("Content-Type: text/plain; charset=utf-8");
    die("Erro ao gerar dados para o Recibo: " . htmlspecialchars($error));
}

// Lógica para quando é Empréstimo
if ($tipoOperacao === 'emprestimo' && $valorEmprestimo > 0) {
    $totalLiquidoPago = $valorEmprestimo;
}

// 9. Gerar o PDF para o Cliente
$pdf = new PDF_Cliente('P','mm','A4');
if ($tipoOperacao === 'emprestimo') {
    $pdf->tituloPdf = 'Simulação de Empréstimo';
    $pdf->isEmprestimo = true;
}
$pdf->AliasNbPages(); $pdf->SetAutoPageBreak(true, 20); $pdf->AddPage(); $pdf->SetFont('Arial','',10); $pdf->SetLeftMargin(15); $pdf->SetRightMargin(15);

// --- CONTEÚDO DO PDF ---
$pdf->SectionTitle('Parâmetros da Operação');
$pdf->ParameterLine($tipoOperacao === 'emprestimo' ? 'Tomador de Empréstimo (Sacado)' : 'Cedente', $cedenteNome);
$pdf->ParameterLine('Data da Operação', pdfFormatDate($dataOperacaoStr));
// *** CORREÇÃO: Chama a função pdfFormatPercent que agora existe ***
$pdf->ParameterLine($tipoOperacao === 'emprestimo' ? 'Taxa de Juros' : 'Taxa de Desconto Nominal', pdfFormatPercent($taxaMensal) . ' ao mês');

if ($tipoOperacao === 'emprestimo') {
    $pdf->ParameterLine('Possui Garantia?', $temGarantia ? 'Sim' : 'Não');
    if ($temGarantia && !empty($descricaoGarantia)) {
        $pdf->ParameterLine('Desc. da Garantia', $descricaoGarantia);
    }
}
$pdf->Ln(5);

if (!empty($notas)) { $pdf->SectionTitle('Anotações'); $pdf->MultiCell(0, 5, pdfEncodeText($notas)); $pdf->Ln(5); }

$pdf->SectionTitle($tipoOperacao === 'emprestimo' ? 'Parcelas do Empréstimo' : 'Detalhamento dos Títulos');
if ($tipoOperacao === 'emprestimo') {
    $header_cliente = ['Vl Original', 'Vencimento', 'Sacado (Devedor)', 'Dias'];
} else {
    $header_cliente = ['Vl Original', 'Vencimento', 'Sacado (Devedor)', 'Dias', 'Vl Líquido Recebido'];
}
$pdf->BasicTable($header_cliente, $calculatedTitles);

$pdf->SectionTitle('Resultados Totais da Operação');
$pdf->ParameterLine('Média Ponderada de Dias', round($mediaPonderadaDiasNumerico) . ' dias');
$pdf->ParameterLine('Total Valor Original', pdfFormatCurrency($totalOriginal));
if ($tipoOperacao !== 'emprestimo') {
    $pdf->ParameterLine('Total Desconto/Juros', pdfFormatCurrency($totalOriginal - $totalLiquidoPago));
}
$pdf->ParameterLine($tipoOperacao === 'emprestimo' ? 'Valor Total do Empréstimo' : 'Total Líquido Pago ao Cliente', pdfFormatCurrency($totalLiquidoPago));
// REMOVIDO: Lucro, Margem, Retorno
$pdf->Ln(5);

// REMOVIDO: Seção Gráfico

// 10. Output PDF
if (ob_get_level()) ob_end_clean();

// Definir nome do arquivo baseado no sacado
$nomeArquivo = 'simulacao_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower($cedenteNome)) . '_' . date('Ymd') . '.pdf';

$pdf->Output('D', $nomeArquivo, true);

// 11. Limpeza Imagem Temp - REMOVIDO

exit;
?>
