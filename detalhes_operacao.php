<?php require_once 'auth_check.php'; ?>
<?php
// --- APENAS PARA DEBUG - REMOVER/COMENTAR EM PRODUÇÃO ---
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
// --- FIM DEBUG ---

require_once 'db_connection.php'; // Conexão $pdo
require_once 'funcoes_compensacao.php'; // Funções de compensação
require_once 'funcoes_calculo_central.php'; // Funções de cálculo centralizadas

// --- Constantes e Funções de Formatação e IOF ---
define('IOF_ADICIONAL_RATE', 0.0038);
define('IOF_DIARIA_RATE', 0.000082);

if (!function_exists('formatHtmlCurrency')) {
    function formatHtmlCurrency($value) {
        return 'R$ ' . number_format($value ?? 0, 2, ',', '.') . '';
    }
}

if (!function_exists('formatHtmlDate')) {
    function formatHtmlDate($value) {
        if (!$value) return '-';
        try {
            return (new DateTime($value))->format('d/m/Y');
        } catch (Exception $e) {
            return '-';
        }
    }
}

if (!function_exists('formatHtmlStatus')) {
    function formatHtmlStatus($status, $data_recebimento = null, $saldo_aberto = null, $operacao_compensadora = null) {
        $badgeClass = 'bg-secondary';
        $tooltip = '';
        switch ($status) {
            case 'Em Aberto':
                $badgeClass = 'bg-info text-dark';
                $tooltip = 'Aguardando ação ou recebimento';
                break;
            case 'Parcialmente Compensado':
                $badgeClass = 'bg-warning text-dark';
                $tooltip = 'Parcialmente compensado por outra operação';
                break;
            case 'Compensado':
                $badgeClass = 'bg-secondary';
                $tooltip = 'Totalmente compensado por outra operação';
                break;
            case 'Recebido':
                $badgeClass = 'bg-success';
                $tooltip = 'Recebimento confirmado';
                // Se tiver data de recebimento, incluir no tooltip
                if (!empty($data_recebimento)) {
                    $dataFormatada = formatHtmlDate($data_recebimento);
                    $tooltip .= ' em ' . $dataFormatada;
                }
                break;
            case 'Problema':
                $badgeClass = 'bg-danger';
                $tooltip = 'Problema no recebimento';
                break;
        }
        
        // Se for "Recebido" e tiver data, usar tooltip customizado
        if ($status === 'Recebido' && !empty($data_recebimento)) {
            return '<div class="tooltip-wrapper">
                        <span class="badge ' . $badgeClass . '">' . htmlspecialchars($status) . '</span>
                        <span class="tooltip-text">' . htmlspecialchars($tooltip) . '</span>
                    </div>';
        }
        
        // Se for "Parcialmente Compensado", mostrar badge + saldo em aberto
        if ($status === 'Parcialmente Compensado' && $saldo_aberto !== null && $saldo_aberto > 0) {
            $badgeContent = htmlspecialchars($status);
            
            // Se tiver operação compensadora, criar link
            if ($operacao_compensadora !== null && $operacao_compensadora > 0) {
                $badgeContent = '<a href="detalhes_operacao.php?id=' . $operacao_compensadora . '"
                                   class="text-decoration-none text-dark"
                                   title="Ver operação #' . $operacao_compensadora . ' que compensou este recebível">' .
                                   htmlspecialchars($status) . '</a>';
            }
            
            return '<div>
                        <span class="badge ' . $badgeClass . '">' . $badgeContent . '</span>
                        <br><small class="text-muted">Saldo: ' . formatHtmlCurrency($saldo_aberto) . '</small>
                    </div>';
        }
        
        // Para outros casos, usar tooltip padrão
        return '<span class="badge ' . $badgeClass . '" title="' . htmlspecialchars($tooltip) . '">' . htmlspecialchars($status) . '</span>';
    }
}

if (!function_exists('getTableRowClass')) {
    function getTableRowClass($status) {
        switch ($status) {
            case 'Recebido': return 'table-light text-muted opacity-75';
            case 'Problema': return 'table-danger fw-bold';
            case 'Parcialmente Compensado': return 'table-warning';
            case 'Compensado': return 'table-secondary text-muted';
            case 'Em Aberto': default: return '';
        }
    }
}

if (!function_exists('formatHtmlSimNao')) {
    function formatHtmlSimNao($value) {
        return $value ? 'Sim' : 'Não';
    }
}


// --- Validar ID da Operação ---
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: listar_operacoes.php?status=error&msg=" . urlencode("ID da operação inválido."));
    exit;
}
$operacao_id = (int)$_GET['id'];

// --- Buscar Dados da Operação e Sacado ---
$operacao = null;
$error_message = null;
try {
    $sql_op = "SELECT
                   o.*,
                   s.empresa AS cedente_nome,
                   s.empresa AS sacado_empresa,
                   s.id AS cedente_id
               FROM
                   operacoes o
               LEFT JOIN
                   cedentes s ON o.cedente_id = s.id
               WHERE
                   o.id = :id";
    $stmt_op = $pdo->prepare($sql_op);
    $stmt_op->bindParam(':id', $operacao_id, PDO::PARAM_INT);
    $stmt_op->execute();
    $operacao = $stmt_op->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_message = "Erro ao buscar dados da operação: " . htmlspecialchars($e->getMessage());
}

if (!$operacao && !isset($error_message)) {
    $error_message = "Operação com ID " . $operacao_id . " não encontrada.";
}

// --- Variáveis para Exibição (Totais) ---
$totalOriginalCalculado = 0;
$totalLiquidoPagoCalculado = (float)($operacao['total_liquido_pago_calc'] ?? 0); // Do banco
$totalLucroLiquidoCalculado = (float)($operacao['total_lucro_liquido_calc'] ?? 0); // Do banco
$totalIOFTeoricoCalculado = 0; // Acumula o IOF calculado em cada título
$percentualLucroLiquido = 0; // Calculado no final
$dataOperacaoDT = null;

// --- Buscar Recebíveis Associados e Recalcular Dados ---
$recebiveis_para_exibir = []; // Array que conterá os recebíveis com os dados recalculados para exibição na tabela

// Dados para o GRÁFICO - agora mais complexos para refletir saída/entrada/lucro
$chartDataRaw = []; // Armazena todos os pontos de dados por mês/tipo

if ($operacao && !isset($error_message)) {
    try {
        $sql_rec = "SELECT r.*, s.empresa as sacado_nome, r.sacado_id
                   FROM recebiveis r
                   LEFT JOIN sacados s ON r.sacado_id = s.id
                   WHERE r.operacao_id = :operacao_id
                   ORDER BY r.data_vencimento ASC";
        $stmt_rec = $pdo->prepare($sql_rec);
        $stmt_rec->bindParam(':operacao_id', $operacao_id, PDO::PARAM_INT);
        $stmt_rec->execute();
        $recebiveis_db = $stmt_rec->fetchAll(PDO::FETCH_ASSOC);

        // Obter os flags de IOF e taxa da operação
        $cobrarIOFCliente = filter_var($operacao['cobrar_iof_cliente'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $incorreCustoIOF = filter_var($operacao['incorre_custo_iof'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $taxaMensal = (float)($operacao['taxa_mensal'] ?? 0);

        try {
            $dataOperacaoDT = new DateTime($operacao['data_operacao']);
            $dataOperacaoDT->setTime(0,0,0);
        } catch (Exception $e) {
            $error_message = "Data da operação inválida no banco: " . htmlspecialchars($operacao['data_operacao']);
            $operacao = null;
        }

        if ($operacao) { // Continua apenas se a data da operação for válida
            // Preparar dados para cálculo centralizado
            $titulos_para_calculo = [];
            $minDate = clone $dataOperacaoDT; // Mês da operação
            $maxDate = clone $dataOperacaoDT; // Iniciar com mês da operação
            
            // Preparar títulos no formato esperado pelas funções centralizadas
            foreach ($recebiveis_db as $r) {
                $dataVencimentoTitulo = new DateTime($r['data_vencimento']);
                if ($dataVencimentoTitulo > $maxDate) {
                    $maxDate = $dataVencimentoTitulo;
                }
                
                $titulos_para_calculo[] = [
                    'valor' => (float)$r['valor_original'],
                    'data_vencimento' => $r['data_vencimento']
                ];
            }
            
            // Criar estrutura de meses vazios entre a data da operação e o último vencimento
            $currentDate = clone $minDate;
            $currentDate->modify('first day of this month');
            $endDate = clone $maxDate;
            $endDate->modify('first day of this month');
            
            while ($currentDate <= $endDate) {
                $monthYearKey = $currentDate->format('Y-m');
                $chartDataRaw[$monthYearKey] = [
                    'capital_emprestado' => 0,
                    'capital_retornado' => 0,
                    'lucro' => 0,
                    'displayLabel' => $currentDate->format('M/Y')
                ];
                $currentDate->modify('+1 month');
            }
            
            // Usar função centralizada para calcular totais
            $totais_calculados = calcularTotaisOperacao(
                $titulos_para_calculo,
                $operacao['data_operacao'],
                $taxaMensal,
                $cobrarIOFCliente,
                [] // Compensações serão processadas separadamente
            );
            
            // Atualizar variáveis de totais com valores calculados
            $totalOriginalCalculado = $totais_calculados['total_original'];
            $totalIOFTeoricoCalculado = $totais_calculados['total_iof'];
            
            // Processar cada título para exibição usando dados calculados
            foreach ($recebiveis_db as $index => $r) {
                $detalhe_titulo = $totais_calculados['detalhes_titulos'][$index];
                
                // Adicionar dados calculados ao array do recebível para exibição na tabela "Recebíveis da Operação"
                $r['dias_para_vencimento_calc'] = $detalhe_titulo['dias'];
                $r['valor_liquido_calc_dinamico'] = $detalhe_titulo['valor_liquido_pago'];
                $r['lucro_liquido_calc_dinamico'] = $detalhe_titulo['lucro_liquido'];
                $recebiveis_para_exibir[] = $r;
                
                // --- DADOS PARA O NOVO GRÁFICO ---
                $dataVencimentoTitulo = new DateTime($r['data_vencimento']);
                $monthYearKeyVencimento = $dataVencimentoTitulo->format('Y-m');
                
                // Capital Retornado = valor que foi emprestado para este título específico
                // Lucro = lucro líquido deste título específico
                if (isset($chartDataRaw[$monthYearKeyVencimento])) {
                    $chartDataRaw[$monthYearKeyVencimento]['capital_retornado'] += $detalhe_titulo['valor_liquido_pago'];
                    $chartDataRaw[$monthYearKeyVencimento]['lucro'] += $detalhe_titulo['lucro_liquido'];
                }
            }

            // Adicionar Capital Emprestado (Total Líquido Pago da operação) no mês da operação
            $monthYearKeyOperacao = $dataOperacaoDT->format('Y-m');
            if (isset($chartDataRaw[$monthYearKeyOperacao])) {
                // Importante: o capital emprestado é o TOTAL líquido pago para o cliente na data da operação.
                // Para o gráfico, ele deve ser associado ao mês da operação, como uma "saída".
                $chartDataRaw[$monthYearKeyOperacao]['capital_emprestado'] += $totalLiquidoPagoCalculado;
            }

            // Adicionar compensações como entrada imediata no mês da operação
            if ($operacao['valor_total_compensacao'] > 0) {
                try {
                    $sql_comp_grafico = "SELECT
                                            c.valor_presente_compensacao,
                                            c.data_compensacao
                                         FROM compensacoes c
                                         WHERE c.operacao_principal_id = :operacao_id";
                    $stmt_comp_grafico = $pdo->prepare($sql_comp_grafico);
                    $stmt_comp_grafico->bindParam(':operacao_id', $operacao_id, PDO::PARAM_INT);
                    $stmt_comp_grafico->execute();
                    $compensacoes_grafico = $stmt_comp_grafico->fetchAll(PDO::FETCH_ASSOC);
                    
                    foreach ($compensacoes_grafico as $comp_grafico) {
                        $dataCompensacao = new DateTime($comp_grafico['data_compensacao']);
                        $monthYearKeyCompensacao = $dataCompensacao->format('Y-m');
                        
                        // Adicionar o valor presente da compensação como capital retornado no mês da compensação
                        if (isset($chartDataRaw[$monthYearKeyCompensacao])) {
                            $chartDataRaw[$monthYearKeyCompensacao]['capital_retornado'] += $comp_grafico['valor_presente_compensacao'];
                        } else {
                            // Se o mês da compensação não existe no array, criar
                            $chartDataRaw[$monthYearKeyCompensacao] = [
                                'capital_emprestado' => 0,
                                'capital_retornado' => $comp_grafico['valor_presente_compensacao'],
                                'lucro' => 0,
                                'displayLabel' => $dataCompensacao->format('M/Y')
                            ];
                        }
                    }
                } catch (Exception $e) {
                    // Em caso de erro, continuar sem as compensações
                }
            }


            // Calcular percentual do lucro líquido APÓS o loop, usando os totais recalculados
            if ($totalOriginalCalculado > 0) {
                $percentualLucroLiquido = ($totalLucroLiquidoCalculado / $totalOriginalCalculado) * 100;
            } else {
                $percentualLucroLiquido = 0;
            }

            // Ordenar os dados do gráfico por mês/ano
            ksort($chartDataRaw);

            $chartLabels = []; // Aqui vamos armazenar os labels formatados para o Chart.js
            $chartDataCapitalEmprestado = [];
            $chartDataCapitalRetornado = [];
            $chartDataLucro = [];

            foreach ($chartDataRaw as $monthYearKey => $data) {
                $chartLabels[] = $data['displayLabel']; // AGORA PEGAMOS O LABEL FORMATADO
                $chartDataCapitalEmprestado[] = $data['capital_emprestado'];
                $chartDataCapitalRetornado[] = $data['capital_retornado'];
                $chartDataLucro[] = $data['lucro'];
            }
        }

    } catch (PDOException $e) {
        $error_message = "Erro ao buscar recebíveis da operação: " . htmlspecialchars($e->getMessage());
        $operacao = null;
    } catch (Exception $e) {
        $error_message = "Erro ao processar dados de recebíveis: " . htmlspecialchars($e->getMessage());
        $operacao = null;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes da Operação #<?php echo $operacao_id; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
    <style>
        .card-header { background-color: #e9ecef; }
        .table th { width: auto; }
        .list-group-item strong { display: inline-block; width: 180px; }
        .chart-wrapper { position: relative; min-height: 700px; margin-top: 20px; margin-bottom: 30px; }
        .chart-wrapper canvas { max-width: 100%; max-height: 100%; }

        /* Estilos para os botões de ação do recebível (copiado de listar_recebiveis.php) */
        .action-btn { margin: 0 2px; padding: 0.15rem 0.4rem; font-size: 0.8em; }
        /* Estilos para linhas de status (copiado de listar_recebiveis.php) */
        tr.table-light.text-muted.opacity-75 td { /* Estilos para recebido */ }
        tr.table-danger.fw-bold td { /* Estilos para problema */ }
        
        /* Tooltip customizado */
        .tooltip-wrapper {
            position: relative;
            display: inline-block;
        }
        .tooltip-wrapper .tooltip-text {
            visibility: hidden;
            width: auto;
            min-width: 220px;
            max-width: 300px;
            background-color: #000 !important;
            color: #fff !important;
            text-align: center;
            border-radius: 6px;
            padding: 10px 15px;
            position: absolute;
            z-index: 1000;
            bottom: 125%;
            left: 50%;
            transform: translateX(-50%);
            opacity: 0;
            transition: opacity 0.2s;
            font-size: 0.75rem;
            white-space: normal;
            box-shadow: 0 2px 8px rgba(0,0,0,0.5);
            border: 1px solid #333;
        }
        .tooltip-wrapper .tooltip-text::after {
            content: "";
            position: absolute;
            top: 100%;
            left: 50%;
            margin-left: -5px;
            border-width: 5px;
            border-style: solid;
            border-color: #000 transparent transparent transparent;
        }
        .tooltip-wrapper:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
        }
    </style>
</head>
<body>
    <?php require_once 'menu.php'; ?>

    <div class="container-fluid px-3 px-md-4 mt-4">

        <?php if (isset($_GET['status']) && $_GET['status'] === 'updated'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill"></i> Operação atualizada com sucesso!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <a href="listar_operacoes.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Voltar para Lista</a>
        <?php elseif ($operacao): ?>

            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3 gap-3">
                 <h1 class="mb-0">Detalhes da Operação #<?php echo htmlspecialchars($operacao['id']); ?></h1>
                 <div class="d-flex flex-wrap gap-2">
                    <button id="editarOperacaoBtn" class="btn btn-warning btn-sm"><i class="bi bi-pencil-fill"></i> Editar Operação</button>
                    <button id="gerarAnaliseInternaBtn" class="btn btn-primary btn-sm"><i class="bi bi-bar-chart-fill"></i> Gerar Análise Interna</button>

                    <a href="gerar_recibo_cliente.php?id=<?php echo htmlspecialchars($operacao['id']); ?>"
                       class="btn btn-outline-secondary btn-sm"
                       target="_blank"
                       title="Gerar Recibo do Cliente">
                        <i class="bi bi-file-earmark-person"></i> Gerar Recibo Cliente
                    </a>

                    <button id="notificarSacadosBtn" class="btn btn-info btn-sm" data-operacao-id="<?php echo htmlspecialchars($operacao['id']); ?>"><i class="bi bi-envelope-fill"></i> Notificar Sacados</button>

                    <a href="listar_operacoes.php" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left"></i> Voltar para Lista</a>
                 </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Dados da Operação</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">
                            <strong>Cedente:</strong>
                            <?php if ($operacao['cedente_id']): ?>
                                <a href="form_cedente.php?id=<?php echo $operacao['cedente_id']; ?>" title="Ver/Editar Cedente">
                                    <?php echo htmlspecialchars($operacao['cedente_nome'] ?? 'Desconhecido'); ?>
                                </a>
                            <?php else: ?>
                                <?php echo htmlspecialchars($operacao['cedente_nome'] ?? 'N/A'); ?>
                            <?php endif; ?>
                        </li>
                        <li class="list-group-item">
                            <strong>Data Base de Cálculo:</strong>
                            <?php echo htmlspecialchars(isset($operacao['data_operacao']) ? date('d/m/Y', strtotime($operacao['data_operacao'])) : '-'); ?>
                        </li>
                        <li class="list-group-item">
                            <strong>Data de Registro da Operação:</strong>
                            <?php echo htmlspecialchars(isset($operacao['data_operacao']) ? date('d/m/Y H:i', strtotime($operacao['data_operacao'])) : '-'); ?>
                        </li>
                         <li class="list-group-item">
                            <strong>Taxa Mensal Aplicada:</strong>
                             <?php echo htmlspecialchars(number_format(($operacao['taxa_mensal'] ?? 0) * 100, 2, ',', '.') . '%'); ?>
                        </li>
                        <li class="list-group-item">
                            <strong>Tipo de Pagamento:</strong>
                            <?php 
                                $tipoPagamento = $operacao['tipo_pagamento'] ?? 'direto';
                                switch($tipoPagamento) {
                                    case 'direto':
                                        echo 'Pagamento Direto (Notificação ao Sacado)';
                                        break;
                                    case 'escrow':
                                        echo 'Pagamento via Conta Escrow (Conta Vinculada)';
                                        break;
                                    case 'indireto':
                                        echo 'Pagamento Indireto (via Cedente)';
                                        break;
                                    case 'cheque':
                                        echo 'Cheque(s)';
                                        break;
                                    default:
                                        echo htmlspecialchars($tipoPagamento);
                                }
                            ?>
                        </li>
                        <li class="list-group-item">
                            <strong>Incorre Custo de IOF:</strong>
                            <?php echo formatHtmlSimNao($incorreCustoIOF); ?>
                        </li>
                        <li class="list-group-item">
                            <strong>Cobra IOF do Cliente:</strong>
                            <?php echo formatHtmlSimNao($cobrarIOFCliente); ?>
                        </li>
                        <li class="list-group-item">
                            <strong>Total Original (Recebíveis):</strong>
                            <?php echo formatHtmlCurrency($totalOriginalCalculado); ?>
                        </li>
                        <li class="list-group-item">
                            <strong>Total IOF (Teórico):</strong>
                             <?php echo formatHtmlCurrency($operacao['iof_total_calc'] ?? 0); ?>
                        </li>
                        <?php if ($operacao['valor_total_compensacao'] > 0): ?>
                        <li class="list-group-item" style="color: #fd7e14;">
                            <strong>Abatimento:</strong>
                             <?php echo formatHtmlCurrency(-$operacao['valor_total_compensacao']); ?>
                        </li>
                        <?php 
                        // Calcular custo da antecipação
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
                        ?>
                        <li class="list-group-item" style="color: #dc3545;">
                            <strong>Custo da Antecipação:</strong>
                             <?php echo formatHtmlCurrency($custo_antecipacao_total); ?>
                        </li>
                        <?php endif; ?>
                        <li class="list-group-item">
                            <strong>Total Líquido Pago:</strong>
                             <?php echo formatHtmlCurrency($totalLiquidoPagoCalculado); ?>
                        </li>
                         <li class="list-group-item">
                            <strong>Lucro Líquido:</strong>
                             <?php echo formatHtmlCurrency($totalLucroLiquidoCalculado); ?> (<?php echo number_format($percentualLucroLiquido, 2, ',', '.') . '%'; ?>)
                        </li>
                        <li class="list-group-item">
                            <strong>Observações:</strong>
                            <?php 
                            $observacoes = $operacao['notas'] ?? '';
                            
                            // Buscar informações de compensação se existirem
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
                                    $compensacoes = $stmt_comp->fetchAll(PDO::FETCH_ASSOC);
                                    
                                    if (!empty($compensacoes)) {
                                        $observacoes .= "\n\n=== ENCONTRO DE CONTAS ===\n";
                                        
                                        $total_compensado = array_sum(array_column($compensacoes, 'valor_compensado'));
                                        $total_presente = array_sum(array_column($compensacoes, 'valor_presente_compensacao'));
                                        $custo_antecipacao = $total_compensado - $total_presente;
                                        
                                        foreach ($compensacoes as $comp) {
                                            // Buscar a operação que contém este recebível para criar o link
                                            $operacao_recebivel = null;
                                            try {
                                                $stmt_op_rec = $pdo->prepare("SELECT operacao_id FROM recebiveis WHERE id = ?");
                                                $stmt_op_rec->execute([$comp['recebivel_id']]);
                                                $operacao_recebivel = $stmt_op_rec->fetchColumn();
                                            } catch (Exception $e) {
                                                // Em caso de erro, usar apenas o ID sem link
                                            }
                                            
                                            $saldo_restante = $comp['recebivel_valor_original'] - $comp['valor_compensado'];
                                            $taxa_formatada = number_format($comp['taxa_antecipacao_aplicada'], 0, ',', '.');
                                            
                                            if ($operacao_recebivel) {
                                                $observacoes .= "\n• R$ " . number_format($comp['valor_compensado'], 2, ',', '.') .
                                                              " antecipados do recebível #{$comp['recebivel_id']} " .
                                                              "(venc. " . date('d/m/Y', strtotime($comp['recebivel_data_vencimento'])) . ") " .
                                                              "com taxa de {$taxa_formatada}% a.m. " .
                                                              "<a href='detalhes_operacao.php?id={$operacao_recebivel}' target='_blank'>Ver Operação</a>\n";
                                            } else {
                                                $observacoes .= "\n• R$ " . number_format($comp['valor_compensado'], 2, ',', '.') .
                                                              " antecipados do recebível #{$comp['recebivel_id']} " .
                                                              "(venc. " . date('d/m/Y', strtotime($comp['recebivel_data_vencimento'])) . ") " .
                                                              "com taxa de {$taxa_formatada}% a.m.\n";
                                            }
                                            
                                            if ($comp['tipo_compensacao'] === 'parcial') {
                                                $observacoes .= "  Saldo restante: R$ " . number_format($saldo_restante, 2, ',', '.') . "\n";
                                            }
                                        }
                                        
                                        $observacoes .= "\n• Resumo: R$ " . number_format($total_compensado, 2, ',', '.') . " compensados\n";
                                        $observacoes .= "• Valor presente: R$ " . number_format($total_presente, 2, ',', '.') . " recebidos\n";
                                        $observacoes .= "• Custo da antecipação (crédito ao cliente): R$ " . number_format($custo_antecipacao, 2, ',', '.') . "\n";
                                    }
                                } catch (Exception $e) {
                                    $observacoes .= "\n\n[Erro ao carregar detalhes da compensação: " . $e->getMessage() . "]";
                                }
                            }
                            
                            echo nl2br($observacoes ?: 'Nenhuma');
                            ?>
                        </li>
                    </ul>
                </div>
            </div>

            <h3 class="mb-3">Recebíveis da Operação</h3>

            <?php if (isset($error_message_recebiveis)): ?>
                 <div class="alert alert-warning"><?php echo $error_message_recebiveis; ?></div>
            <?php elseif (empty($recebiveis_para_exibir)): ?>
                <div class="alert alert-info">Nenhum recebível encontrado para esta operação.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center">ID Recebível</th>
                                <th class="text-center">Vencimento</th>
                                <th class="text-center">Sacado (Devedor)</th>
                                <th class="text-center">Tipo Recebível</th>
                                <th class="text-end">Valor Original</th>
                                <th class="text-center">Dias para Vencimento</th>
                                <th class="text-end">Valor Pago</th>
                                <th class="text-end">Lucro</th>
                                <th class="text-center">Status</th>
                                <th class="text-center" style="width: 110px;">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="recebiveis-table-body">
                            <?php
                            foreach ($recebiveis_para_exibir as $r):
                            ?>
                                <?php
                                    // Usando os campos recalculados dinamicamente
                                    $dias_para_vencimento = $r['dias_para_vencimento_calc'];
                                    $valor_liquido_recebido_item = $r['valor_liquido_calc_dinamico'];
                                    $lucro_atual_recebivel = $r['lucro_liquido_calc_dinamico'];

                                    $rowClass = getTableRowClass($r['status']);
                                    
                                    // Calcular saldo em aberto e buscar operação compensadora para recebíveis parcialmente compensados
                                    $saldo_aberto = null;
                                    $operacao_compensadora = null;
                                    if ($r['status'] === 'Parcialmente Compensado') {
                                        try {
                                            $stmt_saldo = $pdo->prepare("
                                                SELECT
                                                    r.valor_original,
                                                    COALESCE(SUM(c.valor_compensado), 0) as total_compensado,
                                                    c.operacao_principal_id
                                                FROM recebiveis r
                                                LEFT JOIN compensacoes c ON c.recebivel_compensado_id = r.id
                                                WHERE r.id = ?
                                                GROUP BY r.id, r.valor_original, c.operacao_principal_id
                                            ");
                                            $stmt_saldo->execute([$r['id']]);
                                            $dados_saldo = $stmt_saldo->fetch(PDO::FETCH_ASSOC);
                                            
                                            if ($dados_saldo) {
                                                $saldo_aberto = $dados_saldo['valor_original'] - $dados_saldo['total_compensado'];
                                                $operacao_compensadora = $dados_saldo['operacao_principal_id'];
                                            }
                                        } catch (Exception $e) {
                                            // Em caso de erro, não mostrar saldo
                                            $saldo_aberto = null;
                                            $operacao_compensadora = null;
                                        }
                                    }
                                ?>
                                <tr id="recebivel-row-<?php echo $r['id']; ?>" class="<?php echo $rowClass; ?>">
                                    <td class="text-center"><?php echo htmlspecialchars($r['id']); ?></td>
                                    <td class="text-center"><?php echo formatHtmlDate($r['data_vencimento']); ?></td>
                                    <td class="text-center">
                                        <?php if ($r['sacado_id']): ?>
                                            <a href="visualizar_sacado.php?id=<?php echo $r['sacado_id']; ?>" title="Ver/Editar Sacado">
                                                <?php echo htmlspecialchars($r['sacado_nome'] ?? 'Desconhecido'); ?>
                                            </a>
                                        <?php else: ?>
                                            <?php echo htmlspecialchars($r['sacado_nome'] ?? 'N/A'); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center"><?php echo htmlspecialchars($r['tipo_recebivel'] ?? 'N/A'); ?></td>
                                    <td class="text-end"><?php echo formatHtmlCurrency($r['valor_original']); ?></td>
                                    <td class="text-center"><?php echo htmlspecialchars($dias_para_vencimento); ?></td>
                                    <td class="text-end"><?php echo formatHtmlCurrency($valor_liquido_recebido_item); ?></td>
                                    <td class="text-end"><?php echo formatHtmlCurrency($lucro_atual_recebivel); ?></td>
                                    <td class="text-center status-cell"><?php echo formatHtmlStatus($r['status'], $r['data_recebimento'] ?? null, $saldo_aberto, $operacao_compensadora); ?></td>
                                    <td class="text-center actions-cell">
                                        <?php if ($r['status'] === 'Em Aberto'): ?>
                                            <button class="btn btn-success action-btn update-status-btn" data-id="<?php echo $r['id']; ?>" data-status="Recebido" title="Marcar como Recebido"><i class="bi bi-check-lg"></i></button>
                                            <button class="btn btn-danger action-btn update-status-btn" data-id="<?php echo $r['id']; ?>" data-status="Problema" title="Marcar com Problema"><i class="bi bi-exclamation-triangle-fill"></i></button>
                                        <?php elseif ($r['status'] === 'Parcialmente Compensado'): ?>
                                            <button class="btn btn-success action-btn update-status-btn" data-id="<?php echo $r['id']; ?>" data-status="Recebido" title="Marcar como Recebido"><i class="bi bi-check-lg"></i></button>
                                            <button class="btn btn-danger action-btn update-status-btn" data-id="<?php echo $r['id']; ?>" data-status="Problema" title="Marcar com Problema"><i class="bi bi-exclamation-triangle-fill"></i></button>
                                        <?php elseif ($r['status'] === 'Problema'): ?>
                                            <button class="btn btn-success action-btn update-status-btn" data-id="<?php echo $r['id']; ?>" data-status="Recebido" title="Marcar como Recebido"><i class="bi bi-check-lg"></i></button>
                                            <button class="btn btn-secondary action-btn update-status-btn" data-id="<?php echo $r['id']; ?>" data-status="Em Aberto" title="Reverter para Em Aberto"><i class="bi bi-arrow-counterclockwise"></i></button>
                                        <?php elseif ($r['status'] === 'Recebido'): ?>
                                            <button class="btn btn-secondary action-btn update-status-btn" data-id="<?php echo $r['id']; ?>" data-status="Em Aberto" title="Reverter para Em Aberto"><i class="bi bi-arrow-counterclockwise"></i></button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if ($operacao['valor_total_compensacao'] > 0): ?>
                            <tr style="background-color: #fff3cd; color: #856404;">
                                <td class="text-center">-</td>
                                <td class="text-center">Abatimento</td>
                                <td class="text-center">-</td>
                                <td class="text-center">-</td>
                                <td class="text-end"><?php echo formatHtmlCurrency($operacao['valor_total_compensacao']); ?></td>
                                <td class="text-center">-</td>
                                <td class="text-end"><?php echo formatHtmlCurrency(-$operacao['valor_total_compensacao']); ?></td>
                                <td class="text-end"><?php echo formatHtmlCurrency(0); ?></td>
                                <td class="text-center"><span class="badge bg-warning">Abatido</span></td>
                                <td class="text-center">-</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                         <tfoot class="table-group-divider">
                            <tr>
                                <td colspan="3" class="text-end"><strong>Totais:</strong></td>
                                <td class="text-end"><strong><?php echo formatHtmlCurrency($totalOriginalCalculado); ?></strong></td>
                                <td class="text-center"></td>
                                <td class="text-end"><strong><?php echo formatHtmlCurrency($totalLiquidoPagoCalculado); ?></strong></td>
                                <td class="text-end"><strong><?php echo formatHtmlCurrency($totalLucroLiquidoCalculado); ?></strong></td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php endif; ?>

            <!-- Seção de Arquivos da Operação -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Documentos Anexados</h5>
                    <button type="button" class="btn btn-primary btn-sm" id="adicionarArquivosBtn">
                        <i class="bi bi-plus-circle"></i> Adicionar Arquivos
                    </button>
                </div>
                <div class="card-body">
                    <div id="arquivos-loading" class="text-center" style="display: none;">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Carregando...</span>
                        </div>
                        <p class="mt-2">Carregando arquivos...</p>
                    </div>
                    
                    <div id="arquivos-container">
                        <div id="arquivos-lista" class="row"></div>
                        <div id="arquivos-vazio" class="text-center text-muted" style="display: none;">
                            <i class="bi bi-file-earmark-x" style="font-size: 3rem;"></i>
                            <p class="mt-2">Nenhum documento anexado a esta operação.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal para Upload de Arquivos -->
            <div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="uploadModalLabel">Adicionar Documentos</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="uploadForm" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label for="arquivos" class="form-label">Selecionar Arquivos</label>
                                    <input type="file" class="form-control" id="arquivos" name="arquivos[]" multiple
                                           accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.doc,.docx,.xls,.xlsx,.txt">
                                    <div class="form-text">
                                        Tipos aceitos: PDF, JPG, PNG, GIF, WebP, DOC, DOCX, XLS, XLSX, TXT<br>
                                        Tamanho máximo por arquivo: 10MB | Máximo: 20 arquivos por operação
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="descricaoArquivos" class="form-label">Descrição (opcional)</label>
                                    <textarea class="form-control" id="descricaoArquivos" rows="3"
                                              placeholder="Descrição dos documentos anexados..."></textarea>
                                </div>
                                <div id="arquivos-preview" class="mt-3" style="display: none;">
                                    <h6>Arquivos Selecionados:</h6>
                                    <div id="arquivos-list" class="list-group"></div>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="button" class="btn btn-primary" id="uploadBtn" disabled>
                                <i class="bi bi-cloud-upload"></i> Enviar Arquivos
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal para Pré-visualização de Notificação -->
            <div class="modal fade" id="previewNotificacaoModal" tabindex="-1" aria-labelledby="previewNotificacaoModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="previewNotificacaoModalLabel">Pré-visualização do E-mail para Sacados</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div id="previewNotificacaoLoading" class="text-center" style="display: none;">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Carregando...</span>
                                </div>
                                <p class="mt-2">Gerando pré-visualização...</p>
                            </div>
                            <div id="previewNotificacaoContent" style="display: none;">
                                <div class="mb-3">
                                    <label for="selectSacadoPreview" class="form-label">Selecione o Sacado para visualizar o e-mail que ele receberá:</label>
                                    <select class="form-select" id="selectSacadoPreview"></select>
                                </div>
                                <div class="border rounded p-4 bg-light shadow-sm" style="min-height: 400px; overflow-y: auto;">
                                    <iframe id="iframePreviewEmail" style="width: 100%; min-height: 500px; border: none; background: #fff;"></iframe>
                                </div>
                            </div>
                            <div id="previewNotificacaoError" class="alert alert-danger" style="display: none;"></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="button" class="btn btn-success" id="confirmarDisparoBtn">
                                <i class="bi bi-send-fill"></i> Confirmar e Enviar E-mails
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div id="status-feedback" class="mt-2 mb-3" style="min-height: 1.5em;"></div> <fieldset class="border p-3 rounded mb-5 mt-4">
                <legend class="float-none w-auto px-3 h6">Fluxo de Caixa (Saída, Retorno e Lucro por Mês)</legend>
                <?php if (empty($recebiveis_para_exibir) && $totalLiquidoPagoCalculado == 0): ?>
                    <div class="alert alert-info text-center">Nenhum dado para gerar o gráfico.</div>
                <?php else: ?>
                    <div class="chart-wrapper"><canvas id="fluxoCaixaChart"></canvas></div>
                <?php endif; ?>
            </fieldset>

            <form id="analiseInternaForm" method="POST" action="gera_analise_interna_pdf.php" target="_blank" style="display:none;">
                <input type="hidden" name="operacao_id" value="<?php echo htmlspecialchars($operacao['id']); ?>">
                <input type="hidden" name="chartImageData" id="chartImageData">
            </form>

        <?php endif; ?>

    </div>

    <script>
    // Notificação de Sacados - Novo fluxo com preview
    document.addEventListener('DOMContentLoaded', function() {
        const btnNotificar = document.getElementById('notificarSacadosBtn');
        let previewModal;
        if (document.getElementById('previewNotificacaoModal')) {
            previewModal = new bootstrap.Modal(document.getElementById('previewNotificacaoModal'));
        }
        
        let currentPreviews = [];
        let currentOperacaoId = null;

        if (btnNotificar) {
            btnNotificar.addEventListener('click', function() {
                currentOperacaoId = this.getAttribute('data-operacao-id');
                
                // Mostrar modal de loading
                const loading = document.getElementById('previewNotificacaoLoading');
                const content = document.getElementById('previewNotificacaoContent');
                const errorDiv = document.getElementById('previewNotificacaoError');
                const btnConfirmar = document.getElementById('confirmarDisparoBtn');
                
                loading.style.display = 'block';
                content.style.display = 'none';
                errorDiv.style.display = 'none';
                btnConfirmar.disabled = true;
                
                previewModal.show();

                fetch('preview_notificacao_sacados.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ operacao_id: currentOperacaoId })
                })
                .then(response => response.json())
                .then(data => {
                    loading.style.display = 'none';
                    if (data.success && data.previews && data.previews.length > 0) {
                        currentPreviews = data.previews;
                        const select = document.getElementById('selectSacadoPreview');
                        select.innerHTML = '';
                        
                        data.previews.forEach((p, index) => {
                            const option = document.createElement('option');
                            option.value = index;
                            option.textContent = p.sacado + (p.email ? ` (${p.email})` : ' - Sem E-mail Cadastrado');
                            select.appendChild(option);
                        });
                        
                        select.onchange = function() {
                            const p = currentPreviews[this.value];
                            const iframe = document.getElementById('iframePreviewEmail');
                            iframe.srcdoc = p.html_body;
                        };
                        
                        // Selecionar o primeiro
                        select.selectedIndex = 0;
                        select.dispatchEvent(new Event('change'));
                        
                        content.style.display = 'block';
                        btnConfirmar.disabled = false;
                    } else {
                        errorDiv.textContent = data.error || 'Nenhum sacado encontrado para notificar.';
                        errorDiv.style.display = 'block';
                    }
                })
                .catch(error => {
                    loading.style.display = 'none';
                    errorDiv.textContent = 'Erro na requisição de preview: ' + error.message;
                    errorDiv.style.display = 'block';
                });
            });
        }
        
        const btnConfirmar = document.getElementById('confirmarDisparoBtn');
        if (btnConfirmar) {
            btnConfirmar.addEventListener('click', function() {
                if (!currentOperacaoId) return;
                
                const originalHtml = this.innerHTML;
                this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Enviando...';
                this.disabled = true;

                fetch('notificar_sacados.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ operacao_id: currentOperacaoId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Sucesso: ' + data.mensagem);
                        previewModal.hide();
                    } else {
                        alert('Erro ao enviar notificações: ' + data.error);
                    }
                })
                .catch(error => {
                    alert('Erro na requisição: ' + error.message);
                })
                .finally(() => {
                    this.innerHTML = originalHtml;
                    this.disabled = false;
                });
            });
        }
    });

    let myFluxoChart = null;

    function formatCurrencyJS(value) {
        if (typeof value === 'number') {
            return 'R$ ' + value.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
        return '--';
    }

    // Passamos os três conjuntos de dados separados para o JS
    const chartLabels = <?php echo json_encode($chartLabels); ?>;
    const chartDataCapitalEmprestado = <?php echo json_encode($chartDataCapitalEmprestado); ?>;
    const chartDataCapitalRetornado = <?php echo json_encode($chartDataCapitalRetornado); ?>;
    const chartDataLucro = <?php echo json_encode($chartDataLucro); ?>;
    const operacaoId = <?php echo json_encode($operacao_id); ?>;

    function updateChart(labels, dataEmprestado, dataRetornado, dataLucro) {
        if (myFluxoChart) {
            myFluxoChart.destroy();
            myFluxoChart = null;
        }

        // Adicionada validação mais robusta para todos os arrays de dados
        if (!labels || !Array.isArray(labels) || labels.length === 0 ||
            !dataEmprestado || !Array.isArray(dataEmprestado) || dataEmprestado.length === 0 || dataEmprestado.length !== labels.length ||
            !dataRetornado || !Array.isArray(dataRetornado) || dataRetornado.length === 0 || dataRetornado.length !== labels.length ||
            !dataLucro || !Array.isArray(dataLucro) || dataLucro.length === 0 || dataLucro.length !== labels.length
        ) {
            console.warn("Dados inválidos ou inconsistentes para o gráfico. Gráfico não será renderizado.");
            const chartCanvasElement = document.getElementById('fluxoCaixaChart');
            if(chartCanvasElement){
                const ctx = chartCanvasElement.getContext('2d');
                if(ctx) ctx.clearRect(0, 0, chartCanvasElement.width, chartCanvasElement.height);
            }
            return;
        }

        const chartCanvasElement = document.getElementById('fluxoCaixaChart');
        if (!chartCanvasElement) {
            console.error("Canvas do gráfico não encontrado!");
            return;
        }
        const ctx = chartCanvasElement.getContext('2d');
        if (!ctx) {
            console.error("Contexto 2D do gráfico não obtido.");
            return;
        }
        try {
            myFluxoChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels, // Agora 'labels' já contém os nomes dos meses
                    datasets: [
                        {
                            label: 'Capital Emprestado (Saída)',
                            data: dataEmprestado,
                            backgroundColor: 'rgba(108, 117, 125, 0.7)', // Cinza
                            borderColor: 'rgba(108, 117, 125, 1)',
                            borderWidth: 1,
                            stack: 'Stack 0'
                        },
                        {
                            label: 'Capital Retornado (Base)',
                            data: dataRetornado,
                            backgroundColor: 'rgba(0, 123, 255, 0.7)', // Azul
                            borderColor: 'rgba(0, 123, 255, 1)',
                            borderWidth: 1,
                            stack: 'Stack 1'
                        },
                        {
                            label: 'Lucro Líquido (Adicional)',
                            data: dataLucro,
                            backgroundColor: 'rgba(25, 135, 84, 0.7)', // Verde
                            borderColor: 'rgba(25, 135, 84, 1)',
                            borderWidth: 1,
                            stack: 'Stack 1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            stacked: true,
                            ticks: {
                                callback: function(value, index, ticks) {
                                    const mesAno = this.chart.data.labels[index];
                                    const lucroMes = dataLucro[index] || 0;
                                    const capitalRetornado = dataRetornado[index] || 0;
                                    
                                    // Função para formatar moeda
                                    function formatCurrency(value) {
                                        return value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL', minimumFractionDigits: 0, maximumFractionDigits: 0 });
                                    }
                                    
                                    return [
                                        mesAno,
                                        'Lucro: ' + formatCurrency(lucroMes),
                                        'Capital Retornado: ' + formatCurrency(capitalRetornado)
                                    ];
                                }
                            }
                        },
                        y: {
                            beginAtZero: true,
                            stacked: true,
                            ticks: {
                                callback: function(value) {
                                    return value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL', minimumFractionDigits: 0, maximumFractionDigits: 0 });
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                        },
                        tooltip: {
                            callbacks: {
                                title: function(context) {
                                    // O título do tooltip será o próprio label do mês
                                    return context[0].label;
                                },
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) { label += ': '; }
                                    if (context.parsed.y !== null) {
                                        label += context.parsed.y.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
                                    }
                                    return label;
                                }
                            }
                        }
                    }
                }
            });
        } catch (chartError) {
            console.error("Erro Chart.js:", chartError);
        }
    }

    // --- Lógica para o status do Recebível (copiada de listar_recebiveis.php) ---
    document.addEventListener('DOMContentLoaded', function () {
        const feedbackDiv = document.getElementById('status-feedback');
        const tableBody = document.getElementById('recebiveis-table-body');

        if (tableBody) {
            tableBody.addEventListener('click', function(event) {
                const button = event.target.closest('.update-status-btn');
                if (!button) return;

                const recebivelId = button.dataset.id;
                const newStatus = button.dataset.status;
                const row = document.getElementById('recebivel-row-' + recebivelId);
                if (!row) {
                    console.error('Elemento da linha não encontrado:', 'recebivel-row-' + recebivelId);
                    return;
                }

                const statusCell = row.querySelector('.status-cell');
                const actionsCell = row.querySelector('.actions-cell');
                if (!statusCell || !actionsCell) {
                     console.error('Célula de status ou ações não encontrada na linha:', row);
                    return;
                }

                feedbackDiv.innerHTML = '<div class="spinner-border spinner-border-sm text-primary" role="status"><span class="visually-hidden">Atualizando...</span></div> Atualizando status...';
                feedbackDiv.className = 'alert alert-info';

                fetch('atualizar_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: 'id=' + encodeURIComponent(recebivelId) + '&status=' + encodeURIComponent(newStatus)
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`Erro HTTP ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    const closeButtonHtml = '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';

                     if (data.success && typeof data.newStatusHtml !== 'undefined' && typeof data.newActionsHtml !== 'undefined' && typeof data.newRowClass !== 'undefined') {
                        statusCell.innerHTML = data.newStatusHtml;
                        actionsCell.innerHTML = data.newActionsHtml;
                        row.className = data.newRowClass;
                        feedbackDiv.innerHTML = `Status do recebível ${recebivelId} atualizado para ${newStatus}. ${closeButtonHtml}`;
                        feedbackDiv.className = 'alert alert-success alert-dismissible fade show';

                        window.location.reload(); // Recarrega para refletir todos os cálculos atualizados

                    } else {
                        feedbackDiv.innerHTML = `Erro ao atualizar status: ${data.message || 'Dados de resposta incompletos ou falha no servidor.'} ${closeButtonHtml}`;
                        feedbackDiv.className = 'alert alert-danger alert-dismissible fade show';
                    }
                })
                .catch(error => {
                    console.error('Erro no catch:', error);
                    const closeButtonHtml = '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                    feedbackDiv.innerHTML = `Erro na comunicação ou processamento da resposta. Ver Console (F12). [${error.message}] ${closeButtonHtml}`;
                    feedbackDiv.className = 'alert alert-danger alert-dismissible fade show';
                });
            });
        }

        // --- Lógica para Gerar Análise Interna PDF (mantida) ---
        const gerarAnaliseInternaBtn = document.getElementById('gerarAnaliseInternaBtn');
        const analiseInternaForm = document.getElementById('analiseInternaForm');
        const chartImageDataInput = document.getElementById('chartImageData');
        const chartCanvasElement = document.getElementById('fluxoCaixaChart');

        // Se não houver dados para o gráfico, desabilita o botão
        if (chartLabels.length > 0 && (chartDataCapitalEmprestado.some(val => val > 0) || chartDataCapitalRetornado.some(val => val > 0) || chartDataLucro.some(val => val > 0))) {
            updateChart(chartLabels, chartDataCapitalEmprestado, chartDataCapitalRetornado, chartDataLucro);
        } else {
             gerarAnaliseInternaBtn.disabled = true;
             gerarAnaliseInternaBtn.title = 'Nenhum dado financeiro para gerar análise de fluxo de caixa.';
             const chartWrapper = document.querySelector('.chart-wrapper');
             if(chartWrapper) chartWrapper.innerHTML = '<div class="alert alert-info text-center">Nenhum dado para gerar o gráfico de fluxo de caixa.</div>';
        }

        if (gerarAnaliseInternaBtn) {
            gerarAnaliseInternaBtn.addEventListener('click', function() {
                if (gerarAnaliseInternaBtn.disabled) {
                    console.log("[Gerar Análise Interna] Botão desabilitado.");
                    return;
                }
                console.log("[Gerar Análise Interna] Botão clicado. Gerando imagem...");

                chartImageDataInput.value = '';
                if (myFluxoChart && chartCanvasElement) {
                    try {
                        if (myFluxoChart.isDestroyed) {
                            updateChart(chartLabels, chartDataCapitalEmprestado, chartDataCapitalRetornado, chartDataLucro);
                        }
                        chartImageDataInput.value = myFluxoChart.toBase64Image('image/png', 1.0);
                        if (!chartImageDataInput.value || !chartImageDataInput.value.startsWith('data:image/png;base64,')) {
                            console.error("[Gerar Análise Interna] Erro: Dados base64 inválidos gerados pelo Chart.js.");
                            alert("Erro ao gerar imagem do gráfico. O PDF será gerado sem o gráfico.");
                            chartImageDataInput.value = '';
                        } else {
                            console.log("[Gerar Análise Interna] Imagem Base64 gerada OK.");
                        }
                    } catch (e) {
                        console.error("[Gerar Análise Interna] Erro CATASTRÓFICO ao gerar Base64:", e);
                        alert("Erro inesperado ao preparar gráfico. O PDF será gerado sem o gráfico.");
                        chartImageDataInput.value = '';
                    }
                } else {
                    console.log("[Gerar Análise Interna] Gráfico não renderizado ou sem dados. Imagem não será enviada.");
                    chartImageDataInput.value = '';
                }

                console.log("[Gerar Análise Interna] Submetendo formulário para gera_analise_interna_pdf.php...");
                analiseInternaForm.submit();
            });
        }

        // Event listener para o botão Editar Operação
        const editarOperacaoBtn = document.getElementById('editarOperacaoBtn');
        if (editarOperacaoBtn) {
            editarOperacaoBtn.addEventListener('click', function() {
                window.location.href = 'editar_operacao.php?id=' + operacaoId;
            });
        }

        // --- Funcionalidade de Arquivos ---
        let uploadModal;
        const adicionarArquivosBtn = document.getElementById('adicionarArquivosBtn');
        const arquivosInput = document.getElementById('arquivos');
        const arquivosPreview = document.getElementById('arquivos-preview');
        const arquivosList = document.getElementById('arquivos-list');
        const descricaoArquivos = document.getElementById('descricaoArquivos');
        const uploadBtn = document.getElementById('uploadBtn');
        const arquivosLoading = document.getElementById('arquivos-loading');
        const arquivosContainer = document.getElementById('arquivos-container');
        const arquivosListaDiv = document.getElementById('arquivos-lista');
        const arquivosVazio = document.getElementById('arquivos-vazio');

        // Inicializar modal
        uploadModal = new bootstrap.Modal(document.getElementById('uploadModal'));

        // Carregar arquivos ao carregar a página
        carregarArquivosOperacao(operacaoId);

        // Event listeners
        adicionarArquivosBtn.addEventListener('click', function() {
            uploadModal.show();
        });

        arquivosInput.addEventListener('change', function() {
            const files = this.files;
            if (files.length === 0) {
                arquivosPreview.style.display = 'none';
                uploadBtn.disabled = true;
                return;
            }

            arquivosList.innerHTML = '';
            let totalSize = 0;
            let hasErrors = false;

            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                totalSize += file.size;
                
                const listItem = document.createElement('div');
                listItem.className = 'list-group-item d-flex justify-content-between align-items-center';
                
                let statusClass = 'text-success';
                let statusIcon = 'bi-check-circle';
                let statusText = 'OK';
                
                // Validações
                if (file.size > 10 * 1024 * 1024) { // 10MB
                    statusClass = 'text-danger';
                    statusIcon = 'bi-x-circle';
                    statusText = 'Muito grande (>10MB)';
                    hasErrors = true;
                }
                
                const allowedTypes = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'doc', 'docx', 'xls', 'xlsx', 'txt'];
                const extension = file.name.split('.').pop().toLowerCase();
                if (!allowedTypes.includes(extension)) {
                    statusClass = 'text-danger';
                    statusIcon = 'bi-x-circle';
                    statusText = 'Tipo não permitido';
                    hasErrors = true;
                }

                listItem.innerHTML = `
                    <div>
                        <strong>${file.name}</strong><br>
                        <small class="text-muted">${formatFileSize(file.size)}</small>
                    </div>
                    <span class="${statusClass}">
                        <i class="bi ${statusIcon}"></i> ${statusText}
                    </span>
                `;
                
                arquivosList.appendChild(listItem);
            }

            // Mostrar total
            const totalItem = document.createElement('div');
            totalItem.className = 'list-group-item bg-light';
            totalItem.innerHTML = `<strong>Total: ${files.length} arquivo(s) - ${formatFileSize(totalSize)}</strong>`;
            arquivosList.appendChild(totalItem);

            arquivosPreview.style.display = 'block';
            uploadBtn.disabled = hasErrors || files.length === 0;
        });

        uploadBtn.addEventListener('click', async function() {
            const files = arquivosInput.files;
            
            if (files.length === 0) {
                alert('Selecione arquivos para enviar.');
                return;
            }

            const originalText = uploadBtn.innerHTML;
            uploadBtn.disabled = true;
            uploadBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Enviando...';

            try {
                const formData = new FormData();
                formData.append('operacao_id', operacaoId);
                
                for (let i = 0; i < files.length; i++) {
                    formData.append('arquivos[]', files[i]);
                    formData.append('descricao[]', descricaoArquivos.value || '');
                }

                const response = await fetch('upload_arquivos.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    alert(`${result.total_uploaded} arquivo(s) enviado(s) com sucesso!`);
                    
                    // Limpar formulário
                    arquivosInput.value = '';
                    descricaoArquivos.value = '';
                    arquivosPreview.style.display = 'none';
                    
                    // Fechar modal
                    uploadModal.hide();
                    
                    // Recarregar lista de arquivos
                    carregarArquivosOperacao(operacaoId);
                } else {
                    let errorMsg = result.error || 'Erro desconhecido no upload.';
                    if (result.errors && result.errors.length > 0) {
                        errorMsg += '\n\nDetalhes:\n' + result.errors.join('\n');
                    }
                    alert('Erro no upload:\n' + errorMsg);
                }

            } catch (error) {
                console.error('Erro no upload:', error);
                alert('Erro de comunicação durante o upload: ' + error.message);
            } finally {
                uploadBtn.disabled = false;
                uploadBtn.innerHTML = originalText;
            }
        });

        // Função para carregar arquivos da operação
        async function carregarArquivosOperacao(operacaoId) {
            arquivosLoading.style.display = 'block';
            arquivosContainer.style.display = 'none';

            try {
                const response = await fetch(`listar_arquivos.php?operacao_id=${operacaoId}`);
                const result = await response.json();

                if (result.success) {
                    renderizarArquivos(result.arquivos);
                } else {
                    console.error('Erro ao carregar arquivos:', result.error);
                    arquivosVazio.innerHTML = '<p class="text-danger">Erro ao carregar arquivos: ' + result.error + '</p>';
                    arquivosVazio.style.display = 'block';
                }
            } catch (error) {
                console.error('Erro na comunicação:', error);
                arquivosVazio.innerHTML = '<p class="text-danger">Erro de comunicação ao carregar arquivos.</p>';
                arquivosVazio.style.display = 'block';
            } finally {
                arquivosLoading.style.display = 'none';
                arquivosContainer.style.display = 'block';
            }
        }

        // Função para renderizar lista de arquivos
        function renderizarArquivos(arquivos) {
            arquivosListaDiv.innerHTML = '';

            if (arquivos.length === 0) {
                arquivosVazio.style.display = 'block';
                return;
            }

            arquivosVazio.style.display = 'none';

            arquivos.forEach(arquivo => {
                const col = document.createElement('div');
                col.className = 'col-md-6 col-lg-4 mb-3';

                const card = document.createElement('div');
                card.className = 'card h-100';

                const isImage = ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(arquivo.extensao.toLowerCase());
                
                card.innerHTML = `
                    <div class="card-body">
                        <div class="d-flex align-items-start">
                            <div class="me-3">
                                <i class="bi ${arquivo.icone}" style="font-size: 2rem; color: #6c757d;"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="card-title mb-1" title="${arquivo.nome_original}">${arquivo.nome_original.length > 25 ? arquivo.nome_original.substring(0, 25) + '...' : arquivo.nome_original}</h6>
                                <p class="card-text">
                                    <small class="text-muted">
                                        ${arquivo.tamanho_formatado}<br>
                                        ${arquivo.data_upload_formatada}<br>
                                        ${arquivo.usuario_upload || 'Sistema'}
                                    </small>
                                </p>
                                ${arquivo.descricao ? `<p class="card-text"><small>${arquivo.descricao}</small></p>` : ''}
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent">
                        <div class="btn-group w-100" role="group">
                            ${arquivo.pode_visualizar ?
                                `<a href="${arquivo.download_url}" target="_blank" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-eye"></i> Ver
                                </a>` : ''
                            }
                            <a href="${arquivo.download_url}&download=1" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-download"></i> Download
                            </a>
                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="excluirArquivo(${arquivo.id}, '${arquivo.nome_original}')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                `;

                col.appendChild(card);
                arquivosListaDiv.appendChild(col);
            });
        }

        // Função para excluir arquivo
        window.excluirArquivo = async function(arquivoId, nomeArquivo) {
            if (!confirm(`Tem certeza que deseja excluir o arquivo "${nomeArquivo}"?`)) {
                return;
            }

            try {
                const response = await fetch('excluir_arquivo.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `id=${arquivoId}`
                });

                const result = await response.json();

                if (result.success) {
                    alert('Arquivo excluído com sucesso!');
                    carregarArquivosOperacao(operacaoId);
                } else {
                    alert('Erro ao excluir arquivo: ' + result.error);
                }
            } catch (error) {
                console.error('Erro ao excluir arquivo:', error);
                alert('Erro de comunicação ao excluir arquivo.');
            }
        };

        // Função para formatar tamanho de arquivo
        function formatFileSize(bytes) {
            if (bytes >= 1073741824) {
                return (bytes / 1073741824).toFixed(2) + ' GB';
            } else if (bytes >= 1048576) {
                return (bytes / 1048576).toFixed(2) + ' MB';
            } else if (bytes >= 1024) {
                return (bytes / 1024).toFixed(2) + ' KB';
            } else {
                return bytes + ' bytes';
            }
        }
    });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
