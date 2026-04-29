# Corrigir Valor Líquido e Label de Empréstimo Spec

## Why
O contrato de empréstimo ainda exibe a linha `= VALOR LÍQUIDO A PAGAR AO CEDENTE` sem o valor numérico correspondente, mesmo com o total calculado no resumo financeiro.
Além disso, a tela de detalhes da operação ainda usa o rótulo `Valor Futuro da Operação`, e o usuário deseja a nomenclatura `Valor a Receber`.

## What Changes
- Corrigir o payload do resumo financeiro do contrato para que a linha `VALOR LÍQUIDO A PAGAR AO CEDENTE` exiba o total monetário correto.
- Garantir que esse total seja coerente com os títulos e com o valor líquido por extenso já gerado no contrato.
- Ajustar em `detalhes_operacao.php` o rótulo de empréstimo de `Valor Futuro da Operação:` para `Valor a Receber`.
- Preservar o comportamento atual dos contratos de desconto e dos demais labels já ajustados.

## Impact
- Affected specs: geração de contratos, resumo financeiro do borderô, labels da tela de detalhes
- Affected code: `api_contratos.php`, templates de contrato em `_contratos`, `detalhes_operacao.php`

## ADDED Requirements
### Requirement: Exibir Valor Líquido Numérico no Resumo do Contrato
O sistema SHALL preencher a linha `VALOR LÍQUIDO A PAGAR AO CEDENTE` com o valor monetário total calculado para a operação.

#### Scenario: Contrato de empréstimo com resumo financeiro válido
- **WHEN** o sistema gerar o contrato de empréstimo
- **THEN** a linha `VALOR LÍQUIDO A PAGAR AO CEDENTE` deve exibir o valor numérico correspondente ao total líquido
- **AND** esse valor não deve ficar em branco nem zerado por erro de mapeamento

### Requirement: Label de Valor na Tela de Detalhes
O sistema SHALL exibir o texto `Valor a Receber` na tela de detalhes para operações de empréstimo.

#### Scenario: Exibição do resumo da operação
- **WHEN** a tela `detalhes_operacao.php` renderizar uma operação de empréstimo
- **THEN** o rótulo atualmente mostrado como `Valor Futuro da Operação:` deve ser substituído por `Valor a Receber`

## MODIFIED Requirements
### Requirement: Coerência do Resumo Financeiro no Borderô
O sistema SHALL manter o resumo financeiro do contrato coerente, exibindo tanto o valor líquido por extenso quanto o valor líquido numérico a partir da mesma base de cálculo.

#### Scenario: Valor líquido consolidado
- **WHEN** o backend montar o objeto `bordero` para o template
- **THEN** o campo numérico do valor líquido deve usar o mesmo total empregado no texto por extenso
- **AND** o template deve renderizar esse valor no local correto

## REMOVED Requirements
### Requirement: Label `Valor Futuro da Operação`
**Reason**: O usuário prefere a nomenclatura `Valor a Receber`, mais aderente ao significado exibido na tela.
**Migration**: Substituir apenas o rótulo visual para operações de empréstimo, preservando os mesmos dados e cálculos.
