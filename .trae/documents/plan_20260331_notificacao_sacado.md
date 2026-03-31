# Plano de Implementação: Dados Bancários do Cedente e Notificação de Sacado via Resend

## Resumo
Este plano descreve as alterações necessárias para adicionar campos de dados bancários ao cadastro de Cedentes e implementar um sistema de notificação por e-mail para os Sacados, utilizando a API REST do Resend. As configurações de e-mail e o template editável ficarão centralizados na página de configurações do sistema.

## Análise do Estado Atual
- O cadastro de Cedentes (`cedentes`) não possui campos para dados bancários (banco, agência, conta, PIX).
- As configurações (`config.json`, `config.php`) armazenam apenas taxas, sem dados de e-mail.
- O sistema atual de e-mail (`envia_email.php`) está configurado para usar a função `mail()` nativa ou requer PHPMailer. O usuário confirmou a preferência por usar a **API REST do Resend via cURL**, eliminando a necessidade de bibliotecas externas.
- As operações são registradas via AJAX no `index.php` -> `registrar_operacao.php`.

## Mudanças Propostas

### 1. Banco de Dados (Dados Bancários do Cedente)
- **Arquivo**: `adicionar_dados_bancarios_cedente.sql` (Novo)
- **O que**: Criar script SQL para adicionar as colunas `banco`, `agencia`, `conta`, `tipo_conta`, `chave_pix` na tabela `cedentes`.
- **Por que**: Para armazenar as informações necessárias para repasse de pagamentos ao Cedente.

### 2. Interface e Lógica do Cedente
- **Arquivos**: `form_cedente.php`, `salvar_cedente.php`, `visualizar_cedente.php`
- **O que**: 
  - Adicionar os campos de dados bancários no formulário HTML.
  - Atualizar as queries `INSERT` e `UPDATE` no `salvar_cedente.php` para contemplar as novas colunas.
  - Exibir os dados bancários na tela de visualização do cedente.

### 3. Configurações de E-mail (Resend) e Template
- **Arquivos**: `config.php`, `config.json`
- **O que**:
  - Adicionar novos campos no formulário do `config.php`:
    - **Resend API Key**: Chave de API para autenticação.
    - **E-mail do Remetente**: E-mail autorizado no Resend (ex: `notificacoes@seudominio.com`).
    - **Template de E-mail**: Um `textarea` contendo o texto padrão sugerido (em Markdown ou HTML simples), permitindo ao usuário editar os dados bancários do Cessionário e o texto.
  - Criar um botão "Testar Disparo de E-mail" que acionará um script de teste para validar a API Key.

### 4. Motor de Disparo de E-mail
- **Arquivo**: `funcoes_email.php` (Novo)
- **O que**: Criar uma função reutilizável `enviar_email_resend($para, $assunto, $corpo)` que executa um `cURL` POST para `https://api.resend.com/emails`.

### 5. Lógica de Notificação de Sacados
- **Arquivo**: `notificar_sacados.php` (Novo)
- **O que**: Script que recebe o ID de uma operação e:
  1. Busca todos os `recebiveis` agrupados por `sacado_id`.
  2. Substitui as variáveis no template (ex: `[CEDENTE_NOME]`, `[TABELA_TITULOS]`, `[BORDERO_NUMERO]`, etc).
  3. Dispara o e-mail para cada Sacado que possua endereço de e-mail cadastrado.
  4. Retorna um JSON com o status do envio (sucessos e falhas).

### 6. Integração na Operação (index.php e detalhes_operacao.php)
- **Arquivo**: `index.php`
  - **O que**: Adicionar um checkbox `[ ] Notificar Sacado(s) por e-mail` próximo ao botão de Registrar Operação. Se marcado, o `registrar_operacao.php` chamará internamente a função de notificação após salvar a operação com sucesso.
- **Arquivo**: `detalhes_operacao.php`
  - **O que**: Adicionar um botão "Notificar Sacados" no cabeçalho da operação para permitir o reenvio ou envio manual posterior.

## Premissas e Decisões
- **API do Resend**: Utilizaremos a API REST (via cURL) conforme escolha do usuário, o que é mais estável e não requer instalação do PHPMailer.
- **Template Flexível**: Os dados bancários do **Cessionário** (Factoring) serão inseridos diretamente no texto do Template pelo usuário na tela de Configurações, pois são fixos para a empresa. Os dados do **Cedente** (inseridos no banco) não são enviados para o Sacado, servindo apenas para controle interno da Factoring.
- **Variáveis do Template**: O sistema substituirá marcações fixas (ex: `[TABELA_TITULOS]`) pelo conteúdo dinâmico HTML gerado a partir dos títulos associados a cada Sacado específico.
- **Multi-Sacado**: Se uma operação contiver múltiplos Sacados, cada um receberá um e-mail individual contendo **apenas** os seus respectivos títulos.

## Passos de Verificação
1. Executar o script SQL para atualizar a tabela `cedentes`.
2. Cadastrar e editar um Cedente garantindo que os dados bancários sejam salvos corretamente.
3. Preencher a API Key e o Template no `config.php` e usar o botão "Testar Disparo" para validar a integração com o Resend.
4. Registrar uma nova operação com o checkbox de notificação marcado e verificar se o Sacado recebe o e-mail corretamente com a tabela de títulos.
5. Acessar os detalhes de uma operação existente e testar o botão manual de "Notificar Sacados".