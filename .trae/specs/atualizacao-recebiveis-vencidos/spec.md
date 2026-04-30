# Atualização de Recebíveis Vencidos Spec

## Why
A empresa precisa poder atualizar automaticamente recebíveis vencidos, calculando juros e mora baseados em configurações globais, para que os operadores possam cobrar o valor correto. Quando um título for pago após o vencimento, o sistema deve permitir o registro do valor exato recebido e recalcular os totais da operação (como lucro) para refletir o ganho extra com os juros/mora.

## What Changes
- Adicionar campos `taxa_juros_atraso` (% ao mês) e `taxa_multa_atraso` (%) na tela de configurações (`config.php`) e salvar no arquivo `config.json`.
- Adicionar no banco de dados a coluna `valor_recebido` DECIMAL(15,2) na tabela `recebiveis` para armazenar o valor exato pago.
- Atualizar os contratos (`api_contratos.php`) para extraírem essas taxas de `config.json` e passá-las para os templates.
- No carregamento da listagem de recebíveis e detalhes de operações, calcular dinamicamente o valor corrigido para títulos vencidos e exibi-lo junto com o original.
- Alterar o fluxo de "Marcar como Recebido": 
  - Se o título estiver vencido ou se for desejado informar o valor pago, abrir um modal mostrando o "Valor Original", "Valor Corrigido", e um campo editável "Valor Recebido".
  - Ao confirmar, enviar o valor para `atualizar_status.php`.
- Atualizar `atualizar_status.php` para receber o `valor_recebido` e salvá-lo no banco.
- **NOVO**: Após atualizar o recebível, recalcular/atualizar os totais da operação correspondente na tabela `operacoes` (ex: adicionar a diferença `valor_recebido - valor_original` ao lucro da operação).

## Impact
- Affected code: 
  - `config.php`
  - `api_contratos.php`
  - `listar_recebiveis.php`
  - `detalhes_operacao.php`
  - `atualizar_status.php`
  - Banco de dados (`recebiveis` e `operacoes` update logic)

## ADDED Requirements
### Requirement: Recálculo da Operação
Ao registrar um recebimento com valor maior que o original, o sistema SHALL atualizar os valores consolidados da operação, somando a diferença (juros/mora) ao lucro calculado.
