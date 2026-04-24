# Fechamento Mensal Spec

## Por que
O usuário precisa de uma tela de "Fechamento Mensal" para facilitar o controle e o registro de cada mês que passou. Essa funcionalidade permite o lançamento de despesas, o registro da distribuição de lucro entre os sócios, e fornece um resumo geral do mês (Total Recebido, Retorno de Capital, Lucro Bruto, Despesas, Lucro Líquido e Inadimplência/Títulos Atrasados), agrupando operações de Empréstimos e Antecipações.

## O que muda
- Criação das tabelas `despesas` e `distribuicao_lucros` no banco de dados.
- Criação da API `api_fechamento.php` para consolidar os recebimentos (Empréstimos + Antecipações) e os cruzamentos de dados financeiros do mês.
- Integração e adaptação do arquivo `fechamento.php` (frontend) ao ecossistema atual.
- Adição da opção "Fechamento Mensal" no menu principal (`menu.php`).

## Impacto
- Affected specs: `menu.php` (menu dropdown de relatórios).
- Affected code:
  - Banco de Dados (criação de tabelas `despesas` e `distribuicao_lucros`)
  - `api_fechamento.php` (nova API)
  - `fechamento.php` (novo frontend baseado no layout fornecido pelo usuário)
  - `menu.php`

## ADDED Requirements
### Requirement: Tabelas de Apoio
O sistema DEVE possuir tabelas no banco de dados para armazenar despesas avulsas (`despesas`) e repasses aos sócios (`distribuicao_lucros`).

### Requirement: Consumo de Dados Mensal
O sistema DEVE calcular as entradas baseadas nos títulos `Recebido`, `Compensado` e `Parcialmente Compensado` dentro de um mês e ano específicos, bem como exibir os títulos atrasados (`Problema` e `Em Aberto` com vencimento no mês selecionado).

#### Scenario: Sucesso na visualização do Fechamento
- **QUANDO** o usuário acessar a tela Fechamento Mensal e filtrar um mês/ano.
- **THEN** o sistema exibe os cartões com os valores totais, gráficos de composição, listas de despesas, inadimplência daquele mês e distribuições de lucros, podendo o usuário cadastrar e excluir despesas ou repasses livremente.