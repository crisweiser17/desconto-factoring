<?php
// export_pdf.php

// Proteção removida para simulações - não requer autenticação
// require_once 'auth_check.php';

// Habilitar exibição de erros APENAS para depuração - REMOVA ou comente em produção!
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

// 1. Incluir dependências
require('fpdf/fpdf.php'); // Verifique o caminho
require_once 'db_connection.php'; // Conexão $pdo

// 2. Constantes e Funções Auxiliares Globais
define('IOF_ADICIONAL_RATE', 0.0038);
define('IOF_DIARIA_RATE', 0.000082);
function pdfFormatCurrency($value) { return 'R$ ' . number_format($value ?? 0, 2, ',', '.'); }
function pdfFormatPercent($value) { return number_format(($value ?? 0) * 100, 2, ',', '.') . '%'; }
function pdfFormatDate($dateStr) { if(empty($dateStr)) return ''; try { return (new DateTime($dateStr))->format('d/m/Y'); } catch (Exception $e){ return $dateStr; } }

// 3. Definição da Classe PDF personalizada
class PDF extends FPDF { /* ... (código da classe PDF mantido) ... */
    public $tituloPdf = 'Resumo da Operação de Desconto';
    public $isEmprestimo = false;

    function Header() { $this->SetFont('Arial','B',14); $this->Cell(0,10,pdfEncodeText($this->tituloPdf),0,1,'C'); $this->Ln(5); }
    function Footer() { $this->SetY(-15); $this->SetFont('Arial','I',8); $this->Cell(0,10,pdfEncodeText('Página ').$this->PageNo().'/{nb}',0,0,'C'); }
    function SectionTitle($label) { $this->SetFont('Arial','B',11); $this->SetFillColor(230,230,230); $this->Cell(0,7,pdfEncodeText($label),0,1,'L',true); $this->Ln(4); $this->SetFont('Arial','',10); }
    function ParameterLine($key, $value) { $this->SetFont('Arial','B',10); $this->Cell(60, 6, pdfEncodeText($key.': '), 0, 0, 'L'); $this->SetFont('Arial','',10); $this->MultiCell(0, 6, pdfEncodeText($value), 0, 'L'); $this->Ln(1); }
    function BasicTable($header, $data) { 
        $this->SetFillColor(230,230,230); 
        $this->SetFont('Arial','B',8); 
        if ($this->isEmprestimo) {
            $widths = [30, 25, 45, 15, 30, 0, 45]; // Ajuste colunas (ignora IOF e muda Vl Liquido se quiser, mas aqui vou manter as posicoes ou esconder o IOF)
            // Na verdade, vou desenhar menos colunas:
            $widths = [35, 30, 55, 20, 50]; // Original, Vencimento, Sacado, Dias, Vl Presente/Líquido
        } else {
            $widths = [30, 22, 35, 12, 30, 25, 30]; 
        }

        for($i=0;$i<count($header);$i++) {
            $this->Cell($widths[$i],7,pdfEncodeText($header[$i]),1,0,'C',true); 
        }
        $this->Ln(); 
        $this->SetFont('Arial','',8); 
        $this->SetFillColor(255); 
        
        foreach($data as $row) {
            $this->Cell($widths[0],6,pdfFormatCurrency($row['original']),'LR',0,'R');
            $this->Cell($widths[1],6,pdfFormatDate($row['vencimento']),'LR',0,'C');
            if ($this->isEmprestimo) {
                $this->Cell($widths[2],6,pdfEncodeText(substr($row['sacado'] ?? 'N/D', 0, 25)),'LR',0,'L');
                $this->Cell($widths[3],6,$row['dias'],'LR',0,'C');
                $this->Cell($widths[4],6,pdfFormatCurrency($row['presente']),'LR',0,'R');
            } else {
                $this->Cell($widths[2],6,pdfEncodeText(substr($row['sacado'] ?? 'N/D', 0, 15)),'LR',0,'L');
                $this->Cell($widths[3],6,$row['dias'],'LR',0,'C');
                $this->Cell($widths[4],6,pdfFormatCurrency($row['presente']),'LR',0,'R');
                $this->Cell($widths[5],6,pdfFormatCurrency($row['iof']),'LR',0,'R');
                $this->Cell($widths[6],6,pdfFormatCurrency($row['liquido']),'LR',0,'R');
            }
            $this->Ln();
        } 
        $this->Cell(array_sum($widths),0,'','T'); 
        $this->Ln(5); 
    }
}

function pdfEncodeText($text) {
    if (empty($text)) return '';
    return mb_convert_encoding($text, 'ISO-8859-1', 'UTF-8');
}

// 4. Receber Dados POST
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
$chartImageData = isset($_POST['chartImageData']) ? $_POST['chartImageData'] : null;

// 4.5 Buscar nome do Cedente/Tomador
$cedenteNome = 'N/D';
if ($cedente_id && $cedente_id > 0) {
    try {
        if ($tipoOperacao === 'emprestimo') {
            $stmtSacado = $pdo->prepare("SELECT empresa FROM sacados WHERE id = :id"); 
        } else {
            $stmtSacado = $pdo->prepare("SELECT empresa FROM cedentes WHERE id = :id"); 
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
        error_log("Erro PDF Cedente: " . $e->getMessage());
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
            $stmtSacados = $pdo->prepare("SELECT id, empresa FROM sacados WHERE id IN ($placeholders)");
            $stmtSacados->execute($sacadosIdsLimpos);
            $resultSacados = $stmtSacados->fetchAll(PDO::FETCH_ASSOC);
            foreach ($resultSacados as $sacado) {
                $sacadosNomes[$sacado['id']] = $sacado['empresa'];
            }
        }
    } catch (PDOException $e) {
        error_log("Erro PDF Sacados: " . $e->getMessage());
    }
}

// 5. Combinar Títulos (incluindo sacados)
$titulos = []; $count = min(count($valoresOriginais), count($datasVencimento), count($sacadosIds)); for ($i = 0; $i < $count; $i++) { if (!empty($valoresOriginais[$i]) && !empty($datasVencimento[$i]) && (float)$valoresOriginais[$i] > 0) { $sacadoId = !empty($sacadosIds[$i]) ? $sacadosIds[$i] : null; $sacadoNome = $sacadoId && isset($sacadosNomes[$sacadoId]) ? $sacadosNomes[$sacadoId] : 'Não informado'; $titulos[] = [ 'valorOriginal' => (float) $valoresOriginais[$i], 'dataVencimento' => $datasVencimento[$i], 'sacadoId' => $sacadoId, 'sacadoNome' => $sacadoNome ]; } }

// 6. Recalcular Tudo para o PDF (*** LÓGICA IOF CORRIGIDA ***)
$totalOriginal = 0; $totalPresente = 0; $totalIOF = 0; // IOF Teórico
$totalLiquidoPago = 0; $totalLucroLiquido = 0; $weightedDaysNumerator = 0; $totalWeightForDays = 0;
$mediaPonderadaDiasNumerico = 0; $calculatedTitles = []; $error = null;
$totalLucroPercentual = 0; $retornoMensalDecimal = 0; $dataOperacao = null;

if ($taxaMensal <= 0) { $error = 'Taxa mensal deve ser maior que zero.'; }
elseif (empty($titulos)) { $error = 'Nenhum título válido fornecido.'; }
else {
    try { // Valida data operação primeiro
        $dataOperacao = new DateTime($dataOperacaoStr);
        $dataOperacao->setTime(0, 0, 0);
    } catch (Exception $e) { $error = 'Data da operação inválida: ' . htmlspecialchars($dataOperacaoStr); }
}

if (!$error) {
    try {
        $titulosTemp = [];
        
        // Primeiro loop: calcular valores presentes e acumular totais
        foreach ($titulos as $titulo) {
            $valorOriginalTitulo = $titulo['valorOriginal'];
            $dataVencimentoStrTitulo = $titulo['dataVencimento'];
            $dataVencimentoTitulo = new DateTime($dataVencimentoStrTitulo);
            $dataVencimentoTitulo->setTime(0, 0, 0);

            $dias = 0;
            if ($dataVencimentoTitulo < $dataOperacao) { $error = "Vencimento (" . $dataVencimentoTitulo->format('d/m/Y') . ") anterior à operação (" . $dataOperacao->format('d/m/Y') . ")."; break; }
            elseif ($dataVencimentoTitulo >= $dataOperacao) { $interval = $dataOperacao->diff($dataVencimentoTitulo); $dias = $interval->days; }

            $valorPresenteTitulo = $valorOriginalTitulo;
            if ($dias > 0 && (1 + $taxaMensal) > 1e-9) { $valorPresenteTitulo = $valorOriginalTitulo / pow(1 + $taxaMensal, $dias / 30.0); }
            elseif ($dias > 0) { $error = $error ?? 'Taxa inválida.'; $valorPresenteTitulo = 0; }

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
        
        // Segundo loop: calcular IOF sobre valor original de cada título e processar títulos
        if (!$error && !empty($titulosTemp)) {
            
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
                
                // Custo real de IOF para sua empresa
                $custoRealIOFParaVoce = $incorreIOF ? $iofTitulo : 0;
                
                // Valor Líquido que sua empresa PAGA ao cliente por este título
                $valorLiquidoPagoTitulo = max(0, $valorPresenteTitulo - $iofDescontadoDoCliente);
                
                // Lucro Líquido Real para sua empresa
                $lucroLiquidoTitulo = $valorOriginalTitulo - $valorLiquidoPagoTitulo - $custoRealIOFParaVoce;
                
                // Acumula totais finais
                $totalLiquidoPago += $valorLiquidoPagoTitulo;
                $totalLucroLiquido += $lucroLiquidoTitulo;
                
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
                    'presente' => $valorPresenteTitulo,
                    'iof' => $iofTitulo, // Mostra IOF do título na tabela
                    'liquido' => $valorLiquidoPagoTitulo,
                    'sacado' => $tituloOriginal ? $tituloOriginal['sacadoNome'] : 'N/D'
                ];
            }
        }

        // Calcula totais derivados se não houve erro
        if (!$error) {
            if ($totalWeightForDays > 0) { $mediaPonderadaDiasNumerico = $weightedDaysNumerator / $totalWeightForDays; }

            // Se for empréstimo, o valor líquido deve ser cravado no valorEmprestimo
            if ($tipoOperacao === 'emprestimo' && $valorEmprestimo > 0) {
                $totalLiquidoPago = $valorEmprestimo;
                $totalPresente = $valorEmprestimo;
                $totalLucroLiquido = max(0, $totalOriginal - $valorEmprestimo);
            }

            $totalLucroPercentual = ($totalOriginal > 0) ? ($totalLucroLiquido / $totalOriginal) : 0;
            if ($totalLiquidoPago > 0 && $mediaPonderadaDiasNumerico > 0) {
                $taxaPeriodo = $totalLucroLiquido / $totalLiquidoPago; $base = 1 + $taxaPeriodo;
                $expoente = 30.0 / $mediaPonderadaDiasNumerico;
                if ($base >= 0) { $retornoMensalDecimal = pow($base, $expoente) - 1; }
                else { $retornoMensalDecimal = -1; }
            }
        }
    } catch (Exception $e) { $error = 'Erro ao processar títulos para PDF: ' . $e->getMessage(); }
} // Fim if !$error inicial

// 7. Processar e Salvar Imagem do Gráfico (mantido)
$chartImagePath = null; $tempImageCleanupNeeded = false;
if (!$error && $chartImageData && strpos($chartImageData, 'data:image/png;base64,') === 0) { /* ... lógica salvar imagem ... */
    $imageData = base64_decode(str_replace('data:image/png;base64,', '', $chartImageData)); if ($imageData) { $tempDir = sys_get_temp_dir(); if (!@is_writable($tempDir)) { $tempDir = __DIR__; } if (@is_writable($tempDir)) { $tempImageFilename = rtrim($tempDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'chart_' . uniqid() . '.png'; if (file_put_contents($tempImageFilename, $imageData)) { $chartImagePath = $tempImageFilename; $tempImageCleanupNeeded = true; } else { $error = ($error ?? '').' Erro: Salvar img temp.'; } } else { $error = ($error ?? '').' Erro: Dir temp não gravável.'; } } else { $error = ($error ?? '').' Erro: Decodificar img.'; }
}


// 8. Lidar com erros antes de gerar PDF (mantido)
$mostrarErroTexto = $error && !$chartImagePath;
if ($mostrarErroTexto) { header("Content-Type: text/plain; charset=utf-8"); die("Erro PDF: " . htmlspecialchars($error)); }

// 9. Gerar o PDF
$pdf = new PDF('P','mm','A4'); /* ... (Config PDF) ... */
if ($tipoOperacao === 'emprestimo') {
    $pdf->tituloPdf = 'Análise Completa de Empréstimo';
    $pdf->isEmprestimo = true;
}
$pdf->AliasNbPages(); $pdf->SetAutoPageBreak(true, 20); $pdf->AddPage(); $pdf->SetFont('Arial','',10); $pdf->SetLeftMargin(15); $pdf->SetRightMargin(15);

// Adicionar Conteúdo
$pdf->SectionTitle('Parâmetros da Operação');
$pdf->ParameterLine($tipoOperacao === 'emprestimo' ? 'Tomador de Empréstimo (Sacado)' : 'Cedente', $cedenteNome);
$pdf->ParameterLine('Data da Operação', pdfFormatDate($dataOperacaoStr)); // Exibe data op
$pdf->ParameterLine($tipoOperacao === 'emprestimo' ? 'Taxa de Juros' : 'Taxa de Desconto Nominal', pdfFormatPercent($taxaMensal) . ' ao mês');
if ($tipoOperacao !== 'emprestimo') {
    $pdf->ParameterLine('Você Incorre Custo IOF', $incorreIOF ? 'Sim' : 'Não');
    // Mostra "Cobrar IOF" mesmo que input estivesse escondido, para clareza no PDF
    $pdf->ParameterLine('Cobrar IOF do Cliente', $cobrarIOF ? 'Sim' : 'Não');
} else {
    $pdf->ParameterLine('Possui Garantia?', $temGarantia ? 'Sim' : 'Não');
    if ($temGarantia && !empty($descricaoGarantia)) {
        $pdf->ParameterLine('Desc. da Garantia', $descricaoGarantia);
    }
}
$pdf->Ln(5);

// Notas (mantido)
if (!empty($notas)) { $pdf->SectionTitle('Anotações'); $pdf->MultiCell(0, 5, pdfEncodeText($notas)); $pdf->Ln(5); }

// Detalhamento Títulos
$pdf->SectionTitle($tipoOperacao === 'emprestimo' ? 'Parcelas do Empréstimo' : 'Detalhamento dos Títulos');
if ($tipoOperacao === 'emprestimo') {
    $header = ['Vl Original', 'Vencimento', 'Sacado', 'Dias', 'Vl Presente'];
} else {
    $header = ['Vl Original', 'Vencimento', 'Sacado', 'Dias', 'Vl Presente', 'IOF Calc.', 'Vl Líquido Pgo.']; // Inclui coluna Sacado
}
$pdf->BasicTable($header, $calculatedTitles); // Usa $calculatedTitles com valores corrigidos

// Resultados Totais (Usa totais corrigidos)
$pdf->SectionTitle('Resultados Totais da Operação');
$pdf->ParameterLine('Média Ponderada de Dias', round($mediaPonderadaDiasNumerico) . ' dias');
$pdf->ParameterLine('Total Valor Original', pdfFormatCurrency($totalOriginal));
if ($tipoOperacao !== 'emprestimo') {
    $pdf->ParameterLine('Total Valor Presente', pdfFormatCurrency($totalPresente));
    $pdf->ParameterLine('Total IOF Calculado', pdfFormatCurrency($totalIOF)); // IOF Teórico Total
}
$pdf->ParameterLine($tipoOperacao === 'emprestimo' ? 'Valor Total do Empréstimo' : 'Total Líquido Pago ao Cliente', pdfFormatCurrency($totalLiquidoPago)); // Líquido Real
// Lucro, Margem, Retorno (com valores corrigidos)
$pdf->SetFont('Arial','B',10); $pdf->Cell(60, 6, pdfEncodeText('Total Lucro Líquido: '), 0, 0, 'L'); $pdf->SetFont('Arial','',10); $pdf->Cell(0, 6, pdfFormatCurrency($totalLucroLiquido), 0, 1, 'L');
$pdf->SetFont('Arial','B',10); $pdf->Cell(60, 6, pdfEncodeText('Margem Total (% / Original): '), 0, 0, 'L'); $pdf->SetFont('Arial','',10); $pdf->Cell(0, 6, pdfFormatPercent($totalLucroPercentual), 0, 1, 'L');
$pdf->SetFont('Arial','B',10); $pdf->Cell(60, 6, pdfEncodeText('Retorno Mensal (% / Líq. Pago): '), 0, 0, 'L'); $pdf->SetFont('Arial','',10); $pdf->Cell(0, 6, ($retornoMensalDecimal == -1 ? 'N/A' : pdfFormatPercent($retornoMensalDecimal)), 0, 1, 'L');
$pdf->Ln(5);

// Mensagem de erro no PDF, se houver (mantido)
if ($error) { /* ... exibe erro no PDF ... */
    $pdf->Ln(5); $pdf->SetFont('Arial','B',10); $pdf->SetTextColor(255,0,0); $pdf->Cell(0, 6, pdfEncodeText('ATENÇÃO: Erro nos Cálculos!'), 0, 1, 'L'); $pdf->SetFont('Arial','I',9); $pdf->MultiCell(0, 5, pdfEncodeText($error)); $pdf->SetTextColor(0);
}

// Inserir Imagem do Gráfico (mantido)
if ($chartImagePath && file_exists($chartImagePath)) { try { /* ... lógica de inserir imagem ... */ $maxWidth=180;$maxHeight=180;list($imgWidth,$imgHeight)=@getimagesize($chartImagePath);$newWidth=0;$newHeight=0;if($imgWidth&&$imgHeight){$ratio=$imgWidth/$imgHeight;$newWidth=$maxWidth;$newHeight=$newWidth/$ratio;if($newHeight>$maxHeight){$newHeight=$maxHeight;$newWidth=$newHeight*$ratio;}}else{throw new Exception("Dimensões inválidas.");}$altTotalNec=11+$newHeight+5;$yAtual=$pdf->GetY();$mInfDef=20;$espRest=$pdf->GetPageHeight()-$yAtual-$mInfDef;if($espRest<$altTotalNec){$pdf->AddPage('P','A4');}$pdf->SectionTitle('Gráfico - Fluxo de Caixa dos Vencimentos');$xPos=(210-$newWidth)/2;$yPos=$pdf->GetY();$pdf->Image($chartImagePath,$xPos,$yPos,$newWidth,$newHeight,'PNG');} catch (Exception $imgE){$yAErr=$pdf->GetY();$mIErr=20;$eRErr=$pdf->GetPageHeight()-$yAErr-$mIErr;if($eRErr<15){$pdf->AddPage('P','A4');}$pdf->Ln(5);$pdf->SetFont('Arial','I',9);$pdf->SetTextColor(255,0,0);$pdf->MultiCell(0,5,pdfEncodeText('Erro ao inserir gráfico: '.$imgE->getMessage()));$pdf->SetTextColor(0);} }
elseif ($chartImageData && $error) { /* ... exibe erro do gráfico se cálculo falhou ... */ $yAErr=$pdf->GetY();$mIErr=20;$eRErr=$pdf->GetPageHeight()-$yAErr-$mIErr;if($eRErr<20){$pdf->AddPage('P','A4');}$pdf->SectionTitle('Gráfico - Fluxo de Caixa');$pdf->Ln(5);$pdf->SetFont('Arial','I',9);$pdf->SetTextColor(255,0,0);$pdf->MultiCell(0,5,pdfEncodeText('Gráfico não incluído devido a erro no cálculo: '.htmlspecialchars(str_replace('Erro: ','',$error))));$pdf->SetTextColor(0);}


// 10. Output PDF (mantido)
if (ob_get_level()) ob_end_clean();
$pdf->Output('D', 'Analise_Operacao_Desconto_'.date('Ymd_His').'.pdf', true);

// 11. Limpeza Imagem Temp (mantido)
if ($tempImageCleanupNeeded && $chartImagePath && file_exists($chartImagePath)) { register_shutdown_function(function($filename) { @unlink($filename); }, $chartImagePath); }

exit;
?>
