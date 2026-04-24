# Calculadora Flexível de Simulação Spec

## Por que (Why)
Atualmente, a calculadora costuma exigir a inserção do valor principal, taxa de juros e prazo para calcular a parcela. No entanto, muitas vezes o cliente já possui uma proposta de um concorrente (com valor de empréstimo e valor de parcela definidos) e precisamos descobrir qual foi a taxa aplicada para podermos cobrir ou igualar a oferta. Tornar a calculadora flexível permitirá que a equipe de vendas preencha os dados que possui e o sistema deduza automaticamente a variável faltante, aumentando a agilidade e a competitividade do negócio.

## O que muda (What Changes)
- Adição de um seletor de "Modo de Cálculo" (O que você deseja descobrir?) na tela de Nova Simulação.
- **Modos de Cálculo Suportados:**
  1. **Descobrir Parcela:** Informa Valor do Empréstimo (Principal), Taxa e Prazo. (Retorna: Parcela, Valor Total, Lucro).
  2. **Descobrir Taxa de Juros:** Informa Valor do Empréstimo, Valor da Parcela e Prazo. (Retorna: Taxa de Juros, Valor Total, Lucro).
  3. **Descobrir Valor do Empréstimo (Poder de Compra):** Informa Valor da Parcela desejada/suportada pelo cliente, Taxa e Prazo. (Retorna: Valor Máximo de Empréstimo, Valor Total, Lucro).
- **Layout dinâmico e óbvio:** os campos de formulário habilitados/exibidos mudam dependendo do modo selecionado.
- Criação de um "Card de Resumo" (Summary Card) em destaque que exibe a radiografia completa da operação: Valor do Empréstimo, Valor da Parcela, Prazo, Taxa de Juros, Valor Total a Pagar e Lucro da Operação.

## Impacto (Impact)
- Especificações afetadas: Fluxo de Nova Simulação.
- Código afetado: Componentes de formulário de simulação, funções utilitárias de matemática financeira (será necessário incluir o cálculo de PMT, PV, e o cálculo da Taxa via aproximação/Newton-Raphson).

## Requisitos ADICIONADOS (ADDED Requirements)
### Requisito: Seletor de Objetivo da Simulação
O sistema DEVE permitir que o usuário escolha explicitamente qual variável ele deseja que a calculadora resolva, mantendo as outras como entrada (inputs).

#### Cenário: Calculando a Taxa de Juros (Match de Concorrência)
- **QUANDO** o usuário seleciona "Descobrir Taxa de Juros"
- **E** preenche Valor do Empréstimo (ex: R$ 10.000), Valor da Parcela (ex: R$ 1.100) e Prazo (10 meses)
- **ENTÃO** o sistema bloqueia/oculta o input de taxa de juros e calcula automaticamente a taxa implícita da operação (aprox. 1.77% a.m.), exibindo o resultado, o total e o lucro no card de resumo.

#### Cenário: Calculando o Valor do Empréstimo
- **QUANDO** o usuário seleciona "Descobrir Valor do Empréstimo"
- **E** preenche o Valor da Parcela que o cliente pode pagar (ex: R$ 500), Taxa de Juros alvo (2% a.m.) e Prazo (12 meses)
- **ENTÃO** o sistema calcula o Valor Presente (PV), mostrando que o cliente pode pegar até ~R$ 5.287,00 emprestados.

### Requisito: Experiência do Usuário (UI/UX) Otimizada e Intuitiva
- Os campos que representam a variável a ser "descoberta" não devem parecer campos editáveis para não confundir o usuário.
- O layout deve ter um design limpo, guiando os olhos para o resultado principal (Card de Resumo).
- **Persistência de Rascunho:** Os dados digitados no modo de edição devem ser salvos no `localStorage` para não serem perdidos em caso de recarregamento, com opção explícita de "Limpar Campos / Nova Simulação" (conforme diretriz de memória do projeto).
