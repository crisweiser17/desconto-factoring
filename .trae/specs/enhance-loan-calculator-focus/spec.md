# Destaque do Resultado na Calculadora de Empréstimo Spec

## Why
O layout atual da calculadora de empréstimo já separa bem os campos de entrada do card de resumo, mas o resultado principal ainda compete visualmente com os inputs da área superior. Quando o usuário escolhe descobrir parcela, taxa ou valor do empréstimo, o ideal é que o número calculado ganhe evidência no resumo da simulação, sem duplicar a mesma informação em dois lugares.

## What Changes
- Ajustar a interface da calculadora de empréstimo em `index.php` para destacar visualmente, dentro do card de resumo, o item que está sendo descoberto no modo atual.
- Reduzir a ênfase do campo calculado na área de inputs, podendo ocultá-lo quando ele já estiver representado claramente no resumo.
- Refinar a função `updateModoFlexivel()` para controlar visibilidade, destaque e estado visual dos campos e do card de resumo conforme o modo selecionado.
- Adicionar classes/estados visuais específicos para o resumo da simulação, sem alterar a lógica financeira já implementada.

## Impact
- Affected specs: `fix-loan-calculator`
- Affected code: `index.php`

## ADDED Requirements
### Requirement: Destaque Visual do Resultado Atual
O sistema SHALL destacar, no card `Resumo da Simulação (Empréstimo)`, o indicador correspondente ao valor que o usuário está tentando descobrir no modo de cálculo selecionado.

#### Scenario: Descobrir Parcela
- **WHEN** o usuário seleciona `Descobrir Parcela`
- **THEN** o bloco `Parcela` do resumo deve receber destaque visual superior aos demais blocos

#### Scenario: Descobrir Taxa de Juros
- **WHEN** o usuário seleciona `Descobrir Taxa de Juros`
- **THEN** o bloco `Taxa a.m.` do resumo deve receber destaque visual superior aos demais blocos

#### Scenario: Descobrir Valor do Empréstimo
- **WHEN** o usuário seleciona `Descobrir Valor do Empréstimo`
- **THEN** o bloco `Empréstimo` do resumo deve receber destaque visual superior aos demais blocos

## MODIFIED Requirements
### Requirement: Área de Inputs da Calculadora Flexível
O sistema SHALL manter a estrutura atual com campos de entrada acima e card de resumo abaixo, mas o campo calculado na área superior não deve competir visualmente com o resumo quando o mesmo valor já estiver claramente representado no card.

#### Scenario: Campo Calculado no Modo Atual
- **WHEN** o usuário seleciona um modo em que um dos valores é calculado automaticamente
- **THEN** o campo correspondente na área de inputs pode ser ocultado ou receber baixa ênfase, desde que o valor permaneça em destaque no resumo

## REMOVED Requirements
### Requirement: Exibir com Mesma Ênfase o Valor Calculado em Dois Locais
**Reason**: Duplicar a ênfase visual no input superior e no card de resumo torna a interface mais carregada e reduz a clareza do foco principal.
**Migration**: O valor continuará disponível no fluxo da calculadora, mas o destaque principal passa a ser controlado pelo card de resumo.
