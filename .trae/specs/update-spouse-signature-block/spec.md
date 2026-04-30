# Ajustar Bloco de Assinatura do Cônjuge Spec

## Why
O sistema já possui a opção de incluir a assinatura do cônjuge do mutuário quando o usuário marca essa escolha na geração do contrato. Agora é necessário simplificar o bloco exibido no contrato para seguir o formato solicitado pelo usuário, com um espaço de assinatura direto e campos de nome e CPF.

## What Changes
- Modificar o bloco condicional de assinatura do cônjuge nos templates de mútuo para usar o formato simples "Cônjuge:" seguido de linha de assinatura, nome e CPF.
- Manter a regra atual de exibir esse bloco apenas quando a opção "Cônjuge vai Assinar?" estiver marcada como "Sim".
- Preservar compatibilidade com o motor de template e com a renderização Markdown/PDF usada pelos contratos.

## Impact
- Affected specs: `add-spouse-signature-option-mutuo`
- Affected code:
  - `_contratos/02_template_contrato_mutuo.md`
  - `_contratos/contrato_1_com_veiculo_com_avalista.md`
  - `_contratos/contrato_2_sem_veiculo_sem_avalista.md`
  - `_contratos/contrato_3_com_veiculo_sem_avalista.md`
  - `_contratos/contrato_4_sem_veiculo_com_avalista.md`

## ADDED Requirements
### Requirement: Formato Simplificado da Assinatura do Cônjuge
O sistema DEVE renderizar a área de assinatura do cônjuge do mutuário em formato simples e legível, com identificação do cônjuge, linha de assinatura e campos de nome e CPF.

#### Scenario: Contrato com assinatura do cônjuge habilitada
- **WHEN** o usuário gerar um contrato com a opção "Cônjuge vai Assinar?" marcada como "Sim"
- **THEN** o contrato deve exibir um bloco com o rótulo "Cônjuge:"
- **AND** o bloco deve conter uma linha de assinatura
- **AND** o bloco deve conter os campos "Nome" e "CPF" vinculados aos dados do cônjuge

## MODIFIED Requirements
### Requirement: Impressão Condicional no Contrato
O sistema DEVE imprimir o bloco de assinatura do cônjuge do Mutuário apenas se a opção estiver marcada como "Sim", usando o formato simplificado solicitado pelo usuário em vez do bloco textual anterior.

#### Scenario: Contrato sem assinatura do cônjuge
- **WHEN** o usuário selecionar "Não" para "Cônjuge vai Assinar?"
- **THEN** o contrato não deve exibir nenhum bloco adicional de assinatura do cônjuge

## REMOVED Requirements
### Requirement: Bloco Anterior de Assinatura do Cônjuge
**Reason**: O formato atual está mais verboso do que o necessário para o fluxo desejado pelo usuário.
**Migration**: Substituir o bloco atual nos templates de mútuo pelo novo layout simples, mantendo a mesma condição `{{#devedor.conjuge_assina}}`.
