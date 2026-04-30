# Saneamento De Hardcodes Da Credora Nos Contratos Spec

## Why
Os contratos ativos ainda possuem dados da credora hardcoded em vários pontos, mesmo quando essas informações deveriam vir das configurações do sistema e do payload Mustache. Isso dificulta a troca da empresa operadora, gera risco de inconsistência documental e impede que a base contratual acompanhe os dados configurados pelo usuário.

## What Changes
- Levantar, por template ativo, todos os trechos da credora/cessionária/mutuante que estão hardcoded e deveriam usar variáveis Mustache.
- Padronizar o uso dos dados configuráveis da empresa e da conta de recebimento em todos os contratos ativos.
- Expor no payload de contratos os campos necessários para eliminar hardcodes hoje repetidos, incluindo identificação da credora e documento.
- Substituir nos templates ativos os hardcodes de razão social, CNPJ/documento, endereço e identificação do titular bancário quando esses dados já pertencem à configuração do sistema.
- Manter textos jurídicos genéricos e referências legais hardcoded quando não correspondem a campos configuráveis.

## Impact
- Affected specs: contratos, templates Mustache, configuração da empresa, geração de PDF
- Affected code: `config.php`, `config.json`, `api_contratos.php`, `_contratos/01_template_antecipacao_recebiveis.md`, `_contratos/02a_template_mutuo_simples.md`, `_contratos/02b_template_mutuo_com_aval.md`, `_contratos/02c_template_mutuo_com_garantia.md`, `_contratos/02d_template_mutuo_com_garantia_e_aval.md`, `_contratos/02e_template_mutuo_com_garantia_bem.md`, `_contratos/02f_template_mutuo_com_garantia_bem_e_aval.md`, `_contratos/03_template_nota_promissoria.md`

## ADDED Requirements
### Requirement: Inventário De Hardcodes Configuráveis
O sistema SHALL possuir um inventário revisável dos trechos hardcoded nos contratos ativos que representam dados configuráveis da credora, da cessionária, da mutuante ou da conta bancária.

#### Scenario: Levantamento por template
- **WHEN** for feita a auditoria dos templates ativos em `_contratos`
- **THEN** cada arquivo deverá listar os trechos hardcoded, o dado esperado e a variável Mustache sugerida

#### Scenario: Foco apenas em campos configuráveis
- **WHEN** um trecho do contrato for analisado
- **THEN** somente dados que correspondam a valores configuráveis da empresa ou da conta deverão entrar no inventário de substituição

### Requirement: Dados Da Credora Devem Vir Do Payload
O sistema SHALL renderizar a identificação da credora com dados variáveis vindos do payload Mustache em vez de razão social, documento e endereço hardcoded.

#### Scenario: Qualificação da credora
- **WHEN** um template exibir a qualificação inicial da credora, cessionária ou mutuante
- **THEN** a razão social, o documento e o endereço deverão vir de variáveis do payload

#### Scenario: Assinatura da credora
- **WHEN** um template exibir o bloco de assinatura da credora
- **THEN** o nome empresarial e o documento deverão vir de variáveis do payload

### Requirement: Dados Bancários Devem Vir Da Configuração
O sistema SHALL usar variáveis Mustache baseadas na configuração da conta de recebimento para os blocos de pagamento dos contratos.

#### Scenario: Titular e documento da conta
- **WHEN** o contrato listar os dados bancários para pagamento
- **THEN** titular e documento deverão usar os campos configuráveis da conta e não valores fixos no Markdown

### Requirement: Cobertura Dos Templates Ativos
O sistema SHALL aplicar o saneamento de hardcodes em todos os templates ativos de antecipação, mútuo e nota promissória.

#### Scenario: Templates incluídos
- **WHEN** a correção for implementada
- **THEN** os templates `01`, `02a`, `02b`, `02c`, `02d`, `02e`, `02f` e `03` deverão ficar sem hardcodes de dados configuráveis da credora

## MODIFIED Requirements
### Requirement: Payload De Contratos Da Credora
O payload gerado por `api_contratos.php` deve continuar fornecendo os campos já usados pelos templates e passar a incluir, de forma compatível e explícita, os dados necessários para identificar a credora e a conta configurada sem dependência de textos hardcoded nos Markdown.

## REMOVED Requirements
### Requirement: Uso De Identidade Fixa Da Credora Nos Templates
**Reason**: A identidade textual fixa da empresa impede reutilização do sistema com outra configuração e causa divergência entre contrato e tela de configurações.
**Migration**: Substituir cada ocorrência hardcoded por variável Mustache equivalente, adicionando ao payload os campos faltantes quando necessário.
