# Dynamic Database Connection Spec

## Por que
Atualmente, o arquivo de conexão com o banco de dados (`db_connection.php`) possui credenciais fixas. Isso causa um transtorno na hora de fazer o deploy ou sincronizar os arquivos, pois o desenvolvedor precisa lembrar de não enviar o arquivo local para produção ou ficar alterando as credenciais manualmente, o que gera o risco de "Erro 500" no servidor online se as credenciais erradas forem enviadas.

## O que muda
- O arquivo `db_connection.php` será refatorado para detectar automaticamente o ambiente em que está rodando.
- Ele verificará a variável de servidor (como `$_SERVER['HTTP_HOST']` ou `$_SERVER['SERVER_NAME']`) para descobrir se o código está sendo executado em um ambiente local (ex: `localhost`, `127.0.0.1` ou `::1`).
- Dependendo do ambiente detectado, o PHP fará a atribuição automática das variáveis de conexão locais ou de produção.

## Impacto
- Affected specs: Não há dependência de outros arquivos.
- Affected code: `db_connection.php`.

## ADDED Requirements
### Requirement: Detecção de Ambiente e Switch de Credenciais
O sistema DEVE fornecer um arquivo de conexão que configure automaticamente as credenciais.

#### Scenario: Execução em ambiente Local
- **QUANDO** o sistema é executado a partir do `localhost` ou `127.0.0.1`
- **THEN** o PHP usará o bloco de credenciais de desenvolvimento (ex: root).

#### Scenario: Execução em ambiente de Produção (Online)
- **QUANDO** o sistema é executado a partir de um domínio público
- **THEN** o PHP usará o bloco de credenciais de produção (as credenciais online atuais).
