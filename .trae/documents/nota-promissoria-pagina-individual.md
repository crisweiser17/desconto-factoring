# Plano: Nota Promissória em Página Individual para Impressão

## Contexto
Atualmente, a Nota Promissória é gerada como uma seção adicional no final do Contrato de Mútuo (usando `page-break-before: always`). O usuário deseja que ela possa ser impressa em sua própria página individual, no formato padrão de uma nota promissória comercial (como a imagem de referência amarela).

## Objetivo
Criar uma opção para gerar a Nota Promissória como um documento PDF separado e independente, mantendo a opção atual de gerá-la junto com o contrato.

## Passos de Implementação

### Passo 1: Criar novo endpoint na API
- **Arquivo**: `api_contratos.php`
- **Ação**: Adicionar um novo case `'gerar_nota_promissoria'` no switch principal.
- **Detalhes**: 
  - Criar função `gerarNotaPromissoria($pdo, $operacao_id)`.
  - A função deve buscar os dados da operação (como a função `gerarContrato` já faz).
  - Usar APENAS o template `_contratos/03_template_nota_promissoria.md`.
  - Renderizar com Mustache e gerar um PDF separado usando mPDF.
  - Salvar o PDF em `uploads/contratos/{operacao_id}/nota_promissoria_{timestamp}.pdf`.
  - Retornar o caminho do arquivo no JSON.

### Passo 2: Adicionar botão na interface
- **Arquivo**: `detalhes_operacao.php` (ou o arquivo que contém o modal de geração de contrato).
- **Ação**: Adicionar um botão secundário ao lado do botão "Gerar Contrato".
- **Texto do botão**: "Gerar Nota Promissória (Individual)".
- **Comportamento**: Chamar o novo endpoint `api_contratos.php?action=gerar_nota_promissoria` via AJAX.

### Passo 3: Ajustar o template da Nota Promissória (Opcional - Layout)
- **Arquivo**: `_contratos/03_template_nota_promissoria.md`
- **Ação**: Se necessário, ajustar o CSS/HTML do template para que ocupe melhor uma página A4 quando impresso sozinho.
- **Detalhes**: Adicionar classes CSS específicas para centralização e espaçamento adequado para o formato "folha solta".

### Passo 4: Testar e Validar
- **Ação**: Testar a geração do PDF individual.
- **Validação**: 
  - Verificar se o PDF contém apenas a Nota Promissória.
  - Verificar se os dados (valor, vencimento, partes) estão corretos.
  - Verificar se a formatação está adequada para impressão em folha A4.

## Decisões a Confirmar
1. **Formato visual**: Você quer que a Nota Promissória individual tenha um layout diferente do atual (mais parecido com a imagem de referência amarela, com campos em branco para preenchimento manual), ou apenas a mesma versão preenchida em uma página separada?
2. **Local do botão**: O botão deve ficar no modal de geração de contrato ou em outro lugar na tela de detalhes da operação?
