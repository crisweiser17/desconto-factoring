<?php
// exportar_ics.php
require_once 'db_connection.php'; // Conexão $pdo

// --- Aplicar os mesmos filtros da listagem ---
// Filtros existentes
$filtro_status = isset($_GET['status']) && is_array($_GET['status']) ? $_GET['status'] : [];
$filtro_data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : '';
$filtro_data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$params = [];
$whereClauses = [];

// Por padrão, para o ICS (calendário), se não houver filtro de status, podemos querer apenas os não recebidos
// mas se o usuário filtrou explicitamente, respeitamos os filtros.
if (!empty($filtro_status)) {
    $placeholders = [];
    for ($i = 0; $i < count($filtro_status); $i++) {
        $placeholders[] = ":status_$i";
        $params[":status_$i"] = $filtro_status[$i];
    }
    $whereClauses[] = "status IN (" . implode(',', $placeholders) . ")";
}

if ($filtro_data_inicio && DateTime::createFromFormat('Y-m-d', $filtro_data_inicio)) {
    $whereClauses[] = "data_vencimento >= :data_inicio";
    $params[':data_inicio'] = $filtro_data_inicio;
}

if ($filtro_data_fim && DateTime::createFromFormat('Y-m-d', $filtro_data_fim)) {
    $whereClauses[] = "data_vencimento <= :data_fim";
    $params[':data_fim'] = $filtro_data_fim;
}

// Filtro Rápido
$quick_filter = isset($_GET['quick_filter']) ? $_GET['quick_filter'] : 'todos';

if ($quick_filter === 'inadimplentes') {
    $whereClauses[] = "status NOT IN ('Recebido', 'Compensado', 'Totalmente Compensado') AND data_vencimento < CURDATE()";
} elseif ($quick_filter === 'recebidos') {
    $whereClauses[] = "status IN ('Recebido', 'Compensado', 'Totalmente Compensado')";
} elseif ($quick_filter === 'a_receber') {
    $whereClauses[] = "status NOT IN ('Recebido', 'Compensado', 'Totalmente Compensado') AND data_vencimento >= CURDATE()";
} elseif ($quick_filter === 'vencendo_7_dias') {
    $whereClauses[] = "status NOT IN ('Recebido', 'Compensado', 'Totalmente Compensado') AND data_vencimento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
} elseif ($quick_filter === 'vencendo_hoje') {
    $whereClauses[] = "status NOT IN ('Recebido', 'Compensado', 'Totalmente Compensado') AND data_vencimento = CURDATE()";
} elseif ($quick_filter === 'problemas') {
    $whereClauses[] = "status = 'Problema'";
}

$whereSql = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "WHERE status NOT IN ('Recebido', 'Compensado', 'Totalmente Compensado')";

try {
    $sql = "SELECT * FROM recebiveis $whereSql ORDER BY data_vencimento ASC";
    $stmt = $pdo->prepare($sql);
    
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
    $recebiveis_abertos = $stmt->fetchAll();
} catch (PDOException $e) {
    header("Content-Type: text/plain; charset=utf-8");
    die("Erro ao buscar recebíveis para ICS: " . $e->getMessage());
}

// Define o nome do domínio (importante para o UID) - Altere se necessário
$domain = $_SERVER['HTTP_HOST'] ?? 'sua-aplicacao.com';

// Define os cabeçalhos para forçar o download do arquivo .ics
header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename="lembretes_recebiveis_' . date('Ymd') . '.ics"');

// Início do Arquivo iCalendar
echo "BEGIN:VCALENDAR\r\n";
echo "VERSION:2.0\r\n";
echo "PRODID:-//SuaEmpresa//Calculadora Desconto v1.0//PT\r\n"; // Identificador do programa
echo "CALSCALE:GREGORIAN\r\n";
echo "METHOD:PUBLISH\r\n"; // Método padrão

// Loop pelos recebíveis em aberto para criar eventos
foreach ($recebiveis_abertos as $r) {
    try {
        $dtVenc = new DateTime($r['data_vencimento']);
        // Formato YYYYMMDD para datas no iCal
        $dtStart = $dtVenc->format('Ymd');
        // Eventos de dia inteiro geralmente terminam no dia seguinte
        $dtEnd = (clone $dtVenc)->modify('+1 day')->format('Ymd');
        // Timestamp de criação/modificação do evento
        $dtStamp = gmdate('Ymd\THis\Z'); // Formato UTC Zulu time
        // UID único e consistente para este recebível
        $uid = 'recebivel-' . $r['id'] . '@' . $domain;
        // Título do evento
        $summary = 'Venc: Recebível #' . $r['id'] . ' - R$ ' . number_format($r['valor_original'] ?? 0, 2, ',', '.');
        // Descrição (opcional)
        $description = 'Referente à Operação ID: ' . $r['operacao_id'] . '\nStatus Atual: Em Aberto';

        echo "BEGIN:VEVENT\r\n";
        echo "UID:" . $uid . "\r\n";
        echo "DTSTAMP:" . $dtStamp . "\r\n";
        // Define como evento de dia inteiro (all-day)
        echo "DTSTART;VALUE=DATE:" . $dtStart . "\r\n";
        echo "DTEND;VALUE=DATE:" . $dtEnd . "\r\n";
        echo "SUMMARY:" . preg_replace('/([,;])/', '\\\$1', $summary) . "\r\n"; // Escapa vírgulas e ponto-e-vírgula no título
        echo "DESCRIPTION:" . preg_replace('/([,;])/', '\\\$1', str_replace("\n", "\\n", $description)) . "\r\n"; // Escapa e substitui quebras de linha

        // --- Lembrete (VALARM) - Opcional ---
        // Lembrete no próprio dia às 9:00 (ou ajuste conforme preferir)
        // Para lembrete no dia anterior use TRIGGER:-P1D
        echo "BEGIN:VALARM\r\n";
        echo "ACTION:DISPLAY\r\n"; // Tipo de alarme (mostrar notificação)
        echo "DESCRIPTION:" . preg_replace('/([,;])/', '\\\$1', $summary) . "\r\n"; // Repete o título no lembrete
        // Define o gatilho para o início do dia do vencimento (00:00) - Ajuste se quiser horário específico
        echo "TRIGGER;VALUE=DATE-TIME:" . $dtStart . "T090000\r\n"; // 9 da manhã no dia
        // Para lembrete 1 dia antes às 9h:
        // $triggerDate = (clone $dtVenc)->modify('-1 day')->format('Ymd');
        // echo "TRIGGER;VALUE=DATE-TIME:" . $triggerDate . "T090000\r\n";
        echo "END:VALARM\r\n";
        // --- Fim Lembrete ---

        echo "END:VEVENT\r\n";

    } catch (Exception $e) {
        // Loga erro se uma data for inválida, mas continua o loop
        error_log("Erro ao processar recebível ID " . $r['id'] . " para ICS: " . $e->getMessage());
    }
}

// Fim do Arquivo iCalendar
echo "END:VCALENDAR\r\n";

exit; // Termina o script após gerar o arquivo

?>
