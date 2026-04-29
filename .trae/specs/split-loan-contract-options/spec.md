# Separar Opções de Empréstimo no Modal Spec

## Why
O modal de geração de contratos para empréstimo hoje concentra quatro combinações de garantia/avalista em um único campo, o que dificulta o entendimento da escolha pelo usuário.
Separar essa decisão em dois controles binários deixa a interface mais clara e reduz ambiguidades na seleção do template contratual correto.

## What Changes
- Substituir o campo `Possui Garantia?` por dois controles independentes no modal de geração de contratos para operações de empréstimo.
- Exibir um controle para `O empréstimo tem garantia real?` com opções `Sim` e `Não`.
- Exibir um controle para `O sacado tem avalista?` com opções `Sim` e `Não`.
- Mapear a combinação escolhida para o template contratual correto com base no nome dos arquivos `.md`.
- Preservar o fluxo atual de geração de contratos para operação de desconto.

## Impact
- Affected specs: geração de contratos, seleção de templates de empréstimo
- Affected code: `detalhes_operacao.php`, `api_contratos.php`, lógica JavaScript do modal de contratos

## ADDED Requirements
### Requirement: Controles Separados para Garantia e Avalista
O sistema SHALL apresentar dois controles distintos no modal de geração de contratos quando a natureza da operação for empréstimo.

#### Scenario: Exibição dos controles de empréstimo
- **WHEN** o usuário selecionar `Empréstimo` no modal de geração de contratos
- **THEN** o modal deve exibir o campo `O empréstimo tem garantia real?` com opções `Sim` e `Não`
- **AND** o modal deve exibir o campo `O sacado tem avalista?` com opções `Sim` e `Não`
- **AND** o modal não deve exibir o select antigo com as quatro combinações consolidadas

### Requirement: Seleção de Template por Combinação Binária
O sistema SHALL derivar o template contratual de empréstimo a partir da combinação dos campos de garantia real e avalista.

#### Scenario: Empréstimo com garantia real e com avalista
- **WHEN** o usuário selecionar `Sim` para garantia real e `Sim` para avalista
- **THEN** o sistema deve utilizar `_contratos/contrato_1_com_veiculo_com_avalista.md`

#### Scenario: Empréstimo sem garantia real e sem avalista
- **WHEN** o usuário selecionar `Não` para garantia real e `Não` para avalista
- **THEN** o sistema deve utilizar `_contratos/contrato_2_sem_veiculo_sem_avalista.md`

#### Scenario: Empréstimo com garantia real e sem avalista
- **WHEN** o usuário selecionar `Sim` para garantia real e `Não` para avalista
- **THEN** o sistema deve utilizar `_contratos/contrato_3_com_veiculo_sem_avalista.md`

#### Scenario: Empréstimo sem garantia real e com avalista
- **WHEN** o usuário selecionar `Não` para garantia real e `Sim` para avalista
- **THEN** o sistema deve utilizar `_contratos/contrato_4_sem_veiculo_com_avalista.md`

## MODIFIED Requirements
### Requirement: Entrada de Garantias no Modal de Contratos
O sistema SHALL coletar as características de garantia de operações de empréstimo por meio de dois campos binários independentes, em vez de um único select com combinações pré-montadas.

#### Scenario: Substituição do campo consolidado
- **WHEN** o usuário abrir o modal de geração de contratos para uma operação de empréstimo
- **THEN** a decisão sobre garantia real deve ser feita em um campo separado da decisão sobre avalista
- **AND** a interface deve manter nomenclatura clara e direta para ambas as perguntas

## REMOVED Requirements
### Requirement: Select Único com Quatro Combinações
**Reason**: O modelo atual mistura duas decisões distintas em um único campo e reduz a clareza da interface.
**Migration**: Substituir o valor consolidado antigo por duas flags explícitas no frontend e no backend, preservando o mesmo resultado final de template para cada combinação.
