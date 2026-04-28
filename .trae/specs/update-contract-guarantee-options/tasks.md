# Tasks
- [x] Task 1: Atualizar Interface em `detalhes_operacao.php`
  - [x] SubTask 1.1: Atualizar o select `#modalTemGarantia` para as opções `com_veiculo_com_avalista`, `sem_veiculo_sem_avalista`, `com_veiculo_sem_avalista`, e `sem_veiculo_com_avalista`.
  - [x] SubTask 1.2: Modificar a estrutura HTML para separar os blocos do Avalista e Veículo em `#avalistaContainer` e `#veiculoContainer`.
  - [x] SubTask 1.3: Atualizar a lógica JavaScript no evento `change` de `#modalTemGarantia` para exibir/ocultar `#avalistaContainer` e `#veiculoContainer` com base na opção selecionada, habilitando ou desabilitando o atributo `required` dos inputs através da classe `.req-avalista` e `.req-veiculo` de forma individualizada.

- [x] Task 2: Atualizar Backend em `api_contratos.php`
  - [x] SubTask 2.1: Recuperar a variável `tem_garantia` e substituir as flags `$tem_veiculo` e `$tem_avalista` para utilizar os valores `com_veiculo_com_avalista` etc.
  - [x] SubTask 2.2: O condicional para escolher o `$templatePath` e para salvar os dados nas tabelas `operation_vehicles` e `operation_guarantors` deve basear-se nessas duas flags (ou no valor enviado em `tem_garantia`).

# Task Dependencies
- [Task 2] depends on [Task 1]