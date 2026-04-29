# Tarefas

- [x] Tarefa 1: Atualizar regras de negócio na documentação
  - [x] SubTarefa 1.1: Atualizar `_contratos/01_regras_de_negocio.md` para remover menção a `possui_cnpj_mei` e documentar restrição de PJ para Cedentes.
  - [x] SubTarefa 1.2: Atualizar `_contratos/05_prompt_para_trae.md` para remover as referências à coluna `possui_cnpj_mei`.

- [x] Tarefa 2: Refatorar Interface e Backend de Sacados
  - [x] SubTarefa 2.1: Remover campo `possui_cnpj_mei` de `form_sacado.php` e manter o select de `Porte` e `Tipo de Pessoa`.
  - [x] SubTarefa 2.2: Remover campo `possui_cnpj_mei` de `visualizar_sacado.php`.
  - [x] SubTarefa 2.3: Remover processamento de `possui_cnpj_mei` em `salvar_sacado.php`.

- [x] Tarefa 3: Refatorar Interface e Backend de Cedentes
  - [x] SubTarefa 3.1: Em `form_cedente.php`, remover o select de `tipo_pessoa` (fixar comportamento para JURIDICA e remover lógica de alternância para CPF) e adicionar o menu select de `Porte` (MEI, ME, EPP, etc).
  - [x] SubTarefa 3.2: Em `visualizar_cedente.php`, remover informações de PF e exibir o `Porte`.
  - [x] SubTarefa 3.3: Em `salvar_cedente.php`, forçar `$tipoPessoa = 'JURIDICA'`, remover checagens de PF e incluir processamento/salvamento do campo `porte`.

- [x] Tarefa 4: Atualizar API de Contratos
  - [x] SubTarefa 4.1: Em `api_contratos.php`, remover `possui_cnpj_mei` das consultas SQL (SELECTs).
  - [x] SubTarefa 4.2: Em `api_contratos.php`, remover a lógica que verificava se PF tinha a flag `possui_cnpj_mei`. Apenas validar se é JURIDICA e tem Porte adequado quando necessário.

- [x] Tarefa 5: Limpar Banco de Dados e Scripts de Setup
  - [x] SubTarefa 5.1: Criar arquivo `remove_possui_cnpj_mei.php` que execute `ALTER TABLE sacados DROP COLUMN possui_cnpj_mei` e `ALTER TABLE cedentes DROP COLUMN possui_cnpj_mei`.
  - [x] SubTarefa 5.2: Remover menções de `possui_cnpj_mei` dos arquivos de instalação `setup_contratos_full.php` e `update_sacados_columns.php`.