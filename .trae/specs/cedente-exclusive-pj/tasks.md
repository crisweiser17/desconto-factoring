# Tasks

- [x] Task 1: Remover Opção Física do Form Cedente
  - [x] SubTask 1.1: No arquivo `form_cedente.php`, substituir o `<select id="tipo_pessoa">` por um `<input type="text" class="form-control" value="Pessoa Jurídica" readonly>` (visível para o usuário) e um `<input type="hidden" id="tipo_pessoa" name="tipo_pessoa" value="JURIDICA">` (para envio e compatibilidade JS).

- [x] Task 2: Forçar JURIDICA no backend
  - [x] SubTask 2.1: No `salvar_cedente.php`, forçar que a variável de inserção de `tipo_pessoa` seja invariavelmente `'JURIDICA'`, independentemente do que vier no POST.

- [x] Task 3: Verificações e Limpezas (Revisão da rodada anterior)
  - [x] SubTask 3.1: Validar (grep) se de fato só há um "Sócios da Empresa" em `form_sacado.php`. (Já foi removido, mas deve ser validado para assegurar que a duplicidade relatada pelo usuário sumiu).

# Task Dependencies
- Task 2 depende da Task 1.