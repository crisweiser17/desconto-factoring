# Tasks
- [x] Task 1: Mapear a origem dos dados da Cláusula 3 do contrato de antecipação.
  - [x] SubTask 1.1: Confirmar quais chaves o template `_contratos/01_template_antecipacao_recebiveis.md` espera para a tabela da Cláusula 3.2.
  - [x] SubTask 1.2: Confirmar quais chaves `api_contratos.php` efetivamente monta para operações de antecipação e identificar incompatibilidades.

- [x] Task 2: Corrigir o payload financeiro do contrato de antecipação.
  - [x] SubTask 2.1: Ajustar o backend ou o template para que quantidade de títulos, total de face, taxa de deságio, prazo médio, total de deságio, tarifas e valor líquido usem a mesma estrutura de dados.
  - [x] SubTask 2.2: Garantir que a linha de tarifas continue condicional e não quebre a tabela Markdown.

- [x] Task 3: Corrigir o preenchimento dos dados bancários do cedente.
  - [x] SubTask 3.1: Validar a consulta e o merge dos campos `conta_banco`, `conta_agencia`, `conta_numero`, `conta_tipo` e `conta_pix` do cadastro vinculado à operação.
  - [x] SubTask 3.2: Garantir que a Cláusula 3.3 renderize os dados do cedente sem afetar os dados da credora em outros templates.

- [x] Task 4: Validar a geração do contrato ponta a ponta.
  - [x] SubTask 4.1: Gerar novamente o contrato da operação afetada ou de uma operação equivalente e verificar visualmente a Cláusula 3.
  - [x] SubTask 4.2: Rodar verificações de sintaxe/lint nos arquivos PHP alterados.
  - [x] SubTask 4.3: Abrir preview/local e confirmar no navegador que o contrato gerado exibe os dados corretos.

# Task Dependencies
- Task 2 depends on Task 1
- Task 3 depends on Task 1
- Task 4 depends on Task 2
- Task 4 depends on Task 3
