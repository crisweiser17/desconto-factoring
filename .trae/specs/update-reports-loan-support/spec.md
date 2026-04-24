# Novo Dashboard Financeiro e Estruturação de Relatórios

## Por que
Os relatórios atuais do sistema foram idealizados quando a operação era exclusivamente de "Antecipação" (Factoring). Com a inclusão de "Empréstimos", tentar adaptar as telas antigas resulta em uma visão confusa que mistura risco direto (Tomador) com risco indireto (Sacado) e não deixa claro qual operação é mais rentável. 
A melhor abordagem para um fundo/factoring moderno é construir um **Novo Relatório Geral Financeiro** do zero. Ele trará a verdade matemática sobre o dinheiro: quanto saiu do nosso bolso (Capital Adiantado), quanto já voltou (Capital Retornado) e qual foi o ganho real (Juros/Deságio), sempre separando Antecipação e Empréstimo. Após isso, os relatórios legados serão descartados.

## O que muda
1. **Criação do Novo Relatório Geral (`dashboard_financeiro.php`)**: Uma tela centralizadora que exibirá o fluxo do dinheiro e a saúde da carteira.
2. **Nova Lógica Matemática (`api_dashboard_financeiro.php`)**: Um backend dedicado a calcular as métricas financeiras agrupadas por `tipo_operacao`.
3. **Métricas Chave (Por Tipo de Operação)**:
   - **Capital Adiantado Total**: O dinheiro que efetivamente saiu do caixa (`valor_liquido_calc`).
   - **Capital Retornado (Amortização)**: A parcela do Principal que já foi paga (`valor_liquido_calc` dos recebíveis com status 'Recebido').
   - **Lucro Realizado (Juros/Deságio)**: O rendimento efetivo que entrou no caixa (`valor_original - valor_liquido_calc` dos recebíveis 'Recebidos').
   - **Capital em Aberto (Risco Ativo)**: O principal que ainda está na rua.
   - **Lucro Projetado**: Juros/Deságio a receber.
   - **Inadimplência**: Capital em atraso.
4. **Substituição Gradual**: O novo dashboard será o centro do sistema. Os links do menu para os relatórios antigos serão removidos e os arquivos apagados ao final.

## Impacto
- **Adicionados**: `dashboard_financeiro.php`, `api_dashboard_financeiro.php`.
- **Modificados**: `menu.php` (para apontar para o novo relatório e remover os antigos).
- **Removidos**: `relatorio.php`, `relatorio_cedentes.php`, `relatorio_sacados.php`, `relatorio_sacados_corrigido.php`, `relatorio_contas_a_pagar.php`, `fechamento.php` (substituídos pela nova visão unificada e limpa).

## Requisitos ADICIONADOS

### Requisito: Novo Dashboard Financeiro
O sistema DEVE fornecer um painel consolidado que exiba a performance financeira do fundo, com as seguintes seções:
#### Cenário: Visão Consolidada e Comparativa
- **DADO** que o usuário acessa o Relatório Geral
- **QUANDO** ele visualiza os indicadores
- **ENTÃO** ele deve ver cards mostrando os Totais (Soma de tudo) e a Quebra (Antecipação vs Empréstimo) para: Capital Adiantado, Capital Retornado e Lucro Realizado.

#### Cenário: Exposição de Risco Separada
- **DADO** que a página é carregada
- **ENTÃO** deve exibir listas de "Maiores Tomadores (Empréstimo)", "Maiores Cedentes (Antecipação)" e "Maiores Sacados (Antecipação)", garantindo que o risco não se misture.

## Requisitos REMOVIDOS
### Requisito: Relatórios Antigos Fragmentados
**Razão**: Os relatórios `relatorio_cedentes.php`, `relatorio_sacados.php`, `fechamento.php`, etc., não servem mais para um modelo multi-produto e suas lógicas de agrupamento estão obsoletas.
**Migração**: Todo o acompanhamento de carteira e risco será feito no `dashboard_financeiro.php`. Os arquivos antigos serão excluídos.
