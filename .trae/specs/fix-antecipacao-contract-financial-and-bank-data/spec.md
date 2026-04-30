# Correção do Contrato de Antecipação Spec

## Why
Contratos gerados a partir de `detalhes_operacao.php` para operações de antecipação estão saindo com a tabela da Cláusula 3ª vazia e com os dados bancários do cedente em branco. Isso compromete a validade operacional do documento e gera divergência entre os dados calculados no sistema e o PDF emitido.

## What Changes
- Alinhar o payload enviado ao template do contrato de antecipação para que a Cláusula 3ª use os valores financeiros efetivamente calculados da operação.
- Garantir que os dados bancários do cedente sejam carregados do cadastro correto e renderizados na Cláusula 3.3.
- Definir fallback seguro e comportamento esperado quando algum dado bancário opcional estiver ausente.
- Adicionar validação específica para geração de contrato de antecipação cobrindo resumo financeiro e conta de pagamento.

## Impact
- Affected specs: gerador de contratos, contrato de antecipação de recebíveis, cadastro de clientes/cedentes
- Affected code: `api_contratos.php`, `_contratos/01_template_antecipacao_recebiveis.md`, fluxo de geração em `detalhes_operacao.php`

## ADDED Requirements
### Requirement: Resumo financeiro da Cláusula 3 no contrato de antecipação
O sistema SHALL preencher a tabela da Cláusula 3.2 do contrato de antecipação com os valores financeiros calculados para a operação.

#### Scenario: Operação de desconto com títulos e totais calculados
- **WHEN** o usuário gerar um contrato de antecipação para uma operação com recebíveis vinculados
- **THEN** a tabela da Cláusula 3.2 deve exibir quantidade de títulos, valor total de face, taxa de deságio, prazo médio, total de deságio, tarifas quando existirem, e valor líquido
- **AND** nenhum desses campos deve depender de chaves incompatíveis entre backend e template

### Requirement: Dados bancários do cedente no contrato de antecipação
O sistema SHALL renderizar na Cláusula 3.3 os dados bancários do cedente carregados do cadastro relacionado à operação.

#### Scenario: Cedente com dados bancários cadastrados
- **WHEN** o usuário gerar o contrato de antecipação
- **THEN** os campos Banco, Agência, Conta e Tipo devem ser preenchidos com os valores do cadastro do cedente
- **AND** o titular e o documento do titular devem permanecer coerentes com a parte contratual exibida no contrato

#### Scenario: Dado bancário opcional ausente
- **WHEN** um campo opcional como PIX não estiver preenchido no cadastro
- **THEN** o contrato deve omitir apenas esse campo opcional
- **AND** os demais dados bancários disponíveis devem continuar sendo renderizados normalmente

## MODIFIED Requirements
### Requirement: Geração de contrato de antecipação
O sistema SHALL gerar contratos de antecipação com os dados financeiros e bancários consistentes entre cálculo, payload Mustache e template Markdown, sem deixar linhas críticas vazias quando a operação possuir dados cadastrados.

## REMOVED Requirements
