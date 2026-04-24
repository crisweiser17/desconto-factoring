# Tasks

- [x] Task 1: Modificar `db_connection.php` para suporte multi-ambiente.
  - [x] SubTask 1.1: Adicionar um array ou condição verificando as origens de um ambiente de teste local: `localhost`, `127.0.0.1`, e `::1`.
  - [x] SubTask 1.2: Definir um bloco "IF" (se estiver local, use usuário `root`, etc.).
  - [x] SubTask 1.3: Definir um bloco "ELSE" (ambiente de produção/online) configurado com as credenciais on-line corretas (`msmhbbtppg` / `GeM6cee7vh`).
  - [x] SubTask 1.4: Manter o final do script que inicializa o `$pdo` da mesma forma que está hoje.
