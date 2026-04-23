# Refinar Operação de Empréstimo Spec

## Why
A interface e a lógica de Empréstimos precisam ser mais aderentes à realidade do negócio. Em um empréstimo, não há "Cedente" tradicional, e sim um "Tomador" que é um "Sacado" já cadastrado. Além disso, a nomenclatura de taxas e seções precisa refletir a natureza da operação (Taxa de Juros, Parcelas do Empréstimo, Parcela de Empréstimo), e o "Valor Líquido Pago" não faz sentido ser exibido na tabela de parcelas geradas. O vencimento padrão também deve ser otimizado para 30 dias após a data atual.

## What Changes
- **Banco de Dados**: Adicionar o valor `'parcela_emprestimo'` ao ENUM da coluna `tipo_recebivel` na tabela `recebiveis`.
- **Interface (`index.php`)**:
  - Quando "Empréstimo" for selecionado:
    - Alterar label de "Taxa de Desconto (% a.m.)" para "Taxa de Juros (% a.m.)".
    - Ocultar o campo "Cedente" e exibir um novo select "Tomador do Empréstimo (Sacado)", preenchido com a lista de sacados.
    - Alterar título "Títulos a Descontar" para "Parcelas do Empréstimo".
    - Ocultar a coluna "Valor Líquido Pago (R$)" da tabela de recebíveis (e o cabeçalho).
    - O campo "Data do 1º Vencimento" deve vir preenchido por padrão com a data de hoje + 30 dias.
  - Na geração de parcelas, o select "Sacado (Devedor)" de cada linha gerada deve ser automaticamente preenchido com o Sacado selecionado como Tomador, e o "Tipo Recebível" deve ser definido como `parcela_emprestimo`.
- **Backend (`registrar_operacao.php`)**:
  - Relaxar a obrigatoriedade de `cedente_id` caso a operação seja um `emprestimo`. Nesse caso, `cedente_id` será inserido como `NULL` no banco de dados.
- **Listagem (`listar_operacoes.php` e `detalhes_operacao.php`)**:
  - Atualizar a query SQL para exibir o nome do Sacado (Tomador) caso o `cedente_id` seja nulo. Na coluna de "Cedente", passaremos a exibir o "Tomador (Sacado)" com um indicativo ou usando a mesma coluna com nome genérico "Cliente".

## Impact
- Affected specs: Formulário de Simulação, Registro de Operação, Listagem de Operações.
- Affected code: `index.php`, `registrar_operacao.php`, `listar_operacoes.php`, `detalhes_operacao.php`, e um novo arquivo SQL para atualizar o ENUM.

## ADDED Requirements
### Requirement: Tomador de Empréstimo (Sacado)
O sistema DEVE permitir a escolha de um "Tomador de Empréstimo" da lista de Sacados quando o tipo de operação for Empréstimo, substituindo o campo "Cedente". As parcelas geradas devem ser vinculadas a este sacado e rotuladas como "Parcela de Empréstimo".

#### Scenario: Preenchimento automático de Vencimento
- **WHEN** a tela é carregada
- **THEN** o campo "1º Vencimento" na calculadora de empréstimo exibe a data de hoje + 30 dias.

## MODIFIED Requirements
### Requirement: Tabela de Parcelas
A tabela DEVE ocultar a coluna "Valor Líquido Pago" e alterar seu título para "Parcelas do Empréstimo" quando o modo de operação for Empréstimo.

### Requirement: Gravação da Operação
O script `registrar_operacao.php` DEVE aceitar `cedente_id` nulo ou ausente se `tipoOperacao === 'emprestimo'`, e gravar adequadamente no banco.
