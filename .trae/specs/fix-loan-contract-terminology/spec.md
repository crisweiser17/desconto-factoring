# Corrigir Terminologia dos Contratos de Empréstimo Spec

## Why
Os contratos gerados para operações de empréstimo ainda contêm trechos herdados de contratos de cessão de crédito, como o uso de `CEDENTE` e seções de pagamento ao cedente. Isso torna o documento juridicamente incoerente e faz o texto parecer placeholder ou template errado para o tipo de operação.

## What Changes
- Corrigir a nomenclatura das partes nos templates de empréstimo para usar termos compatíveis com mútuo, como `MUTUANTE`, `MUTUÁRIO`, `DEVEDOR`, `AVALISTA` e `GARANTIDOR`, conforme o caso.
- Remover dos contratos de empréstimo trechos típicos de cessão de crédito, incluindo referências textuais a `CEDENTE`, `CESSIONÁRIA`, `Borderô` e pagamento ao cedente.
- Ajustar o bloco de qualificação das partes nos templates de empréstimo para que a redação não pareça placeholder e reflita corretamente pessoa física, pessoa jurídica e representante legal.
- Garantir que a seção de pagamento em contratos de empréstimo descreva o fluxo correto: pagamento do mutuário para a mutuante, e não depósito ao cedente.
- Preservar os contratos de desconto/cessão sem alteração de terminologia.

## Impact
- Affected specs: geração de contratos, templates de empréstimo, qualificação das partes
- Affected code: `_contratos/contrato_1_com_veiculo_com_avalista.md`, `_contratos/contrato_2_sem_veiculo_sem_avalista.md`, `_contratos/contrato_3_com_veiculo_sem_avalista.md`, `_contratos/contrato_4_sem_veiculo_com_avalista.md`, `api_contratos.php`

## ADDED Requirements
### Requirement: Terminologia Correta para Contratos de Empréstimo
O sistema SHALL gerar contratos de empréstimo com terminologia jurídica própria de mútuo, sem reutilizar nomenclatura de cessão de crédito.

#### Scenario: Geração de contrato de empréstimo
- **WHEN** o usuário gerar um contrato para uma operação classificada como `emprestimo`
- **THEN** o documento deve identificar as partes com termos compatíveis com mútuo
- **AND** o documento não deve conter `CEDENTE` ou `CESSIONÁRIA` como designação das partes principais
- **AND** o documento não deve se apresentar como contrato de cessão onerosa de créditos

### Requirement: Qualificação Coerente das Partes
O sistema SHALL renderizar a qualificação do tomador e de seus representantes com texto natural e coerente com o tipo de pessoa cadastrado.

#### Scenario: Tomador pessoa jurídica com representante
- **WHEN** o contrato de empréstimo for gerado para tomador pessoa jurídica
- **THEN** a qualificação deve exibir razão social, documento principal e dados do representante legal
- **AND** a redação não deve repetir nome empresarial no lugar do representante
- **AND** o texto final não deve parecer placeholder incompleto

#### Scenario: Tomador pessoa física elegível
- **WHEN** o contrato de empréstimo for gerado para tomador pessoa física elegível no fluxo do sistema
- **THEN** a qualificação deve usar os campos pessoais do tomador
- **AND** a redação deve manter consistência com a nomenclatura de mútuo

### Requirement: Seção de Pagamento Compatível com Mútuo
O sistema SHALL apresentar instruções de pagamento condizentes com a lógica de empréstimo.

#### Scenario: Exibição da forma de pagamento no contrato de empréstimo
- **WHEN** o contrato de empréstimo exibir dados bancários de pagamento
- **THEN** a seção deve indicar o pagamento devido pelo mutuário à mutuante
- **AND** os rótulos não devem mencionar `Pagamento ao CEDENTE` nem `Conta de Depósito do CEDENTE`
- **AND** os dados bancários devem corresponder à conta da parte credora quando o contrato tratar de quitação das parcelas

## MODIFIED Requirements
### Requirement: Templates de Empréstimo Distintos de Templates de Cessão
Os templates usados para operações de empréstimo SHALL manter estrutura textual própria de mútuo em todas as cláusulas, cabeçalhos, qualificações e seções operacionais, ainda que compartilhem variáveis ou blocos de dados com outros fluxos contratuais.

#### Scenario: Seleção de template de empréstimo
- **WHEN** o backend selecionar qualquer um dos quatro templates de empréstimo
- **THEN** o conteúdo textual do arquivo selecionado deve ser juridicamente compatível com empréstimo
- **AND** o documento não deve conter remanescentes semânticos de contrato de desconto ou cessão

## REMOVED Requirements
### Requirement: Reaproveitamento Literal de Texto de Cessão em Empréstimo
**Reason**: O reaproveitamento literal produz contratos incoerentes, com papéis contratuais errados e linguagem incompatível com a natureza da operação.
**Migration**: Substituir os trechos herdados por redação própria de mútuo nos quatro templates de empréstimo, preservando apenas as variáveis de dados que continuarem semanticamente corretas.
