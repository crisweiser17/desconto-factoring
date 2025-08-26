<?php
// exportar_ics.php
require_once 'db_connection.php'; // Conexão $pdo

// Busca apenas recebíveis 'Em Aberto'
try {
    $stmt = $pdo->query("SELECT * FROM recebiveis WHERE status = 'Em Aberto' ORDER BY data_vencimento ASC");
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
