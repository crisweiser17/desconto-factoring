# Correção do Carregamento Online do Finance Math Spec

## Why
Na versão online, o console mostra `ReferenceError: calculatePMTFromDays is not defined` durante o uso da calculadora de empréstimo. Isso indica que o `index.php` está chamando funções financeiras novas sem garantir que `financeMath.js` foi carregado corretamente no ambiente publicado.

## What Changes
- Garantir que `financeMath.js` seja carregado de forma confiável antes do script inline de `index.php`
- Adicionar proteção contra cache/descompasso entre `index.php` e `financeMath.js` na versão online
- Validar que as funções `calculatePMTFromDays`, `calculatePVFromDays` e `calculateRATEFromDays` estejam acessíveis no escopo esperado
- Adicionar fallback/guarda defensiva para evitar quebra total da interface caso o arquivo JS externo falhe

## Impact
- Affected specs: calculadora de empréstimo
- Affected code: `index.php`, `financeMath.js`

## ADDED Requirements
### Requirement: Carregamento Confiável das Funções Financeiras
O sistema SHALL carregar as funções de matemática financeira antes de qualquer uso no fluxo de empréstimo.

#### Scenario: Uso online da calculadora
- **WHEN** o usuário preencher os campos do empréstimo na versão online
- **THEN** as funções `calculatePMTFromDays`, `calculatePVFromDays` e `calculateRATEFromDays` devem estar disponíveis sem gerar `ReferenceError`

#### Scenario: Versão em cache desatualizada
- **WHEN** o navegador estiver com uma versão antiga do JavaScript em cache
- **THEN** o sistema deve reduzir o risco de incompatibilidade entre o `index.php` novo e o `financeMath.js` antigo

## MODIFIED Requirements
### Requirement: Robustez da Calculadora de Empréstimo
O sistema SHALL tratar dependências JavaScript externas como pré-requisito explícito do fluxo de cálculo, evitando que uma falha de carregamento quebre silenciosamente a interface.

#### Scenario: Falha no carregamento do arquivo externo
- **WHEN** `financeMath.js` não carregar corretamente
- **THEN** o sistema deve impedir a execução quebrada do cálculo e fornecer comportamento defensivo previsível
