# Tasks
- [x] Task 1: Atualizar o modal de geração de contratos para capturar o subtipo da garantia.
  - [x] SubTask 1.1: Ajustar `detalhes_operacao.php` para mostrar a pergunta `Tipo da garantia` apenas quando a natureza for `EMPRESTIMO` e a opção de garantia estiver marcada como `Sim`.
  - [x] SubTask 1.2: Incluir as opções `Veículo` e `Outro bem móvel` com comportamento claro no frontend.
  - [x] SubTask 1.3: Atualizar o JavaScript do modal para exibir e ocultar os blocos corretos com base em `garantia`, `tipo da garantia` e `avalista`.

- [x] Task 2: Revisar a coleta e a validação mínima do avalista no modal.
  - [x] SubTask 2.1: Garantir que, ao marcar `Sim` para avalista, pelo menos `avalista_nome` e `avalista_cpf` se tornem obrigatórios.
  - [x] SubTask 2.2: Garantir que campos adicionais do avalista continuem opcionais ou contextuais, sem bloquear a geração do contrato se nome e CPF estiverem preenchidos.
  - [x] SubTask 2.3: Validar que a ausência de avalista remova a obrigatoriedade e o envio desnecessário desses campos.

- [x] Task 3: Atualizar a seleção de templates de mútuo no backend.
  - [x] SubTask 3.1: Ajustar `api_contratos.php` para receber o novo campo de tipo de garantia.
  - [x] SubTask 3.2: Mapear as combinações de mútuo para os templates corretos:
  - [x] SubTask 3.3: `garantia = veículo` e `avalista = não` -> `_contratos/02c_template_mutuo_com_garantia.md`
  - [x] SubTask 3.4: `garantia = veículo` e `avalista = sim` -> template veicular com aval já existente no projeto
  - [x] SubTask 3.5: `garantia = bem_movel` e `avalista = não` -> `_contratos/02e_template_mutuo_com_garantia_bem.md`
  - [x] SubTask 3.6: `garantia = bem_movel` e `avalista = sim` -> `_contratos/02f_template_mutuo_com_garantia_bem_e_aval.md`

- [x] Task 4: Garantir compatibilidade dos dados com os contratos resultantes.
  - [x] SubTask 4.1: Confirmar que os dados enviados pelo modal atendem aos campos esperados pelos templates de bem móvel.
  - [x] SubTask 4.2: Ajustar nomes de campos ou payloads do backend se necessário para não quebrar os templates já existentes de veículo.

- [x] Task 5: Validar o fluxo completo de geração.
  - [x] SubTask 5.1: Testar o caso empréstimo com garantia por veículo e sem avalista.
  - [x] SubTask 5.2: Testar o caso empréstimo com garantia por veículo e com avalista.
  - [x] SubTask 5.3: Testar o caso empréstimo com garantia por bem móvel e sem avalista.
  - [x] SubTask 5.4: Testar o caso empréstimo com garantia por bem móvel e com avalista.
  - [x] SubTask 5.5: Testar a validação de bloqueio quando avalista = sim e nome/CPF não forem informados.

# Task Dependencies
- Task 2 depends on Task 1.
- Task 3 depends on Task 1.
- Task 4 depends on Task 3.
- Task 5 depends on Task 2, Task 3 e Task 4.
