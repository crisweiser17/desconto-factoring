# Tasks
- [x] Task 1: Adicionar Opção Pagamento Único e Renomear Campo
  - [x] SubTask 1.1: Adicionar opção "Pagamento Único" no select de frequência em `index.php`
  - [x] SubTask 1.2: Renomear label "Qtd. de parcelas" para "Num. de parcelas"
  - [x] SubTask 1.3: Adicionar JavaScript para desabilitar campo de parcelas quando "Pagamento Único" for selecionado
  - [x] SubTask 1.4: Mover campo de taxa de juros para dentro do fieldset de empréstimo

- [x] Task 2: Corrigir Cálculo de Juros para Vencimentos > 30 Dias
  - [x] SubTask 2.1: Remover tratamento especial para dias=0 em `calculate.php`
  - [x] SubTask 2.2: Ajustar limiar de validação de taxa de 1e-9 para valor mais apropriado
  - [x] SubTask 2.3: Adicionar suporte para cálculo de pagamento único em `financeMath.js`
  - [x] SubTask 2.4: Testar cálculos com diferentes cenários de vencimento

- [x] Task 3: Testar e Validar
  - [x] SubTask 3.1: Testar cálculo com vencimento de 45 dias (taxa deve ser aplicada proporcionalmente)
  - [x] SubTask 3.2: Testar modo "Pagamento Único" com diferentes valores
  - [x] SubTask 3.3: Verificar consistência entre frontend e backend

# Task Dependencies
- [Task 2] depends on [Task 1]
