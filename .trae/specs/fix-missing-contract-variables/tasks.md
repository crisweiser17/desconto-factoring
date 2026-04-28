# Tasks
- [x] Task 1: Atualizar Banco de Dados
  - [x] SubTask 1.1: Criar o script PHP `update_sacados_columns.php` para executar comandos SQL que adicionem as colunas `porte` (ENUM), `possui_cnpj_mei` (TINYINT) e todos os `representante_*` (nome, cpf, rg, estado_civil, profissao, nacionalidade, endereco) à tabela `sacados`.
  - [x] SubTask 1.2: Rodar o script `update_sacados_columns.php` para efetivar as alterações no banco de dados local.

- [x] Task 2: Atualizar Cadastro de Sacado
  - [x] SubTask 2.1: Editar `form_sacado.php` para incluir os campos HTML equivalentes a Porte, Possui CNPJ MEI e Dados do Representante (nome, CPF, RG, estado civil, profissão, nacionalidade e endereço).
  - [x] SubTask 2.2: Atualizar `salvar_sacado.php` para capturar os novos inputs do `POST` e incluí-los nas queries de `INSERT` e `UPDATE` da tabela `sacados`.

- [x] Task 3: Corrigir Backend de Geração de Contratos
  - [x] SubTask 3.1: Em `api_contratos.php`, ajustar a lógica para verificar se `tipo_operacao` é `'emprestimo'`. Caso positivo, fazer a query usando a tabela `sacados` (via `recebiveis`) para extrair os dados de `cedente_nome` e demais.
  - [x] SubTask 3.2: Corrigir a lógica de verificação `pessoa_juridica` para checar `in_array($operacao['tipo_pessoa'], ['PJ', 'JURIDICA'])`.
  - [x] SubTask 3.3: Implementar fallback do `cnpj` e `cpf` do Devedor/Cedente para usar a coluna `documento_principal` caso `cnpj` ou `cpf` retornem vazios.