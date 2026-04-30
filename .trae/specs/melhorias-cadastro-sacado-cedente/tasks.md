# Tasks

- [x] Task 1: Atualizar Banco de Dados
  - [x] SubTask 1.1: Criar um script PHP (`update_db_sacados_cedentes.php`) para adicionar as colunas `anotacoes` (TEXT) e `conta_pix_tipo` (VARCHAR) nas tabelas `sacados` e `cedentes`.
  - [x] SubTask 1.2: Executar o script para aplicar as alterações no banco de dados.

- [x] Task 2: Atualizar Lógica de Salvamento (`salvar_sacado.php` e `salvar_cedente.php`)
  - [x] SubTask 2.1: Em `salvar_sacado.php`, incluir `anotacoes` e `conta_pix_tipo` no recebimento de POST e nas queries de INSERT/UPDATE.
  - [x] SubTask 2.2: Em `salvar_sacado.php`, adicionar validação: se `$tipo_pessoa === 'FISICA'`, forçar `representante_nome = $empresa` e `representante_cpf = $documento_principal`.
  - [x] SubTask 2.3: Em `salvar_cedente.php`, remover a atribuição forçada de `JURIDICA` e usar o `$_POST['tipo_pessoa']`.
  - [x] SubTask 2.4: Em `salvar_cedente.php`, adicionar as colunas `representante_*` na query de INSERT/UPDATE, assim como feito em `salvar_sacado.php`.
  - [x] SubTask 2.5: Em `salvar_cedente.php`, incluir `anotacoes` e `conta_pix_tipo` nas queries. Aplicar a mesma lógica de override de representante se for Pessoa Física.

- [x] Task 3: Atualizar Layout de `form_sacado.php` e `form_cedente.php`
  - [x] SubTask 3.1: Em ambos, adicionar o campo `anotacoes` (Textarea) na seção "Dados da Empresa".
  - [x] SubTask 3.2: Em `form_cedente.php`, transformar o input hidden de `tipo_pessoa` em um dropdown visível (copiar do sacado). Copiar também o card de "Dados do Representante" que estava faltando.
  - [x] SubTask 3.3: Mover a seção "Sócios da Empresa" para logo abaixo de "Dados da Empresa" em ambos os arquivos.
  - [x] SubTask 3.4: Na seção "Dados Bancários", adicionar o select `conta_pix_tipo` (opções: CPF, CNPJ, EMAIL, TELEFONE, CHAVE ALEATÓRIA) ao lado de `conta_pix`.
  - [x] SubTask 3.5: Na seção "Dados Bancários", adicionar um select `titular_selecao` (com opções padrão como Empresa e preenchido via JS com Sócios) que ao ser alterado preenche automaticamente os campos `conta_titular` e `conta_documento` (que podem se tornar readonly).

- [x] Task 4: Atualizar JavaScript dos Formulários
  - [x] SubTask 4.1: Implementar lógica que popula o select de titular bancário (`titular_selecao`) e o select de representante com os dados dinâmicos dos sócios e da empresa.
  - [x] SubTask 4.2: Ocultar o card "Dados do Representante" quando "Pessoa Física" for selecionado.
  - [x] SubTask 4.3: Quando PJ for selecionado, obrigar a escolha de um sócio no card de Representante (preenchendo os inputs readonly de representante).
  - [x] SubTask 4.4: Adaptar as máscaras e validações de CPF/CNPJ no `form_cedente.php` baseando-se no tipo de pessoa (copiar a lógica já funcional do sacado).

- [x] Task 5: Atualizar Telas de Visualização
  - [x] SubTask 5.1: Em `visualizar_sacado.php`, exibir `Anotações` e `Tipo PIX` caso existam.
  - [x] SubTask 5.2: Em `visualizar_cedente.php`, exibir `Anotações`, `Tipo PIX`, além de exibir corretamente o "Tipo de Pessoa" e os dados de Representante.

# Task Dependencies
- Task 2 depends on Task 1
- Task 3 depends on Task 1
- Task 4 depends on Task 3
- Task 5 depends on Task 1