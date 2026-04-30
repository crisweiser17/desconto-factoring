# Tasks
- [x] Task 1: Consolidar o inventário dos hardcodes configuráveis nos contratos ativos.
  - [x] SubTask 1.1: Confirmar, em cada template ativo, ocorrências hardcoded de razão social, documento, endereço da credora e titular/documento bancário.
  - [x] SubTask 1.2: Mapear para cada ocorrência a variável Mustache de destino e identificar se o dado já existe no payload atual ou se falta expor via `api_contratos.php`.

- [x] Task 2: Completar os dados configuráveis da credora no backend de geração de contratos.
  - [x] SubTask 2.1: Revisar `config.php` e `config.json` para garantir que todos os dados empresariais necessários à renderização contratual estejam disponíveis de forma explícita.
  - [x] SubTask 2.2: Ajustar `api_contratos.php` para expor no payload os campos da credora necessários para substituir todos os hardcodes identificados.
  - [x] SubTask 2.3: Manter compatibilidade com os campos já consumidos pelos templates atuais.

- [x] Task 3: Substituir hardcodes por variáveis Mustache em todos os templates ativos impactados.
  - [x] SubTask 3.1: Atualizar `01_template_antecipacao_recebiveis.md`.
  - [x] SubTask 3.2: Atualizar `02a_template_mutuo_simples.md`, `02b_template_mutuo_com_aval.md`, `02c_template_mutuo_com_garantia.md` e `02d_template_mutuo_com_garantia_e_aval.md`.
  - [x] SubTask 3.3: Atualizar `02e_template_mutuo_com_garantia_bem.md`, `02f_template_mutuo_com_garantia_bem_e_aval.md` e `03_template_nota_promissoria.md`.

- [x] Task 4: Validar a geração dos contratos após o saneamento.
  - [x] SubTask 4.1: Gerar exemplos de contratos de antecipação, mútuo e nota promissória para confirmar renderização sem dados hardcoded da credora.
  - [x] SubTask 4.2: Revisar manualmente os blocos de qualificação, pagamento, comunicações e assinatura para confirmar que os dados vieram do payload/configuração.
  - [x] SubTask 4.3: Executar verificação de sintaxe/lint relevante e checar o preview local conforme a rotina do projeto.

# Task Dependencies
- [Task 2] depends on [Task 1]
- [Task 3] depends on [Task 2]
- [Task 4] depends on [Task 3]
