# Tasks
- [x] Task 1: Atualizar Lógica de Configurações no Backend (`config.php`)
  - [x] SubTask 1.1: Na função `readConfig`, adicionar valores padrão (fallback) para `conta_banco`, `conta_agencia`, `conta_numero`, `conta_pix`, `conta_tipo`, `conta_titular`, e `conta_documento`.
  - [x] SubTask 1.2: No bloco `if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_config')`, capturar e validar esses novos campos oriundos de `$_POST`.
  - [x] SubTask 1.3: Adicionar os campos validados ao array `$config` antes de chamar `file_put_contents`.

- [x] Task 2: Atualizar a Interface de Configurações (`config.php`)
  - [x] SubTask 2.1: Criar um novo bloco/seção no HTML de `config.php` intitulado "Dados Bancários de Recebimento" (para os sacados pagarem).
  - [x] SubTask 2.2: Adicionar os *inputs* HTML (`conta_titular`, `conta_documento`, `conta_banco`, `conta_agencia`, `conta_numero`, `conta_tipo`, `conta_pix`) associados à variável `$currentConfig`.

- [x] Task 3: Injetar os Dados no Gerador de Contratos (`api_contratos.php`)
  - [x] SubTask 3.1: Carregar o arquivo `config.json` no arquivo `api_contratos.php`.
  - [x] SubTask 3.2: Substituir os dados fixos ("Banco do Brasil", "0001", etc.) dentro de `$data['credor']['conta']` pelos dados lidos de `$config`. Incluir também `tipo`, `titular` e `documento`.

# Task Dependencies
- [Task 2] depends on [Task 1]
- [Task 3] depends on [Task 1]