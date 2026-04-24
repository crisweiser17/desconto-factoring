# Adicionar Ícone de Visualização na Lista de Recebíveis Spec

## Why
O usuário deseja um atalho rápido (um ícone de visualização) diretamente na coluna de "Ações" da lista de recebíveis (`listar_recebiveis.php`), permitindo acessar imediatamente a página de detalhes da operação (`detalhes_operacao.php`) à qual o recebível pertence, sem precisar clicar no ID da operação lá no começo da tabela.

## What Changes
- `listar_recebiveis.php`: Adicionar um link com ícone de "olho" (view) posicionado à direita dos botões de mudança de status na coluna "Ações".
- `atualizar_status.php`: Ajustar a rotina que devolve o HTML atualizado dos botões via AJAX para incluir a busca pelo `operacao_id` no banco de dados e renderizar o mesmo ícone de visualização, garantindo que ele não desapareça quando o usuário alterar o status do recebível.

## Impact
- Affected specs: Listagem e Gestão de Recebíveis.
- Affected code: `listar_recebiveis.php`, `atualizar_status.php`.

## ADDED Requirements
### Requirement: Visualizar Operação via Ícone
O sistema SHALL exibir um botão de ação com ícone de olho em cada linha da lista de recebíveis, apontando para a página de detalhes da operação correspondente.

#### Scenario: Success case
- **WHEN** user acessa a lista de recebíveis
- **THEN** ele vê o ícone de olho na coluna "Ações"
- **AND** ao alterar o status do recebível, o ícone de olho permanece na tela renderizado pelo AJAX

## MODIFIED Requirements
N/A

## REMOVED Requirements
N/A