# Tarefas

- [x] Tarefa 1: Atualizar `installer.php`
  - [x] SubTarefa 1.1: Adicionar checagem no início do arquivo verificando se `db_connection.php` já existe.
  - [x] SubTarefa 1.2: Se existir, exibir uma mensagem "Sistema já instalado" e um botão que redirecione para `update.php`, bloqueando a submissão do formulário de nova instalação.
  - [x] SubTarefa 1.3: Se não existir, o fluxo normal (pedindo credenciais de banco e criando admin) continuará, sendo seguro para novos deploys.

- [x] Tarefa 2: Criar o script `update.php`
  - [x] SubTarefa 2.1: Incluir `db_connection.php` (com fallback caso não encontre, redirecionando para `installer.php`).
  - [x] SubTarefa 2.2: Adicionar uma UI básica informando que a atualização está sendo executada no banco de dados da produção e mostrar um botão de iniciar atualização.
  - [x] SubTarefa 2.3: Consolidar todas as criações de tabelas mais recentes em `update.php` (`contract_templates`, `generated_contracts`, `master_cession_contracts`, `operation_vehicles`, `operation_guarantors`, `despesas`, `distribuicao_lucros`, `configuracoes_bancarias`, etc). O uso será estrito com `CREATE TABLE IF NOT EXISTS`.
  - [x] SubTarefa 2.4: Consolidar as adições de colunas (`ALTER TABLE ... ADD COLUMN`) das atualizações passadas (`sacados`, `cedentes`, `operacoes`, `recebiveis`, etc) utilizando um array de comandos.
  - [x] SubTarefa 2.5: Implementar lógica try/catch em um loop que ignore erros `1060` (coluna existente), `1050` (tabela existente) ou mensagens contendo `Duplicate column name`. Reportar na tela o resultado de cada comando (sucesso vs ignorado/já existe).

- [x] Tarefa 3: Documentar resposta para o usuário
  - [x] SubTarefa 3.1: Escrever uma resposta direta à pergunta do usuário esclarecendo que o banco de dados utilizado é o **MySQL** (ou MariaDB, via PDO).