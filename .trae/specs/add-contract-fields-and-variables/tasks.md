# Tasks
- [x] Task 1: Atualizar Banco de Dados
  - [x] SubTask 1.1: Criar e executar um script `update_db_add_contract_fields.php` para adicionar colunas de cônjuge (`casado`, `regime_casamento`, `conjuge_nome`, `conjuge_cpf`, `conjuge_rg`, `conjuge_nacionalidade`, `conjuge_profissao`) nas tabelas `cedentes` e `sacados`.
  - [x] SubTask 1.2: Adicionar colunas bancárias (`conta_banco`, `conta_agencia`, `conta_numero`, `conta_pix`, `conta_tipo`, `conta_titular`, `conta_documento`) nas tabelas `cedentes` e `sacados`.
  - [x] SubTask 1.3: Adicionar coluna `whatsapp` nas tabelas `cedentes` e `sacados`.

- [x] Task 2: Atualizar Formulários de Cadastro (Cedente e Sacado)
  - [x] SubTask 2.1: Modificar `form_cedente.php` para adicionar campos de contato (WhatsApp), dados do cônjuge (condicionalmente se `casado` for marcado) e dados da conta bancária.
  - [x] SubTask 2.2: Modificar `salvar_cedente.php` para processar e salvar esses novos campos.
  - [x] SubTask 2.3: Modificar `form_sacado.php` para adicionar campos de contato (WhatsApp), dados do cônjuge e conta bancária.
  - [x] SubTask 2.4: Modificar `salvar_sacado.php` para processar e salvar esses novos campos.

- [x] Task 3: Atualizar o Modal de Gerar Contrato
  - [x] SubTask 3.1: No arquivo `detalhes_operacao.php`, adicionar no modal de "Gerar Contrato" (quando tiver Veículo) os inputs: Chassi, Município de Registro, UF.
  - [x] SubTask 3.2: No mesmo modal, adicionar para o Avalista os inputs: Email, WhatsApp.

- [x] Task 4: Atualizar o Backend de Geração de Contratos (`api_contratos.php`)
  - [x] SubTask 4.1: Ajustar a extração dos POSTs de Avalista (email, whatsapp) e Veículo (chassi, municipio_registro, uf).
  - [x] SubTask 4.2: Atualizar as Queries de `cedentes` e `sacados` para buscar os novos campos bancários, conjugais, e contatos, injetando-os no `$data['cedente']` e `$data['devedor']`.
  - [x] SubTask 4.3: Injetar `chassi`, `municipio_registro`, `uf` no array `$data['veiculo']` e salvá-los no banco (`operation_vehicles`).
  - [x] SubTask 4.4: Injetar `email` e `whatsapp` no array `$data['avalista']` e salvá-los no banco (`operation_guarantors`).
  - [x] SubTask 4.5: Injetar `email` e `whatsapp` no array `$data['credor']` (dados estáticos ou via config).
  - [x] SubTask 4.6: Implementar o carregamento da lista de títulos (`recebiveis`) da operação atual para popular o array `$data['titulos']` (com numero, ordem, tipo, sacado_nome, sacado_documento, data_emissao, data_vencimento, valor_face, valor_presente).
  - [x] SubTask 4.7: Calcular os totais da operação para injetar no array `$data['bordero']` (total_face, total_titulos, taxa_desagio, total_desagio, prazo_medio, tarifas, valor_liquido, valor_liquido_extenso, forma_pagamento).

- [x] Task 5: Atualizar Documentação de Variáveis
  - [x] SubTask 5.1: Atualizar `_contratos/variaveis_disponiveis.md` para refletir as novas tags implementadas.

# Task Dependencies
- [Task 2] depends on [Task 1]
- [Task 3] depends on [Task 1]
- [Task 4] depends on [Task 2, Task 3]
- [Task 5] depends on [Task 4]
