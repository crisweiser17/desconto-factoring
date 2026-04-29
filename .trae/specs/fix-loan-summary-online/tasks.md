# Tasks
- [x] Task 1: Isolar a causa da divergência entre `localhost` e versão online
  - [x] SubTask 1.1: Revisar em `index.php` as funções que controlam `updateModoFlexivel()` e `atualizarCardResumoEmprestimo()`
  - [x] SubTask 1.2: Identificar se a regressão está na lógica de cálculo, na visibilidade do card ou na ordem de inicialização dos eventos
  - [x] SubTask 1.3: Confirmar quais mudanças recentes de layout/foco visual impactaram o preenchimento dos indicadores

- [x] Task 2: Restaurar o preenchimento confiável dos indicadores do resumo
  - [x] SubTask 2.1: Ajustar a lógica para que os indicadores sejam atualizados corretamente ao informar o valor do empréstimo
  - [x] SubTask 2.2: Reverter ou simplificar trechos visuais recentes se eles estiverem interferindo no comportamento online
  - [x] SubTask 2.3: Garantir que os três modos da calculadora continuem funcionando após a correção

- [x] Task 3: Validar compatibilidade e ausência de regressão
  - [x] SubTask 3.1: Testar o fluxo no `localhost`
  - [x] SubTask 3.2: Validar o comportamento no preview/browser simulando a versão online
  - [x] SubTask 3.3: Rodar verificação de sintaxe e revisar se o resumo volta a preencher os indicadores corretamente

# Task Dependencies
- [Task 2] depends on [Task 1]
- [Task 3] depends on [Task 2]
