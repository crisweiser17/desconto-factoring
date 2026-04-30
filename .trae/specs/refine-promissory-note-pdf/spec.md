# Nota Promissória em PDF Spec

## Why
A Nota Promissória já é anexada aos contratos de mútuo, mas o layout e as regras de emissão ainda não estão especificados com o nível jurídico e visual necessário para uso operacional e eventual protesto. É preciso padronizar a geração do PDF para produzir uma NP preenchida, legível, juridicamente consistente e restrita a uma única página A4.

## What Changes
- Refinar o template da Nota Promissória para seguir um layout próprio de página A4 em orientação retrato, com apresentação visual distinta do contrato.
- Garantir que a Nota Promissória seja emitida em via única e que essa informação conste no documento.
- Padronizar o uso do valor total da dívida com `operacao.valor_total_devido` e seu valor por extenso no corpo do título.
- Padronizar o vencimento da NP como `à vista` por padrão, mantendo preenchimento completo no momento da geração.
- Preservar no documento os requisitos essenciais do art. 75 da LUG já previstos no template.
- Ajustar a renderização PDF para que a NP permaneça em uma única página A4, com tipografia, espaçamento e blocos de assinatura adequados.

## Impact
- Affected specs: `update-new-contract-templates`
- Affected code: `_contratos/03_template_nota_promissoria.md`, `api_contratos.php`, fluxo de renderização HTML/PDF com mPDF

## ADDED Requirements
### Requirement: Layout A4 Único da Nota Promissória
O sistema SHALL gerar a Nota Promissória em página A4 única, orientação retrato, com layout próprio e sem depender da formatação visual genérica do contrato.

#### Scenario: Renderização visual da NP
- **WHEN** uma operação de empréstimo gerar contrato com Nota Promissória anexada
- **THEN** a NP deve usar página A4 em retrato
- **AND** deve apresentar cabeçalho centralizado, bloco de valor destacado, corpo justificado e bloco de assinatura visualmente separado
- **AND** o conteúdo da NP deve caber em uma única página

### Requirement: Emissão em Via Única
O sistema SHALL indicar expressamente que a Nota Promissória é emitida em única via.

#### Scenario: Identificação formal da via
- **WHEN** o PDF da NP for gerado
- **THEN** o texto do título deve indicar que a cártula é emitida em única via

### Requirement: Valor Cambial da Dívida
O sistema SHALL preencher a Nota Promissória com o valor total da dívida contratada, correspondente ao principal somado aos juros previstos para todo o período.

#### Scenario: Preenchimento do valor
- **WHEN** a NP for renderizada
- **THEN** o valor numérico deve usar `operacao.valor_total_devido`
- **AND** o valor por extenso deve corresponder ao mesmo montante

### Requirement: Vencimento Padrão à Vista
O sistema SHALL emitir a Nota Promissória com vencimento `à vista` como padrão operacional da ACM.

#### Scenario: Preenchimento do vencimento
- **WHEN** a NP for gerada sem regra específica que imponha data final diversa
- **THEN** o título deve indicar vencimento `à vista`
- **AND** a redação do corpo do título não deve conflitar com essa modalidade

### Requirement: Título Já Preenchido
O sistema SHALL emitir a Nota Promissória já preenchida no momento da geração do PDF.

#### Scenario: Preenchimento integral
- **WHEN** o contrato e a NP forem gerados
- **THEN** o documento deve sair com valor, beneficiário, praça, local de emissão, data de emissão e identificação do emitente já preenchidos
- **AND** não deve depender de preenchimento manual posterior para sua versão padrão emitida pelo sistema

## MODIFIED Requirements
### Requirement: Geração de Nota Promissória Acoplada
O sistema DEVE concatenar a Nota Promissória em operações de empréstimo e renderizá-la como anexo com layout próprio, distinto do contrato principal.
- **WHEN** a operação é de mútuo (empréstimo)
- **THEN** o sistema anexa o template da Nota Promissória ao final do documento
- **AND** aplica apresentação compatível com cártula em página A4 única
- **AND** usa vencimento `à vista` como padrão documental
- **AND** preenche o valor do título com `operacao.valor_total_devido`

### Requirement: Conformidade Cambial Mínima
O sistema DEVE manter no texto da Nota Promissória todos os requisitos essenciais do art. 75 da Lei Uniforme de Genebra já contemplados pelo template.
- **WHEN** a NP for emitida
- **THEN** o texto deve conter a denominação `Nota Promissória`
- **AND** a promessa pura e simples de pagar quantia determinada
- **AND** a época de pagamento
- **AND** o lugar de pagamento
- **AND** o nome do beneficiário
- **AND** a data e o lugar de emissão
- **AND** o campo de assinatura do emitente
