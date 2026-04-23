# Tasks
- [x] Task 1: Remover validação no botão Gerar Parcelas (`index.php`)
  - [x] SubTask 1.1: Localizar e remover/comentar a linha `if (!tomadorId) { alert('Selecione o Tomador de Empréstimo (Sacado).'); return; }` dentro do evento de clique de `btnGerarParcelas`.
- [x] Task 2: Adicionar validação no botão Registrar Operação (`index.php`)
  - [x] SubTask 2.1: Dentro do evento de clique do `registerBtn`, adicionar uma condição para verificar se a operação é empréstimo (`isEmprestimo`).
  - [x] SubTask 2.2: Se for empréstimo e o valor de `document.getElementById('tomador').value` estiver vazio, exibir erro visual no `registerFeedback` e interromper o fluxo com `return`.
- [x] Task 3: Melhorar feedback visual no campo Tomador (`index.php`)
  - [x] SubTask 3.1: Adicionar um `addEventListener('change')` no select `#tomador` para remover a classe `is-invalid` e limpar mensagens de erro ao selecionar um valor.

# Task Dependencies
- [Task 2] depends on [Task 1]
- [Task 3] depends on [Task 2]