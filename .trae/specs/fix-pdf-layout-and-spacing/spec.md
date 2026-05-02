# Fix PDF Layout and Spacing Spec

## Why
Os PDFs gerados para os contratos e para a Nota Promissória estão exibindo caracteres literais de barra invertida (`\`) nos campos de texto e assinaturas devido a falhas na interpretação do Markdown pelo gerador de PDF. Além disso, as áreas reservadas para assinaturas (MUTUANTE, MUTUÁRIO, CÔNJUGE, TESTEMUNHAS) estão muito coladas, dificultando a assinatura física, e não possuem um bom alinhamento/centralização.

## What Changes
- **Remoção de Barras Invertidas Literais**: Substituir usos de `\` no final de linhas (que tentavam forçar quebras de linha em Markdown) por tags explícitas `<br />` em todos os templates de contratos, especialmente na qualificação inicial e na Nota Promissória.
- **Refatoração dos Blocos de Assinatura**: 
  - Centralizar os blocos de assinatura para maior elegância no documento (opcional mas recomendado se via div, ou manter alinhado à esquerda com espaçamento adequado).
  - Adicionar múltiplas quebras de espaço (ex: `<br><br><br>`) antes das linhas de assinatura para garantir que há altura suficiente para assinaturas físicas grandes.
  - Substituir `\ \_\_\_\_\_ \` por uma linha de underscore limpa `_______________________________________<br>`, removendo escapes problemáticos.

## Impact
- Affected specs: `geracao_contratos`, `layout_pdf`
- Affected code: `_contratos/*.md` (focado na região inferior de assinaturas e formatação de texto comum).

## ADDED/MODIFIED Requirements

### Requirement: Espaçamento de Assinaturas
**Quando** um contrato PDF for gerado, **então** o documento deverá conter espaçamento adequado entre o final de um declarante e o bloco de assinatura do próximo, garantindo que "MUTUANTE", "MUTUÁRIO" e "CÔNJUGE" tenham "respiro" visual.

### Requirement: Quebra de Linhas Seguras
**Quando** quebras de linha explícitas (new lines) forem necessárias na mesma estrutura (ex: dados do emitente), **então** os templates deverão usar a tag padrão HTML `<br />` ao invés do operador Markdown `\`, que tem histórico de conflito com o motor DOMPDF do sistema.