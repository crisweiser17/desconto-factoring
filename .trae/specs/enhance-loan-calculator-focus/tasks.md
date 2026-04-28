# Tasks
- [x] Task 1: Destacar o resultado principal no card de resumo
  - [x] SubTask 1.1: Identificar no HTML do resumo os blocos ligados a `resumoPv`, `resumoPmt` e `resumoTaxa`
  - [x] SubTask 1.2: Adicionar classes/estrutura visual para permitir destacar exatamente um bloco do resumo por vez
  - [x] SubTask 1.3: Ajustar o CSS/markup em `index.php` para que o destaque seja perceptível sem descaracterizar o card atual

- [x] Task 2: Ajustar a calculadora por modo de descoberta
  - [x] SubTask 2.1: Refinar `updateModoFlexivel()` para aplicar o destaque correto no resumo conforme os modos `parcela`, `taxa` e `emprestimo`
  - [x] SubTask 2.2: Reduzir a ênfase visual do campo calculado na área superior
  - [x] SubTask 2.3: Ocultar o campo calculado na área superior quando ele já estiver adequadamente representado no resumo, sem quebrar o fluxo do cálculo

- [x] Task 3: Validar usabilidade e consistência visual
  - [x] SubTask 3.1: Verificar o comportamento nos três modos de descoberta
  - [x] SubTask 3.2: Confirmar que o card de resumo continua sendo atualizado corretamente
  - [x] SubTask 3.3: Validar sintaxe/lint e revisar o preview no navegador

# Task Dependencies
- [Task 2] depends on [Task 1]
- [Task 3] depends on [Task 2]
