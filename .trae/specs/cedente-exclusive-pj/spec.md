# Cedente Exclusivo Pessoa Jurídica Spec

## Why
O Cedente de uma operação de desconto deve ser obrigatoriamente Pessoa Jurídica, de acordo com as regras de negócio. O usuário solicitou a remoção da opção de "Pessoa Física" que havia sido liberada para testes em interações anteriores. O usuário também pontuou duplicação da seção de sócios em `form_sacado.php` e preenchimento de contas, ajustes estes que já haviam sido concluídos na etapa passada (forçar CNPJ/Razão Social na conta bancária e remoção da div duplicada).

## What Changes
- **form_cedente.php**: Substituir o `<select>` de "Tipo de Pessoa" por um `<input type="text" readonly>` exibindo "Pessoa Jurídica" (ou um select desativado) e garantir o envio do valor através de um input hidden.
- **salvar_cedente.php**: Forçar o valor de `tipo_pessoa` no backend para `'JURIDICA'` como medida de segurança.
- As demais lógicas (conta bancária copiada automaticamente, readonly, etc) continuam mantidas como as implementadas anteriormente. A seção duplicada de sócios no `form_sacado.php` já não existe mais no código atual, então a tarefa apenas confirmará a ausência.

## Impact
- Affected specs: Cadastro de Cedente
- Affected code: `form_cedente.php`, `salvar_cedente.php`

## ADDED Requirements
N/A

## MODIFIED Requirements
### Requirement: Tipo de Pessoa do Cedente
O sistema SHALL permitir o cadastro de Cedentes APENAS como Pessoa Jurídica. Opções de Pessoa Física não serão mais oferecidas neste formulário.

## REMOVED Requirements
### Requirement: Cedente como PF/PJ
**Reason**: Regra de negócio exige PJ para cedente.
**Migration**: Voltar a travar o Cedente exclusivamente como JURIDICA.