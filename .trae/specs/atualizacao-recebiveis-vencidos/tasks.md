# Tasks
- [x] Task 1: Adicionar coluna `valor_recebido` DECIMAL(15,2) DEFAULT NULL na tabela `recebiveis` no banco de dados (criar script SQL `adicionar_valor_recebido.sql` ou modificar DB).
- [x] Task 2: Atualizar `config.php` para incluir e salvar os campos `taxa_juros_atraso` (% ao mês) e `taxa_multa_atraso` (%).
- [x] Task 3: Atualizar `api_contratos.php` para injetar `taxa_juros_atraso` e `taxa_multa_atraso` nas variáveis de template de contrato (incluindo versões por extenso).
- [x] Task 4: Criar funções de cálculo de juros e mora (em `functions.php` ou `funcoes_calculo_central.php`) e exibir valor corrigido em `listar_recebiveis.php` e `detalhes_operacao.php` para recebíveis vencidos.
- [x] Task 5: Implementar um Modal de recebimento em `listar_recebiveis.php` e `detalhes_operacao.php` que captura o valor pago (com campo editável de `valor_recebido`) quando o título for marcado como pago.
- [x] Task 6: Modificar `atualizar_status.php` para receber e persistir o `valor_recebido` no banco de dados.
- [x] Task 7: Em `atualizar_status.php` ou numa nova função, recalcular os valores da operação (ex: somar `(valor_recebido - valor_original)` ao `total_lucro_liquido_calc`) e atualizar a tabela `operacoes` quando o título for marcado como Recebido com valor excedente.

# Task Dependencies
- Task 4, 5 e 6 dependem de Task 1 e Task 2.
- Task 7 depende da Task 6.
