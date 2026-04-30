# Tasks
- [x] Task 1: Refinar o template da Nota Promissória
  - [x] Atualizar `_contratos/03_template_nota_promissoria.md` para refletir emissão em única via, valor total da dívida e vencimento à vista sem ambiguidade textual.
  - [x] Reorganizar a estrutura visual do documento com classes e marcação compatíveis com o layout jurídico solicitado.
  - [x] Preservar a exibição condicional dos blocos de aval e anuência conjugal, quando aplicáveis.

- [x] Task 2: Aplicar estilo dedicado da NP na geração do PDF
  - [x] Ajustar o fluxo de renderização em `api_contratos.php` para permitir CSS específico da NP sem degradar a formatação do contrato.
  - [x] Implementar o conjunto de estilos necessário para página A4 retrato, cabeçalho, bloco de valor, corpo do texto e assinatura.
  - [x] Garantir que a NP permaneça em uma única página A4 nas saídas usuais do sistema.

- [x] Task 3: Padronizar os dados usados na NP
  - [x] Revisar o preenchimento dos dados `np` e `operacao` usados pela NP para alinhar o documento com vencimento à vista como padrão.
  - [x] Confirmar que o valor exibido na NP usa `operacao.valor_total_devido` e o correspondente por extenso.
  - [x] Validar que beneficiário, praça, local de emissão e identificação do emitente saem preenchidos no PDF gerado.

- [x] Task 4: Validar a saída final da Nota Promissória
  - [x] Executar a geração do PDF em um cenário de empréstimo sem aval e em um cenário com aval.
  - [x] Verificar sintaxe PHP, diagnósticos do editor e integridade visual do PDF final.
  - [x] Abrir preview local do sistema e confirmar o fluxo de geração conforme a regra operacional da ACM.

# Task Dependencies
- [x] [Task 2] depends on [Task 1]
- [x] [Task 3] depends on [Task 1]
- [x] [Task 4] depends on [Task 2]
- [x] [Task 4] depends on [Task 3]
