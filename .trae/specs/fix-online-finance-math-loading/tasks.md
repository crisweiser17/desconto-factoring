# Tasks
- [x] Task 1: Revisar o carregamento das dependências financeiras no frontend
  - [x] SubTask 1.1: Confirmar em `index.php` a ordem de carregamento entre `financeMath.js` e o script inline da calculadora
  - [x] SubTask 1.2: Verificar em `financeMath.js` se as funções financeiras estão expostas no escopo esperado pelo navegador
  - [x] SubTask 1.3: Identificar o ponto mínimo de correção para eliminar o `ReferenceError` online

- [x] Task 2: Corrigir o carregamento online e endurecer o fluxo contra divergência de versão
  - [x] SubTask 2.1: Ajustar o `script src` de `financeMath.js` para reduzir risco de cache desatualizado
  - [x] SubTask 2.2: Garantir que as funções de matemática financeira estejam acessíveis antes do uso no `index.php`
  - [x] SubTask 2.3: Adicionar guarda defensiva para evitar quebra total caso a dependência externa falhe

- [x] Task 3: Validar o fluxo da calculadora após a correção
  - [x] SubTask 3.1: Testar a ausência de `ReferenceError` ao alterar os campos do empréstimo
  - [x] SubTask 3.2: Validar os modos `Descobrir Parcela`, `Descobrir Taxa de Juros` e `Descobrir Valor do Empréstimo`
  - [x] SubTask 3.3: Rodar verificação de sintaxe e checar o preview/browser

# Task Dependencies
- [Task 2] depends on [Task 1]
- [Task 3] depends on [Task 2]
