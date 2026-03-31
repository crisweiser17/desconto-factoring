# Plano de Implementação: Melhorias no Template de E-mail e Integração Resend

## 1. Análise e Verificação das Variáveis
Analisei detalhadamente as tabelas do banco de dados (`cedentes`, `sacados`, `operacoes`, `recebiveis`) e o código atual em `notificar_sacados.php`. 
**Resultado**: **Todas** as variáveis solicitadas no seu modelo existem e estão devidamente mapeadas. Nenhuma variável ficou sobrando ou foi usada "cegamente". As substituições atuais são:
- `[CEDENTE_NOME]` -> Vem da tabela `cedentes` (nome/empresa)
- `[CEDENTE_CNPJ]` -> Vem da tabela `cedentes` (documento_principal)
- `[SACADO_NOME]` -> Vem da tabela `sacados` (nome)
- `[SACADO_CNPJ]` -> Vem da tabela `sacados` (documento_principal)
- `[BORDERO_NUMERO]` -> Vem da tabela `operacoes` (id)
- `[BORDERO_DATA]` -> Vem da tabela `operacoes` (data_operacao)
- `[BORDERO_VALOR]` -> Calculado somando o valor original dos títulos daquele sacado.
- `[TABELA_TITULOS]` -> Tabela HTML gerada dinamicamente com os recebíveis.
- `[CIDADE_DATA]` -> Data e local gerados no momento do envio.

## 2. Editor WYSIWYG em HTML e Botões de Variáveis
Para deixar o template "muito bem feito" e em HTML, faremos as seguintes mudanças:

- **Arquivo `config.php`**:
  - Integração do editor **Quill.js** (um dos melhores editores WYSIWYG modernos) via CDN, substituindo o `textarea` de texto simples.
  - Criação de um painel de botões com todas as variáveis disponíveis (`[CEDENTE_NOME]`, `[TABELA_TITULOS]`, etc).
  - Desenvolvimento de um script JavaScript que, ao clicar em um botão de variável, insere o código exatamente na posição atual do cursor dentro do editor.
  - O conteúdo será salvo no `config.json` como HTML puro.

- **Arquivo `notificar_sacados.php`**:
  - Remoção da função `nl2br(htmlspecialchars(...))` que convertia texto plano. Como o editor já salvará em HTML, o sistema apenas fará o `str_replace` das variáveis diretamente no HTML rico, garantindo que as formatações (negrito, tabelas, cores) sejam mantidas com perfeição.

## 3. Esclarecimento sobre Integração Resend (REST vs SMTP)
Você mencionou que estaria faltando a porta (465) e o usuário (resend).
- **Atenção**: A integração que construí utiliza a **API REST do Resend** (via `cURL`), e **não o protocolo SMTP**. 
- **Por que isso é melhor?** A API REST é o método recomendado pelo próprio Resend. É mais rápida, mais segura, menos suscetível a bloqueios de firewall de hospedagens e **não requer a instalação de bibliotecas externas** como o PHPMailer. Na API REST, a autenticação usa **apenas a API Key**, por isso a porta 465 e o usuário "resend" não são necessários.
- **Decisão no Plano**: Manteremos a integração via API REST por ser a melhor solução técnica. Vou atualizar os textos de ajuda na tela do `config.php` para deixar explícito que estamos usando a "API REST Oficial do Resend" e que portas e usuários não se aplicam a este método.

## Passos de Execução
1. Atualizar `config.php` para carregar o CSS/JS do Quill.js.
2. Construir a interface do editor WYSIWYG e os botões interativos das variáveis.
3. Ajustar o envio do formulário em `config.php` para capturar o conteúdo HTML do Quill.js.
4. Converter o template de texto antigo do `config.json` para HTML.
5. Modificar `notificar_sacados.php` para processar o template como HTML puro sem escapar as tags.
6. Testar o editor, a inserção de variáveis e o disparo de e-mail.