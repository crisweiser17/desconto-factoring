# Tasks
- [x] Task 1: Mapear os labels atuais de `detalhes_operacao.php` por contexto.
  - [x] SubTask 1.1: Identificar quais campos já são neutros e podem permanecer iguais.
  - [x] SubTask 1.2: Identificar quais campos de desconto precisam de texto mais claro.
  - [x] SubTask 1.3: Identificar quais campos ficam semanticamente incorretos em `emprestimo`.

- [x] Task 2: Implementar labels condicionais para operações de desconto.
  - [x] SubTask 2.1: Ajustar o texto de `Taxa Mensal Aplicada` para `Taxa de Desconto Aplicada`.
  - [x] SubTask 2.2: Ajustar o texto de `Total Líquido Pago` para `Valor Líquido Liberado`.
  - [x] SubTask 2.3: Ajustar o texto de `Total Original (Recebíveis)` para `Total Original dos Recebíveis`.

- [x] Task 3: Implementar labels condicionais para operações de empréstimo.
  - [x] SubTask 3.1: Exibir `Tomador do Empréstimo` no lugar do rótulo genérico do cliente.
  - [x] SubTask 3.2: Exibir `Taxa de Juros Aplicada` no lugar de `Taxa Mensal Aplicada`.
  - [x] SubTask 3.3: Exibir `IOF Repassado ao Cliente` no lugar de `Cobra IOF do Cliente`.
  - [x] SubTask 3.4: Exibir `Valor Futuro da Operação` no lugar do total hoje derivado de `$totalOriginalCalculado`.
  - [x] SubTask 3.4.1: Exibir `Valor Original do Empréstimo` a partir de `operacao['valor_emprestimo']` quando o dado estiver disponível, distinguindo principal x valor futuro.
  - [x] SubTask 3.5: Exibir `Valor Liberado ao Tomador` no lugar de `Total Líquido Pago`.
  - [x] SubTask 3.6: Exibir `Receita/Lucro Líquido da Operação` no lugar de `Lucro Líquido`.
  - [x] SubTask 3.7: Avaliar se `Tipo de Pagamento` deve virar `Forma de Recebimento` com base no significado real do dado salvo.
  - [x] SubTask 3.8: Remover a menção a `Notificação ao Sacado` da descrição de pagamento direto em `emprestimo`.

- [x] Task 4: Ocultar campos exclusivos de desconto em operações de empréstimo.
  - [x] SubTask 4.1: Não exibir `Custo da Antecipação` quando `tipo_operacao = emprestimo`.
  - [x] SubTask 4.2: Revisar se `Abatimento` deve continuar visível somente quando houver compensação aplicável.
  - [x] SubTask 4.3: Ocultar ações e modal de `Notificar Sacados` quando a operação for `emprestimo`.

- [x] Task 5: Validar visualmente e tecnicamente a tela ajustada.
  - [x] SubTask 5.1: Testar uma operação de desconto e confirmar os labels esperados.
  - [x] SubTask 5.2: Testar uma operação de empréstimo e confirmar os labels esperados.
  - [x] SubTask 5.3: Rodar lint/diagnóstico no arquivo alterado.
  - [x] SubTask 5.4: Abrir preview local e validar a renderização no navegador.

# Task Dependencies
- [Task 2] depends on [Task 1]
- [Task 3] depends on [Task 1]
- [Task 4] depends on [Task 3]
- [Task 5] depends on [Task 2]
- [Task 5] depends on [Task 3]
- [Task 5] depends on [Task 4]
