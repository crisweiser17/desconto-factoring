# Tasks
- [x] Task 1: Revisar os quatro templates de empréstimo e mapear trechos herdados de cessão de crédito.
  - [x] SubTask 1.1: Identificar cabeçalhos, qualificações, cláusulas e seções operacionais que usam `CEDENTE`, `CESSIONÁRIA`, `Borderô` ou linguagem típica de cessão.
  - [x] SubTask 1.2: Confirmar quais variáveis atuais ainda podem ser mantidas apenas como fonte de dados sem preservar a nomenclatura errada no texto final.

- [x] Task 2: Corrigir a redação jurídica e os rótulos dos templates de empréstimo.
  - [x] SubTask 2.1: Atualizar a qualificação das partes para terminologia de mútuo em cada um dos quatro templates.
  - [x] SubTask 2.2: Corrigir a seção de pagamento para refletir pagamento do mutuário à mutuante.
  - [x] SubTask 2.3: Remover referências textuais indevidas a `CEDENTE`, `CESSIONÁRIA` e `Borderô` nos contratos de empréstimo.

- [x] Task 3: Ajustar o backend apenas se necessário para suportar a nova redação sem placeholders aparentes.
  - [x] SubTask 3.1: Revisar `api_contratos.php` para confirmar que os dados enviados ao template atendem à qualificação corrigida.
  - [x] SubTask 3.2: Complementar ou renomear mapeamentos apenas quando houver lacuna real de dados para os contratos de empréstimo.

- [x] Task 4: Validar geração de contrato de empréstimo ponta a ponta.
  - [x] SubTask 4.1: Gerar ao menos um contrato de empréstimo e verificar a ausência de termos incorretos no texto final.
  - [x] SubTask 4.2: Executar testes, lint/diagnostics e preview local conforme o fluxo do projeto.

# Task Dependencies
- [Task 2] depends on [Task 1]
- [Task 3] depends on [Task 1]
- [Task 4] depends on [Task 2]
- [Task 4] depends on [Task 3]
