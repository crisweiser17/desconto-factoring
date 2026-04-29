# Ajuste de Labels em Detalhes da Operação Spec

## Why
Na tela `detalhes_operacao.php`, alguns rótulos foram herdados do fluxo de desconto e ficam semanticamente incorretos quando a operação é do tipo `emprestimo`. Isso gera confusão para leitura operacional e dificulta o entendimento do que cada valor representa.

## What Changes
- Ajustar dinamicamente os labels da seção `Dados da Operação` conforme `tipo_operacao`
- Manter a linguagem atual para operações de `antecipacao/desconto`, com pequenos refinamentos opcionais de clareza
- Substituir termos ligados a `recebiveis` e `desconto` por termos ligados a `credito`, `parcelas` e `emprestimo` quando a operação for `emprestimo`
- Ocultar campos que não façam sentido semântico para `emprestimo`
- Preservar os mesmos valores calculados já existentes, alterando apenas a apresentação textual nesta etapa

## Impact
- Affected specs: visualização de operações, suporte a empréstimo
- Affected code: `detalhes_operacao.php`

## ADDED Requirements
### Requirement: Labels contextuais por tipo de operação
O sistema SHALL exibir labels compatíveis com o contexto financeiro da operação mostrada em `detalhes_operacao.php`.

#### Scenario: Operação de desconto
- **WHEN** a operação tiver `tipo_operacao` diferente de `emprestimo`
- **THEN** a tela deve exibir labels orientados a desconto/antecipação de recebíveis

#### Scenario: Operação de empréstimo
- **WHEN** a operação tiver `tipo_operacao = emprestimo`
- **THEN** a tela deve exibir labels orientados a crédito/emprestimo e esconder labels semanticamente incorretos para esse contexto

### Requirement: Mapeamento recomendado para desconto
O sistema SHALL usar o seguinte conjunto de labels para operações de desconto:

#### Scenario: Campos exibidos em desconto
- **WHEN** a tela mostrar uma operação de desconto
- **THEN** os campos devem aparecer assim:
- `Cedente:` nome do cliente cedente
- `Data Base de Cálculo:`
- `Data de Registro da Operação:`
- `Taxa de Desconto Aplicada:`
- `Tipo de Operação:`
- `Tipo de Pagamento:`
- `Incorre Custo de IOF:`
- `Cobra IOF do Cliente:`
- `Total Original dos Recebíveis:`
- `Total IOF (Teórico):`
- `Abatimento:` somente quando houver compensação
- `Custo da Antecipação:` somente quando houver compensação
- `Valor Líquido Liberado:` no lugar de `Total Líquido Pago`, para deixar claro que é o valor pago ao cliente
- `Lucro Líquido:`
- `Observações:`

### Requirement: Mapeamento recomendado para empréstimo
O sistema SHALL usar o seguinte conjunto de labels para operações de empréstimo:

#### Scenario: Campos exibidos em empréstimo
- **WHEN** a tela mostrar uma operação de empréstimo
- **THEN** os campos devem aparecer assim:
- `Tomador do Empréstimo:` nome do cliente
- `Data Base de Cálculo:`
- `Data de Registro da Operação:`
- `Taxa de Juros Aplicada:`
- `Tipo de Operação:`
- `Possui Garantia?:`
- `Descrição da Garantia:` somente quando houver
- `Forma de Recebimento:` no lugar de `Tipo de Pagamento`, caso o valor represente como o crédito foi liquidado/liberado
- `Incorre Custo de IOF:`
- `IOF Repassado ao Cliente:` no lugar de `Cobra IOF do Cliente`
- `Valor Nominal das Parcelas:` no lugar de `Total Original (Recebíveis)`
- `Total IOF (Teórico):`
- `Valor Liberado ao Tomador:` no lugar de `Total Líquido Pago`
- `Receita/Lucro Líquido da Operação:` no lugar de `Lucro Líquido`
- `Observações:`

### Requirement: Campos ocultos em empréstimo
O sistema SHALL ocultar campos que remetam a desconto de recebíveis quando a operação for de empréstimo.

#### Scenario: Campo exclusivo de desconto
- **WHEN** a operação for de empréstimo
- **THEN** o campo `Custo da Antecipação` não deve ser exibido

## MODIFIED Requirements
### Requirement: Clareza sem alterar cálculo
O sistema SHALL manter os valores e regras de cálculo atuais, alterando apenas títulos, legendas e visibilidade dos campos nesta mudança.

#### Scenario: Ajuste apenas textual
- **WHEN** os labels forem adaptados por tipo de operação
- **THEN** os totais financeiros devem continuar usando as mesmas bases de cálculo já persistidas ou recalculadas pela tela
