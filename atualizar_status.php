<?php require_once 'auth_check.php'; ?><?php
// Removido: Linhas de ini_set e error_reporting para evitar que Notices/Warnings quebrem o JSON.

// Define o cabeçalho como JSON O MAIS CEDO POSSÍVEL
header('Content-Type: application/json');

require_once 'db_connection.php';

// --- Funções de Formatação e Estilo (Incluídas diretamente) ---
if (!function_exists('formatHtmlStatus')) {
    function formatHtmlStatus($status, $data_recebimento = null) {
        $badgeClass = 'bg-secondary'; $tooltip = '';
        switch ($status) {
            case 'Em Aberto': $badgeClass = 'bg-info text-dark'; $tooltip = 'Aguardando ação ou recebimento'; break;
            case 'Recebido':
                $badgeClass = 'bg-success';
                $tooltip = 'Recebimento confirmado';
                // Se tiver data de recebimento, incluir no tooltip
                if (!empty($data_recebimento)) {
                    if (!function_exists('formatHtmlDate')) {
                        function formatHtmlDate($value) {
                            if(!$value) return '-';
                            try {
                                return (new DateTime($value))->format('d/m/Y');
                            } catch(Exception $e){
                                return '-';
                            }
                        }
                    }
                    $dataFormatada = formatHtmlDate($data_recebimento);
                    $tooltip .= ' em ' . $dataFormatada;
                }
                break;
            case 'Parcialmente Compensado': $badgeClass = 'bg-warning text-dark'; $tooltip = 'Parcialmente compensado'; break;
            case 'Problema': $badgeClass = 'bg-danger'; $tooltip = 'Problema no recebimento'; break;
        }
        
        // Se for "Recebido" e tiver data, usar tooltip customizado
        if ($status === 'Recebido' && !empty($data_recebimento)) {
            return '<div class="tooltip-wrapper" style="position: relative; display: inline-block;">
                        <span class="badge ' . $badgeClass . '">' . htmlspecialchars($status) . '</span>
                        <span class="tooltip-text" style="visibility: hidden; width: auto; min-width: 220px; max-width: 300px; background-color: #000 !important; color: #fff !important; text-align: center; border-radius: 6px; padding: 10px 15px; position: absolute; z-index: 1000; bottom: 125%; left: 50%; transform: translateX(-50%); opacity: 0; transition: opacity 0.2s; font-size: 0.75rem; white-space: normal; box-shadow: 0 2px 8px rgba(0,0,0,0.5); border: 1px solid #333;">' . htmlspecialchars($tooltip) . '</span>
                    </div>
                    <style>
                        .tooltip-wrapper:hover .tooltip-text { visibility: visible; opacity: 1; }
                        .tooltip-text::after { content: ""; position: absolute; top: 100%; left: 50%; margin-left: -5px; border-width: 5px; border-style: solid; border-color: #000 transparent transparent transparent; }
                    </style>';
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
            case 'Em Aberto': default: return '';
        }
    }
}
// --- Fim das Funções ---

// Resposta padrão
$response = ['success' => false, 'message' => 'Erro desconhecido inicial.'];

// Verifica Método e Dados
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Método inválido.';
    echo json_encode($response);
    exit; // Termina o script
}
if (!isset($_POST['id']) || !isset($_POST['status']) || !is_numeric($_POST['id'])) {
    $response['message'] = 'Dados inválidos.';
    echo json_encode($response);
    exit; // Termina o script
}

// Dados
$recebivel_id = (int)$_POST['id'];
$new_status = trim($_POST['status']);
$allowed_statuses = ['Em Aberto', 'Recebido', 'Problema', 'Parcialmente Compensado'];

// Valida Status
if (!in_array($new_status, $allowed_statuses)) {
     $response['message'] = 'Status inválido: ' . htmlspecialchars($new_status);
     echo json_encode($response);
    exit; // Termina o script
}

// Tenta atualizar
try {
    // Se o status for "Recebido", também atualiza a data_recebimento para a data atual
    if ($new_status === 'Recebido') {
        $sql = "UPDATE recebiveis SET status = :status, data_recebimento = CURDATE() WHERE id = :id";
    } else {
        // Se não for "Recebido", limpa a data_recebimento (caso estava marcado como recebido antes)
        $sql = "UPDATE recebiveis SET status = :status, data_recebimento = NULL WHERE id = :id";
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':status', $new_status, PDO::PARAM_STR);
    $stmt->bindParam(':id', $recebivel_id, PDO::PARAM_INT);
    $success = $stmt->execute();

    if ($success && $stmt->rowCount() > 0) {
        // Buscar a data de recebimento atualizada para mostrar no status
        $data_recebimento = null;
        if ($new_status === 'Recebido') {
            $stmt_data = $pdo->prepare("SELECT data_recebimento FROM recebiveis WHERE id = :id");
            $stmt_data->bindParam(':id', $recebivel_id, PDO::PARAM_INT);
            $stmt_data->execute();
            $result = $stmt_data->fetch(PDO::FETCH_ASSOC);
            $data_recebimento = $result['data_recebimento'] ?? null;
        }
        
        // Sucesso
        $newStatusHtml = formatHtmlStatus($new_status, $data_recebimento);
        ob_start();
        if ($new_status === 'Em Aberto') {
            ?>
            <button class="btn btn-success action-btn update-status-btn" data-id="<?php echo $recebivel_id; ?>" data-status="Recebido" title="Marcar como Recebido"><i class="bi bi-check-lg"></i></button>
            <button class="btn btn-danger action-btn update-status-btn" data-id="<?php echo $recebivel_id; ?>" data-status="Problema" title="Marcar com Problema"><i class="bi bi-exclamation-triangle-fill"></i></button>
            <?php
        } elseif ($new_status === 'Parcialmente Compensado') {
            ?>
            <button class="btn btn-success action-btn update-status-btn" data-id="<?php echo $recebivel_id; ?>" data-status="Recebido" title="Marcar como Recebido"><i class="bi bi-check-lg"></i></button>
            <button class="btn btn-danger action-btn update-status-btn" data-id="<?php echo $recebivel_id; ?>" data-status="Problema" title="Marcar com Problema"><i class="bi bi-exclamation-triangle-fill"></i></button>
            <?php
        } elseif ($new_status === 'Problema') {
            ?>
            <button class="btn btn-success action-btn update-status-btn" data-id="<?php echo $recebivel_id; ?>" data-status="Recebido" title="Marcar como Recebido"><i class="bi bi-check-lg"></i></button>
            <button class="btn btn-secondary action-btn update-status-btn" data-id="<?php echo $recebivel_id; ?>" data-status="Em Aberto" title="Reverter para Em Aberto"><i class="bi bi-arrow-counterclockwise"></i></button>
            <?php
        } elseif ($new_status === 'Recebido') {
            ?>
            <button class="btn btn-secondary action-btn update-status-btn" data-id="<?php echo $recebivel_id; ?>" data-status="Em Aberto" title="Reverter para Em Aberto"><i class="bi bi-arrow-counterclockwise"></i></button>
            <?php
        }
        $newActionsHtml = ob_get_clean();
        $newRowClass = getTableRowClass($new_status);

        $response = [
            'success' => true,
            'message' => 'Status atualizado com sucesso para ' . htmlspecialchars($new_status) . '.',
            'newStatusHtml' => $newStatusHtml,
            'newActionsHtml' => $newActionsHtml,
            'newRowClass' => $newRowClass
        ];

    } elseif ($success && $stmt->rowCount() == 0) {
         $response['message'] = 'Nenhuma linha afetada (ID ' . $recebivel_id . ' não encontrado ou status já era ' . htmlspecialchars($new_status) . ').';
    } else {
        $response['message'] = 'Falha ao executar UPDATE.';
    }

} catch (PDOException $e) {
     error_log("PDO Error: " . $e->getMessage());
     $response['message'] = 'Erro no Banco de Dados (PDO).';
} catch (Exception $e) {
     error_log("General Error: " . $e->getMessage());
     $response['message'] = 'Erro Geral no Servidor.';
}

// Envia a resposta JSON FINAL e termina o script
echo json_encode($response);
exit;
?>
