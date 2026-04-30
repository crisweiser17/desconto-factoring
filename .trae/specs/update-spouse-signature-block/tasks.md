# Tasks

- [x] Task 1: Ajustar o bloco de assinatura do cônjuge nos templates ativos de mútuo
  - [x] SubTask 1.1: Revisar a área de assinaturas dos templates ativos usados por `api_contratos.php`.
  - [x] SubTask 1.2: Substituir o bloco anterior pelo layout simples com "Cônjuge:", linha de assinatura, "Nome" e "CPF".
  - [x] SubTask 1.3: Garantir que a solução escolhida renderize corretamente no pipeline Markdown/PDF sem depender de underscores.

- [x] Task 2: Padronizar o mesmo layout em todos os templates ativos de mútuo
  - [x] SubTask 2.1: Atualizar `02a_template_mutuo_simples.md`.
  - [x] SubTask 2.2: Atualizar `02b_template_mutuo_com_aval.md`.
  - [x] SubTask 2.3: Atualizar `02c_template_mutuo_com_garantia.md`.
  - [x] SubTask 2.4: Atualizar `02d_template_mutuo_com_garantia_e_aval.md`.
  - [x] SubTask 2.5: Atualizar `02e_template_mutuo_com_garantia_bem.md`.
  - [x] SubTask 2.6: Atualizar `02f_template_mutuo_com_garantia_bem_e_aval.md`.

- [x] Task 3: Validar a renderização final do bloco
  - [x] SubTask 3.1: Inspecionar a renderização com `conjuge_assina = 1` e confirmar a presença do novo bloco.
  - [x] SubTask 3.2: Confirmar que o bloco não aparece quando `conjuge_assina = 0`.
  - [x] SubTask 3.3: Verificar que a linha de assinatura e os campos não quebram a renderização Markdown/PDF.

# Task Dependencies
- [Task 2] depends on [Task 1]
- [Task 3] depends on [Task 1]
