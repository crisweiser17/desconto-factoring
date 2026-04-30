# Módulos do Sistema de Factoring

Este documento descreve os principais módulos identificados no sistema, com um resumo de suas funcionalidades e os principais arquivos associados.

## 1. Cadastros (Clientes e Usuários)
Módulo responsável pela gestão de entidades do sistema:
- **Clientes (Cedentes/Sacados):** Permite a inclusão, edição, listagem e exclusão de clientes que interagem com as operações de factoring.
- **Usuários:** Controle de acesso e perfis de usuários do sistema.

**Principais Arquivos:**
- `listar_clientes.php`, `form_cliente.php`, `salvar_cliente.php`, `excluir_cliente.php`
- `listar_usuarios.php`, `form_usuario.php`, `salvar_usuario.php`, `excluir_usuario.php`

## 2. Operações
Módulo central para o registro e acompanhamento de operações financeiras (empréstimos, factoring, antecipação de recebíveis).
- Permite registrar novas operações, visualizar detalhes, editar informações, adicionar anotações e garantias.
- Realiza cálculos financeiros (juros, IOF, taxas) associados a cada operação.

**Principais Arquivos:**
- `registrar_operacao.php`, `listar_operacoes.php`, `detalhes_operacao.php`, `editar_operacao.php`, `excluir_operacao.php`
- `calculate.php`, `funcoes_calculo_central.php`

## 3. Recebíveis
Gerencia os títulos, cheques, notas promissórias e notas fiscais vinculados às operações.
- Listagem e controle do status dos recebíveis (pendente, pago, em atraso).
- Permite a notificação de sacados sobre os recebíveis.

**Principais Arquivos:**
- `listar_recebiveis.php`, `buscar_recebiveis_indiretos.php`
- `notificar_sacados.php`, `preview_notificacao_sacados.php`

## 4. Financeiro e Dashboard
Oferece visão macro das finanças e métricas do sistema.
- Dashboard com indicadores financeiros.
- Fechamento de caixa, controle de despesas e distribuição de lucros.

**Principais Arquivos:**
- `dashboard_financeiro.php`, `api_dashboard_financeiro.php`
- `fechamento.php`, `api_fechamento.php`
- `api_despesas.php`, `api_distribuicao_lucros.php`

## 5. Contratos e Documentos
Responsável pela geração e armazenamento de contratos das operações.
- Templates de contratos (Mútuo, Antecipação, Cessão).
- API para gerar, visualizar e exportar em PDF.
- Upload e gerenciamento de arquivos anexados às operações.

**Principais Arquivos:**
- `api_contratos.php`, `export_pdf.php`, `export_pdf_cliente.php`
- `upload_arquivos.php`, `listar_arquivos.php`, `download_arquivo.php`
- Diretórios: `_contratos/`, `uploads/`

## 6. Configurações e Utilitários
Rotinas de backup, envio de e-mails, migrações de banco de dados e atualizações do sistema.

**Principais Arquivos:**
- `config.php`, `functions.php`
- `envia_email.php`, `envia_alertas.php`
- `migrate_db.php`, `update.php`
