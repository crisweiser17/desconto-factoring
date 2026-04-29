# Correção do Resumo da Simulação Online Spec

## Why
Após as melhorias visuais da calculadora de empréstimo, a versão online passou a exibir a seção `Resumo da Simulação` sem atualizar os indicadores quando o usuário informa o valor do empréstimo. No `localhost` o comportamento funciona, então precisamos corrigir a regressão priorizando compatibilidade e previsibilidade no ambiente online.

## What Changes
- Corrigir a inicialização e atualização do card `Resumo da Simulação` para que os indicadores sejam preenchidos corretamente na versão online
- Remover a dependência de estados visuais recentes que possam esconder ou interromper a atualização dos indicadores
- Restaurar o comportamento anterior do resumo quando isso for necessário para manter consistência entre `localhost` e produção
- Validar a compatibilidade do fluxo de empréstimo nos modos `Descobrir Parcela`, `Descobrir Taxa de Juros` e `Descobrir Valor do Empréstimo`

## Impact
- Affected specs: calculadora de empréstimo
- Affected code: `index.php`, possíveis funções JS ligadas ao resumo e ao modo flexível

## ADDED Requirements
### Requirement: Compatibilidade do Resumo Online
O sistema SHALL atualizar os indicadores do `Resumo da Simulação` no modo `Empréstimo` de forma consistente tanto no `localhost` quanto na versão online.

#### Scenario: Preenchimento do valor do empréstimo
- **WHEN** o usuário informa os dados mínimos necessários no modo `Descobrir Parcela`
- **THEN** os indicadores do resumo devem refletir os valores calculados sem depender de comportamento exclusivo do ambiente local

#### Scenario: Ambiente com estado parcial
- **WHEN** a página estiver no modo `Empréstimo` mas ainda sem todos os dados preenchidos
- **THEN** o resumo pode permanecer visível, porém não deve impedir a atualização posterior dos indicadores

## MODIFIED Requirements
### Requirement: Resumo da Simulação no Empréstimo
O sistema SHALL priorizar a atualização funcional dos indicadores do resumo sobre ajustes visuais, evitando regressões causadas por ocultação dinâmica ou reorganização de campos.

#### Scenario: Regressão após melhoria visual
- **WHEN** uma melhoria de layout interferir na atualização dos indicadores
- **THEN** o sistema deve preservar o comportamento funcional anterior mesmo que parte do refinamento visual precise ser simplificada
