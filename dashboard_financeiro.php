<?php 
require_once 'auth_check.php'; 
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Financeiro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
    <style>
        body { background-color: #f8f9fa; }
        .card-metric { transition: transform 0.2s; border: none; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 1rem; }
        .card-metric:hover { transform: translateY(-5px); }
        .metric-title { font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px; color: #6c757d; font-weight: 600; }
        .metric-value { font-size: 1.5rem; font-weight: bold; color: #343a40; }
        .metric-value-lg { font-size: 1.8rem; }
        .table-container { background: #fff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); padding: 1.5rem; margin-bottom: 1.5rem; }
        .section-title { border-bottom: 2px solid #dee2e6; padding-bottom: 10px; margin-bottom: 20px; color: #495057; font-size: 1.1rem; }
        .bg-light-blue { background-color: #e9f2fb; }
        .bg-light-green { background-color: #e9fbed; }
    </style>
</head>
<body>
    <?php require_once 'menu.php'; ?>

    <div class="container-fluid px-4 py-4">
        <h2 class="mb-4"><i class="bi bi-graph-up-arrow text-primary"></i> Dashboard Financeiro</h2>

        <!-- Visão Geral do Fundo -->
        <div class="table-container mb-4">
            <h5 class="section-title"><i class="bi bi-globe"></i> Visão Geral do Fundo</h5>
            <div class="row">
                <div class="col-md">
                    <div class="card card-metric border-start border-4 border-primary">
                        <div class="card-body">
                            <div class="metric-title">Capital Adiantado Total</div>
                            <div class="metric-value metric-value-lg" id="geral-capital">R$ 0,00</div>
                        </div>
                    </div>
                </div>
                <div class="col-md">
                    <div class="card card-metric border-start border-4 border-success">
                        <div class="card-body">
                            <div class="metric-title">Amortizado Total</div>
                            <div class="metric-value metric-value-lg" id="geral-amortizado">R$ 0,00</div>
                        </div>
                    </div>
                </div>
                <div class="col-md">
                    <div class="card card-metric border-start border-4 border-info">
                        <div class="card-body">
                            <div class="metric-title">Lucro Realizado Total</div>
                            <div class="metric-value metric-value-lg" id="geral-lucro">R$ 0,00</div>
                        </div>
                    </div>
                </div>
                <div class="col-md">
                    <div class="card card-metric border-start border-4 border-warning">
                        <div class="card-body">
                            <div class="metric-title">Lucro Projetado</div>
                            <div class="metric-value metric-value-lg" id="geral-projetado">R$ 0,00</div>
                        </div>
                    </div>
                </div>
                <div class="col-md">
                    <div class="card card-metric border-start border-4 border-danger">
                        <div class="card-body">
                            <div class="metric-title">Inadimplência Total</div>
                            <div class="metric-value metric-value-lg" id="geral-inadimplencia">R$ 0,00</div>
                            <div class="text-muted small mt-1" id="geral-taxa-inadimplencia">Taxa: 0.00%</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Lado a Lado -->
        <div class="row">
            <!-- Empréstimos -->
            <div class="col-xl-6 mb-4">
                <div class="table-container h-100 bg-light-blue">
                    <h5 class="section-title"><i class="bi bi-bank"></i> Performance de Empréstimos</h5>
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="card card-metric">
                                <div class="card-body py-2">
                                    <div class="metric-title">Capital Adiantado</div>
                                    <div class="metric-value text-primary" id="emp-capital">R$ 0,00</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="card card-metric">
                                <div class="card-body py-2">
                                    <div class="metric-title">Amortizado</div>
                                    <div class="metric-value text-success" id="emp-amortizado">R$ 0,00</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="card card-metric">
                                <div class="card-body py-2">
                                    <div class="metric-title">Lucro Realizado</div>
                                    <div class="metric-value text-info" id="emp-lucro">R$ 0,00</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="card card-metric">
                                <div class="card-body py-2">
                                    <div class="metric-title">Inadimplência</div>
                                    <div class="metric-value text-danger" id="emp-inadimplencia">R$ 0,00</div>
                                    <div class="text-muted small" style="font-size: 0.75rem;" id="emp-taxa-inadimplencia">Taxa: 0.00%</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <h6 class="mt-3"><i class="bi bi-list-ol"></i> Top 5 Tomadores de Empréstimo</h6>
                    <div class="table-responsive bg-white rounded p-2 border">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Tomador</th>
                                    <th class="text-end">Em Aberto</th>
                                    <th class="text-end">Vencido</th>
                                </tr>
                            </thead>
                            <tbody id="emp-top5-body">
                                <tr><td colspan="3" class="text-center text-muted">Carregando...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Antecipações -->
            <div class="col-xl-6 mb-4">
                <div class="table-container h-100 bg-light-green">
                    <h5 class="section-title"><i class="bi bi-cash-coin"></i> Performance de Antecipações</h5>
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="card card-metric">
                                <div class="card-body py-2">
                                    <div class="metric-title">Capital Adiantado</div>
                                    <div class="metric-value text-primary" id="ant-capital">R$ 0,00</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="card card-metric">
                                <div class="card-body py-2">
                                    <div class="metric-title">Amortizado</div>
                                    <div class="metric-value text-success" id="ant-amortizado">R$ 0,00</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="card card-metric">
                                <div class="card-body py-2">
                                    <div class="metric-title">Lucro Realizado</div>
                                    <div class="metric-value text-info" id="ant-lucro">R$ 0,00</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="card card-metric">
                                <div class="card-body py-2">
                                    <div class="metric-title">Inadimplência</div>
                                    <div class="metric-value text-danger" id="ant-inadimplencia">R$ 0,00</div>
                                    <div class="text-muted small" style="font-size: 0.75rem;" id="ant-taxa-inadimplencia">Taxa: 0.00%</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-6 mb-3">
                            <h6><i class="bi bi-list-ol"></i> Top 5 Cedentes</h6>
                            <div class="table-responsive bg-white rounded p-2 border">
                                <table class="table table-sm table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Cedente</th>
                                            <th class="text-end">Risco</th>
                                        </tr>
                                    </thead>
                                    <tbody id="ant-top5-cedentes-body">
                                        <tr><td colspan="2" class="text-center text-muted">Carregando...</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <h6><i class="bi bi-list-ol"></i> Top 5 Sacados</h6>
                            <div class="table-responsive bg-white rounded p-2 border">
                                <table class="table table-sm table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Sacado</th>
                                            <th class="text-end">Risco</th>
                                        </tr>
                                    </thead>
                                    <tbody id="ant-top5-sacados-body">
                                        <tr><td colspan="2" class="text-center text-muted">Carregando...</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráficos -->
        <div class="row">
            <div class="col-lg-4 mb-4">
                <div class="table-container h-100">
                    <h5 class="section-title"><i class="bi bi-pie-chart"></i> Composição da Carteira</h5>
                    <div style="position: relative; height: 300px;">
                        <canvas id="chartComposicao"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-8 mb-4">
                <div class="table-container h-100">
                    <h5 class="section-title"><i class="bi bi-bar-chart"></i> Lucro Realizado (Últimos 12 Meses)</h5>
                    <div style="position: relative; height: 300px;">
                        <canvas id="chartLucro12m"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráficos 2 -->
        <div class="row">
            <div class="col-lg-4 mb-4">
                <div class="table-container h-100">
                    <h5 class="section-title"><i class="bi bi-clock-history"></i> Aging da Inadimplência</h5>
                    <div style="position: relative; height: 300px;">
                        <canvas id="chartAging"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-8 mb-4">
                <div class="table-container h-100">
                    <h5 class="section-title"><i class="bi bi-calendar-event"></i> Recebimentos Projetados (Próx 6 Meses)</h5>
                    <div style="position: relative; height: 300px;">
                        <canvas id="chartProjetado"></canvas>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const formatCurrency = (value) => {
            return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value);
        };

        const formatCurrencyCompact = (value) => {
            return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL', notation: 'compact', maximumFractionDigits: 1 }).format(value);
        };

        Chart.register(ChartDataLabels);

        const renderTop5 = (data, tbodyId, showVencido = true) => {
            const tbody = document.getElementById(tbodyId);
            tbody.innerHTML = '';
            
            if (!data || data.length === 0) {
                const cols = showVencido ? 3 : 2;
                tbody.innerHTML = `<tr><td colspan="${cols}" class="text-center text-muted">Nenhum dado</td></tr>`;
                return;
            }

            data.forEach(item => {
                const tr = document.createElement('tr');
                let html = `<td class="text-truncate" style="max-width: 150px;" title="${item.nome}">${item.nome}</td>
                            <td class="text-end">${formatCurrency(item.valor_em_aberto)}</td>`;
                if (showVencido) {
                    html += `<td class="text-end text-danger">${formatCurrency(item.valor_vencido)}</td>`;
                }
                tr.innerHTML = html;
                tbody.appendChild(tr);
            });
        };

        document.addEventListener('DOMContentLoaded', async () => {
            try {
                const response = await fetch('api_dashboard_financeiro.php');
                const result = await response.json();

                if (result.success && result.data) {
                    const d = result.data;

                    // Geral
                    if (d.geral) {
                        document.getElementById('geral-capital').textContent = formatCurrency(d.geral.capital_adiantado);
                        document.getElementById('geral-amortizado').textContent = formatCurrency(d.geral.amortizado);
                        document.getElementById('geral-lucro').textContent = formatCurrency(d.geral.lucro_realizado);
                        document.getElementById('geral-projetado').textContent = formatCurrency(d.geral.lucro_projetado);
                        document.getElementById('geral-inadimplencia').textContent = formatCurrency(d.geral.inadimplencia);
                    }

                    // Empréstimo
                    if (d.emprestimo) {
                        document.getElementById('emp-capital').textContent = formatCurrency(d.emprestimo.capital_adiantado);
                        document.getElementById('emp-amortizado').textContent = formatCurrency(d.emprestimo.amortizado);
                        document.getElementById('emp-lucro').textContent = formatCurrency(d.emprestimo.lucro_realizado);
                        document.getElementById('emp-inadimplencia').textContent = formatCurrency(d.emprestimo.inadimplencia);
                        renderTop5(d.emprestimo.top_5_tomadores, 'emp-top5-body', true);
                    }

                    // Antecipação
                    if (d.antecipacao) {
                        document.getElementById('ant-capital').textContent = formatCurrency(d.antecipacao.capital_adiantado);
                        document.getElementById('ant-amortizado').textContent = formatCurrency(d.antecipacao.amortizado);
                        document.getElementById('ant-lucro').textContent = formatCurrency(d.antecipacao.lucro_realizado);
                        document.getElementById('ant-inadimplencia').textContent = formatCurrency(d.antecipacao.inadimplencia);
                        renderTop5(d.antecipacao.top_5_cedentes, 'ant-top5-cedentes-body', false);
                        renderTop5(d.antecipacao.top_5_sacados, 'ant-top5-sacados-body', false);
                    }

                    // Gráfico de Composição (Pizza) - Empréstimo vs Antecipação em Aberto
                    const ctxComposicao = document.getElementById('chartComposicao').getContext('2d');
                    const empAberto = Math.max(0, d.emprestimo.capital_adiantado - d.emprestimo.amortizado);
                    const antAberto = Math.max(0, d.antecipacao.capital_adiantado - d.antecipacao.amortizado);
                    
                    new Chart(ctxComposicao, {
                        type: 'pie',
                        data: {
                            labels: ['Empréstimos (Risco)', 'Antecipações (Risco)'],
                            datasets: [{
                                data: [empAberto, antAberto],
                                backgroundColor: ['#0d6efd', '#20c997'],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                datalabels: {
                                    color: '#fff',
                                    font: { weight: 'bold' },
                                    formatter: (value, ctx) => {
                                        return value > 0 ? formatCurrencyCompact(value) : '';
                                    }
                                },
                                legend: { position: 'bottom' },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            let label = context.label || '';
                                            if (label) label += ': ';
                                            label += formatCurrency(context.raw);
                                            return label;
                                        }
                                    }
                                }
                            }
                        }
                    });

                    // Gráfico de Lucro 12m (Barras)
                    if (d.grafico_lucro_12m) {
                        const ctxLucro = document.getElementById('chartLucro12m').getContext('2d');
                        new Chart(ctxLucro, {
                            type: 'bar',
                            data: {
                                labels: d.grafico_lucro_12m.meses,
                                datasets: [
                                    {
                                        label: 'Empréstimo',
                                        data: d.grafico_lucro_12m.emprestimo,
                                        backgroundColor: '#0d6efd'
                                    },
                                    {
                                        label: 'Antecipação',
                                        data: d.grafico_lucro_12m.antecipacao,
                                        backgroundColor: '#20c997'
                                    }
                                ]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                layout: { padding: { top: 20 } },
                                scales: {
                                    x: { stacked: true },
                                    y: { 
                                        stacked: true,
                                        ticks: {
                                            callback: function(value) {
                                                return 'R$ ' + value;
                                            }
                                        }
                                    }
                                },
                                plugins: {
                                    datalabels: {
                                        anchor: 'end',
                                        align: 'top',
                                        color: '#495057',
                                        font: { weight: 'bold', size: 10 },
                                        formatter: (value, ctx) => {
                                            let sum = 0;
                                            let dataArr = ctx.chart.data.datasets;
                                            if (ctx.datasetIndex === dataArr.length - 1) {
                                                dataArr.forEach(dataset => {
                                                    sum += dataset.data[ctx.dataIndex];
                                                });
                                                if (sum > 0) return formatCurrencyCompact(sum);
                                            }
                                            return '';
                                        }
                                    },
                                    tooltip: {
                                        callbacks: {
                                            label: function(context) {
                                                let label = context.dataset.label || '';
                                                if (label) label += ': ';
                                                label += formatCurrency(context.raw);
                                                return label;
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    }

                    // Cálculo de Taxas de Inadimplência
                    const calcTaxa = (inadimplencia, capital, amortizado) => {
                        const emAberto = Math.max(0, capital - amortizado);
                        if (emAberto > 0) {
                            return ((inadimplencia / emAberto) * 100).toFixed(2) + '%';
                        }
                        return '0.00%';
                    };

                    if (d.geral) {
                        document.getElementById('geral-taxa-inadimplencia').textContent = 'Taxa: ' + calcTaxa(d.geral.inadimplencia, d.geral.capital_adiantado, d.geral.amortizado);
                    }
                    if (d.emprestimo) {
                        document.getElementById('emp-taxa-inadimplencia').textContent = 'Taxa: ' + calcTaxa(d.emprestimo.inadimplencia, d.emprestimo.capital_adiantado, d.emprestimo.amortizado);
                    }
                    if (d.antecipacao) {
                        document.getElementById('ant-taxa-inadimplencia').textContent = 'Taxa: ' + calcTaxa(d.antecipacao.inadimplencia, d.antecipacao.capital_adiantado, d.antecipacao.amortizado);
                    }

                    // Gráfico de Aging
                    if (d.aging) {
                        const ctxAging = document.getElementById('chartAging').getContext('2d');
                        new Chart(ctxAging, {
                            type: 'doughnut',
                            data: {
                                labels: ['Até 15 dias', '16 a 30 dias', 'Mais de 30 dias'],
                                datasets: [{
                                    data: [d.aging.ate_15_dias, d.aging.de_16_a_30_dias, d.aging.mais_de_30_dias],
                                    backgroundColor: ['#ffc107', '#fd7e14', '#dc3545'],
                                    borderWidth: 1
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    datalabels: {
                                        color: '#fff',
                                        font: { weight: 'bold' },
                                        formatter: (value, ctx) => {
                                            return value > 0 ? formatCurrencyCompact(value) : '';
                                        }
                                    },
                                    legend: { position: 'bottom' },
                                    tooltip: {
                                        callbacks: {
                                            label: function(context) {
                                                let label = context.label || '';
                                                if (label) label += ': ';
                                                label += formatCurrency(context.raw);
                                                return label;
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    }

                    // Gráfico de Recebimentos Projetados
                    if (d.grafico_recebimentos) {
                        const ctxProj = document.getElementById('chartProjetado').getContext('2d');
                        new Chart(ctxProj, {
                            type: 'bar',
                            data: {
                                labels: d.grafico_recebimentos.meses,
                                datasets: [
                                    {
                                        label: 'Empréstimo',
                                        data: d.grafico_recebimentos.emprestimo,
                                        backgroundColor: '#0d6efd'
                                    },
                                    {
                                        label: 'Antecipação',
                                        data: d.grafico_recebimentos.antecipacao,
                                        backgroundColor: '#20c997'
                                    }
                                ]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                layout: { padding: { top: 20 } },
                                scales: {
                                    x: { stacked: true },
                                    y: { 
                                        stacked: true,
                                        ticks: {
                                            callback: function(value) {
                                                return 'R$ ' + value;
                                            }
                                        }
                                    }
                                },
                                plugins: {
                                    datalabels: {
                                        anchor: 'end',
                                        align: 'top',
                                        color: '#495057',
                                        font: { weight: 'bold', size: 10 },
                                        formatter: (value, ctx) => {
                                            let sum = 0;
                                            let dataArr = ctx.chart.data.datasets;
                                            if (ctx.datasetIndex === dataArr.length - 1) {
                                                dataArr.forEach(dataset => {
                                                    sum += dataset.data[ctx.dataIndex];
                                                });
                                                if (sum > 0) return formatCurrencyCompact(sum);
                                            }
                                            return '';
                                        }
                                    },
                                    tooltip: {
                                        callbacks: {
                                            label: function(context) {
                                                let label = context.dataset.label || '';
                                                if (label) label += ': ';
                                                label += formatCurrency(context.raw);
                                                return label;
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    }

                } else {
                    console.error('Falha ao carregar os dados:', result.message);
                }
            } catch (error) {
                console.error('Erro na requisição da API:', error);
            }
        });
    </script>
</body>
</html>
