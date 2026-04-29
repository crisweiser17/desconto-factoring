# Refatoração do Instalador e Criação do Update Spec

## Por quê
O usuário precisa de um processo claro e seguro para realizar o deploy do sistema em diferentes cenários:
1. **Nova Instalação (Deploy do Zero)**: Onde o banco de dados e as configurações precisam ser criados do zero.
2. **Atualização (Deploy em Produção)**: Onde o banco de dados já existe e possui dados valiosos, e as tabelas/colunas precisam ser atualizadas sem sobrescrever ou deletar os registros existentes.

Atualmente, não existe um script centralizado de "update" e o instalador não avisa se o sistema já está instalado.

## O que muda
- **installer.php**:
  - Adição de uma verificação inicial: se o arquivo `db_connection.php` já existir, o instalador bloqueará a criação de um novo banco de dados para evitar perda de dados.
  - Ao detectar uma instalação existente, a tela exibirá um aviso e um botão "Ir para Atualização do Sistema" que direcionará o usuário para `update.php`.
  - O fluxo de nova instalação (onde `db_connection.php` não existe) continuará funcionando normalmente.
- **update.php** (NOVO ARQUIVO):
  - Um script que concentra todas as migrações (alterações de colunas, novas tabelas).
  - Executará os comandos de forma segura (`CREATE TABLE IF NOT EXISTS`, `ALTER TABLE ADD COLUMN`), ignorando erros caso as tabelas ou colunas já existam (Error 1060 "Duplicate column name" e Error 1050 "Table already exists").
  - Garante que nenhuma estrutura atual seja deletada (`DROP`) e nenhum dado seja perdido.

## Banco de Dados Utilizado
O sistema utiliza **MySQL** (ou MariaDB compatível). As strings de conexão (DSN) no código utilizam o driver `mysql:host=...`.

## Impacto
- Afeta o fluxo de instalação (`installer.php`).
- Consolida scripts esparsos de setup (como `setup_contratos_full.php`, `update_sacados_columns.php`) em uma única ferramenta padrão (`update.php`).

## REQUISITOS ADICIONADOS
### Requisito: Proteção de Sobrescrita na Instalação
- **QUANDO** o usuário acessar `installer.php` em um servidor onde o sistema já foi instalado
- **ENTÃO** o sistema deve detectar o `db_connection.php` e exibir uma tela com a mensagem de que o sistema já está instalado, oferecendo um botão para "Atualizar Banco de Dados".

### Requisito: Execução Segura de Atualizações
- **QUANDO** o usuário acessar `update.php`
- **ENTÃO** o sistema deve executar as migrações estruturais necessárias no MySQL, capturando e ignorando erros de "coluna já existe" ou "tabela já existe", exibindo um relatório final das alterações aplicadas ou já existentes.