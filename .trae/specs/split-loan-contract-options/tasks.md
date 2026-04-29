# Tasks
- [x] Task 1: Atualizar o modal de geração de contratos para empréstimo.
  - [x] SubTask 1.1: Localizar o campo atual `Possui Garantia?` e substituir o select consolidado por dois controles binários distintos.
  - [x] SubTask 1.2: Definir os rótulos `O empréstimo tem garantia real?` e `O sacado tem avalista?` com opções `Sim` e `Não`.
  - [x] SubTask 1.3: Ajustar a coleta dos valores no JavaScript do modal para enviar duas flags explícitas ao backend.

- [x] Task 2: Atualizar a seleção de template para operações de empréstimo no backend.
  - [x] SubTask 2.1: Interpretar as duas flags recebidas do modal como `garantia real` e `avalista`.
  - [x] SubTask 2.2: Mapear cada combinação para o arquivo correto:
    - [x] `Sim/Sim` -> `contrato_1_com_veiculo_com_avalista.md`
    - [x] `Não/Não` -> `contrato_2_sem_veiculo_sem_avalista.md`
    - [x] `Sim/Não` -> `contrato_3_com_veiculo_sem_avalista.md`
    - [x] `Não/Sim` -> `contrato_4_sem_veiculo_com_avalista.md`
  - [x] SubTask 2.3: Garantir que o fluxo de geração de contratos de desconto permaneça inalterado.

- [x] Task 3: Validar a mudança ponta a ponta.
  - [x] SubTask 3.1: Verificar que o modal mostra os dois novos campos para empréstimo.
  - [x] SubTask 3.2: Verificar que cada uma das quatro combinações escolhe o template correto.
  - [x] SubTask 3.3: Executar testes, lint e preview local conforme o fluxo do projeto.

# Task Dependencies
- [Task 2] depends on [Task 1]
- [Task 3] depends on [Task 1]
- [Task 3] depends on [Task 2]
