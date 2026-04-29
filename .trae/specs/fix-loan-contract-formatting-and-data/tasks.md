# Tasks
- [x] Task 1: Atualizar Configurações do Sistema
  - [x] SubTask 1.1: Adicionar campos `empresa_representante_nome`, `empresa_representante_cpf`, `empresa_endereco`, `empresa_email`, e `empresa_whatsapp` na tela `config.php`.
  - [x] SubTask 1.2: Garantir que esses novos campos sejam salvos e lidos corretamente do arquivo `config.json`.
- [x] Task 2: Atualizar `api_contratos.php`
  - [x] SubTask 2.1: Ler os novos campos do `config.json` e aplicá-los no array de `$data['credor']` para os contatos e representante.
  - [x] SubTask 2.2: Construir o array `cronograma` iterando sobre os `$titulos` da operação (parcelas, datas de vencimento, e valores) ao invés do hardcode de uma única parcela.
  - [x] SubTask 2.3: Atualizar as variáveis de contagem e valor total (`num_parcelas`, `valor_total_devido`, etc.) dinamicamente com base nos títulos.
  - [x] SubTask 2.4: Tratar o fallback de `email` e `whatsapp` vazios do `MUTUARIO` para evitar formatação quebrada.
- [x] Task 3: Atualizar Templates de Contrato
  - [x] SubTask 3.1: Remover a barra invertida (`\`) ao lado de `**MUTUANTE:**\` e `**MUTUARIO:**\` nos quatro arquivos de template (`_contratos/contrato_1_com_veiculo_com_avalista.md`, `_contratos/contrato_2_sem_veiculo_sem_avalista.md`, `_contratos/contrato_3_com_veiculo_sem_avalista.md`, `_contratos/contrato_4_sem_veiculo_com_avalista.md`).
  - [x] SubTask 3.2: Ajustar o texto da cláusula de pagamento (ex: Cláusula 2.1) para que seja genérica para 1 ou mais parcelas (ex: "pagará a obrigação em X parcela(s), perfazendo o valor total de R$ Y, conforme cronograma do Anexo II").

# Task Dependencies
- [Task 2] depends on [Task 1]
- [Task 3] depends on [Task 2]