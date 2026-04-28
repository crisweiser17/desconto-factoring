# Adição de Campos de Contrato e Variáveis Específicas

## Why
O usuário identificou que ainda faltam algumas variáveis vitais para a confecção adequada de Contratos de Mútuo e Borderôs. Faltam informações sobre o estado civil e o cônjuge do cedente (importante para outorga uxória), informações da conta bancária de recebimento do cedente, identificadores extras do veículo (chassi, uf, município), listagem e totais de títulos no borderô, além de dados de contato (email/whatsapp) para cedente, credor e avalista, fundamentais para a comunicação eLGPD.

## What Changes
- **Banco de Dados**: 
  - Adição de campos de cônjuge na tabela `cedentes` e `sacados` (`casado`, `regime_casamento`, `conjuge_nome`, `conjuge_cpf`, `conjuge_rg`, `conjuge_nacionalidade`, `conjuge_profissao`).
  - Adição de campos de conta bancária na tabela `cedentes` e `sacados` (`conta_banco`, `conta_agencia`, `conta_numero`, `conta_pix`, `conta_tipo`, `conta_titular`, `conta_documento`).
  - Adição do campo `whatsapp` em `cedentes` e `sacados`.
- **Backend (`api_contratos.php`)**:
  - Incorporação dos novos dados do Cedente / Devedor (Cônjuge, Conta Bancária, Email, WhatsApp) no array `$data`.
  - Incorporação de Email e WhatsApp no array do Avalista e do Credor.
  - Incorporação de Chassi, Município de Registro e UF no array do Veículo.
  - Implementação do laço `{{#titulos}}` dentro da variável `bordero`, listando os recebíveis (com `numero`, `ordem`, `tipo`, `sacado_nome`, `sacado_documento`, `data_emissao`, `data_vencimento`, `valor_face`, `valor_presente`) associados à operação.
  - Implementação dos totalizadores do Borderô (`total_face`, `total_titulos`, `taxa_desagio`, `total_desagio`, `prazo_medio`, `tarifas`, `valor_liquido`, `valor_liquido_extenso`, `forma_pagamento`).
- **Frontend**:
  - Atualizar `form_cedente.php` e `salvar_cedente.php` para incluir os inputs do cônjuge, da conta bancária e whatsapp.
  - Atualizar `form_sacado.php` e `salvar_sacado.php` para incluir os inputs do cônjuge, da conta bancária e whatsapp.
  - Atualizar `detalhes_operacao.php` para que no modal de "Gerar Contrato", caso haja veículo, ele peça Chassi, Município e UF. E caso haja avalista, peça Email e WhatsApp.

## Impact
- Affected specs: `document-contract-variables` (Precisará atualizar a documentação gerada após as mudanças).
- Affected code: `api_contratos.php`, `form_cedente.php`, `salvar_cedente.php`, `form_sacado.php`, `salvar_sacado.php`, `detalhes_operacao.php`.

## ADDED Requirements
### Requirement: Suporte Completo a Dados de Cônjuge e Conta
O sistema SHALL permitir o cadastro completo do cônjuge do cedente/sacado (PF ou representante) e de suas informações bancárias para recebimento dos valores líquidos da operação.

### Requirement: Listagem de Títulos em Borderô
O sistema SHALL consultar os `recebiveis` atrelados à operação de Desconto para montar o array iterável `titulos` e totalizar os valores da operação, permitindo que a engine Mustache gere uma tabela de títulos no documento final.

#### Scenario: Geração de Borderô
- **WHEN** o usuário gera um Contrato de Cessão (Borderô)
- **THEN** o documento conterá uma tabela com cada título negociado e os valores totalizados corretos na cláusula financeira.
