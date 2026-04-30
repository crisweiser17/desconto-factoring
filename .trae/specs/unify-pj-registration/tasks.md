# Tasks

- [x] Task 1: Atualização do Banco de Dados
  - [x] SubTask 1.1: Criar ou adaptar a tabela unificada (ex: `clientes` ou unificar na estrutura atual) com campos para Dados da Empresa, Endereço, Sócios (Nome e CPF), Representante Legal (Nome, CPF, RG, Nacionalidade, Estado Civil, Profissão) e Dados Bancários.
  - [x] SubTask 1.2: Desenvolver script de migração (se necessário) para transferir dados de `cedentes` e `sacados` para a tabela unificada.
  - [x] SubTask 1.3: Atualizar chaves estrangeiras em tabelas dependentes (ex: `operacoes`, `recebiveis`) para apontar para a nova tabela unificada.

- [x] Task 2: Página Unificada de Cadastro
  - [x] SubTask 2.1: Criar/Atualizar a interface de usuário (ex: `clientes.php` e formulário de cadastro) para incluir todos os campos obrigatórios de PJ.
  - [x] SubTask 2.2: Implementar a lógica de backend (PHP/PDO) para inserir/atualizar registros na tabela unificada.
  - [x] SubTask 2.3: Remover opções e campos de Pessoa Física (PF) e dados de cônjuge do cadastro inicial.
  - [x] SubTask 2.4: Remover as antigas páginas de `cadastro_cedente.php` e `cadastro_sacado.php` e atualizar o menu de navegação.

- [x] Task 3: Atualização das Telas de Operação
  - [x] SubTask 3.1: Atualizar o formulário de Desconto (`nova_operacao.php`) para buscar e listar a base unificada de PJ nos selects de Cedente e Sacado.
  - [x] SubTask 3.2: Atualizar o formulário de Empréstimo (`novo_emprestimo.php`) para usar a base unificada.
  - [x] SubTask 3.3: Garantir que o processamento no backend salve corretamente os IDs unificados nas operações.

- [x] Task 4: Atualização de Contratos e Relatórios
  - [x] SubTask 4.1: Ajustar a geração de contratos para buscar os dados das partes (Cedente/Sacado/Tomador) na nova tabela unificada.
  - [x] SubTask 4.2: Ajustar visualização de detalhes da operação e relatórios para refletir a mudança estrutural.

# Task Dependencies
- Task 2 depende da Task 1.
- Task 3 depende da Task 1 e 2.
- Task 4 depende da Task 1.
