# Tasks
- [x] Task 1: Adicionar coluna `tipo_operacao` no banco de dados
  - [x] SubTask 1.1: Criar o script SQL `adicionar_tipo_operacao.sql` para alterar a tabela `operacoes` e adicionar `tipo_operacao` `ENUM('antecipacao', 'emprestimo') DEFAULT 'antecipacao'`.
  - [x] SubTask 1.2: Opcionalmente, atualizar também arquivos de dump SQL caso necessário para garantir a integridade de futuros backups.
- [x] Task 2: Modificar a interface de Simulação (`index.php`)
  - [x] SubTask 2.1: Adicionar um elemento `<select>` no topo do formulário para o Tipo de Operação (Antecipação ou Empréstimo).
  - [x] SubTask 2.2: Criar a seção HTML (oculta por padrão) para os parâmetros do Empréstimo: Valor do Empréstimo, Frequência (Semanal, Quinzenal, Mensal), Quantidade de Parcelas e Data do 1º Vencimento.
  - [x] SubTask 2.3: Desenvolver a lógica JavaScript que: 
    - Alterna a visibilidade entre a seção de Títulos a Descontar e os Parâmetros do Empréstimo; 
    - Ao selecionar Empréstimo, desabilita a adição manual de títulos e o botão de Adicionar Título.
- [x] Task 3: Implementar a Calculadora de Empréstimo em JavaScript
  - [x] SubTask 3.1: Escrever função JS que aplique a fórmula da Tabela Price (PMT) com base na taxa de juros, quantidade de parcelas e valor do empréstimo. Ajustar a taxa conforme a frequência (Mensal, Quinzenal, Semanal).
  - [x] SubTask 3.2: A função deve gerar automaticamente as linhas da tabela de títulos (`#titulosTable`) com o "Valor Original" (PMT), "Data Vencimento" (somando os dias/meses da frequência a partir da data inicial) e tipo (padrão 'nota_promissoria').
  - [x] SubTask 3.3: Acionar o recálculo do sistema (função `calcular()` existente) automaticamente sempre que os parâmetros do empréstimo mudarem, para exibir o "Valor Líquido Pago" (Crédito) gerado.
- [x] Task 4: Atualizar a lógica de gravação no Backend (`registrar_operacao.php`)
  - [x] SubTask 4.1: Capturar o campo `tipoOperacao` do formulário enviado.
  - [x] SubTask 4.2: Inserir a variável `:tipo_operacao` na query SQL de gravação da tabela `operacoes`.
- [x] Task 5: Adaptar a Listagem e Visualização (`listar_recebiveis.php` e `listar_operacoes.php`)
  - [x] SubTask 5.1: Na página `listar_recebiveis.php`, adicionar um filtro no cabeçalho por "Tipo de Operação".
  - [x] SubTask 5.2: Adicionar uma coluna na tabela exibindo "Antecipação" ou "Empréstimo".
  - [x] SubTask 5.3: Adaptar a query SQL para juntar (JOIN) com `operacoes` e filtrar por `tipo_operacao`.
  - [x] SubTask 5.4: Modificar as páginas `listar_operacoes.php`, `detalhes_operacao.php` e `editar_operacao.php` (se aplicável) para exibir o `tipo_operacao`.

# Task Dependencies
- [Task 2] depends on [Task 1]
- [Task 3] depends on [Task 2]
- [Task 4] depends on [Task 3]
- [Task 5] depends on [Task 4]
