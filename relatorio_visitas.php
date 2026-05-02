<?php require_once 'auth_check.php'; ?>
<?php
require_once 'db_connection.php';

// --- Filtro de período ---
$periodo = $_GET['periodo'] ?? 'mes_atual';
$dataInicio = $_GET['data_inicio'] ?? '';
$dataFim    = $_GET['data_fim']    ?? '';

$hoje = new DateTime('today');
switch ($periodo) {
    case 'mes_anterior':
        $dataInicio = (clone $hoje)->modify('first day of last month')->format('Y-m-d');
        $dataFim    = (clone $hoje)->modify('last day of last month')->format('Y-m-d');
        break;
    case '30dias':
        $dataInicio = (clone $hoje)->modify('-29 days')->format('Y-m-d');
        $dataFim    = $hoje->format('Y-m-d');
        break;
    case '90dias':
        $dataInicio = (clone $hoje)->modify('-89 days')->format('Y-m-d');
        $dataFim    = $hoje->format('Y-m-d');
        break;
    case 'ano_atual':
        $dataInicio = (clone $hoje)->modify('first day of January this year')->format('Y-m-d');
        $dataFim    = $hoje->format('Y-m-d');
        break;
    case 'custom':
        if (!$dataInicio || !DateTime::createFromFormat('Y-m-d', $dataInicio)) {
            $dataInicio = (clone $hoje)->modify('first day of this month')->format('Y-m-d');
        }
        if (!$dataFim || !DateTime::createFromFormat('Y-m-d', $dataFim)) {
            $dataFim = $hoje->format('Y-m-d');
        }
        break;
    case 'mes_atual':
    default:
        $periodo = 'mes_atual';
        $dataInicio = (clone $hoje)->modify('first day of this month')->format('Y-m-d');
        $dataFim    = $hoje->format('Y-m-d');
}

$dataInicioSql = $dataInicio . ' 00:00:00';
$dataFimSql    = $dataFim    . ' 23:59:59';

// --- Query principal: agregados por responsável ---
// Junta usuários (LEFT) com leads sob responsabilidade (LEFT) e os eventos de histórico no período (LEFT, com filtro na própria condição do JOIN para preservar usuários sem atividade).
$linhas = [];
try {
    $sql = "
        SELECT
            u.id   AS usuario_id,
            u.email AS usuario_email,
            COUNT(DISTINCT CASE WHEN h.estagio_para = 'visita_agendada' THEN h.id END) AS visitas_agendadas,
            COUNT(DISTINCT CASE WHEN h.estagio_para = 'visita_feita'    THEN h.id END) AS visitas_feitas,
            COUNT(DISTINCT CASE WHEN h.estagio_para = 'aprovado'        THEN h.id END) AS aprovados,
            COUNT(DISTINCT CASE WHEN h.estagio_para = 'convertido'      THEN h.id END) AS convertidos,
            COUNT(DISTINCT CASE WHEN h.estagio_para = 'perdido'         THEN h.id END) AS perdidos,
            (SELECT COUNT(*) FROM leads l2
             WHERE l2.responsavel_id = u.id
               AND l2.estagio IN ('novo','visita_agendada','visita_feita','aprovado')) AS ativos_atual
        FROM usuarios u
        LEFT JOIN leads l ON l.responsavel_id = u.id
        LEFT JOIN leads_historico h ON h.lead_id = l.id
            AND h.data_evento BETWEEN :inicio AND :fim
        GROUP BY u.id, u.email
        ORDER BY visitas_feitas DESC, visitas_agendadas DESC, u.email ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':inicio' => $dataInicioSql, ':fim' => $dataFimSql]);
    $linhas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Linha "Sem responsável" — leads sem responsavel_id que tiveram eventos no período
    $sqlSem = "
        SELECT
            COUNT(DISTINCT CASE WHEN h.estagio_para = 'visita_agendada' THEN h.id END) AS visitas_agendadas,
            COUNT(DISTINCT CASE WHEN h.estagio_para = 'visita_feita'    THEN h.id END) AS visitas_feitas,
            COUNT(DISTINCT CASE WHEN h.estagio_para = 'aprovado'        THEN h.id END) AS aprovados,
            COUNT(DISTINCT CASE WHEN h.estagio_para = 'convertido'      THEN h.id END) AS convertidos,
            COUNT(DISTINCT CASE WHEN h.estagio_para = 'perdido'         THEN h.id END) AS perdidos,
            (SELECT COUNT(*) FROM leads l2
             WHERE l2.responsavel_id IS NULL
               AND l2.estagio IN ('novo','visita_agendada','visita_feita','aprovado')) AS ativos_atual
        FROM leads l
        LEFT JOIN leads_historico h ON h.lead_id = l.id
            AND h.data_evento BETWEEN :inicio AND :fim
        WHERE l.responsavel_id IS NULL
    ";
    $stmtSem = $pdo->prepare($sqlSem);
    $stmtSem->execute([':inicio' => $dataInicioSql, ':fim' => $dataFimSql]);
    $semResp = $stmtSem->fetch(PDO::FETCH_ASSOC);

    if ($semResp && (
        (int)$semResp['visitas_agendadas'] + (int)$semResp['visitas_feitas']
        + (int)$semResp['aprovados'] + (int)$semResp['convertidos']
        + (int)$semResp['perdidos'] + (int)$semResp['ativos_atual']
    ) > 0) {
        $linhas[] = [
            'usuario_id' => null,
            'usuario_email' => 'Sem responsável',
            'visitas_agendadas' => $semResp['visitas_agendadas'],
            'visitas_feitas'    => $semResp['visitas_feitas'],
            'aprovados'         => $semResp['aprovados'],
            'convertidos'       => $semResp['convertidos'],
            'perdidos'          => $semResp['perdidos'],
            'ativos_atual'      => $semResp['ativos_atual'],
        ];
    }
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Erro: " . htmlspecialchars($e->getMessage()) . '</div>';
}

// --- Totais ---
$totais = ['visitas_agendadas' => 0, 'visitas_feitas' => 0, 'aprovados' => 0, 'convertidos' => 0, 'perdidos' => 0, 'ativos_atual' => 0];
foreach ($linhas as $r) {
    foreach ($totais as $k => $v) $totais[$k] += (int)$r[$k];
}

// Drill-down link helper
function drillUrl($responsavelId, $evento, $dataInicio, $dataFim) {
    $params = [
        'responsavel_id' => $responsavelId === null ? 'sem' : $responsavelId,
        'evento' => $evento,
        'data_evento_inicio' => $dataInicio,
        'data_evento_fim' => $dataFim,
    ];
    return 'listar_leads.php?' . http_build_query($params);
}
function drillAtivosUrl($responsavelId) {
    $params = ['responsavel_id' => $responsavelId === null ? 'sem' : $responsavelId, 'quick' => 'ativos'];
    return 'listar_leads.php?' . http_build_query($params);
}
function fmtPct($num, $den) {
    if (!$den) return '<span class="text-muted">—</span>';
    return number_format(($num / $den) * 100, 0) . '%';
}
function periodoLabel($p, $ini, $fim) {
    $map = [
        'mes_atual' => 'Mês atual',
        'mes_anterior' => 'Mês anterior',
        '30dias' => 'Últimos 30 dias',
        '90dias' => 'Últimos 90 dias',
        'ano_atual' => 'Ano atual',
        'custom' => 'Personalizado',
    ];
    return ($map[$p] ?? '—') . ' · ' . date('d/m/Y', strtotime($ini)) . ' a ' . date('d/m/Y', strtotime($fim));
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Visitas por Usuário</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --profit: #198754; --profit-soft: #d1f0dc;
            --warn: #b76b00; --warn-soft: #fff3d6;
            --danger: #b02a37; --danger-soft: #fde2e4;
            --info: #0a4ea8; --info-soft: #eef4ff;
            --neutral: #6c757d;
            --surface: #ffffff; --surface-2: #f6f8fb;
            --border: #e3e8ef;
        }
        body { background: #eef2f7; font-size: 0.95rem; }
        .page-toolbar { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 14px 18px; margin-bottom: 18px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
        .page-toolbar h1 { font-size: 1.35rem; margin: 0; font-weight: 600; }
        .id-pill { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 999px; background: var(--info-soft); color: var(--info); font-size: 0.78rem; font-weight: 700; margin-left: 6px; }

        .filter-bar { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 10px 14px; margin-bottom: 18px; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .filter-label { font-size: 0.74rem; color: var(--neutral); text-transform: uppercase; letter-spacing: 0.04em; font-weight: 600; margin-right: 4px; }
        .filter-chip { display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 999px; background: var(--surface-2); color: var(--neutral); font-size: 0.82rem; font-weight: 600; border: 1px solid var(--border); text-decoration: none; cursor: pointer; }
        .filter-chip:hover { background: #e9ecef; color: #212529; }
        .filter-chip.active { background: var(--info-soft); color: var(--info); border-color: #c8dafc; }

        .kpi-strip { display: grid; grid-template-columns: repeat(5, 1fr); gap: 12px; margin-bottom: 18px; }
        @media (max-width: 1100px) { .kpi-strip { grid-template-columns: repeat(3, 1fr); } }
        @media (max-width: 600px)  { .kpi-strip { grid-template-columns: repeat(2, 1fr); } }
        .kpi-card { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 14px 16px; }
        .kpi-card .k-icon { width: 36px; height: 36px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; font-size: 1.1rem; margin-bottom: 6px; }
        .k-icon.b-blue   { background: var(--info-soft); color: var(--info); }
        .k-icon.b-green  { background: var(--profit-soft); color: var(--profit); }
        .k-icon.b-warn   { background: var(--warn-soft); color: var(--warn); }
        .k-icon.b-purple { background: #efe8fa; color: #6f42c1; }
        .k-icon.b-danger { background: var(--danger-soft); color: var(--danger); }
        .kpi-card .k-label { font-size: 0.72rem; color: var(--neutral); text-transform: uppercase; letter-spacing: 0.04em; font-weight: 600; }
        .kpi-card .k-value { font-size: 1.4rem; font-weight: 700; line-height: 1.1; margin-top: 2px; }
        .kpi-card .k-trend { font-size: 0.78rem; color: var(--neutral); margin-top: 4px; }

        .data-table-wrap { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; overflow: hidden; }
        .data-table-head { display: flex; justify-content: space-between; align-items: center; padding: 14px 18px; border-bottom: 1px solid var(--border); background: var(--surface-2); }
        .data-table-head h3 { margin: 0; font-size: 0.95rem; font-weight: 600; }
        .data-table { width: 100%; margin-bottom: 0; font-size: 0.9rem; }
        .data-table thead th { background: var(--surface-2); font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.04em; color: var(--neutral); font-weight: 700; border-bottom: 1px solid var(--border); border-top: none; padding: 10px 12px; white-space: nowrap; }
        .data-table tbody td { padding: 12px; vertical-align: middle; border-top: 1px solid var(--border); text-align: center; }
        .data-table tbody td:first-child { text-align: left; }
        .data-table tbody tr:hover { background: #fafbfd; }
        .data-table tfoot td { background: var(--surface-2); font-weight: 700; border-top: 2px solid var(--border); padding: 10px 12px; text-align: center; font-size: 0.85rem; }
        .data-table tfoot td:first-child { text-align: left; }

        .num-cell { font-variant-numeric: tabular-nums; font-weight: 600; }
        .num-cell a { color: var(--info); text-decoration: none; padding: 3px 8px; border-radius: 6px; display: inline-block; min-width: 32px; }
        .num-cell a:hover { background: var(--info-soft); }
        .num-cell.zero { color: var(--neutral); font-weight: 400; opacity: 0.5; }
        .num-cell.zero a { color: var(--neutral); pointer-events: none; }

        .user-cell { display: flex; align-items: center; gap: 10px; }
        .user-cell .avatar { width: 32px; height: 32px; border-radius: 50%; color: #fff; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.78rem; flex-shrink: 0; background: linear-gradient(135deg, #0d6efd, #15b079); }
        .user-cell .avatar.b1 { background: linear-gradient(135deg, #0d6efd, #15b079); }
        .user-cell .avatar.b2 { background: linear-gradient(135deg, #fd7e14, #b76b00); }
        .user-cell .avatar.b3 { background: linear-gradient(135deg, #0a8754, #15b079); }
        .user-cell .avatar.b4 { background: linear-gradient(135deg, #d63384, #b02a37); }
        .user-cell .avatar.sem { background: #adb5bd; }
        .user-cell .name { font-weight: 600; font-size: 0.9rem; }

        .empty-state { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: 60px 20px; text-align: center; color: var(--neutral); }
        .empty-state i { font-size: 3.5rem; opacity: 0.4; }

        .periodo-badge { background: var(--info-soft); color: var(--info); padding: 6px 12px; border-radius: 8px; font-size: 0.85rem; font-weight: 600; }
    </style>
</head>
<body>
    <?php require_once 'menu.php'; ?>
    <div class="container-fluid px-3 px-md-4 mt-4" style="max-width: 1500px;">

        <div class="page-toolbar">
            <div>
                <h1>
                    <i class="bi bi-bar-chart-line-fill text-info"></i>
                    Visitas por usuário
                </h1>
                <div class="text-muted small mt-1">
                    <span class="periodo-badge"><i class="bi bi-calendar3"></i> <?php echo htmlspecialchars(periodoLabel($periodo, $dataInicio, $dataFim)); ?></span>
                </div>
            </div>
        </div>

        <!-- Filtro de período -->
        <?php
        $periodoLink = function($p) {
            return '?' . http_build_query(['periodo' => $p]);
        };
        ?>
        <div class="filter-bar">
            <span class="filter-label">Período:</span>
            <a href="<?php echo $periodoLink('mes_atual'); ?>"    class="filter-chip <?php echo $periodo === 'mes_atual' ? 'active' : ''; ?>">Mês atual</a>
            <a href="<?php echo $periodoLink('mes_anterior'); ?>" class="filter-chip <?php echo $periodo === 'mes_anterior' ? 'active' : ''; ?>">Mês anterior</a>
            <a href="<?php echo $periodoLink('30dias'); ?>"       class="filter-chip <?php echo $periodo === '30dias' ? 'active' : ''; ?>">Últimos 30 dias</a>
            <a href="<?php echo $periodoLink('90dias'); ?>"       class="filter-chip <?php echo $periodo === '90dias' ? 'active' : ''; ?>">Últimos 90 dias</a>
            <a href="<?php echo $periodoLink('ano_atual'); ?>"    class="filter-chip <?php echo $periodo === 'ano_atual' ? 'active' : ''; ?>">Ano atual</a>

            <form method="GET" class="d-flex align-items-center gap-2 ms-auto">
                <input type="hidden" name="periodo" value="custom">
                <input type="date" name="data_inicio" class="form-control form-control-sm" style="width: 150px;" value="<?php echo htmlspecialchars($dataInicio); ?>">
                <span class="text-muted small">até</span>
                <input type="date" name="data_fim"    class="form-control form-control-sm" style="width: 150px;" value="<?php echo htmlspecialchars($dataFim); ?>">
                <button type="submit" class="btn btn-sm btn-outline-primary">Aplicar</button>
            </form>
        </div>

        <!-- KPIs totais do período -->
        <div class="kpi-strip">
            <div class="kpi-card">
                <div class="k-icon b-warn"><i class="bi bi-calendar-event"></i></div>
                <div class="k-label">Visitas agendadas</div>
                <div class="k-value"><?php echo $totais['visitas_agendadas']; ?></div>
                <div class="k-trend">eventos no período</div>
            </div>
            <div class="kpi-card">
                <div class="k-icon b-purple"><i class="bi bi-clipboard-check"></i></div>
                <div class="k-label">Visitas realizadas</div>
                <div class="k-value"><?php echo $totais['visitas_feitas']; ?></div>
                <div class="k-trend">conv. agendada→feita: <?php echo fmtPct($totais['visitas_feitas'], $totais['visitas_agendadas']); ?></div>
            </div>
            <div class="kpi-card">
                <div class="k-icon b-blue"><i class="bi bi-check2-circle"></i></div>
                <div class="k-label">Aprovados</div>
                <div class="k-value"><?php echo $totais['aprovados']; ?></div>
                <div class="k-trend">conv. visita→aprovado: <?php echo fmtPct($totais['aprovados'], $totais['visitas_feitas']); ?></div>
            </div>
            <div class="kpi-card">
                <div class="k-icon b-green"><i class="bi bi-person-check-fill"></i></div>
                <div class="k-label">Convertidos</div>
                <div class="k-value"><?php echo $totais['convertidos']; ?></div>
                <div class="k-trend">viraram clientes</div>
            </div>
            <div class="kpi-card">
                <div class="k-icon b-danger"><i class="bi bi-x-circle"></i></div>
                <div class="k-label">Perdidos</div>
                <div class="k-value"><?php echo $totais['perdidos']; ?></div>
                <div class="k-trend">eventos no período</div>
            </div>
        </div>

        <!-- Tabela por usuário -->
        <?php if (empty($linhas)): ?>
            <div class="empty-state">
                <i class="bi bi-bar-chart"></i>
                <h4 class="mt-3 text-muted">Sem dados para o período</h4>
                <p>Cadastre leads e atribua responsáveis para ver atividade aqui.</p>
            </div>
        <?php else: ?>
            <div class="data-table-wrap">
                <div class="data-table-head">
                    <h3>Atividade por responsável</h3>
                    <div class="text-muted small">Clique nos números para ver os leads</div>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Responsável</th>
                                <th>Visitas agendadas</th>
                                <th>Visitas realizadas</th>
                                <th>Conv. agend.→feita</th>
                                <th>Aprovados</th>
                                <th>Convertidos</th>
                                <th>Perdidos</th>
                                <th>Ativos hoje</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($linhas as $i => $r):
                                $semResp = $r['usuario_id'] === null;
                                $iniciais = $semResp ? '?' : mb_strtoupper(mb_substr(strtok($r['usuario_email'], '@'), 0, 2));
                                $bClass = $semResp ? 'sem' : ('b' . (((int)$r['usuario_id']) % 4 + 1));
                            ?>
                                <tr>
                                    <td>
                                        <div class="user-cell">
                                            <div class="avatar <?php echo $bClass; ?>"><?php echo htmlspecialchars($iniciais); ?></div>
                                            <div class="name"><?php echo htmlspecialchars($r['usuario_email']); ?></div>
                                        </div>
                                    </td>
                                    <td class="num-cell <?php echo (int)$r['visitas_agendadas'] === 0 ? 'zero' : ''; ?>">
                                        <a href="<?php echo drillUrl($r['usuario_id'], 'visita_agendada', $dataInicio, $dataFim); ?>"><?php echo (int)$r['visitas_agendadas']; ?></a>
                                    </td>
                                    <td class="num-cell <?php echo (int)$r['visitas_feitas'] === 0 ? 'zero' : ''; ?>">
                                        <a href="<?php echo drillUrl($r['usuario_id'], 'visita_feita', $dataInicio, $dataFim); ?>"><?php echo (int)$r['visitas_feitas']; ?></a>
                                    </td>
                                    <td class="num-cell"><?php echo fmtPct($r['visitas_feitas'], $r['visitas_agendadas']); ?></td>
                                    <td class="num-cell <?php echo (int)$r['aprovados'] === 0 ? 'zero' : ''; ?>">
                                        <a href="<?php echo drillUrl($r['usuario_id'], 'aprovado', $dataInicio, $dataFim); ?>"><?php echo (int)$r['aprovados']; ?></a>
                                    </td>
                                    <td class="num-cell <?php echo (int)$r['convertidos'] === 0 ? 'zero' : ''; ?>">
                                        <a href="<?php echo drillUrl($r['usuario_id'], 'convertido', $dataInicio, $dataFim); ?>"><?php echo (int)$r['convertidos']; ?></a>
                                    </td>
                                    <td class="num-cell <?php echo (int)$r['perdidos'] === 0 ? 'zero' : ''; ?>">
                                        <a href="<?php echo drillUrl($r['usuario_id'], 'perdido', $dataInicio, $dataFim); ?>"><?php echo (int)$r['perdidos']; ?></a>
                                    </td>
                                    <td class="num-cell <?php echo (int)$r['ativos_atual'] === 0 ? 'zero' : ''; ?>">
                                        <a href="<?php echo drillAtivosUrl($r['usuario_id']); ?>"><?php echo (int)$r['ativos_atual']; ?></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td>Total</td>
                                <td><?php echo $totais['visitas_agendadas']; ?></td>
                                <td><?php echo $totais['visitas_feitas']; ?></td>
                                <td><?php echo fmtPct($totais['visitas_feitas'], $totais['visitas_agendadas']); ?></td>
                                <td><?php echo $totais['aprovados']; ?></td>
                                <td><?php echo $totais['convertidos']; ?></td>
                                <td><?php echo $totais['perdidos']; ?></td>
                                <td><?php echo $totais['ativos_atual']; ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
