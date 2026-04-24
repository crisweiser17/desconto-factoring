# Tasks
- [x] Task 1: Criar tabela `operacao_anotacoes` no banco de dados.
  - [x] SubTask 1.1: Criar script PHP `criar_tabela_anotacoes.php` que execute um `CREATE TABLE IF NOT EXISTS operacao_anotacoes (id INT AUTO_INCREMENT PRIMARY KEY, operacao_id INT, recebivel_id INT NULL, usuario_id INT, anotacao TEXT, data_criacao DATETIME, FOREIGN KEY(operacao_id) REFERENCES operacoes(id), FOREIGN KEY(recebivel_id) REFERENCES recebiveis(id), FOREIGN KEY(usuario_id) REFERENCES usuarios(id))`.
  - [x] SubTask 1.2: Rodar o script de criação da tabela (garantindo que não afete os dados existentes) e verificar.
- [x] Task 2: Desenvolver backend para salvar anotações.
  - [x] SubTask 2.1: Criar arquivo `ajax_salvar_anotacao.php` que receba via POST: `operacao_id`, `recebivel_id` (opcional), `anotacao`.
  - [x] SubTask 2.2: O script deverá inserir no banco com a `data_criacao` atual e o `usuario_id` pegando da `$_SESSION['user_id']`.
- [x] Task 3: Modificar a interface de `detalhes_operacao.php`.
  - [x] SubTask 3.1: Adicionar CSS/JS do Quill.js na página.
  - [x] SubTask 3.2: Criar um botão "Nova Anotação" (+) próximo ao cabeçalho ou em uma nova aba/seção.
  - [x] SubTask 3.3: Criar um Modal (Bootstrap) contendo um formulário com: select para "Associar a" (Geral ou listar todos os recebíveis da operação) e o editor Quill para digitar o conteúdo.
  - [x] SubTask 3.4: Implementar script JS para enviar os dados via AJAX para `ajax_salvar_anotacao.php` e recarregar a seção/página em caso de sucesso.
- [x] Task 4: Exibir as anotações existentes.
  - [x] SubTask 4.1: No PHP (`detalhes_operacao.php`), buscar as anotações do banco (`SELECT a.*, u.nome, r.numero_documento FROM operacao_anotacoes a JOIN usuarios u ON a.usuario_id = u.id LEFT JOIN recebiveis r ON a.recebivel_id = r.id WHERE a.operacao_id = ? ORDER BY a.data_criacao DESC`).
  - [x] SubTask 4.2: Renderizar as anotações em uma lista (timeline ou cards) mostrando data, hora, autor, vínculo e o texto da anotação formatado em HTML.
- [x] Task 5: Validar funcionamento.
  - [x] SubTask 5.1: Testar a criação de uma nota global e uma nota para recebível específico.
  - [x] SubTask 5.2: Garantir que não há quebras de layout e o HTML do Quill seja renderizado adequadamente com segurança.
- [x] Task 6: Implementar "Apagar Anotação".
  - [x] SubTask 6.1: Criar arquivo `excluir_anotacao.php` com validação de sessão e exclusão no banco de dados.
  - [x] SubTask 6.2: Adicionar botão de exclusão (ícone de lixeira) em cada anotação em `detalhes_operacao.php`.
  - [x] SubTask 6.3: Criar função JS `apagarAnotacao(id)` com confirmação e chamada AJAX para exclusão.

# Task Dependencies
- Task 2 depende da Task 1
- Task 3 depende da Task 2
- Task 4 depende da Task 1
- Task 5 depende das Tasks 1, 2, 3 e 4
