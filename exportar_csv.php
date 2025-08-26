<?php
// exportar_csv.php
require_once 'db_connection.php'; // Conexão $pdo

// --- Aplicar os mesmos filtros da listagem ---
// Filtros existentes
$filtro_status = isset($_GET['status']) && is_array($_GET['status']) ? $_GET['status'] : [];
$filtro_data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : '';
$filtro_data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Construção da Query com Filtros (igual ao listar_recebiveis.php)
$params = [];
$whereClauses = [];

// Filtro de status (múltiplos valores)
if (!empty($filtro_status)) {
    $placeholders = [];
    for ($i = 0; $i < count($filtro_status); $i++) {
        $placeholders[] = ":status_$i";
        $params[":status_$i"] = $filtro_status[$i];
    }
    $whereClauses[] = "r.status IN (" . implode(',', $placeholders) . ")";
}

// Filtro de data de início
if ($filtro_data_inicio && DateTime::createFromFormat('Y-m-d', $filtro_data_inicio)) {
    $whereClauses[] = "r.data_vencimento >= :data_inicio";
    $params[':data_inicio'] = $filtro_data_inicio;
} else {
    $filtro_data_inicio = '';
}

// Filtro de data de fim
if ($filtro_data_fim && DateTime::createFromFormat('Y-m-d', $filtro_data_fim)) {
    $whereClauses[] = "r.data_vencimento <= :data_fim";
    $params[':data_fim'] = $filtro_data_fim;
} else {
    $filtro_data_fim = '';
}

// Filtro de Busca - Inclui busca pelo nome do cedente e sacado
if (!empty($search)) {
     $whereClauses[] = "(CAST(r.id AS CHAR) LIKE :search_rid OR CAST(r.operacao_id AS CHAR) LIKE :search_oid OR CAST(r.valor_original AS CHAR) LIKE :search_valor OR s.empresa LIKE :search_cedente OR sac.empresa LIKE :search_sacado)";
     $search_param = "%" . $search . "%";
     $search_valor_param = "%" . str_replace(',', '.', $search) . "%";

     $params[':search_rid'] = $search_param;
     $params[':search_oid'] = $search_param;
     $params[':search_valor'] = $search_valor_param;
     $params[':search_cedente'] = $search_param;
     $params[':search_sacado'] = $search_param;
}

$whereSql = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

// Busca recebíveis com filtros aplicados
try {
    $sql = "SELECT r.*, o.data_operacao, o.taxa_mensal, o.tipo_pagamento, o.total_liquido_pago_calc as op_total_liquido,
                   s.empresa AS cedente_nome, sac.empresa AS sacado_nome
            FROM recebiveis r
            LEFT JOIN operacoes o ON r.operacao_id = o.id
            LEFT JOIN cedentes s ON o.cedente_id = s.id
            LEFT JOIN sacados sac ON r.sacado_id = sac.id
            $whereSql
            ORDER BY r.data_vencimento ASC, r.id ASC";
    
    $stmt = $pdo->prepare($sql);
    
    // Bind dos parâmetros de filtros e busca
    if (!empty($search)) {
         $stmt->bindParam(':search_rid', $params[':search_rid'], PDO::PARAM_STR);
         $stmt->bindParam(':search_oid', $params[':search_oid'], PDO::PARAM_STR);
         $stmt->bindParam(':search_valor', $params[':search_valor'], PDO::PARAM_STR);
         $stmt->bindParam(':search_cedente', $params[':search_cedente'], PDO::PARAM_STR);
         $stmt->bindParam(':search_sacado', $params[':search_sacado'], PDO::PARAM_STR);
    }
    if (!empty($filtro_status)) {
        for ($i = 0; $i < count($filtro_status); $i++) {
            $stmt->bindParam(":status_$i", $params[":status_$i"], PDO::PARAM_STR);
        }
    }
    if ($filtro_data_inicio) {
        $stmt->bindParam(':data_inicio', $params[':data_inicio'], PDO::PARAM_STR);
    }
    if ($filtro_data_fim) {
        $stmt->bindParam(':data_fim', $params[':data_fim'], PDO::PARAM_STR);
    }
    
    $stmt->execute();
    $recebiveis = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    header("Content-Type: text/plain; charset=utf-8");
    die("Erro ao buscar recebíveis para CSV: " . $e->getMessage());
}

// Define os cabeçalhos para forçar o download do arquivo .csv
$filename = "lista_recebiveis_" . date('Ymd') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Abre o stream de saída do PHP
$output = fopen('php://output', 'w');

// Adiciona o BOM (Byte Order Mark) para UTF-8 (ajuda o Excel a abrir corretamente)
fprintf($output, "\xEF\xBB\xBF");

// Define e escreve o cabeçalho do CSV
$header = [
    'ID Recebivel',
    'ID Operacao',
    'Cedente',
    'Sacado',
    'Data Operacao',
    'Tipo Pagamento',
    'Data Vencimento',
    'Status',
    'Valor Original (R$)',
    'Valor Presente Calc (R$)',
    'IOF Calc (R$)',
    'Valor Liquido Calc (R$)',
    'Taxa Mensal (%)',
];
fputcsv($output, $header, ';'); // Usando ponto e vírgula como delimitador (comum no Brasil/Excel)

// Função para formatar número para CSV (ponto decimal, sem R$)
function formatCsvNumber($value) {
    return number_format($value ?? 0, 2, '.', ''); // Ponto decimal, sem separador de milhar
}

// Escreve os dados de cada recebível
foreach ($recebiveis as $r) {
    $rowData = [
        $r['id'],
        $r['operacao_id'],
        $r['cedente_nome'] ?? 'N/A',
        $r['sacado_nome'] ?? 'N/A',
        $r['data_operacao'] ? (new DateTime($r['data_operacao']))->format('d/m/Y') : '', // Formata data da operação
        $r['tipo_pagamento'] ?? 'N/A',
        $r['data_vencimento'] ? (new DateTime($r['data_vencimento']))->format('d/m/Y') : '', // Formata data de vencimento
        $r['status'],
        formatCsvNumber($r['valor_original']),
        formatCsvNumber($r['valor_presente_calc']),
        formatCsvNumber($r['iof_calc']),
        formatCsvNumber($r['valor_liquido_calc']),
        formatCsvNumber(($r['taxa_mensal'] ?? 0) * 100), // Taxa em percentual
    ];
    fputcsv($output, $rowData, ';'); // Usando ponto e vírgula
}

// fclose($output); // Não é necessário para php://output

exit; // Termina o script após gerar o CSV
?>
