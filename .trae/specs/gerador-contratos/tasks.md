# Tasks

- [ ] Task 1: Preparação do Banco de Dados e Composer
  - [ ] SubTask 1.1: Criar o script `setup_contracts.php` com o schema SQL fornecido pelo usuário (Tabelas `contract_templates`, `generated_contracts`, `master_cession_contracts`, `operation_vehicles`, `operation_guarantors`, `operation_witnesses`, e `ALTER TABLE` em `clientes` e `operacoes`).
  - [ ] SubTask 1.2: Rodar `setup_contracts.php` para garantir que as tabelas existam.
  - [ ] SubTask 1.3: Instalar as dependências do Composer: `composer require mpdf/mpdf mustache/mustache erusev/parsedown`.

- [ ] Task 2: Estrutura de Diretórios e Templates
  - [ ] SubTask 2.1: Criar pastas `app/Modules/Contracts/`, `Services`, `Validators`, `Templates`, `Controllers`.
  - [ ] SubTask 2.2: Criar pasta `storage/app/contratos` e garantir permissões.
  - [ ] SubTask 2.3: Criar arquivos de templates base (`mutuo_esc.md`, `cessao_mae.md`, `bordero.md`, `nota_promissoria.md`).

- [ ] Task 3: Classes de Serviços (Core Backend)
  - [ ] SubTask 3.1: Criar a classe de TemplateRendererService (renderiza Markdown com Mustache para HTML).
  - [ ] SubTask 3.2: Criar a classe de PdfBuilderService (usa mPDF para exportar o arquivo para a pasta de storage).
  - [ ] SubTask 3.3: Criar a classe OperationDataService para agregar e buscar dados complexos (cliente, recebíveis/sacados, garantidores) de forma estruturada.
  - [ ] SubTask 3.4: Criar classes MutuoValidator e CessaoValidator baseadas nas regras de negócio exigidas.
  - [ ] SubTask 3.5: Criar a classe orquestradora ContractGeneratorService adaptada para o PDO nativo (substituindo Eloquent do Laravel por SQL PDO padrão).

- [ ] Task 4: Endpoints API e Controlador
  - [ ] SubTask 4.1: Criar o arquivo de requisição API `api_gerar_contratos.php` e `api_baixar_contratos.php`.
  - [ ] SubTask 4.2: O endpoint chamará o `ContractGeneratorService` com a sessão do usuário ativa e lidará com os JSONs de retorno (200 OK ou 422/500 Errors).

- [ ] Task 5: Frontend Integration (Detalhes da Operação)
  - [ ] SubTask 5.1: Adicionar um botão na tela `detalhes_operacao.php` chamado "Gerar Contratos e Documentos".
  - [ ] SubTask 5.2: Fazer a chamada Ajax ao endpoint de geração.
  - [ ] SubTask 5.3: Mostrar spinner durante a geração e exibir uma lista de botões de Download ao concluir a geração.
