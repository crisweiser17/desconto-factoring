# Auto-Select Natureza Operacao Spec

## Why
Atualmente, quando o usuário está visualizando uma operação de Empréstimo e clica no botão "Gerar Contratos", o modal exige que ele selecione manualmente a "Natureza da Operação" (Empréstimo ou Desconto). Para operações que já são do tipo empréstimo, isso adiciona um passo redundante e abre margem para erros. O sistema já sabe que a operação é um empréstimo, portanto a interface deve refletir e forçar isso.

## What Changes
- O modal "Gerar Contratos" em `detalhes_operacao.php` será alterado para pré-selecionar automaticamente a opção "Empréstimo" quando a operação visualizada for um empréstimo.
- Quando for um empréstimo, o campo de seleção de natureza ficará desabilitado (read-only / pointer-events: none) para impedir que o usuário altere para "Desconto" acidentalmente.
- O campo original desabilitado não enviará o valor no form submit normal, então garantiremos que o valor correto de "natureza" seja enviado na requisição AJAX de geração do contrato.

## Impact
- Affected specs: N/A
- Affected code: `detalhes_operacao.php`

## MODIFIED Requirements
### Requirement: Modal de Gerar Contratos
O sistema DEVE abrir o modal de contratos configurado adequadamente para o tipo da operação atual.

#### Scenario: Operação de Empréstimo
- **WHEN** o usuário clica em "Gerar Contratos" em uma operação de empréstimo
- **THEN** o campo "Natureza da Operação" deve estar preenchido com "Empréstimo"
- **AND** o campo "Natureza da Operação" deve estar desabilitado ou imutável.