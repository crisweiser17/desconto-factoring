# Adicionar Operação de Empréstimo Spec

## Why
Atualmente, o sistema suporta operações de desconto (antecipação de recebíveis), operando "de trás pra frente" (descontando um título futuro para o presente). A empresa agora precisa emitir créditos (empréstimos), onde aplica-se juros sobre um valor presente (o crédito concedido) para gerar recebíveis futuros (parcelas) em datas específicas. Essa nova modalidade deve estar totalmente integrada com o fluxo existente de cálculo, registro e controle de recebíveis.

## What Changes
- **Banco de Dados**: Adicionar a coluna `tipo_operacao` ENUM('antecipacao', 'emprestimo') DEFAULT 'antecipacao' na tabela `operacoes`.
- **Interface da Simulação (`index.php`)**:
  - Adicionar um campo de seleção "Tipo de Operação" (Antecipação ou Empréstimo) no topo da tela.
  - Se "Empréstimo" for selecionado, exibir uma nova seção "Calculadora de Empréstimo" com os campos:
    - Valor do Empréstimo (R$)
    - Frequência das Parcelas (Semanal, Quinzenal, Mensal)
    - Quantidade de Parcelas
    - Data do Primeiro Vencimento
  - Ocultar/desabilitar a tabela manual de títulos (`#titulosTable`) e seu botão de adicionar, e preenchê-la dinamicamente via JavaScript usando a fórmula da Tabela Price para gerar as parcelas do empréstimo.
- **Lógica de Registro (`registrar_operacao.php`)**: Capturar o novo campo `tipo_operacao` e salvá-ol no banco de dados. As parcelas geradas serão salvas automaticamente como recebíveis (tipo `nota_promissoria` ou outro padrão).
- **Listagem e Controle (`listar_recebiveis.php` e `listar_operacoes.php`)**:
  - Incluir filtros visuais e de busca para separar operações de "Antecipação" das de "Empréstimo".
  - Mostrar o tipo da operação nas tabelas de listagem para rápida identificação.

## Impact
- Affected specs: Fluxo de criação de simulações, registro de operações, e controle de recebíveis.
- Affected code:
  - `index.php` (Nova UI e lógica JS da calculadora).
  - `registrar_operacao.php` (Receber e gravar `tipo_operacao`).
  - `listar_recebiveis.php` / `listar_operacoes.php` (Filtros e colunas de listagem).
  - `detalhes_operacao.php` (Exibir o tipo da operação).
  - Arquivo SQL para migração do banco (`adicionar_tipo_operacao.sql`).

## ADDED Requirements
### Requirement: Calculadora de Empréstimo Integrada
O sistema DEVE prover uma calculadora na tela de Simulação que, ao informar o valor principal, taxa, parcelas e frequência, gere as datas e valores futuros das parcelas e alimente a tabela de títulos para posterior registro.

#### Scenario: Geração de Parcelas
- **WHEN** o usuário seleciona "Empréstimo", insere R$ 1.000,00, 5% a.m., 2 parcelas mensais,
- **THEN** o JS calcula e insere 2 linhas na tabela de recebíveis (com valores nominais calculados pela Tabela Price) e invoca o cálculo do sistema para mostrar o "Valor Líquido Pago" (o valor creditado ao cliente).

## MODIFIED Requirements
### Requirement: Listagem de Recebíveis e Operações
O controle de recebíveis e operações DEVE diferenciar visualmente e permitir o filtro entre operações do tipo "Antecipação" e "Empréstimo", mantendo a compatibilidade do modelo de dados.
