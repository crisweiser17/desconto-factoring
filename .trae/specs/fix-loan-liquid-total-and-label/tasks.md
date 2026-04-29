# Tasks
- [x] Task 1: Diagnosticar por que o valor líquido numérico não aparece no resumo do contrato.
  - [x] SubTask 1.1: Localizar no template e em `api_contratos.php` qual variável deveria preencher a linha `VALOR LÍQUIDO A PAGAR AO CEDENTE`.
  - [x] SubTask 1.2: Confirmar se o problema está no payload, no nome da variável do template ou na renderização final.

- [x] Task 2: Corrigir o valor líquido do resumo financeiro do contrato.
  - [x] SubTask 2.1: Ajustar o backend e/ou template para exibir o valor líquido numérico correto.
  - [x] SubTask 2.2: Garantir que o valor líquido numérico use a mesma base do valor líquido por extenso.
  - [x] SubTask 2.3: Preservar o comportamento atual dos contratos de desconto sem regressão.

- [x] Task 3: Ajustar o label da tela de detalhes para empréstimo.
  - [x] SubTask 3.1: Alterar em `detalhes_operacao.php` o texto `Valor Futuro da Operação:` para `Valor a Receber`.
  - [x] SubTask 3.2: Preservar os demais labels condicionais já existentes na tela.

- [x] Task 4: Validar a correção ponta a ponta.
  - [x] SubTask 4.1: Gerar novamente o contrato de empréstimo e verificar a linha `VALOR LÍQUIDO A PAGAR AO CEDENTE`.
  - [x] SubTask 4.2: Verificar que a tela de detalhes exibe `Valor a Receber`.
  - [x] SubTask 4.3: Executar testes, lint e preview local antes da entrega.

# Task Dependencies
- [Task 2] depends on [Task 1]
- [Task 4] depends on [Task 2]
- [Task 4] depends on [Task 3]
