# Fix Loan Contract Formatting and Data Spec

## Why
A interface de configurações do sistema precisa permitir o cadastro do representante legal (Nome e CPF) para que seja refletido dinamicamente nos contratos de empréstimo. Além disso, há problemas de formatação (`\` indesejadas) e hardcodes na cláusula de pagamento e cronograma (Anexo II) que assumem apenas uma parcela, mesmo quando a operação possui vários títulos/recebíveis. Também é necessário garantir a substituição correta de variáveis de endereço e contato (E-mail e WhatsApp) para Mutuante e Mutuário.

## What Changes
- Adicionar campos `empresa_representante_nome`, `empresa_representante_cpf`, `empresa_endereco`, `empresa_email`, e `empresa_whatsapp` na página de configurações (`config.php` e `config.json`).
- Atualizar `api_contratos.php` para usar esses novos dados do `config.json` no bloco do `MUTUANTE` (Credor).
- Remover a barra invertida (`\`) que aparece logo após `MUTUANTE:` e `MUTUARIO:` em todos os templates `.md` de contratos.
- Ajustar a cláusula 2.1 (ou correspondente) nos templates para referenciar o número total de parcelas e o valor total devido, ao invés de fixar o valor de "cada" parcela.
- Gerar o array `cronograma` no `api_contratos.php` dinamicamente a partir dos títulos (recebíveis) da operação, ao invés de um item fixo.
- Atualizar a contagem e valores do resumo de parcelas com base na lista de recebíveis.

## Impact
- Affected specs: Geração de Contratos de Empréstimo, Configurações do Sistema.
- Affected code: `config.php`, `api_contratos.php`, `_contratos/contrato_1_com_veiculo_com_avalista.md`, `_contratos/contrato_2_sem_veiculo_sem_avalista.md`, `_contratos/contrato_3_com_veiculo_sem_avalista.md`, `_contratos/contrato_4_sem_veiculo_com_avalista.md`.

## ADDED Requirements
### Requirement: Representante Legal e Contatos Dinâmicos
O sistema SHALL permitir configurar os dados de representação e contato da Factoring (Nome, CPF, Endereço, E-mail e WhatsApp) na tela de configurações e aplicá-los dinamicamente nos contratos gerados.

## MODIFIED Requirements
### Requirement: Cronograma e Cláusula de Pagamento Dinâmicos
O sistema SHALL gerar a cláusula de forma de pagamento e o cronograma do Anexo II refletindo a quantidade exata de recebíveis (parcelas) da operação, somando os valores corretos e exibindo as datas de vencimento reais.
