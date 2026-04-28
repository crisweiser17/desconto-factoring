# Tasks

- [x] Task 1: Banco de Dados Completo (`01_regras_de_negocio.md`)
  - [x] SubTask 1.1: Criar script `setup_contratos_full.php` contendo todas as queries do item "4.6 Schema SQL Sugerido".
  - [x] SubTask 1.2: Rodar o script para criar as tabelas `contract_templates`, `generated_contracts`, `operation_vehicles`, `operation_guarantors`, e alterar as tabelas `cedentes` (clientes) e `operacoes`.

- [x] Task 2: Frontend (`detalhes_operacao.php`)
  - [x] SubTask 2.1: Atualizar o botão "Gerar Contratos" para abrir um `<div class="modal">`.
  - [x] SubTask 2.2: O Modal deve conter: Select para Natureza (Empréstimo/Desconto), Select para Porte do Cliente (MEI/ME/EPP), Dados do Avalista (Nome, CPF, Estado Civil), e Dados do Veículo em Garantia (Marca, Modelo, Placa).
  - [x] SubTask 2.3: Enviar esse formulário serializado para `api_contratos.php?action=gerar`.

- [x] Task 3: Backend e Regras (`api_contratos.php`)
  - [x] SubTask 3.1: Receber os dados do formulário e salvar as informações adicionais na operação e nas tabelas de garantia (`operation_vehicles`, `operation_guarantors`).
  - [x] SubTask 3.2: Implementar **Regra 2 (Validação de Tomador)**: Se Empréstimo, bloquear PF sem CNPJ e porte fora de MEI/ME/EPP.
  - [x] SubTask 3.3: Implementar **Regra 3 (Validação de Desconto)**: Bloquear se sacado == cedente (consultando `recebiveis` da operação).
  - [x] SubTask 3.4: Instalar biblioteca PHP para Números por Extenso (ex: `wgenial/numero-por-extenso` ou implementar função própria) exigida pelos templates.

- [x] Task 4: Compilação de Templates Originais
  - [x] SubTask 4.1: Construir o Array `$data` mapeando `credor`, `devedor`, `operacao`, `avalista`, `veiculo` exatamente como a seção 4.2 do documento de regras exige.
  - [x] SubTask 4.2: Se Empréstimo, carregar `_contratos/02_template_contrato_mutuo.md` e `_contratos/04_template_nota_promissoria.md`, renderizar com Mustache e Parsedown, e salvar via mPDF.
  - [x] SubTask 4.3: Se Desconto, carregar `_contratos/03_template_cessao_bordero.md`, renderizar e salvar.
  - [x] SubTask 4.4: Atualizar `status_contrato` para `aguardando_assinatura` e retornar JSON.