# Tasks
- [x] Task 1: Atualizar a lógica de Seleção de Templates (`api_contratos.php`)
  - [x] Substituir os caminhos de template na cláusula de "EMPRESTIMO" pelos novos arquivos `02a_template_mutuo_simples.md`, `02b_template_mutuo_com_aval.md`, `02c_template_mutuo_com_garantia.md`, `02d_template_mutuo_com_garantia_e_aval.md` baseados em `$tem_veiculo` e `$tem_avalista`.
  - [x] Substituir os caminhos de template na cláusula "else" (Cessão) por `01_template_antecipacao_recebiveis.md`.

- [x] Task 2: Implementar Remoção de Metadados e Concatenação da Nota Promissória
  - [x] Criar lógica para remover metadados iniciais dos templates usando `preg_replace('/^.*?\n---\n+/s', '', $markdownTemplate, 1)`.
  - [x] Para empréstimos, ler também `03_template_nota_promissoria.md`, limpar seus metadados, e concatenar à variável `$markdownTemplate` com `<div style="page-break-before: always;"></div>` (ou equivalente em markdown/html) separando.

- [x] Task 3: Preparar Variáveis da Nota Promissória no Array Mustache
  - [x] Localizar a última data de vencimento da operação (verificando o último item da lista `$recebiveis` ou array equivalente).
  - [x] Converter essa data para formato `d/m/Y` e para texto por extenso (usando `$extenso->converterData`).
  - [x] Adicionar o bloco `$data['np']` contendo `numero`, `vencimento` e `data_vencimento_extenso`.