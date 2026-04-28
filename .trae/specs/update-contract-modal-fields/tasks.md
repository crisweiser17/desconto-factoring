# Tasks
- [x] Task 1: Modificar o Dropdown de Porte do Cliente
  - [x] SubTask 1.1: Remover opções MÉDIO e GRANDE do select `modalPorteCliente` no arquivo `detalhes_operacao.php`.
  - [x] SubTask 1.2: Adicionar uma nota/tooltip no HTML abaixo do campo "Porte" explicando que LTDA é Natureza Jurídica, e que o cliente deve ser classificado por porte (ME, EPP).
- [x] Task 2: Implementar o Toggle de Garantia no Modal
  - [x] SubTask 2.1: Adicionar um `select` ou radio buttons "Possui Garantia?" no HTML do modal (apenas visível quando `Natureza == EMPRESTIMO`).
  - [x] SubTask 2.2: Atualizar a lógica JavaScript em `detalhes_operacao.php` para exibir as seções `Avalista` e `Veiculo` (e marcar campos como `required`) APENAS se a "Garantia" for selecionada como "Sim".
- [x] Task 3: Remover campos específicos de Veículo
  - [x] SubTask 3.1: Remover os `inputs` de `veiculo_municipio`, `veiculo_uf` e `veiculo_chassi` do arquivo `detalhes_operacao.php`.
  - [x] SubTask 3.2: Remover a captura, envio e registro dessas variáveis no backend em `api_contratos.php` (na função `gerarContrato` e nas queries do banco).
- [x] Task 4: Adicionar Máscaras de CPF e CNPJ
  - [x] SubTask 4.1: Incluir um script de máscara leve no frontend (ex: usar Vanilla JS ou IMask se já estiver carregado) para formatar os inputs `avalista_cpf` e `avalista_conjuge_cpf`.

# Task Dependencies
- Nenhuma dependência bloqueante entre as Tasks 1, 3 e 4.
- Task 2 afeta a exibição do HTML, portanto a SubTask 2.2 depende da criação do HTML na SubTask 2.1.
