# Expandir Garantia de Mútuo com Bem Móvel Spec

## Why
O modal "Gerar Contratos" já diferencia operações com e sem garantia, mas ainda trata toda garantia como se fosse de veículo. Agora existem novos templates específicos para garantia por outro bem móvel e o fluxo precisa perguntar isso explicitamente para gerar o contrato correto.

Além disso, como os dados de cônjuge deixaram de existir no cadastro, o modal precisa garantir a coleta mínima dos dados do avalista quando ele for incluído, evitando geração de contrato incompleta.

## What Changes
- Adicionar no modal de geração de contratos uma pergunta condicional sobre o tipo da garantia quando o usuário marcar que o cliente ofereceu garantia.
- Permitir escolher entre `Veículo` e `Outro bem móvel`.
- Atualizar a seleção de template de mútuo para usar os novos arquivos `_contratos/02e_template_mutuo_com_garantia_bem.md` e `_contratos/02f_template_mutuo_com_garantia_bem_e_aval.md` quando a garantia for de bem móvel.
- Manter os templates atuais de veículo para os casos em que a garantia selecionada for veículo.
- Alterar a validação do avalista no modal para exigir, no mínimo, nome completo e CPF quando o usuário marcar que existe avalista.
- Ajustar o backend para receber o novo subtipo de garantia e mapear corretamente a combinação `garantia + tipo da garantia + avalista`.

## Impact
- Affected specs: geração de contratos de mútuo, captura de garantias no modal, seleção de template de contrato
- Affected code:
  - `detalhes_operacao.php`
  - `api_contratos.php`
  - `_contratos/02c_template_mutuo_com_garantia.md`
  - `_contratos/02d_template_mutuo_com_garantia_e_aval.md`
  - `_contratos/02e_template_mutuo_com_garantia_bem.md`
  - `_contratos/02f_template_mutuo_com_garantia_bem_e_aval.md`

## ADDED Requirements
### Requirement: Seleção do Tipo de Garantia no Modal
O sistema SHALL perguntar qual é o tipo de garantia do contrato de mútuo quando o usuário indicar que o cliente ofereceu garantia.

#### Scenario: Garantia por veículo
- **WHEN** o usuário abrir o modal "Gerar Contratos", selecionar natureza `EMPRESTIMO` e marcar que existe garantia
- **THEN** o modal deve exibir uma escolha entre `Veículo` e `Outro bem móvel`
- **AND** ao selecionar `Veículo`, o fluxo deve manter os campos e templates já usados para garantia veicular

#### Scenario: Garantia por outro bem móvel
- **WHEN** o usuário abrir o modal "Gerar Contratos", selecionar natureza `EMPRESTIMO` e marcar que existe garantia
- **THEN** o modal deve permitir selecionar `Outro bem móvel`
- **AND** essa escolha deve ser enviada ao backend para definir o template correto

### Requirement: Seleção de Template para Bem Móvel
O sistema SHALL usar templates distintos de mútuo quando a garantia informada for um bem móvel diferente de veículo.

#### Scenario: Bem móvel sem avalista
- **WHEN** o usuário selecionar natureza `EMPRESTIMO`, garantia `Sim`, tipo de garantia `Outro bem móvel` e avalista `Não`
- **THEN** o sistema deve gerar o contrato com `_contratos/02e_template_mutuo_com_garantia_bem.md`

#### Scenario: Bem móvel com avalista
- **WHEN** o usuário selecionar natureza `EMPRESTIMO`, garantia `Sim`, tipo de garantia `Outro bem móvel` e avalista `Sim`
- **THEN** o sistema deve gerar o contrato com `_contratos/02f_template_mutuo_com_garantia_bem_e_aval.md`

### Requirement: Captura Mínima do Avalista
O sistema SHALL exigir pelo menos nome completo e CPF do avalista quando o usuário indicar que existe avalista no modal de geração de contratos.

#### Scenario: Avalista obrigatório
- **WHEN** o usuário marcar `Sim` para avalista
- **THEN** os campos `Nome Completo` e `CPF` do avalista devem ser obrigatórios
- **AND** o contrato não deve ser gerado se esses dois campos estiverem vazios ou inválidos

#### Scenario: Avalista opcional sem seleção
- **WHEN** o usuário marcar `Não` para avalista
- **THEN** os campos do avalista devem ficar ocultos ou opcionais
- **AND** o backend não deve exigir dados de avalista

## MODIFIED Requirements
### Requirement: Seleção de Template de Mútuo
O sistema DEVE selecionar o template de mútuo com base na combinação entre existência de garantia, tipo da garantia e presença de avalista.

#### Scenario: Garantia veicular com avalista
- **WHEN** o usuário selecionar garantia `Sim`, tipo `Veículo` e avalista `Sim`
- **THEN** o sistema deve continuar usando o template veicular com avalista já existente

#### Scenario: Garantia veicular sem avalista
- **WHEN** o usuário selecionar garantia `Sim`, tipo `Veículo` e avalista `Não`
- **THEN** o sistema deve continuar usando o template veicular sem avalista já existente

## REMOVED Requirements
### Requirement: Garantia tratada sempre como veículo
**Reason**: O fluxo atual não cobre contratos garantidos por outros bens móveis e não consegue escolher os novos templates corretamente.
**Migration**: Substituir a lógica binária atual por uma lógica com subtipo explícito de garantia (`veiculo` ou `bem_movel`) repassado do modal para o backend.
