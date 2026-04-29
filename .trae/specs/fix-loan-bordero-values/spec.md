# Corrigir Valores do Borderô em Contrato de Empréstimo Spec

## Why
O contrato de empréstimo gerado para a operação 59 exibe linhas do borderô com `Valor de Face` e `Valor Presente` zerados, embora a operação possua títulos válidos.
Isso compromete a legibilidade do contrato e torna o resumo financeiro inconsistente com os dados reais da operação.

## What Changes
- Corrigir a montagem dos dados enviados ao template de contrato de empréstimo para que cada item do borderô use os valores reais dos títulos da operação.
- Garantir que o resumo financeiro do contrato utilize os mesmos valores efetivos exibidos na relação de títulos cedidos.
- Evitar fallback silencioso para `0,00` quando houver dados válidos disponíveis na operação.
- Preservar o comportamento atual dos contratos de desconto, sem regressão.

## Impact
- Affected specs: geração de contratos, payload de templates, contratos de empréstimo
- Affected code: `api_contratos.php`, templates de contrato de empréstimo em `_contratos`, lógica de montagem do borderô e resumo financeiro

## ADDED Requirements
### Requirement: Itens do Borderô com Valores Reais
O sistema SHALL preencher cada linha do borderô do contrato de empréstimo com os valores monetários reais dos títulos vinculados à operação.

#### Scenario: Operação de empréstimo com títulos válidos
- **WHEN** o sistema gerar um contrato de empréstimo para uma operação com recebíveis válidos
- **THEN** cada linha do borderô deve exibir `Valor de Face` diferente de `0,00` quando o título possuir valor nominal
- **AND** cada linha do borderô deve exibir `Valor Presente` diferente de `0,00` quando houver valor presente calculado para o título

### Requirement: Resumo Financeiro Consistente com o Borderô
O sistema SHALL manter o resumo financeiro do contrato coerente com a lista de títulos exibida no próprio documento.

#### Scenario: Totais do resumo financeiro
- **WHEN** o contrato de empréstimo for gerado
- **THEN** o `Valor Total de Face` deve corresponder à soma dos valores de face exibidos no borderô
- **AND** os demais totais financeiros não devem depender de valores zerados por erro de mapeamento

## MODIFIED Requirements
### Requirement: Montagem do Payload de Contratos de Empréstimo
O sistema SHALL mapear os dados dos títulos de operações de empréstimo para o template usando os campos monetários corretos da operação, sem substituir valores válidos por zeros.

#### Scenario: Dados válidos no backend
- **WHEN** o backend preparar o payload do template para um contrato de empréstimo
- **THEN** ele deve buscar os valores corretos de cada recebível
- **AND** deve formatá-los no payload final do template
- **AND** deve evitar popular o template com `0,00` por ausência de mapeamento incorreto

## REMOVED Requirements
### Requirement: Fallback Implícito para Valores Zerados no Borderô
**Reason**: Exibir `0,00` em títulos válidos mascara defeitos de mapeamento e produz contratos incorretos.
**Migration**: Substituir o fallback silencioso por leitura correta dos campos monetários reais usados na operação e validar o resultado gerado no contrato.
