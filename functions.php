<?php
// functions.php - Funções auxiliares para o sistema de factoring

// Funções de formatação
if (!function_exists('formatCurrency')) {
    function formatCurrency($value) {
        return 'R$ ' . number_format($value ?? 0, 2, ',', '.');
    }
}

if (!function_exists('formatHtmlCurrency')) {
    function formatHtmlCurrency($value) {
        return 'R$ ' . number_format($value ?? 0, 2, ',', '.');
    }
}

if (!function_exists('formatPercent')) {
    function formatPercent($value) {
        return number_format(($value ?? 0) * 100, 2, ',', '.') . ' %';
    }
}

if (!function_exists('formatHtmlPercentage')) {
    function formatHtmlPercentage($value, $decimals = 2) {
        return number_format($value ?? 0, $decimals, ',', '.') . '%';
    }
}

// Função para formatar data
if (!function_exists('formatDate')) {
    function formatDate($dateStr) {
        if (empty($dateStr)) return '--';
        try {
            return (new DateTime($dateStr))->format('d/m/Y');
        } catch (Exception $e) {
            return 'Data Inválida';
        }
    }
}

// Função para calcular dias entre datas
if (!function_exists('calcularDias')) {
    function calcularDias($dataInicio, $dataFim) {
        try {
            $inicio = new DateTime($dataInicio);
            $fim = new DateTime($dataFim);
            $diff = $inicio->diff($fim);
            return $diff->days;
        } catch (Exception $e) {
            return 0;
        }
    }
}

// Função para calcular dias para vencimento (considera apenas data, sem horário)
if (!function_exists('calcularDiasParaVencimento')) {
    function calcularDiasParaVencimento($dataVencimento) {
        if (empty($dataVencimento)) {
            return 'N/A';
        }
        
        try {
            // Criar objetos DateTime apenas com data (sem horário) para evitar problemas de timezone
            $hoje = new DateTime('today'); // Meia-noite de hoje
            $vencimento = new DateTime($dataVencimento);
            $vencimento->setTime(0, 0, 0); // Garantir que seja meia-noite
            
            $interval = $hoje->diff($vencimento);
            $dias = $interval->days;
            
            if ($vencimento < $hoje) {
                // Já venceu - mostrar negativo
                return '-' . $dias;
            } elseif ($vencimento->format('Y-m-d') === $hoje->format('Y-m-d')) {
                // Vence hoje - mostrar "hoje"
                return 'hoje';
            } else {
                // Vence no futuro - mostrar número de dias
                return $dias;
            }
        } catch (Exception $e) {
            return 'Erro Data';
        }
    }
}

// Função para validar data
if (!function_exists('validarData')) {
    function validarData($data) {
        if (empty($data)) return false;
        try {
            $d = DateTime::createFromFormat('Y-m-d', $data);
            return $d && $d->format('Y-m-d') === $data;
        } catch (Exception $e) {
            return false;
        }
    }
}

// Função para validar valor numérico
if (!function_exists('validarValor')) {
    function validarValor($valor) {
        return is_numeric($valor) && $valor > 0;
    }
}

// Função para sanitizar entrada
if (!function_exists('sanitizar')) {
    function sanitizar($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}

// Função para log de erros
if (!function_exists('logError')) {
    function logError($message, $context = []) {
        $logMessage = date('Y-m-d H:i:s') . ' - ' . $message;
        if (!empty($context)) {
            $logMessage .= ' - Context: ' . json_encode($context);
        }
        error_log($logMessage);
    }
}

?>