# Atualização das Opções de Garantia Spec

## Why
O modal de geração de contrato possui um select "Possui Garantia?" com apenas 2 opções (Sim/Não). Para dar mais controle ao usuário e espelhar as 4 opções de templates existentes, o campo deve oferecer escolhas explícitas sobre a presença de veículo e avalista, melhorando a experiência do usuário e a clareza sobre quais campos devem ser preenchidos.

## What Changes
- **detalhes_operacao.php**: 
  - Alterar o select `modalTemGarantia` para oferecer 4 opções claras:
    1. Com Veículo e Com Avalista
    2. Sem Veículo e Sem Avalista
    3. Com Veículo e Sem Avalista
    4. Sem Veículo e Com Avalista
  - Atualizar o código JavaScript que manipula a visibilidade e a obrigatoriedade (`req-garantia`) dos campos de Avalista e Veículo. Agora teremos dois containers separados (`avalistaContainer` e `veiculoContainer`) e a visibilidade de cada um dependerá da opção escolhida no select.
- **api_contratos.php**:
  - Ajustar a lógica da variável `$tem_garantia` e `$tem_avalista` para corresponder aos novos valores enviados pelo select, garantindo que o template correto seja carregado de acordo com a seleção feita na UI.

## Impact
- Affected specs: `update-contract-template-selection` (A lógica feita lá deverá ser levemente ajustada para refletir a seleção explícita da UI).
- Affected code: `detalhes_operacao.php`, `api_contratos.php`.

## ADDED Requirements
### Requirement: Seleção Explícita de Garantias
O sistema SHALL fornecer 4 opções claras de garantias no momento da geração do contrato, habilitando dinamicamente na tela apenas os formulários pertinentes à escolha do usuário.

#### Scenario: Sem Veículo e Com Avalista
- **WHEN** o usuário seleciona "Sem Veículo e Com Avalista" no modal de contrato
- **THEN** o formulário de dados do veículo deve ficar oculto e com validação desabilitada, enquanto o formulário de avalista deve ficar visível e obrigatório.