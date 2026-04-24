# Melhoria no Menu de Anotações Spec

## Why
Ao adicionar uma anotação vinculada a um recebível, o usuário tem dificuldade de identificar qual é o recebível correto apenas pelo tipo e ID. Mostrar a data de vencimento e o valor torna a seleção mais fácil e precisa.

## What Changes
- Alteração do texto exibido no select "Associar a" (modal de nova anotação).
- Adição da data de vencimento (formatada em d/m/Y) e do valor original (formatado em R$) na listagem de recebíveis (tag `<option>`).

## Impact
- Affected specs: operation-notes
- Affected code: `detalhes_operacao.php`

## ADDED Requirements
### Requirement: Seleção Facilitada de Recebíveis
O sistema SHALL exibir o valor original e a data de vencimento de cada recebível listado no menu de associação de uma nova anotação.

#### Scenario: Sucesso ao abrir o menu de associação
- **WHEN** usuário clica para adicionar uma anotação e abre o campo "Associar a"
- **THEN** as opções de recebíveis devem ser exibidas no formato: "Recebível #ID (Tipo) - Venc: DD/MM/AAAA - R$ X.XXX,XX".
