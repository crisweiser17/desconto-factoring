# Anotações em Operações Spec

## Why
Usuários frequentemente precisam inserir observações sobre uma operação específica ou seus recebíveis, como acordos, problemas ou andamentos de cobrança. Atualmente não há um local estruturado para registrar esse histórico com data e autoria.

## What Changes
- Criação de uma nova tabela `operacao_anotacoes` no banco de dados para armazenar as notas.
- Adição de um botão "+" na tela de detalhes da operação (`detalhes_operacao.php`) para adicionar anotações.
- Modal com editor WYSIWYG (Quill.js) para compor a anotação, permitindo vincular a anotação à operação geral ou a um recebível específico.
- Listagem (timeline/cards) na mesma página para exibir o histórico de anotações com data, hora, autor e recebível associado.
- Criação de endpoint/script (`salvar_anotacao.php` ou via POST na mesma página) para processar o salvamento das notas de forma assíncrona ou tradicional.

## Impact
- Affected specs: N/A
- Affected code:
  - `detalhes_operacao.php` (UI e listagem)
  - Novo script de migração do banco de dados (ex: `migrations/create_anotacoes_table.php` ou direto via script SQL a ser executado).
  - Novo script/endpoint para salvar anotações (`ajax_salvar_anotacao.php`).

## ADDED Requirements
### Requirement: Adicionar Anotação
O sistema SHALL permitir que o usuário adicione uma anotação formatada rica (WYSIWYG) em uma operação.

#### Scenario: Sucesso ao registrar nota geral
- **WHEN** usuário clica no botão de adicionar anotação, digita no editor e escolhe "Operação Geral", depois clica em registrar
- **THEN** a anotação é salva no banco vinculada ao `operacao_id` e exibida no histórico de notas da operação.

#### Scenario: Sucesso ao registrar nota de recebível
- **WHEN** usuário seleciona um recebível específico no modal e registra a anotação
- **THEN** a anotação é vinculada também ao `recebivel_id` e destacada na listagem como referente àquele título.

## MODIFIED Requirements
### Requirement: Detalhes da Operação
A página `detalhes_operacao.php` agora exibirá uma seção de "Histórico de Anotações" listando as notas registradas ordenadas pela mais recente.
