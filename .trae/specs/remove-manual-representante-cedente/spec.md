# Remover Preenchimento Manual Representante Spec

## Why
O usuário solicitou que no formulário de Cedente (e por consistência, no de Sacado) não seja mais permitida a opção "Outro (Preenchimento Manual)" no campo de "Selecionar Sócio como Representante". Essa opção induz o usuário a achar que pode preencher os dados manualmente, mas a regra atual (já implementada) define que o representante de uma Pessoa Jurídica deve ser obrigatoriamente um dos sócios cadastrados, com os campos bloqueados (`readonly`).

## What Changes
- Alterar o texto da option padrão do select `<select id="representante_socio_select">` de "Outro (Preenchimento Manual)" para "Selecione um Sócio...".
- Atualizar o código JavaScript (`repSelect.html(...)`) que repopula esse `<select>` dinamicamente, para que a option default seja "Selecione um Sócio...".

## Impact
- Affected specs: Cadastro de Cedentes e Cadastro de Sacados.
- Affected code: `form_cedente.php` e `form_sacado.php`.

## ADDED Requirements
N/A

## MODIFIED Requirements
### Requirement: Seleção de Sócio como Representante
O sistema SHALL exibir "Selecione um Sócio..." como opção padrão e vazia no dropdown de representantes, indicando claramente que o preenchimento deve ser feito obrigatoriamente escolhendo um sócio existente na lista. A opção "Outro (Preenchimento Manual)" não existirá mais.

## REMOVED Requirements
N/A