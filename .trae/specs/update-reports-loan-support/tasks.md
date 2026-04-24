# Tasks

- [x] Task 1: Construção da Nova API Matemática (`api_dashboard_financeiro.php`)
  - [x] SubTask 1.1: Criar o arquivo `api_dashboard_financeiro.php` com suporte a autenticação.
  - [x] SubTask 1.2: Desenvolver a query `SQL` que calcula o **Capital Adiantado Total** e o agrupa por `tipo_operacao` (Antecipação vs Empréstimo).
  - [x] SubTask 1.3: Desenvolver a query `SQL` que calcula o **Capital Retornado (Amortização)** e o **Lucro Realizado (Juros/Deságio)**, filtrando por `status = 'Recebido'` e agrupando por `tipo_operacao`.
  - [x] SubTask 1.4: Desenvolver as queries de **Inadimplência** (Títulos Vencidos e não pagos) e **Lucro Projetado**.
  - [x] SubTask 1.5: Retornar todos os dados consolidados e separados (Antecipação vs Empréstimo) em JSON.

- [x] Task 2: Construção da Interface do Novo Dashboard (`dashboard_financeiro.php`)
  - [x] SubTask 2.1: Criar o arquivo `dashboard_financeiro.php` e incluir a estrutura base do Bootstrap e Menu do sistema.
  - [x] SubTask 2.2: Implementar os painéis (Cards) superiores: "Visão Geral do Fundo" com os somatórios totais.
  - [x] SubTask 2.3: Implementar uma seção dividida (ou cards duplos) comparando lado a lado: "Performance de Empréstimos" e "Performance de Antecipações".
  - [x] SubTask 2.4: Consumir a `api_dashboard_financeiro.php` via AJAX/Fetch e popular os dados dinamicamente.
  - [x] SubTask 2.5: Implementar um Gráfico (Chart.js) de Composição da Carteira (Pizza) e outro de Lucro Realizado nos últimos 12 meses (Barras separadas por tipo de operação).

- [x] Task 3: Tabelas de Risco e Exposição no Dashboard
  - [x] SubTask 3.1: Adicionar uma tabela de "Top 5 Tomadores de Empréstimo" (ordenado por risco em aberto).
  - [x] SubTask 3.2: Adicionar uma tabela de "Top 5 Cedentes de Antecipação" (risco indireto).
  - [x] SubTask 3.3: Adicionar uma tabela de "Top 5 Sacados" (filtrando estritamente `tipo_operacao = 'antecipacao'`).

- [x] Task 4: Atualização do Menu e Remoção do Legado
  - [x] SubTask 4.1: Atualizar o arquivo `menu.php`, alterando o item de "Relatório" para apontar para `dashboard_financeiro.php`.
  - [x] SubTask 4.2: Excluir os itens antigos de "Relatório de Cedentes", "Relatório de Sacados", "Contas a Pagar" e "Fechamento" do menu.
  - [x] SubTask 4.3: Excluir fisicamente os arquivos obsoletos (`relatorio.php`, `relatorio_cedentes.php`, `relatorio_sacados.php`, `relatorio_sacados_corrigido.php`, `relatorio_contas_a_pagar.php`, `fechamento.php`, `api_fechamento.php`, `funcoes_lucro.php`).
