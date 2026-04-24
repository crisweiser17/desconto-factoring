# Dashboard Improvements Spec

## Por que
- A lista de "Top 5 Tomadores de Empréstimo" está atualmente vazia devido à busca na tabela incorreta (`cedentes` ao invés de `sacados`), já que os tomadores de empréstimo são salvos no sistema usando a coluna `sacado_id`.
- É necessária uma projeção visual (gráfico) dos recebimentos futuros para auxiliar no planejamento do fluxo de caixa.
- Para um controle efetivo de risco, precisamos de um indicador aprimorado de inadimplência. Sugiro a implementação de um "Aging de Inadimplência" (títulos atrasados por faixas de dias) e o percentual de inadimplência (Taxa de Inadimplência da carteira ativa).

## O que muda
- **Correção do Top 5 Tomadores**: A query SQL no backend será ajustada para realizar o `JOIN` correto na tabela `sacados`.
- **Novo Gráfico de Recebimentos Projetados**: Será incluído, abaixo do gráfico de lucros, um gráfico de barras empilhadas projetando os valores a receber (`status = 'Em Aberto'`) para os próximos meses.
- **Novo Controle de Inadimplência (Aging)**: Inclusão de um novo painel ou card destacando a "Taxa de Inadimplência" e uma distribuição (Aging List) mostrando o montante vencido em faixas (Ex: 1-15 dias, 16-30 dias, > 30 dias). Isso é o mais recomendado no mercado financeiro para avaliar a "idade" da dívida.

## Impacto
- Affected specs: Nenhuma dependência externa, afeta apenas o Dashboard.
- Affected code: `api_dashboard_financeiro.php` e `dashboard_financeiro.php`.

## ADDED Requirements
### Requirement: Gráfico de Recebimentos Projetados
O sistema DEVE fornecer um gráfico mostrando os recebimentos esperados nos meses futuros, agrupados por tipo de operação (Empréstimo vs Antecipação).

### Requirement: Controle de Inadimplência (Aging)
O sistema DEVE calcular e retornar as faixas de atraso do capital vencido, exibindo visualmente na interface.

## MODIFIED Requirements
### Requirement: Top 5 Tomadores de Empréstimo
A lista DEVE renderizar corretamente os tomadores através da correção do `JOIN` na tabela `sacados`.