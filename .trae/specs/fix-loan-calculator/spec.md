# Correção da Calculadora de Empréstimo Spec

## Why
O usuário identificou três problemas na calculadora de empréstimo: 1) A taxa de juros está sendo ignorada em certas condições, especialmente quando o primeiro vencimento é maior que 30 dias; 2) Falta uma opção de "Pagamento Único" no campo de frequência; 3) O campo de taxa deveria estar dentro da seção de empréstimo para melhor organização. Estas melhorias vão garantir cálculos mais precisos e uma interface mais intuitiva.

## What Changes
- **calculate.php**: Corrigir o tratamento de dias=0 e ajustar o limiar de validação de taxa
- **index.php**: Adicionar opção "Pagamento Único", renomear "Qtd. de parcelas" para "Num. de parcelas", mover campo de taxa para dentro do fieldset de empréstimo
- **financeMath.js**: Adicionar suporte para cálculo de pagamento único
- Validação para quantidade de parcelas = 1 quando "Pagamento Único" for selecionado

## Impact
- Affected specs: Nenhuma
- Affected code: `calculate.php`, `index.php`, `financeMath.js`

## ADDED Requirements
### Requirement: Opção Pagamento Único
O sistema SHALL oferecer uma opção de "Pagamento Único" que automaticamente define a quantidade de parcelas como 1 e desabilita a edição deste campo.

#### Scenario: Usuário seleciona Pagamento Único
- **WHEN** o usuário seleciona "Pagamento Único" na frequência
- **THEN** o campo "Num. de parcelas" deve ser automaticamente preenchido com 1 e desabilitado para edição

## MODIFIED Requirements
### Requirement: Cálculo com Primeiro Vencimento > 30 Dias
O sistema SHALL aplicar corretamente a taxa de juros mesmo quando o primeiro vencimento for maior que 30 dias, removendo o tratamento especial para dias=0.

#### Scenario: Vencimento com 45 dias
- **WHEN** o usuário define o primeiro vencimento para 45 dias no futuro
- **THEN** o sistema deve aplicar a taxa proporcionalmente (45/30) ao invés de ignorar a taxa