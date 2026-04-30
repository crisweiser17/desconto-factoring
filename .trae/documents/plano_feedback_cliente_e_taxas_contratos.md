# Plano: Feedback no Salvamento de Cliente e Taxas de Mora Configuráveis

## Resumo
Implementar dois ajustes coordenados:

1. No cadastro/edição de cliente, exibir mensagem de sucesso ou falha no próprio `form_cliente.php`; em caso de sucesso, recarregar a mesma página com os dados persistidos, sem redirecionar para `listar_clientes.php`.
2. Nos contratos ativos gerados por `api_contratos.php`, substituir os textos hardcoded de multa moratória e juros de mora pelas variáveis já expostas a partir de `config.json`.

## Current State Analysis

### Salvamento de cliente
- `form_cliente.php` renderiza o formulário e, no modo de edição, carrega o cliente por `id`.
- O `<form>` de `form_cliente.php` envia para `salvar_cliente.php`.
- `salvar_cliente.php` valida os dados, grava em `clientes` e `clientes_socios`, e hoje sempre redireciona para `listar_clientes.php?status=...&msg=...`.
- Em caso de erro de validação ou banco, o usuário sai da tela de edição e perde o contexto.
- `form_cliente.php` ainda não possui um bloco de alerta para consumir `status`/`msg`, nem mecanismo para reaproveitar dados enviados após falha.

### Taxas de mora em contratos
- `config.php` já mantém `taxa_juros_atraso` e `taxa_multa_atraso` em `config.json`.
- `api_contratos.php` já injeta no payload Mustache:
  - `operacao.taxa_juros_atraso`
  - `operacao.taxa_juros_atraso_extenso`
  - `operacao.taxa_multa_atraso`
  - `operacao.taxa_multa_atraso_extenso`
- Apesar disso, os templates ativos ainda trazem valores literais `2%` e `1%` nas cláusulas de mora.
- Arquivos ativos afetados, identificados pelo fluxo atual de `api_contratos.php`:
  - `_contratos/01_template_antecipacao_recebiveis.md`
  - `_contratos/02a_template_mutuo_simples.md`
  - `_contratos/02b_template_mutuo_com_aval.md`
  - `_contratos/02c_template_mutuo_com_garantia.md`
  - `_contratos/02d_template_mutuo_com_garantia_e_aval.md`
  - `_contratos/02e_template_mutuo_com_garantia_bem.md`
  - `_contratos/02f_template_mutuo_com_garantia_bem_e_aval.md`
- Arquivos em `_contratos/antigos/` foram encontrados, mas não são usados pelo fluxo atual e ficam fora do escopo.

## Proposed Changes

### 1. Ajustar o fluxo de retorno em `salvar_cliente.php`
- Manter a lógica atual de validação e persistência.
- Alterar os redirects finais:
  - **Sucesso em edição**: redirecionar para `form_cliente.php?id=<id>&status=success&msg=...`
  - **Sucesso em criação**: redirecionar para `form_cliente.php?id=<novo_id>&status=success&msg=...`
  - **Falha em edição**: redirecionar para `form_cliente.php?id=<id>&status=error&msg=...` preservando os dados enviados
  - **Falha em criação**: redirecionar para `form_cliente.php?status=error&msg=...` preservando os dados enviados
- Para preservar os dados na falha, serializar o payload relevante do formulário em sessão antes do redirect de erro, e limpá-lo após o reaproveitamento no formulário.
- Não alterar o destino de páginas não relacionadas, como `visualizar_cliente.php` ou `listar_clientes.php`.

### 2. Exibir alertas e reaproveitar payload em `form_cliente.php`
- Adicionar leitura de `status` e `msg` via query string para mostrar alerta Bootstrap no topo do formulário.
- Priorizar um payload temporário salvo em sessão quando houver falha no salvamento, para reidratar:
  - dados do cliente
  - sócios
  - campos de representante
  - endereço
  - dados bancários
- Garantir que esse reaproveitamento só aconteça quando:
  - o contexto for compatível com o formulário aberto
  - a mensagem for de erro
- Em caso de sucesso, carregar novamente do banco como já acontece hoje, de modo que o usuário veja os dados efetivamente persistidos.

### 3. Remover hardcode das taxas nos templates ativos de contrato
- Substituir nos templates ativos:
  - `2% (dois por cento)` por `{{operacao.taxa_multa_atraso}}% ({{operacao.taxa_multa_atraso_extenso}} por cento)`
  - `1% (um por cento)` por `{{operacao.taxa_juros_atraso}}% ({{operacao.taxa_juros_atraso_extenso}} por cento)`
- Aplicar apenas aos templates ativos listados acima, mantendo o restante do texto jurídico igual.
- Não alterar `api_contratos.php` nessa parte, a menos que a implementação revele divergência no nome das chaves; hoje o backend já fornece as variáveis necessárias.

### 4. Verificação
- Validar sintaxe com `php -l` em:
  - `form_cliente.php`
  - `salvar_cliente.php`
  - `api_contratos.php` se houver qualquer ajuste incidental
- Validar visualmente no navegador:
  - editar cliente existente, salvar com sucesso e confirmar permanência em `form_cliente.php?id=<id>` com alerta de sucesso
  - provocar falha de validação e confirmar alerta de erro com campos preservados
- Validar geração de contrato no preview local para confirmar que as cláusulas de multa e juros mostram os valores vindos da configuração atual.

## Assumptions & Decisions
- Escopo principal do feedback é o fluxo de `form_cliente.php` -> `salvar_cliente.php`.
- Em falha de salvamento, os dados digitados devem ser preservados no formulário.
- Em sucesso, o formulário deve recarregar com dados persistidos do banco, e não com uma cópia do POST.
- Apenas templates ativos usados por `api_contratos.php` entram nesta mudança; arquivos em `_contratos/antigos/` ficam fora de escopo.
- As variáveis `operacao.taxa_juros_atraso`, `operacao.taxa_juros_atraso_extenso`, `operacao.taxa_multa_atraso` e `operacao.taxa_multa_atraso_extenso` já são a interface oficial para os templates.

## Verification Steps
1. Abrir `form_cliente.php?id=3`, editar um campo e salvar com sucesso.
2. Confirmar URL permanece em `form_cliente.php?id=3` e aparece alerta de sucesso.
3. Reabrir o mesmo cliente e confirmar que o valor persistiu.
4. Forçar uma falha de validação no formulário e confirmar:
   - alerta de erro no próprio formulário
   - campos reaproveitados com o que foi digitado
5. Gerar um contrato de antecipação e um de mútuo no preview local.
6. Confirmar nas cláusulas de mora que multa e juros exibem os valores configurados em `config.php` / `config.json`, sem `2%` e `1%` hardcoded nos templates ativos.
