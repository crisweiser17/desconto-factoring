<?php
// gerar_recibo_cliente.php - Gera PDF/Recibo para cliente usando cálculos centralizados

// Proteção - Adicione se implementou o login
 require_once 'auth_check.php';

// Habilitar exibição de erros TEMPORARIAMENTE para depuração
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
// APÓS RESOLVER, COMENTE AS 3 LINHAS ACIMA EM PRODUÇÃO!

// 1. Incluir dependências
require('fpdf/fpdf.php'); // Verifique o caminho para a biblioteca FPDF
require_once 'db_connection.php'; // Conexão $pdo
require_once 'functions.php'; // Para formatCurrency

// 2. Funções Auxiliares Globais (similares ao export_pdf_cliente)
function pdfFormatCurrency($value) { return 'R$ ' . number_format($value ?? 0, 2, ',', '.'); }
function pdfFormatPercent($value) { return number_format(($value ?? 0) * 100, 2, ',', '.') . '%'; } // Usado para taxa
function pdfFormatDate($dateStr) { if(empty($dateStr)) return '--'; try { return (new DateTime($dateStr))->format('d/m/Y'); } catch (Exception $e){ return 'Data Inválida'; } }

// Função para converter UTF-8 para ISO-8859-1 (FPDF compatibility)
function pdfText($text) {
    if (empty($text)) return '';
    // Converter UTF-8 para ISO-8859-1 para compatibilidade com FPDF
    return iconv('UTF-8', 'ISO-8859-1//IGNORE', $text);
}

// 3. Definição da Classe PDF personalizada (reutilizada)
class PDF_Cliente extends FPDF {
    function Header() {
        $this->SetFont('Arial','B',14);
        $this->Cell(0,10,pdfText('Comprovante da Operação de Desconto'),0,1,'C'); // Título alterado
        $this->Ln(5);
    }
    function Footer() {
        $this->SetY(-15); $this->SetFont('Arial','I',8);
        $this->Cell(0,10,pdfText('Página ').$this->PageNo().'/{nb}',0,0,'C');
    }
    function SectionTitle($label) {
        $this->SetFont('Arial','B',11); $this->SetFillColor(230,230,230);
        $this->Cell(0,7,pdfText($label),0,1,'L',true); $this->Ln(4); $this->SetFont('Arial','',10);
    }
    function ParameterLine($key, $value) {
        $this->SetFont('Arial','B',10); $this->Cell(60, 6, pdfText($key).': ', 0, 0, 'L');
        $this->SetFont('Arial','',10); $this->MultiCell(0, 6, pdfText($value), 0, 'L'); $this->Ln(1);
    }
    // Tabela ajustada para cliente
    function BasicTable($header_cliente, $data) {
        $this->SetFillColor(230,230,230); $this->SetFont('Arial','B',8);
        // Larguras: Vl Orig(50), Venc(40), Dias(20), Vl Liq(65) = 175
        $widths = [50, 40, 20, 65];
        for($i=0; $i<count($header_cliente); $i++) { $this->Cell($widths[$i], 7, $header_cliente[$i], 1, 0, 'C', true); }
        $this->Ln(); $this->SetFont('Arial','',8); $this->SetFillColor(255);
        $fill = false; // Para zebrar a tabela opcionalmente
        foreach($data as $row){
            // $this->SetFillColor(245, 245, 245); // Cor para zebrado
            // $fill = !$fill; // Alterna cor
            $this->Cell($widths[0], 6, pdfFormatCurrency($row['original']), 'LR', 0, 'R', $fill);
            $this->Cell($widths[1], 6, pdfFormatDate($row['vencimento']), 'LR', 0, 'C', $fill);
            $this->Cell($widths[2], 6, $row['dias'], 'LR', 0, 'C', $fill);
            $this->Cell($widths[3], 6, pdfFormatCurrency($row['liquido']), 'LR', 0, 'R', $fill);
            $this->Ln();
        }
        $this->Cell(array_sum($widths), 0, '', 'T'); // Linha final da tabela
        $this->Ln(5);
    }
}

// --- INÍCIO DA LÓGICA DO SCRIPT ---
$operacao_id = null;
$operacao = null;
$recebiveis = [];
$calculatedTitles = []; // Para a tabela do PDF
$error_message = null;

// 1. Obter e Validar ID da Operação da URL
if (!isset($_GET['id']) || !is_numeric($_GET['id']) || (int)$_GET['id'] <= 0) {
    $error_message = "ID da operação inválido ou não fornecido.";
} else {
    $operacao_id = (int)$_GET['id'];

    // 2. Buscar dados da Operação e do Cedente no Banco
    try {
        $sql_op = "SELECT o.*, COALESCE(s.empresa, s.nome, (SELECT COALESCE(sac.empresa, sac.nome) FROM recebiveis r2 JOIN clientes sac ON r2.sacado_id = sac.id WHERE r2.operacao_id = o.id LIMIT 1)) AS cedente_nome
                   FROM operacoes o
                   LEFT JOIN clientes s ON o.cedente_id = s.id
                   WHERE o.id = :id";
        $stmt_op = $pdo->prepare($sql_op);
        $stmt_op->bindParam(':id', $operacao_id, PDO::PARAM_INT);
        $stmt_op->execute();
        $operacao = $stmt_op->fetch(PDO::FETCH_ASSOC);

        if (!$operacao) {
            $error_message = "Operação com ID " . htmlspecialchars($operacao_id) . " não encontrada.";
        } else {
            // 3. Buscar Recebíveis associados
            $sql_rec = "SELECT * FROM recebiveis WHERE operacao_id = :operacao_id ORDER BY data_vencimento ASC";
            $stmt_rec = $pdo->prepare($sql_rec);
            $stmt_rec->bindParam(':operacao_id', $operacao_id, PDO::PARAM_INT);
            $stmt_rec->execute();
            $recebiveis = $stmt_rec->fetchAll(PDO::FETCH_ASSOC);

            // 4. Usar EXATAMENTE a mesma lógica do detalhes_operacao.php
            require_once 'funcoes_calculo_central.php';
            
            // Obter os flags de IOF e taxa da operação
            $cobrarIOFCliente = filter_var($operacao['cobrar_iof_cliente'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $incorreCustoIOF = filter_var($operacao['incorre_custo_iof'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $taxaMensal = (float)($operacao['taxa_mensal'] ?? 0);
            
            // Preparar títulos no formato esperado pelas funções centralizadas
            $titulos_para_calculo = [];
            foreach ($recebiveis as $r) {
                $titulos_para_calculo[] = [
                    'valor' => (float)$r['valor_original'],
                    'data_vencimento' => $r['data_vencimento']
                ];
            }
            
            // Usar função centralizada para calcular totais (IGUAL ao detalhes_operacao.php)
            $totais_calculados = calcularTotaisOperacao(
                $titulos_para_calculo,
                $operacao['data_operacao'],
                $taxaMensal,
                $cobrarIOFCliente,
                [] // Compensações serão processadas separadamente
            );
            
            // Atualizar variáveis de totais com valores calculados
            $totalOriginalReal = $totais_calculados['total_original'];
            $totalLiquidoPagoReal = (float)($operacao['total_liquido_pago_calc'] ?? 0); // Do banco
            $totalLucroLiquidoCalculado = (float)($operacao['total_lucro_liquido_calc'] ?? 0); // Do banco
            $totalIOFTeoricoCalculado = $totais_calculados['total_iof'];
            
            // Preparar dados para tabela do PDF usando dados calculados
            $calculatedTitles = [];
            foreach ($recebiveis as $index => $r) {
                $detalhe_titulo = $totais_calculados['detalhes_titulos'][$index];
                
                $calculatedTitles[] = [
                    'original'   => (float)$detalhe_titulo['valor_original'],
                    'vencimento' => $r['data_vencimento'],
                    'dias'       => (int)$detalhe_titulo['dias'],
                    'liquido'    => (float)$detalhe_titulo['valor_liquido_pago']
                ];
            }
            
            // Calcular média ponderada de dias
            $mediaPonderadaDiasNumerico = $totais_calculados['media_dias'];
            
            // Verificar compensação detalhada (CORRIGIDO para calcular saldo correto)
            $compensacao = null;
            $compensacao_detalhes = [];
            if ($operacao['valor_total_compensacao'] > 0) {
                try {
                    $sql_comp = "SELECT c.*, r.valor_original as recebivel_valor_original,
                                       r.data_vencimento as recebivel_data_vencimento,
                                       r.id as recebivel_id
                                FROM compensacoes c
                                INNER JOIN recebiveis r ON c.recebivel_compensado_id = r.id
                                WHERE c.operacao_principal_id = :operacao_id
                                ORDER BY c.data_compensacao ASC";
                    $stmt_comp = $pdo->prepare($sql_comp);
                    $stmt_comp->bindParam(':operacao_id', $operacao_id, PDO::PARAM_INT);
                    $stmt_comp->execute();
                    $compensacoes_data = $stmt_comp->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (!empty($compensacoes_data)) {
                        $compensacao = [
                            'temCompensacao' => true,
                            'valorTotal' => (float)$operacao['valor_total_compensacao'],
                            'quantidadeTitulos' => count($compensacoes_data)
                        ];
                        
                        // Preparar detalhes para exibição (CORRIGIDO)
                        require_once 'funcoes_compensacao.php';
                        
                        foreach ($compensacoes_data as $comp) {
                            $valor_compensado_nesta_operacao = (float)$comp['valor_compensado'];
                            $recebivel_id = $comp['recebivel_id'];
                            
                            // Obter status atual do recebível (após todas as compensações)
                            $status_atual = verificarStatusRecebivel($recebivel_id, $pdo);
                            $saldo_atual = $status_atual['saldo_disponivel'] ?? 0;
                            
                            // Calcular saldo antes desta compensação específica
                            $saldo_antes_desta_compensacao = $saldo_atual + $valor_compensado_nesta_operacao;
                            
                            $compensacao_detalhes[] = [
                                'valor_compensado' => $valor_compensado_nesta_operacao,
                                'recebivel_id' => $recebivel_id,
                                'recebivel_vencimento' => $comp['recebivel_data_vencimento'],
                                'recebivel_valor_original' => (float)$comp['recebivel_valor_original'],
                                'saldo_antes_compensacao' => $saldo_antes_desta_compensacao,
                                'saldo_restante' => $saldo_atual,
                                'tipo_compensacao' => $comp['tipo_compensacao'] ?? 'total'
                            ];
                        }
                    }
                } catch (Exception $e) {
                    // Em caso de erro, manter compensação como null
                }
            }
        }

    } catch (PDOException $e) {
        error_log("Erro DB em gerar_recibo_cliente.php (ID: $operacao_id): " . $e->getMessage());
        $error_message = "Erro ao buscar dados da operação no banco de dados.";
    } catch (Exception $e) {
        error_log("Erro Geral em gerar_recibo_cliente.php (ID: $operacao_id): " . $e->getMessage());
        $error_message = "Ocorreu um erro inesperado ao processar as datas.";
    }
}

// 5. Se houve erro, exibe e para
if ($error_message) {
    header("Content-Type: text/plain; charset=utf-8");
    die("Erro ao gerar Recibo: " . htmlspecialchars($error_message));
}

// 6. Gerar o PDF para o Cliente
$pdf = new PDF_Cliente('P','mm','A4');
$pdf->AliasNbPages(); $pdf->SetAutoPageBreak(true, 20); $pdf->AddPage(); $pdf->SetFont('Arial','',10); $pdf->SetLeftMargin(15); $pdf->SetRightMargin(15);

// --- CONTEÚDO DO PDF ---
$pdf->SectionTitle('Dados da Operação');
$pdf->ParameterLine('Cliente (Sacado)', $operacao['cedente_nome'] ?? 'N/A');
$pdf->ParameterLine('Data da Operação', pdfFormatDate($operacao['data_operacao']));
$pdf->ParameterLine('Taxa de Desconto Aplicada', pdfFormatPercent($operacao['taxa_mensal']) . ' ao mês'); // Taxa salva
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
// Adicionar outras informações da operação se relevante (ID da Operação?)
$pdf->ParameterLine('ID da Operação', $operacao['id']);
$pdf->Ln(5);

if (!empty($operacao['observacoes'])) {
    $pdf->SectionTitle('Observações');
    $pdf->MultiCell(0, 5, pdfText($operacao['observacoes']));
    $pdf->Ln(5);
}

$pdf->SectionTitle('Detalhamento dos Títulos Negociados');
$header_cliente = [pdfText('Vl Original'), pdfText('Vencimento'), pdfText('Dias Prazo'), pdfText('Vl Líquido Recebido')]; // Cabeçalho ajustado
$pdf->BasicTable($header_cliente, $calculatedTitles); // Usa dados formatados em $calculatedTitles

$pdf->SectionTitle('Totais da Operação');
$pdf->ParameterLine('Média Ponderada de Dias', round($mediaPonderadaDiasNumerico) . ' dias');
$pdf->ParameterLine('Total Valor Original dos Títulos', pdfFormatCurrency($totalOriginalReal)); // Usa total real dos recebíveis

// Adicionar detalhamento da compensação se aplicável (igual ao detalhes_operacao.php)
if ($operacao['valor_total_compensacao'] > 0) {
    $pdf->ParameterLine('Abatimento', pdfFormatCurrency(-$operacao['valor_total_compensacao']));
    
    // Calcular custo da antecipação (igual ao detalhes_operacao.php linhas 435-450)
    $custo_antecipacao_total = 0;
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
        }
    } catch (Exception $e) {
        // Em caso de erro, manter custo como 0
    }
    
    $pdf->ParameterLine('Custo da Antecipação', pdfFormatCurrency($custo_antecipacao_total));
}

$pdf->ParameterLine('Total Líquido Recebido pelo Cliente', pdfFormatCurrency($totalLiquidoPagoReal)); // Usa total real dos recebíveis
$pdf->Ln(5);

// Adicionar seção de compensação se aplicável
if (!empty($compensacao) && !empty($compensacao['temCompensacao']) && $compensacao['temCompensacao']) {
    $pdf->SectionTitle('Compensação - Encontro de Contas');
    $pdf->ParameterLine('Valor Total da Compensação', pdfFormatCurrency($compensacao['valorTotal']));
    $pdf->ParameterLine('Quantidade de Títulos Compensados', $compensacao['quantidadeTitulos']);
    $pdf->Ln(3);
    
    // Mostrar detalhes de cada compensação
    if (!empty($compensacao_detalhes)) {
        $pdf->SetFont('Arial','B',10);
        $pdf->Cell(0, 6, pdfText('Detalhes das Compensações:'), 0, 1, 'L');
        $pdf->SetFont('Arial','',9);
        
        foreach ($compensacao_detalhes as $detalhe) {
            $texto = sprintf(
                '• R$ %s abatidos do recebível ID %d com vencimento em %s. Valor original: R$ %s, saldo anterior: R$ %s, saldo atual: R$ %s',
                number_format($detalhe['valor_compensado'], 2, ',', '.'),
                $detalhe['recebivel_id'],
                pdfFormatDate($detalhe['recebivel_vencimento']),
                number_format($detalhe['recebivel_valor_original'], 2, ',', '.'),
                number_format($detalhe['saldo_antes_compensacao'], 2, ',', '.'),
                number_format($detalhe['saldo_restante'], 2, ',', '.')
            );
            $pdf->MultiCell(0, 5, pdfText($texto), 0, 'L');
            $pdf->Ln(2);
        }
    }
    $pdf->Ln(5);
}

// 7. Output PDF
if (ob_get_level()) ob_end_clean(); // Limpa buffer antes de output

// Definir nome do arquivo baseado no tipo (simulação ou operação real)
$isSimulacao = isset($isSimulacao) ? $isSimulacao : false;
if ($isSimulacao) {
    // Para simulação: simulacao_[nome_do_sacado]
    $nomeArquivo = 'simulacao_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower($cedenteNome ?? '')) . '_' . date('Ymd') . '.pdf';
} else {
    // Para operação real: recibo_[ID]
    $nomeArquivo = 'recibo_' . ($operacao_id ?? 'avulso') . '_' . date('Ymd') . '.pdf';
}

$pdf->Output('D', $nomeArquivo, true);

exit;
?>
