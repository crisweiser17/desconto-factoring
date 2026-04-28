# Configuração de Dados Bancários do Credor Spec

## Why
Os dados bancários (titular da conta, banco, agência, tipo de conta, CPF/CNPJ do titular e PIX) utilizados para indicar ao sacado onde ele deve efetuar o pagamento da operação estão atualmente fixos no código (`api_contratos.php`). Sendo uma informação vital da própria empresa (Credor), ela deve ser gerenciável pelo usuário através da tela de configurações do sistema (`config.php`).

## What Changes
- **BREAKING**: A geração de contratos e notificações deixará de utilizar os dados bancários fixos do código fonte e passará a ler os dados a partir de `config.json`.
- Adição de novos campos no formulário de `config.php`: `conta_banco`, `conta_agencia`, `conta_numero`, `conta_tipo`, `conta_titular`, `conta_documento` e `conta_pix`.
- Alteração na função de salvamento e carregamento (`readConfig`) no `config.php` para contemplar as novas chaves.
- Atualização do `api_contratos.php` para carregar as configurações (`$config = json_decode(file_get_contents(__DIR__ . '/config.json'), true);`) e injetá-las no array `$data['credor']['conta']`.

## Impact
- Affected specs: Nenhuma
- Affected code: `config.php`, `api_contratos.php`

## ADDED Requirements
### Requirement: Configuração de Recebimento
O sistema SHALL prover uma seção "Dados Bancários de Recebimento" na página de configurações, permitindo ao administrador definir em qual conta os sacados deverão pagar os títulos.

#### Scenario: Geração de Contrato Atualizada
- **WHEN** o usuário gera um Contrato ou Borderô
- **THEN** o documento exibirá os dados bancários preenchidos em `config.php` para a seção de pagamento.