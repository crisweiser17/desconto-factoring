<?php
// gerar_recibo_cliente.php - Gera PDF/Recibo para cliente usando cálculos centralizados
//
// Suporta dois modos:
//   - GET ?id=N      → recibo de operação já salva no banco
//   - POST (form)    → recibo a partir de dados de simulação (não persistidos)

$isSimulacao = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && !isset($_GET['id']);

// Proteção: exigir login apenas para recibo de operação salva.
// Simulação é pública (mesmo comportamento do antigo export_pdf_cliente.php).
if (!$isSimulacao) {
    require_once 'auth_check.php';
}

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
    public $companyName = '';
    public $companyDoc = '';
    public $documentTitle = 'Comprovante da Operação';
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

    // Tabela ajustada para cliente: Vl Original, Vencimento, Dias, Vl Líquido
    function BasicTable($header_cliente, $data) {
        $widths = [45, 40, 25, 70]; // total 180

        // Header
        $this->SetFillColor(31, 58, 95);
        $this->SetTextColor(255, 255, 255);
        $this->SetDrawColor(31, 58, 95);
        $this->SetFont('Arial','B',9);
        for ($i = 0; $i < count($header_cliente); $i++) {
            $this->Cell($widths[$i], 8, $header_cliente[$i], 1, 0, 'C', true);
        }
        $this->Ln();

        // Body com zebra striping
        $this->SetFont('Arial','',9);
        $this->SetTextColor(0, 0, 0);
        $this->SetDrawColor(220, 225, 232);
        $fill = false;
        $totalOriginal = 0; $totalLiquido = 0;
        foreach ($data as $row) {
            if ($fill) $this->SetFillColor(247, 249, 252);
            else $this->SetFillColor(255, 255, 255);
            $this->Cell($widths[0], 7, pdfFormatCurrency($row['original']), 'B', 0, 'R', true);
            $this->Cell($widths[1], 7, pdfFormatDate($row['vencimento']), 'B', 0, 'C', true);
            $this->Cell($widths[2], 7, $row['dias'], 'B', 0, 'C', true);
            $this->Cell($widths[3], 7, pdfFormatCurrency($row['liquido']), 'B', 0, 'R', true);
            $this->Ln();
            $totalOriginal += $row['original'];
            $totalLiquido += $row['liquido'];
            $fill = !$fill;
        }

        // Linha de totais
        $this->SetFont('Arial','B',9);
        $this->SetFillColor(232, 238, 245);
        $this->SetTextColor(31, 58, 95);
        $this->SetDrawColor(31, 58, 95);
        $this->Cell($widths[0], 7, pdfFormatCurrency($totalOriginal), 'T', 0, 'R', true);
        $this->Cell($widths[1] + $widths[2], 7, pdfText('Totais'), 'T', 0, 'C', true);
        $this->Cell($widths[3], 7, pdfFormatCurrency($totalLiquido), 'T', 0, 'R', true);
        $this->Ln(9);
        $this->SetTextColor(0, 0, 0);
        $this->SetDrawColor(0, 0, 0);
        $this->SetFillColor(255, 255, 255);
    }

    // Tabela para modo simulação: inclui coluna Sacado.
    // Empréstimo: Original | Vencimento | Sacado | Dias       (4 cols, sem líquido)
    // Antecipação: Original | Vencimento | Sacado | Dias | Líquido (5 cols)
    function BasicTableSimulacao($data, $isEmprestimo = false) {
        if ($isEmprestimo) {
            $widths = [40, 30, 90, 20]; // 180
            $headers = [pdfText('Vl Original'), pdfText('Vencimento'), pdfText('Sacado'), pdfText('Dias')];
        } else {
            $widths = [35, 28, 60, 17, 40]; // 180
            $headers = [pdfText('Vl Original'), pdfText('Vencimento'), pdfText('Sacado'), pdfText('Dias'), pdfText('Vl Líquido')];
        }

        $this->SetFillColor(31, 58, 95);
        $this->SetTextColor(255, 255, 255);
        $this->SetDrawColor(31, 58, 95);
        $this->SetFont('Arial','B',9);
        for ($i = 0; $i < count($headers); $i++) {
            $this->Cell($widths[$i], 8, $headers[$i], 1, 0, 'C', true);
        }
        $this->Ln();

        $this->SetFont('Arial','',9);
        $this->SetTextColor(0, 0, 0);
        $this->SetDrawColor(220, 225, 232);
        $fill = false;
        $totalOriginal = 0; $totalLiquido = 0;
        foreach ($data as $row) {
            if ($fill) $this->SetFillColor(247, 249, 252);
            else $this->SetFillColor(255, 255, 255);
            $sacadoMax = $isEmprestimo ? 45 : 28;
            $this->Cell($widths[0], 7, pdfFormatCurrency($row['original']), 'B', 0, 'R', true);
            $this->Cell($widths[1], 7, pdfFormatDate($row['vencimento']), 'B', 0, 'C', true);
            $this->Cell($widths[2], 7, pdfText(mb_substr($row['sacado'] ?? 'N/D', 0, $sacadoMax)), 'B', 0, 'L', true);
            $this->Cell($widths[3], 7, $row['dias'], 'B', 0, 'C', true);
            if (!$isEmprestimo) {
                $this->Cell($widths[4], 7, pdfFormatCurrency($row['liquido']), 'B', 0, 'R', true);
            }
            $this->Ln();
            $totalOriginal += $row['original'];
            if (!$isEmprestimo) $totalLiquido += $row['liquido'];
            $fill = !$fill;
        }

        // Linha de totais
        $this->SetFont('Arial','B',9);
        $this->SetFillColor(232, 238, 245);
        $this->SetTextColor(31, 58, 95);
        $this->SetDrawColor(31, 58, 95);
        $this->Cell($widths[0], 7, pdfFormatCurrency($totalOriginal), 'T', 0, 'R', true);
        if ($isEmprestimo) {
            $this->Cell($widths[1] + $widths[2] + $widths[3], 7, pdfText('Total'), 'T', 0, 'C', true);
        } else {
            $this->Cell($widths[1] + $widths[2] + $widths[3], 7, pdfText('Totais'), 'T', 0, 'C', true);
            $this->Cell($widths[4], 7, pdfFormatCurrency($totalLiquido), 'T', 0, 'R', true);
        }
        $this->Ln(9);
        $this->SetTextColor(0, 0, 0);
        $this->SetDrawColor(0, 0, 0);
        $this->SetFillColor(255, 255, 255);
    }
}

// --- INÍCIO DA LÓGICA DO SCRIPT ---
$operacao_id = null;
$operacao = null;
$recebiveis = [];
$calculatedTitles = []; // Para a tabela do PDF
$error_message = null;
$totalOriginalReal = 0;
$totalLiquidoPagoReal = 0;
$mediaPonderadaDiasNumerico = 0;
$compensacao = null;
$compensacao_detalhes = [];

// Campos exclusivos do modo simulação
$tipoOperacaoSim = 'antecipacao';
$valorEmprestimoSim = 0;
$temGarantiaSim = 0;
$descricaoGarantiaSim = '';

if ($isSimulacao) {
    // ===== MODO SIMULAÇÃO: dados via POST, não persistidos =====
    require_once 'funcoes_calculo_central.php';

    $cedente_id = filter_input(INPUT_POST, 'cedente_id', FILTER_VALIDATE_INT);
    $tomador_id = filter_input(INPUT_POST, 'tomador_id', FILTER_VALIDATE_INT);
    $tipoOperacaoSim = isset($_POST['tipoOperacao']) ? trim($_POST['tipoOperacao']) : 'antecipacao';
    $valorEmprestimoSim = isset($_POST['valor_emprestimo']) ? (float)$_POST['valor_emprestimo'] : 0;
    $taxaMensal = isset($_POST['taxaMensal']) ? (float)$_POST['taxaMensal'] / 100 : 0;
    $dataOperacaoStr = isset($_POST['data_operacao']) ? $_POST['data_operacao'] : date('Y-m-d');
    $cobrarIOFCliente = isset($_POST['cobrarIOF']) && $_POST['cobrarIOF'] === 'Sim';
    $temGarantiaSim = isset($_POST['tem_garantia']) ? (int)$_POST['tem_garantia'] : 0;
    $descricaoGarantiaSim = isset($_POST['descricao_garantia']) ? trim($_POST['descricao_garantia']) : '';
    $observacoesSim = isset($_POST['notas']) ? trim($_POST['notas']) : '';

    // Empréstimo: ignora IOF e usa tomador como "cedente" do PDF
    if ($tipoOperacaoSim === 'emprestimo') {
        $cobrarIOFCliente = false;
        $cedente_id = $tomador_id;
    }

    $valoresOriginais = isset($_POST['titulo_valor']) && is_array($_POST['titulo_valor']) ? $_POST['titulo_valor'] : [];
    $datasVencimento  = isset($_POST['titulo_data'])  && is_array($_POST['titulo_data'])  ? $_POST['titulo_data']  : [];
    $sacadosIds       = isset($_POST['titulo_sacado']) && is_array($_POST['titulo_sacado']) ? $_POST['titulo_sacado'] : [];

    // Buscar nome do cedente/tomador
    $cedenteNome = 'Não informado';
    if ($cedente_id && $cedente_id > 0) {
        try {
            $stmt = $pdo->prepare("SELECT empresa, nome FROM clientes WHERE id = :id");
            $stmt->bindParam(':id', $cedente_id, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) $cedenteNome = $row['empresa'] ?: ($row['nome'] ?? 'N/D');
            else      $cedenteNome = 'ID ' . $cedente_id . ' não encontrado';
        } catch (PDOException $e) {
            error_log("Erro PDF Simulação Cedente: " . $e->getMessage());
            $cedenteNome = 'Erro BD';
        }
    }

    // Buscar nomes dos sacados de uma vez
    $sacadosNomes = [];
    $sacadosIdsLimpos = array_filter($sacadosIds, function($id) { return !empty($id) && is_numeric($id); });
    if (!empty($sacadosIdsLimpos)) {
        try {
            $placeholders = str_repeat('?,', count($sacadosIdsLimpos) - 1) . '?';
            $stmt = $pdo->prepare("SELECT id, empresa, nome FROM clientes WHERE id IN ($placeholders)");
            $stmt->execute(array_values($sacadosIdsLimpos));
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $s) {
                $sacadosNomes[$s['id']] = $s['empresa'] ?: ($s['nome'] ?? 'N/D');
            }
        } catch (PDOException $e) {
            error_log("Erro PDF Simulação Sacados: " . $e->getMessage());
        }
    }

    // Validações básicas
    $titulos_para_calculo = [];
    $sacadosPorIndice = [];
    $count = min(count($valoresOriginais), count($datasVencimento));
    for ($i = 0; $i < $count; $i++) {
        $valor = (float)$valoresOriginais[$i];
        if ($valor <= 0 || empty($datasVencimento[$i])) continue;
        $titulos_para_calculo[] = [
            'valor' => $valor,
            'data_vencimento' => $datasVencimento[$i],
        ];
        $sacadoId = $sacadosIds[$i] ?? null;
        $sacadosPorIndice[] = ($sacadoId && isset($sacadosNomes[$sacadoId])) ? $sacadosNomes[$sacadoId] : 'Não informado';
    }

    if ($taxaMensal <= 0) {
        $error_message = 'Taxa mensal deve ser maior que zero.';
    } elseif (empty($titulos_para_calculo)) {
        $error_message = 'Nenhum título válido fornecido.';
    } else {
        try {
            $totais = calcularTotaisOperacao(
                $titulos_para_calculo,
                $dataOperacaoStr,
                $taxaMensal,
                $cobrarIOFCliente,
                []
            );

            $totalOriginalReal = $totais['total_original'];
            $totalLiquidoPagoReal = ($tipoOperacaoSim === 'emprestimo' && $valorEmprestimoSim > 0)
                ? $valorEmprestimoSim
                : $totais['total_liquido_pago'];
            $mediaPonderadaDiasNumerico = $totais['media_dias'];

            foreach ($totais['detalhes_titulos'] as $idx => $detalhe) {
                $calculatedTitles[] = [
                    'original'   => (float)$detalhe['valor_original'],
                    'vencimento' => $titulos_para_calculo[$idx]['data_vencimento'],
                    'dias'       => (int)$detalhe['dias'],
                    'liquido'    => (float)$detalhe['valor_liquido_pago'],
                    'sacado'     => $sacadosPorIndice[$idx] ?? 'N/D',
                ];
            }
        } catch (Exception $e) {
            error_log("Erro PDF Simulação cálculo: " . $e->getMessage());
            $error_message = 'Erro ao processar títulos: ' . $e->getMessage();
        }
    }

    // Monta $operacao virtual para o restante do código consumir
    $operacao = [
        'id'              => null,
        'cedente_nome'    => $cedenteNome,
        'data_operacao'   => $dataOperacaoStr,
        'taxa_mensal'     => $taxaMensal,
        'tipo_pagamento'  => null,
        'observacoes'     => $observacoesSim,
        'valor_total_compensacao' => 0,
    ];
} elseif (!isset($_GET['id']) || !is_numeric($_GET['id']) || (int)$_GET['id'] <= 0) {
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
// Carregar dados da empresa para branding do cabeçalho
$brandingConfig = [];
$brandingPath = __DIR__ . '/config.json';
if (file_exists($brandingPath)) {
    $brandingConfig = json_decode(file_get_contents($brandingPath), true) ?: [];
}
$empresaNome = $brandingConfig['empresa_razao_social'] ?? ($brandingConfig['conta_titular'] ?? '');
$empresaDoc = $brandingConfig['empresa_documento'] ?? ($brandingConfig['conta_documento'] ?? '');

$pdf = new PDF_Cliente('P','mm','A4');
$pdf->AliasNbPages();
$pdf->SetAutoPageBreak(true, 20);
$pdf->SetLeftMargin(15);
$pdf->SetRightMargin(15);
$pdf->SetTopMargin(12);

if ($isSimulacao) {
    $isEmprestimoSim = $tipoOperacaoSim === 'emprestimo';
    $tituloDoc = $isEmprestimoSim ? 'Simulação de Empréstimo' : 'Simulação de Operação';
    $subtituloDoc = pdfFormatDate($operacao['data_operacao'] ?? null);
} else {
    $tituloDoc = 'Comprovante da Operação';
    $subtituloDoc = 'Operação #' . ($operacao_id ?? '—') . ' • ' . pdfFormatDate($operacao['data_operacao'] ?? null);
}
$pdf->setBranding($empresaNome, $empresaDoc, $tituloDoc, $subtituloDoc);

$pdf->AddPage();
$pdf->SetFont('Arial','',10);

// --- CONTEÚDO DO PDF ---
$pdf->SectionTitle($isSimulacao ? 'Parâmetros da Simulação' : 'Dados da Operação');

if ($isSimulacao && $tipoOperacaoSim === 'emprestimo') {
    $pdf->ParameterLine('Tomador (Sacado)', $operacao['cedente_nome'] ?? 'N/A');
} else {
    $pdf->ParameterLine('Cliente (Sacado)', $operacao['cedente_nome'] ?? 'N/A');
}
$pdf->ParameterLine('Data da Operação', pdfFormatDate($operacao['data_operacao']));
$pdf->ParameterLine($isSimulacao && $tipoOperacaoSim === 'emprestimo' ? 'Taxa de Juros' : 'Taxa Aplicada', pdfFormatPercent($operacao['taxa_mensal']) . ' ao mês');

if (!$isSimulacao) {
    // Tipo de Pagamento com nomenclatura completa (só faz sentido para operação salva)
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
    $pdf->ParameterLine('ID da Operação', $operacao['id']);
} elseif ($tipoOperacaoSim === 'emprestimo') {
    $pdf->ParameterLine('Possui Garantia?', $temGarantiaSim ? 'Sim' : 'Não');
    if ($temGarantiaSim && !empty($descricaoGarantiaSim)) {
        $pdf->ParameterLine('Desc. da Garantia', $descricaoGarantiaSim);
    }
}
$pdf->Ln(5);

if (!empty($operacao['observacoes'])) {
    $pdf->SectionTitle($isSimulacao ? 'Anotações' : 'Observações');
    $pdf->MultiCell(0, 5, pdfText($operacao['observacoes']));
    $pdf->Ln(5);
}

if ($isSimulacao) {
    $pdf->SectionTitle($tipoOperacaoSim === 'emprestimo' ? 'Parcelas do Empréstimo' : 'Detalhamento dos Títulos');
    $pdf->BasicTableSimulacao($calculatedTitles, $tipoOperacaoSim === 'emprestimo');
} else {
    $pdf->SectionTitle('Detalhamento dos Títulos Negociados');
    $header_cliente = [pdfText('Vl Original'), pdfText('Vencimento'), pdfText('Dias Prazo'), pdfText('Vl Líquido Recebido')];
    $pdf->BasicTable($header_cliente, $calculatedTitles);
}

$pdf->SectionTitle($isSimulacao ? 'Resultados Totais' : 'Totais da Operação');
$pdf->ParameterLine('Média Ponderada de Dias', round($mediaPonderadaDiasNumerico) . ' dias');
$pdf->ParameterLine('Total Valor Original dos Títulos', pdfFormatCurrency($totalOriginalReal));

if ($isSimulacao && $tipoOperacaoSim !== 'emprestimo') {
    $pdf->ParameterLine('Total Desconto/Juros', pdfFormatCurrency($totalOriginalReal - $totalLiquidoPagoReal));
}

// Adicionar detalhamento da compensação se aplicável (operação salva apenas)
if (!$isSimulacao && $operacao['valor_total_compensacao'] > 0) {
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

$pdf->Ln(2);
if ($isSimulacao && $tipoOperacaoSim === 'emprestimo') {
    $highlightLabel = 'Valor Total do Empréstimo';
} elseif ($isSimulacao) {
    $highlightLabel = 'Total Líquido a Receber';
} else {
    $highlightLabel = 'Total Líquido Recebido pelo Cliente';
}
$pdf->HighlightBox($highlightLabel, pdfFormatCurrency($totalLiquidoPagoReal), 'success');

// Adicionar seção de compensação se aplicável (operação salva apenas)
if (!$isSimulacao && !empty($compensacao) && !empty($compensacao['temCompensacao']) && $compensacao['temCompensacao']) {
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

// Definir nome do arquivo baseado no modo
if ($isSimulacao) {
    $slugSacado = preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower($operacao['cedente_nome'] ?? ''));
    $nomeArquivo = 'simulacao_' . ($slugSacado ?: 'avulsa') . '_' . date('Ymd') . '.pdf';
} else {
    $nomeArquivo = 'recibo_' . ($operacao_id ?? 'avulso') . '_' . date('Ymd') . '.pdf';
}

$pdf->Output('D', $nomeArquivo, true);

exit;
?>
