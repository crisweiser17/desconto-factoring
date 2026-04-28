# Tasks
- [x] Task 1: Atualizar a lógica de Seleção de Templates (`api_contratos.php`)
  - [x] SubTask 1.1: Localizar a verificação `$natureza === 'EMPRESTIMO'` / `else` no arquivo.
  - [x] SubTask 1.2: Criar flags lógicas `$tem_veiculo` (baseado em `$tem_garantia === 'Sim'` e na existência de dados do veículo, como `$veiculo_placa` ou `$veiculo_chassi`) e `$tem_avalista` (baseado na existência do `$avalista_nome` e `$avalista_cpf`).
  - [x] SubTask 1.3: Dentro da cláusula que lida com operações não-empréstimo (`else`), substituir a chamada estática para `03_template_cessao_bordero.md` por uma estrutura condicional `if/elseif` para carregar o arquivo `.md` correto dentre as 4 opções fornecidas pelo usuário.

# Task Dependencies
- Nenhuma.