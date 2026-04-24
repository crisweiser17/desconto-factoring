# Tasks

- [x] Task 1: Criar Tabelas de Banco de Dados.
  - [x] SubTask 1.1: Criar script `setup_fechamento.php` ou executar SQL para criar a tabela `despesas` com os campos (id, titulo, descricao, valor, data_despesa).
  - [x] SubTask 1.2: Executar SQL para criar a tabela `distribuicao_lucros` com os campos (id, socio_nome, valor, data).
- [x] Task 2: Criar a API de Fechamento Mensal (`api_fechamento.php`).
  - [x] SubTask 2.1: Obter parâmetros `mes` e `ano`.
  - [x] SubTask 2.2: Consultar total recebido e lucro bruto do mês (usando `data_recebimento` e `status IN ('Recebido', 'Compensado', 'Parcialmente Compensado')`).
  - [x] SubTask 2.3: Consultar despesas no mês para calcular o lucro líquido.
  - [x] SubTask 2.4: Consultar títulos atrasados/inadimplentes cujo `data_vencimento` pertença ao mês/ano selecionado.
- [x] Task 3: Adaptar e instalar `fechamento.php` no sistema.
  - [x] SubTask 3.1: Copiar o conteúdo do arquivo fornecido pelo usuário e adicionar ao repositório raiz.
  - [x] SubTask 3.2: Garantir a compatibilidade do layout (include de `menu.php`, links do bootstrap).
- [x] Task 4: Atualizar `menu.php`.
  - [x] SubTask 4.1: Adicionar um link para `fechamento.php` no menu (na aba Relatórios).