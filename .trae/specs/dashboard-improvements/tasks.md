# Tasks

- [x] Task 1: Corrigir Top 5 Tomadores de Empréstimo
  - [x] SubTask 1.1: Em `api_dashboard_financeiro.php`, alterar a query `$sqlTopTomadores` para usar `JOIN sacados s ON r.sacado_id = s.id` (ao invés de `cedentes c ON o.cedente_id = c.id`), além de buscar `s.empresa as nome`.

- [x] Task 2: Implementar Novo Gráfico de Recebimentos Projetados
  - [x] SubTask 2.1: Em `api_dashboard_financeiro.php`, adicionar uma query que agrupe por `DATE_FORMAT(r.data_vencimento, '%Y-%m')` os valores originais (`status = 'Em Aberto'`) dos próximos 6 meses.
  - [x] SubTask 2.2: Formatá-los no array de retorno JSON (meses, valores empréstimo e antecipação).
  - [x] SubTask 2.3: Em `dashboard_financeiro.php`, criar a div e o canvas `<canvas id="chartProjetado"></canvas>` logo abaixo do gráfico de lucro de 12 meses.
  - [x] SubTask 2.4: Renderizar via JavaScript (Chart.js) o gráfico de barras empilhadas dos recebimentos futuros.

- [x] Task 3: Criar Indicador e Controle de Inadimplência (Aging)
  - [x] SubTask 3.1: Em `api_dashboard_financeiro.php`, criar queries ou cálculos para extrair as faixas de atraso (`1 a 15 dias`, `16 a 30 dias`, `Mais de 30 dias`) baseadas em `DATEDIFF(CURDATE(), r.data_vencimento)` de títulos `Em Aberto`.
  - [x] SubTask 3.2: Calcular a **Taxa de Inadimplência (%)** = `(Inadimplência / Capital em Aberto) * 100`.
  - [x] SubTask 3.3: Em `dashboard_financeiro.php`, adicionar na seção de Visão Geral as Taxas (%) abaixo do valor de Inadimplência.
  - [x] SubTask 3.4: Incluir um Gráfico de Rosca (Doughnut) ou Barras deitado intitulado "Aging da Inadimplência (Idade do Atraso)" que detalhe as faixas calculadas.