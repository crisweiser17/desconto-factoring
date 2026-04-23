# Plano de Implementação

## Resumo
O objetivo é melhorar a responsividade do sistema (focando no uso mobile e uso eficiente do espaço de tela) e aprimorar a funcionalidade de notificação de sacados, adicionando um modal de pré-visualização (preview) do e-mail e permitindo configurar endereços em Cópia (CC) e Cópia Oculta (BCC).

## Análise do Estado Atual
- As telas utilizam a classe `.container` do Bootstrap, que possui larguras fixas dependendo da resolução, o que limita o espaço em tabelas largas e pode causar barra de rolagem horizontal desnecessária em desktops maiores, além de ficar apertado em mobile.
- O botão "Notificar Sacados" em `detalhes_operacao.php` dispara o e-mail imediatamente ao ser clicado e confirmado via `confirm()` do JS.
- O sistema de envio de e-mails (`funcoes_email.php` e `notificar_sacados.php`) não suporta CC ou BCC atualmente.

## Mudanças Propostas

### 1. Melhorias de Responsividade nas Listagens (`listar_operacoes.php` e `listar_recebiveis.php`)
- **O que fazer:**
  - Substituir a classe `.container` principal por `.container-fluid px-3 px-md-4` para utilizar toda a largura da tela.
  - Na tabela, manter o `.table-responsive` mas adicionar CSS customizado para a coluna "Ações" (`th:last-child` e `td:last-child`), aplicando `position: sticky; right: 0; z-index: 1;`. Isso fixará a coluna de ações à direita, permitindo que o usuário role as colunas de dados horizontalmente em telas menores sem perder os botões de ação de vista.
  - Ajustar a cor de fundo das células fixadas para combinar com as linhas listradas (`.table-striped`), evitando sobreposição visual confusa.

### 2. Layout e Responsividade em `detalhes_operacao.php`
- **O que fazer:**
  - Substituir `.container` por `.container-fluid px-3 px-md-4`.
  - No contêiner dos botões do topo (Editar, Gerar Análise, Gerar Recibo, Notificar Sacados), adicionar as classes `.flex-wrap` e `.gap-2` para garantir que quebrem de linha graciosamente em telas pequenas sem grudar um no outro. 
  - Ajustar os botões para usarem tamanhos menores ou responsivos se necessário.
  - Garantir que tabelas secundárias (como a lista de arquivos) usem `.table-responsive`.

### 3. Modal de Pré-visualização de Notificação de Sacados
- **O que fazer:**
  - **Novo arquivo:** Criar `preview_notificacao_sacados.php`. Este script fará a mesma substituição de variáveis que `notificar_sacados.php`, mas ao invés de enviar, retornará um JSON com o HTML do e-mail gerado para cada sacado.
  - **Atualizar `detalhes_operacao.php`:** Adicionar a estrutura HTML de um modal do Bootstrap (`#previewNotificacaoModal`).
  - Atualizar o JavaScript: Ao clicar em "Notificar Sacados", fazer um `fetch` para o novo script de preview. Exibir o HTML retornado dentro do modal (se houver mais de um sacado, criar uma seleção ou exibir o primeiro como exemplo representativo). 
  - O modal terá um botão "Confirmar e Enviar". Ao clicar, chama-se o endpoint real `notificar_sacados.php`.

### 4. Suporte a CC e BCC
- **O que fazer:**
  - **Atualizar `config.php`:** Adicionar campos de input para "E-mail(s) em Cópia (CC)" e "E-mail(s) em Cópia Oculta (BCC)". Os campos poderão aceitar múltiplos e-mails separados por vírgula. Salvar essas chaves em `config.json`.
  - **Atualizar `funcoes_email.php`:** Alterar a assinatura da função `enviar_email_resend` para aceitar os parâmetros `$cc` e `$bcc` e incluí-los no payload da API do Resend, formatando adequadamente (como arrays, caso haja separação por vírgula).
  - **Atualizar `notificar_sacados.php`:** Ler as chaves `resend_cc_email` e `resend_bcc_email` do `config.json` e passá-las na chamada de `enviar_email_resend`.

### 5. Melhoria Global Mobile (`index.php` e outros)
- **O que fazer:**
  - Alterar `.container` para `.container-fluid px-3 px-md-4` na tela principal (`index.php`) e nas telas de formulário como `form_cedente.php` ou `form_operacao.php` (se existirem e precisarem).

## Suposições e Decisões
- Supomos que o servidor de hospedagem tenha o arquivo `config.json` com permissões de escrita, o que já está ocorrendo.
- Para a pré-visualização de e-mails, se houver vários sacados, mostraremos um seletor (dropdown ou abas) dentro do modal para visualizar o e-mail de cada sacado, permitindo que o usuário confira tudo.
- Fixar a coluna de "Ações" à direita é a melhor solução UX para tabelas com muitos dados (como a de operações) em telas pequenas, pois evita rolagem dupla.

## Passos de Verificação
1. Abrir `/listar_operacoes.php` em tela pequena/mobile e verificar se o contêiner ocupa 100% da tela e se a coluna de Ações fica fixada na rolagem.
2. Ir em Configurações, adicionar um e-mail de CC/BCC.
3. Entrar em uma operação, clicar em "Notificar Sacados". Um modal deve abrir mostrando o HTML gerado e formatado do e-mail.
4. Clicar em "Confirmar" no modal e verificar se o e-mail foi disparado e recebido com as cópias corretas (CC/BCC).
5. Checar a responsividade dos botões no topo da visualização da operação.