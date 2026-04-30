# Plano: Reordenar campos de garantia no modal de contratos

## Summary
- Ajustar a ordem visual dos campos no modal `Gerar Contratos` para que `Tipo da Garantia` fique imediatamente à esquerda de `Cliente ofereceu garantia?`.
- Manter o campo `O sacado tem avalista?` fora desse intervalo, posicionando-o após os dois campos de garantia.
- Preservar toda a lógica existente de exibição, submissão e habilitação dos campos, alterando apenas a estrutura visual do grid.

## Current State Analysis
- O modal `Gerar Contratos` está em `detalhes_operacao.php`.
- Dentro da seção `#garantiaToggleSection`, os campos estão renderizados hoje nesta ordem:
  1. `Cliente ofereceu garantia?`
  2. `O sacado tem avalista?`
  3. `Tipo da Garantia`
  4. `Cônjuge vai Assinar?`
- A lógica JavaScript que controla essa área referencia os campos por IDs e `name`:
  - `modalTipoGarantia`
  - radios `tem_garantia_real`
  - radios `tem_avalista`
- Pela exploração, a regra de negócio não depende da posição visual dos blocos no HTML; ela depende apenas desses seletores.

## Proposed Changes
- Arquivo: `detalhes_operacao.php`
- Alterar apenas a ordem dos blocos `<div class="col-md-4">` dentro de `#garantiaToggleSection`.
- Nova ordem planejada:
  1. `Tipo da Garantia`
  2. `Cliente ofereceu garantia?`
  3. `O sacado tem avalista?`
  4. `Cônjuge vai Assinar?`
- Motivo:
  - atender ao requisito visual de deixar `Tipo da Garantia` logo à esquerda de `Cliente ofereceu garantia?`
  - remover a separação causada pelo campo `O sacado tem avalista?`
- Como implementar:
  - mover o bloco do select `#modalTipoGarantia` para antes do bloco dos radios `tem_garantia_real`
  - manter intactos `id`, `name`, valores default e classes Bootstrap já usadas
  - não alterar a lógica JavaScript em `atualizarCamposEmprestimo()`, pois ela já usa IDs estáveis e não depende da ordem DOM

## Assumptions & Decisions
- Escopo limitado a layout do modal de contratos, sem mudança de comportamento funcional.
- O texto “logo à esquerda” será atendido na mesma linha do grid Bootstrap em telas `md` ou maiores.
- Não serão alteradas labels, opções, defaults, regras de visibilidade nem payload enviado no submit.
- A ordem do campo `Cônjuge vai Assinar?` permanece após os três campos mencionados, pois o pedido não indicou mudança nele.

## Verification
- Revisar visualmente o modal `Gerar Contratos` em `detalhes_operacao.php` e confirmar a nova sequência dos blocos.
- Abrir o modal em uma operação de empréstimo e validar que a primeira linha mostra:
  - `Tipo da Garantia`
  - `Cliente ofereceu garantia?`
  - `O sacado tem avalista?`
- Confirmar que:
  - `Tipo da Garantia` continua habilitando/desabilitando conforme `Cliente ofereceu garantia?`
  - a troca de `O sacado tem avalista?` continua exibindo/ocultando a seção de avalista
  - o submit do modal continua enviando `tem_garantia_real`, `tem_avalista` e `tipo_garantia`
- Rodar checagem de sintaxe PHP no arquivo alterado e validar o fluxo no navegador após a implementação.
