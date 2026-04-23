# Spec: Tornar Sacado (Tomador) opcional na Simulação de Empréstimo

## Why
Atualmente, para usar a calculadora de empréstimos apenas para simular valores e parcelas, o usuário é obrigado a selecionar um "Tomador de Empréstimo (Sacado)". Isso atrapalha o uso da ferramenta para consultas rápidas ou simulações para clientes que ainda não estão cadastrados no sistema.

## What Changes
- **Interface (`index.php`)**:
  - Remover a validação impeditiva (alert) no botão "Gerar Parcelas" que exige a seleção do Tomador.
  - Se um Tomador não for selecionado na simulação, as linhas da tabela serão geradas com o campo "Sacado" vazio/desmarcado.
  - Adicionar uma validação no botão "Registrar Operação" para garantir que, se a operação for um empréstimo, um Tomador tenha sido selecionado antes de enviar para o banco de dados.

## Impact
- Affected specs: Formulário de Simulação, Registro de Operação.
- Affected code: `index.php`.

## ADDED Requirements
### Requirement: Validação de Registro de Empréstimo
O sistema DEVE impedir o registro de uma operação de empréstimo se o campo "Tomador de Empréstimo (Sacado)" estiver vazio, exibindo uma mensagem de erro na interface.

#### Scenario: Tentativa de registro sem Tomador
- **WHEN** usuário clica em "Registrar Operação"
- **AND** o tipo de operação é "Empréstimo"
- **AND** o campo "Tomador" está vazio
- **THEN** o registro é bloqueado e uma mensagem de erro "Erro: Selecione o Tomador para registrar." é exibida.

## MODIFIED Requirements
### Requirement: Simulação de Empréstimo
O sistema DEVE permitir a geração de parcelas e o cálculo de totais na modalidade Empréstimo mesmo que o campo "Tomador de Empréstimo (Sacado)" não esteja preenchido.