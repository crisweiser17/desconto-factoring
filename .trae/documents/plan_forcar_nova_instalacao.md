# Plano: Opção para Forçar Nova Instalação no installer.php

## Contexto
O usuário ganhou um servidor de Staging que já possui o arquivo `db_connection.php` gerado por uma instalação anterior, mas as credenciais estão incorretas. Atualmente, o `installer.php` bloqueia totalmente a reinstalação quando detecta `db_connection.php`, exibindo apenas uma tela de "Instalação Concluída" sem opção de refazer.

## Objetivo
Adicionar uma opção segura no `installer.php` para forçar uma nova instalação, permitindo sobrescrever o `db_connection.php` com credenciais corretas.

## Passos de Implementação

### 1. Adicionar Parâmetro de Forçar Reinstalação via Query String
- No bloco de verificação inicial (`if (file_exists(__DIR__ . '/db_connection.php'))`), verificar também se existe um parâmetro `?force=1` na URL.
- Se `force=1` estiver presente, **não fazer `exit`** e continuar para o fluxo normal de instalação.
- Se `db_connection.php` existir **e** `force` não estiver presente, manter a tela atual de "já instalado", mas adicionar um botão/link para "Reinstalar / Corrigir Configuração" que redirecione para `installer.php?force=1`.

### 2. Adicionar Confirmação de Segurança na Tela de Reinstalação
- Quando `force=1` estiver ativo, exibir um alerta visual (ex: `alert alert-danger`) avisando que a reinstalação irá sobrescrever o arquivo de configuração e que dados do banco podem ser afetados se o dump for executado novamente.
- Exigir confirmação explícita, por exemplo, um checkbox "Entendo que isso sobrescreverá a configuração atual" antes de permitir o submit do formulário.

### 3. Ajustar o Processamento POST para Sobrescrever `db_connection.php`
- No bloco `if ($_SERVER['REQUEST_METHOD'] === 'POST')`, garantir que o arquivo `db_connection.php` seja sempre recriado (com `file_put_contents`), mesmo que já exista.
- Como o `file_put_contents` já sobrescreve por padrão, nenhuma mudança complexa é necessária aqui — apenas garantir que o fluxo POST seja acessível quando `force=1` estiver presente.

### 4. Adicionar Botão na Tela "Já Instalado"
- Na tela de "Sistema já instalado" (quando `db_connection.php` existe e não há `force`), adicionar abaixo dos botões existentes um novo botão estilizado em vermelho/outline-danger:
  - Texto: "Reinstalar / Corrigir Configuração"
  - Link: `installer.php?force=1`
  - Ícone: `bi bi-arrow-repeat` ou similar

### 5. Testar Localmente
- Simular a existência de `db_connection.php`.
- Acessar `installer.php` e verificar se a tela "Já instalado" aparece com o novo botão.
- Clicar em "Reinstalar" e verificar se o formulário de instalação é exibido com o alerta de segurança.
- Submeter o formulário e confirmar que `db_connection.php` é sobrescrito com os novos dados.

## Resumo das Alterações no Arquivo
Arquivo único a ser alterado: `installer.php`
- Linhas 5–45: Modificar a verificação de `db_connection.php` para respeitar `$_GET['force']`.
- Linhas 28–34: Adicionar botão de reinstalação na tela "já instalado".
- Linhas 181–211: Quando em modo force, exibir alerta de segurança e checkbox de confirmação antes do formulário.
