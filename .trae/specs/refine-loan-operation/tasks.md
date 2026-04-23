# Tasks
- [x] Task 1: Alterar banco de dados (ENUM)
  - [x] SubTask 1.1: Criar o script SQL `alterar_enum_recebiveis.sql` que altera a coluna `tipo_recebivel` na tabela `recebiveis` para adicionar `'parcela_emprestimo'`.
  - [x] SubTask 1.2: Atualizar os arquivos de dump `.sql` se necessário.
- [x] Task 2: Ajustes no HTML de Simulação (`index.php`)
  - [x] SubTask 2.1: Criar o dropdown "Tomador de Empréstimo (Sacado)" e esconder o "Cedente" quando em modo empréstimo.
  - [x] SubTask 2.2: Alterar a label de "Taxa de Desconto (% a.m.)" para "Taxa de Juros (% a.m.)" dinamicamente ou estaticamente via JS.
  - [x] SubTask 2.3: Adicionar a option "Parcela de Empréstimo" (`parcela_emprestimo`) no select `titulo_tipo[]`.
  - [x] SubTask 2.4: Atribuir IDs e Classes para ocultar a coluna "Valor Líquido Pago (R$)" e renomear o cabeçalho "Títulos a Descontar" para "Parcelas do Empréstimo" quando em modo empréstimo.
- [x] Task 3: Ajustes no JavaScript de Simulação (`index.php`)
  - [x] SubTask 3.1: Configurar a Data do 1º Vencimento para Hoje + 30 dias no carregamento da página.
  - [x] SubTask 3.2: Modificar `toggleModoOperacao()` para atualizar labels (Taxa e Títulos), exibir/ocultar "Cedente" e "Tomador", e mostrar/esconder a coluna "Valor Líquido Pago".
  - [x] SubTask 3.3: Na função `btnGerarParcelas`, capturar o valor do "Tomador (Sacado)" selecionado e preencher a coluna "Sacado (Devedor)" de cada linha gerada. Também definir o tipo como `parcela_emprestimo`.
- [x] Task 4: Ajustes no Backend de Registro (`registrar_operacao.php`)
  - [x] SubTask 4.1: Permitir que `cedente_id` seja vazio/nulo quando `$tipoOperacao === 'emprestimo'`. Se for nulo, inserir `NULL` na tabela `operacoes`.
- [x] Task 5: Ajustes nas Listagens (`listar_operacoes.php` e `detalhes_operacao.php`)
  - [x] SubTask 5.1: Na query SQL de `listar_operacoes.php`, usar `COALESCE(s.empresa, (SELECT sac.empresa FROM recebiveis r2 JOIN sacados sac ON r2.sacado_id = sac.id WHERE r2.operacao_id = o.id LIMIT 1))` ou um JOIN adicional para preencher `cedente_nome` com o nome do Sacado/Tomador. Alterar a label do cabeçalho da tabela para "Cliente (Cedente/Tomador)".
  - [x] SubTask 5.2: Em `detalhes_operacao.php`, mostrar "Tomador de Empréstimo" ao invés de "Cedente" se for um empréstimo, ou usar "Cliente".

# Task Dependencies
- [Task 2] depends on [Task 1]
- [Task 3] depends on [Task 2]
- [Task 4] depends on [Task 3]
- [Task 5] depends on [Task 4]
