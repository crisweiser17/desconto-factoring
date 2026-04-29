# Tasks
- [x] Task 1: Diagnosticar a origem dos valores zerados no borderô do contrato de empréstimo.
  - [x] SubTask 1.1: Localizar em `api_contratos.php` onde os títulos da operação são lidos e transformados no payload do template.
  - [x] SubTask 1.2: Confirmar quais campos monetários dos recebíveis representam `Valor de Face` e `Valor Presente` para empréstimo.
  - [x] SubTask 1.3: Identificar por que o template está recebendo `0,00` em vez dos valores reais.

- [x] Task 2: Corrigir a montagem dos dados do borderô e do resumo financeiro.
  - [x] SubTask 2.1: Ajustar o payload dos títulos para usar os campos monetários corretos em contratos de empréstimo.
  - [x] SubTask 2.2: Garantir que o resumo financeiro utilize os mesmos valores efetivos exibidos na relação de títulos.
  - [x] SubTask 2.3: Preservar o fluxo atual dos contratos de desconto sem regressão.

- [x] Task 3: Validar a correção ponta a ponta.
  - [x] SubTask 3.1: Gerar novamente o contrato da operação 59 e verificar que os itens do borderô não aparecem zerados indevidamente.
  - [x] SubTask 3.2: Verificar que o `Valor Total de Face` do resumo condiz com os títulos listados.
  - [x] SubTask 3.3: Executar testes, lint e preview local antes da entrega.

# Task Dependencies
- [Task 2] depends on [Task 1]
- [Task 3] depends on [Task 2]
