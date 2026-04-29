# Tasks

- [x] Task 1: Adicionar a opção "Cônjuge vai Assinar?" no modal "Gerar Contratos"
  - [x] SubTask 1.1: Editar `detalhes_operacao.php` e encontrar o formulário `#formGerarContrato`.
  - [x] SubTask 1.2: Adicionar um select/checkbox com id `modalConjugeAssina` e as opções "Sim (Incluir no contrato)" (valor "1") e "Não" (valor "0").
  - [x] SubTask 1.3: No javascript do botão `btnGerarContrato`, capturar esse valor e adicionar ao `formData`.

- [x] Task 2: Repassar a opção para os templates em `api_contratos.php`
  - [x] SubTask 2.1: Em `api_contratos.php`, capturar `$_POST['conjuge_assina']` e determinar se é verdadeiro ou falso.
  - [x] SubTask 2.2: Na montagem do array de `$data`, garantir que o nó `devedor` receba a propriedade booleana `conjuge_assina` baseada no valor do POST e se ele é efetivamente casado.
  - [x] SubTask 2.3: Atualizar `variaveis_disponiveis.md` para documentar a variável `{{devedor.conjuge_assina}}`.

- [x] Task 3: Atualizar os templates de Mútuo para imprimir a assinatura
  - [x] SubTask 3.1: Em `_contratos/02_template_contrato_mutuo.md`, procurar o final do contrato onde tem a assinatura do `MUTUÁRIO / DEVEDOR FIDUCIANTE`.
  - [x] SubTask 3.2: Logo abaixo, adicionar o bloco condicional `{{#devedor.conjuge_assina}} ... {{/devedor.conjuge_assina}}` com os dados do cônjuge (`devedor.conjuge.nome` e `devedor.conjuge.cpf`) como "CÔNJUGE DO MUTUÁRIO / DEVEDOR FIDUCIANTE (anuência)".
  - [x] SubTask 3.3: Aplicar a mesma mudança nos arquivos de template auxiliares: `contrato_1_com_veiculo_com_avalista.md`, `contrato_2_sem_veiculo_sem_avalista.md`, `contrato_3_com_veiculo_sem_avalista.md`, `contrato_4_sem_veiculo_com_avalista.md` (conforme aplicável, após a assinatura do Mutuário).

# Task Dependencies
- [Task 2] depends on [Task 1]
- [Task 3] depends on [Task 2]