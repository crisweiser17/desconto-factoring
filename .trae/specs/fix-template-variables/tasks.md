# Tasks
- [x] Task 1: Atualizar o payload de dados `$data` em `api_contratos.php`
  - [x] SubTask 1.1: Adicionar o array `cedente` ao `$data` com a verificação booleana `pessoa_juridica` baseada em `tipo_pessoa === 'PJ'`.
  - [x] SubTask 1.2: Mapear os dados de `razao_social`, `descricao_juridica`, `cnpj`, `endereco_completo` e `representante` para dentro de `cedente` para pessoa jurídica.
  - [x] SubTask 1.3: Mapear os dados de `nome_completo`, `nacionalidade`, `estado_civil`, `profissao`, `rg`, `cpf` e `endereco_completo` para dentro de `cedente` para pessoa física.
  - [x] SubTask 1.4: Adicionar os objetos `contrato_mae` e `bordero` ao array `$data` com as propriedades `id`, `local` e `data_extenso`.