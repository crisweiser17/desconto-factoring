# Update V2 Script Spec

## Why
Ao longo do desenvolvimento da funcionalidade de "Empréstimos", foram criados vários scripts SQL independentes para modificar a estrutura do banco de dados (adicionar `tipo_operacao`, `tem_garantia`, `descricao_garantia` na tabela `operacoes` e adicionar `parcela_emprestimo` ao enum `tipo_recebivel` na tabela `recebiveis`). O usuário precisa de um único arquivo PHP (`update_v2.php`) que rode automaticamente todas essas migrações, facilitando a atualização de outros ambientes (como produção) sem a necessidade de rodar comandos SQL manualmente.

## What Changes
- Criação do arquivo `update_v2.php`.
- O script fará a conexão com o banco de dados usando `db_connection.php`.
- O script verificará se as colunas necessárias já existem antes de tentar criá-las, evitando erros de duplicidade (ex: `Duplicate column name`).
- Executará a adição de `tipo_operacao` (ENUM) na tabela `operacoes`.
- Executará a adição de `tem_garantia` (TINYINT) e `descricao_garantia` (TEXT) na tabela `operacoes`.
- Executará a modificação do campo `tipo_recebivel` (ENUM) na tabela `recebiveis` para incluir a opção `parcela_emprestimo`.

## Impact
- Affected specs: Implantação e Atualização do Banco de Dados.
- Affected code: Novo arquivo `update_v2.php`.

## ADDED Requirements
### Requirement: Script de Migração V2
O sistema SHALL prover um script PHP acessível via navegador ou CLI que aplica as alterações de esquema do banco de dados necessárias para a funcionalidade de Empréstimos, de forma idempotente (pode ser rodado várias vezes sem causar erros).

#### Scenario: Success case
- **WHEN** user acessa `update_v2.php`
- **THEN** o script aplica as alterações SQL pendentes e exibe mensagens de sucesso confirmando que o banco foi atualizado.

## MODIFIED Requirements
N/A

## REMOVED Requirements
N/A