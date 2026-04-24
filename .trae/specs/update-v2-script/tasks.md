# Tasks
- [x] Task 1: Criar o arquivo `update_v2.php` com o scaffolding básico
  - [x] Requerer `db_connection.php`
  - [x] Configurar um bloco `try...catch` geral para tratar `PDOException`
- [x] Task 2: Implementar a migração para a tabela `operacoes`
  - [x] Verificar se a coluna `tipo_operacao` já existe antes de adicionar (ENUM `antecipacao`, `emprestimo`) e atualizar dados nulos/vazios
  - [x] Verificar se as colunas `tem_garantia` e `descricao_garantia` já existem antes de adicionar
- [x] Task 3: Implementar a migração para a tabela `recebiveis`
  - [x] Modificar o `ENUM` da coluna `tipo_recebivel` para incluir `parcela_emprestimo`

# Task Dependencies
- Task 2 e 3 dependem da Task 1.