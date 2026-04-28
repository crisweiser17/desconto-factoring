# Plano: Melhorar Layout da Calculadora de Empréstimo

## Summary
Melhorar o layout do `fieldset` da calculadora de empréstimo em `index.php` para deixar os campos principais mais equilibrados visualmente, colocar a taxa na linha de cima e reduzir a quebra visual quando o usuário alterna entre `Descobrir Parcela`, `Descobrir Taxa de Juros` e `Descobrir Valor do Empréstimo`.

## Current State Analysis
- O `fieldset` de empréstimo está em [index.php](file:///Users/crisweiser/Downloads/Projetos%20IDE/Descontos%20Factoring/index.php#L147-L207).
- Os modos de cálculo são controlados pelos rádios `modoCalculo` em [index.php](file:///Users/crisweiser/Downloads/Projetos%20IDE/Descontos%20Factoring/index.php#L151-L163) e pela função `updateModoFlexivel()` em [index.php](file:///Users/crisweiser/Downloads/Projetos%20IDE/Descontos%20Factoring/index.php#L757-L792).
- Hoje os campos variáveis ficam na linha [index.php](file:///Users/crisweiser/Downloads/Projetos%20IDE/Descontos%20Factoring/index.php#L167-L193) e a taxa foi movida para uma linha separada via `containerTaxaEmprestimo`.
- A função `posicionarCampoTaxa()` em [index.php](file:///Users/crisweiser/Downloads/Projetos%20IDE/Descontos%20Factoring/index.php#L730-L738) move o nó `taxaFieldContainer` inteiro para dentro de `containerTaxaEmprestimo`, o que gera um `col-md-4` dentro de outro `col-md-4`.
- Esse aninhamento de colunas é visivelmente incorreto no Bootstrap e é a principal causa do bloco “feio” mostrado no browser.
- O layout também sofre porque os campos que aparecem e somem (`containerValorEmprestimo` e `containerValorParcela`) deixam lacunas, enquanto os demais campos continuam com larguras fixas.

## Proposed Changes

### 1. Reestruturar o topo do fieldset de empréstimo
- Arquivo: `index.php`
- O que mudar:
  - Substituir a linha atual da taxa em [index.php](file:///Users/crisweiser/Downloads/Projetos%20IDE/Descontos%20Factoring/index.php#L194-L196) por uma linha superior mais compacta.
  - Colocar nessa linha: `Taxa de Juros`, `Data Base de Cálculo` e `Possui Garantia?`.
- Por quê:
  - Isso atende diretamente ao pedido de “colocar esse campo na linha de cima”.
  - Centraliza os campos institucionais/fixos no topo da calculadora.
- Como:
  - Remover a estratégia de mover a coluna Bootstrap inteira.
  - Tornar `taxaFieldContainer` um bloco reutilizável sem `col-*` fixa, ou então criar um wrapper específico para a versão da taxa dentro do fieldset.
  - Manter `containerTemGarantia` nessa mesma linha superior, visível apenas em empréstimo.

### 2. Corrigir a arquitetura do campo de taxa no Bootstrap
- Arquivo: `index.php`
- O que mudar:
  - Eliminar o `col-md-4` aninhado dentro de `containerTaxaEmprestimo`.
- Por quê:
  - O HTML atual está estruturalmente errado para grid Bootstrap.
  - Mesmo com a posição “certa”, o campo continuará mal alinhado se a coluna interna continuar existindo.
- Como:
  - Transformar `taxaFieldContainer` em conteúdo neutro de grid, ou
  - deixar o `col-*` no container pai e mover apenas o conteúdo interno do campo.
- Decisão proposta:
  - Preferir deixar o `col-*` apenas no pai visível em cada contexto.

### 3. Colocar os campos variáveis em uma única linha responsiva
- Arquivo: `index.php`
- O que mudar:
  - Reorganizar a linha principal da calculadora para comportar, na mesma linha quando houver espaço:
    - `Valor do Empréstimo`
    - `Valor da Parcela`
    - `Frequência`
    - `Num. de Parcelas`
    - `1º Vencimento`
- Por quê:
  - Esse é o grupo que muda conforme o modo escolhido.
  - O usuário pediu explicitamente tentar colocar “todos os campos numa mesma linha”.
- Como:
  - Ajustar as larguras Bootstrap para distribuir melhor os 5 blocos.
  - Usar classes como `col-lg-*` e `col-md-*` em vez de depender só de `col-md-3`.
  - Quando um dos campos variáveis estiver oculto, o restante deve continuar visualmente equilibrado.

### 4. Melhorar o comportamento visual ao alternar o modo de cálculo
- Arquivo: `index.php`
- O que mudar:
  - Refinar `updateModoFlexivel()` para que a troca entre os três modos não gere “buracos” visuais.
- Por quê:
  - O layout atual foi pensado funcionalmente, mas não visualmente.
  - A experiência fica ruim quando um campo some e os outros não se reorganizam.
- Como:
  - Além de `display`, atualizar classes de largura ou classes auxiliares do container.
  - Definir uma regra clara por modo:
    - `parcela`: mostra `Valor do Empréstimo`, oculta `Valor da Parcela`.
    - `taxa`: mostra ambos.
    - `emprestimo`: mostra ambos, mas com `Valor do Empréstimo` readonly.

### 5. Preservar a lógica funcional já implementada
- Arquivo: `index.php`
- O que manter:
  - `syncQuantidadeParcelasComFrequencia()`
  - `syncGarantiaVisibility()`
  - `buildEmprestimoSchedule()`
  - `calcularValoresFlexiveis()`
- Por quê:
  - O pedido atual é de layout, não de recalcular regra financeira.
- Como:
  - Fazer mudanças apenas na estrutura do HTML e nos pontos mínimos de JS necessários para reposicionar e redimensionar os blocos.

## Assumptions & Decisions
- O objetivo principal é melhorar a usabilidade visual sem alterar a regra de cálculo.
- O campo `Taxa de Juros` deve continuar sendo o mesmo `input` já existente, para não duplicar comportamento, validação e integração com a calculadora.
- `Possui Garantia?` continua sendo exibido apenas quando a operação é `Empréstimo`.
- “Todos os campos numa mesma linha” será tratado como objetivo preferencial em desktop; em telas menores, o layout continuará responsivo com quebra natural.
- Não entra neste plano nenhuma mudança adicional de regra de negócio, apenas reorganização visual e correção estrutural do grid.

## Verification Steps
- Validar sintaxe com `php -l index.php`.
- Verificar no navegador que o topo do `fieldset` de empréstimo exibe na mesma linha:
  - `Taxa de Juros`
  - `Data Base de Cálculo`
  - `Possui Garantia?`
- Verificar que a linha principal dos campos variáveis fica visualmente equilibrada nos modos:
  - `Descobrir Parcela`
  - `Descobrir Taxa de Juros`
  - `Descobrir Valor do Empréstimo`
- Verificar que não existe mais `col-*` aninhado indevidamente dentro de `containerTaxaEmprestimo`.
- Verificar no preview que o layout permanece funcional ao alterar frequência, parcelas, vencimento e garantia.
