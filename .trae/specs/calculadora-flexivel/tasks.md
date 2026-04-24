# Tasks

- [x] Tarefa 1: Criar/Atualizar funções utilitárias de matemática financeira.
  - [x] Subtarefa 1.1: Implementar função para cálculo de Parcela (PMT - Present Value).
  - [x] Subtarefa 1.2: Implementar função para cálculo de Taxa de Juros (I) a partir do Valor do Empréstimo, Parcela e Prazo (usando método de aproximação iterativa como Newton-Raphson).
  - [x] Subtarefa 1.3: Implementar função para cálculo de Valor do Empréstimo (PV - Present Value) a partir da Parcela, Taxa e Prazo.
  - [x] Subtarefa 1.4: Adicionar testes automatizados (unitários) para garantir a precisão dos três cálculos.
- [x] Tarefa 2: Desenvolver a Interface da Calculadora Flexível.
  - [x] Subtarefa 2.1: Criar um componente de seleção visual ("O que você deseja calcular?": Parcela, Taxa de Juros, Valor do Empréstimo).
  - [x] Subtarefa 2.2: Ajustar o formulário para exibir/desabilitar os inputs corretos baseados na seleção do usuário (layout dinâmico).
  - [x] Subtarefa 2.3: Desenvolver o "Card de Resumo" (Summary) que apresenta todos os dados calculados em tempo real (Total do Empréstimo, Parcela, Taxa, Prazo, Valor Total, Lucro da Operação).
- [x] Tarefa 3: Integrar lógica de Rascunho (Draft) e Persistência.
  - [x] Subtarefa 3.1: Salvar os dados do formulário e a aba selecionada no `localStorage` durante a digitação.
  - [x] Subtarefa 3.2: Restaurar os dados ao recarregar a página (dentro de `useEffect` ou similar).
  - [x] Subtarefa 3.3: Adicionar o botão "Limpar Campos / Nova Simulação" para limpar o formulário e o `localStorage`.
