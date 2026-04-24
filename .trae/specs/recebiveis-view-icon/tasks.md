# Tasks
- [x] Task 1: Adicionar o ícone de visualização na tabela principal (`listar_recebiveis.php`)
  - [x] Inserir o HTML do botão `<a href="detalhes_operacao.php?id=..." class="btn btn-primary action-btn">` com ícone `bi-eye` dentro do `td` da classe `actions-cell`, após a condicional dos botões de status.
- [x] Task 2: Adicionar o ícone na resposta AJAX de alteração de status (`atualizar_status.php`)
  - [x] Alterar a consulta SQL de sucesso para sempre buscar `operacao_id` além de `data_recebimento`.
  - [x] Ajustar o `ob_start()` do `$newActionsHtml` para incluir também o botão `<a href="detalhes_operacao.php?id=..." class="btn btn-primary action-btn">` com ícone `bi-eye` no final da string gerada.

# Task Dependencies
- Task 2 é independente da Task 1, mas juntas elas formam o funcionamento completo do recurso.