# Apagar Anotação Spec

## Why
Atualmente, se um usuário registrar uma anotação incorreta ou que não é mais relevante, não há como removê-la. Permitir a exclusão de anotações garante um histórico mais limpo e organizado.

## What Changes
- Criação de um endpoint `excluir_anotacao.php` para realizar a remoção no banco de dados.
- Adição de um botão/ícone de lixeira (apagar) ao lado de cada anotação na interface da `detalhes_operacao.php`.
- Função JavaScript para enviar a requisição via AJAX solicitando a exclusão (com confirmação prévia) e recarregar a página.

## Impact
- Affected specs: operation-notes
- Affected code: 
  - `detalhes_operacao.php` (UI)
  - `excluir_anotacao.php` (Novo script de exclusão)

## ADDED Requirements
### Requirement: Excluir Anotação
O sistema SHALL permitir que o usuário apague uma anotação existente.

#### Scenario: Sucesso ao apagar anotação
- **WHEN** o usuário clica no botão "Apagar" de uma anotação e confirma na caixa de diálogo
- **THEN** a anotação é permanentemente deletada do banco de dados e removida da interface do usuário.