# Tasks
- [x] Task 1: Auto-selecionar e bloquear campo de natureza para Empréstimo
  - [x] Modificar o `<select name="natureza" id="modalNatureza">` no modal de contratos para carregar pré-selecionado se a operação (`$isEmprestimo`) for de empréstimo.
  - [x] Adicionar o atributo `disabled` ou uma sobreposição para impedir alteração, mantendo um `<input type="hidden">` ou enviando explicitamente no JS.
  - [x] Garantir que o valor "EMPRESTIMO" ainda é coletado corretamente pela submissão do formulário no botão de "Gerar Contratos" (através do objeto `FormData` na linha 2432 do script atual).