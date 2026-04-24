# Suporte a Pessoa Física e Jurídica para Sacados Spec

## Why
Atualmente o sistema permite cadastrar "Sacados" apenas como Pessoa Jurídica (CNPJ). Para suportar diferentes tipos de operações, como empréstimos para indivíduos, é necessário que o sistema permita cadastrar o Sacado também como Pessoa Física, adaptando os campos de documento (para CPF) e nome/razão social.
Além disso, quando o Sacado for Pessoa Física, não faz sentido exibir a seção de Sócios, devendo esta ser ocultada.
Por fim, o usuário solicitou a inclusão de configurações globais para o "Nome do Aplicativo" (editável) e "Versão do Aplicativo" (somente leitura).

## What Changes
- `form_sacado.php`: Esconder a seção de "Sócios" quando o Tipo de Pessoa for "Pessoa Física" (usando JS).
- `salvar_sacado.php`: Garantir que o valor `tipo_pessoa` seja processado corretamente (resolvendo o bug que forçava "JURIDICA").
- `config.php` e `config.json`: Adicionar campo editável `app_name` (padrão: "Factoring 5.1") e campo somente leitura `app_version` (padrão: "5.2 de abril de 2026").
- `menu.php`: Ler e exibir o `app_name` e `app_version` do `config.json` dinamicamente no topo do menu.

## Impact
- Affected specs: Cadastro e Gestão de Sacados, Configurações Globais e Layout do Menu.
- Affected code: `form_sacado.php`, `salvar_sacado.php`, `config.php`, `menu.php`.

## ADDED Requirements
### Requirement: Ocultar Sócios para PF
O sistema SHALL ocultar a seção de Sócios no cadastro de Sacados caso o usuário selecione "Pessoa Física".

### Requirement: Configuração de Nome e Versão do App
O sistema SHALL permitir que o administrador altere o nome do aplicativo nas configurações e SHALL exibir a versão do sistema de forma somente-leitura. Estes dados devem refletir no menu principal.

## MODIFIED Requirements
N/A

## REMOVED Requirements
N/A
