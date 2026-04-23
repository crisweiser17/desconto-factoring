# Plano de Implementação: Refinamento dos Cálculos e PDF para Empréstimos

## Resumo
Ajustar o cálculo e a exibição de Empréstimos para que o valor líquido pago seja exatamente igual ao valor do empréstimo solicitado, sem desconto de IOF ou divergências por contagem de dias quebrados. Adicionalmente, adaptar a geração do PDF do cliente para que reflita a nomenclatura e a estrutura adequadas a uma operação de empréstimo.

## Análise do Estado Atual
- Atualmente, ao simular um empréstimo de R$ 10.000, o sistema usa as parcelas geradas (Tabela Price) e aplica a fórmula de desconto a valor presente (com dias exatos) mais as taxas de IOF. Isso resulta em um "Total Líquido Pago" menor (ex: 9.988,43).
- O PDF gerado pelo botão "PDF Simulação Cliente" (script `export_pdf_cliente.php`) é formatado como "Resumo da Operação de Desconto" e mostra colunas como "Cedente" e "Valor Líquido Pago" por parcela, o que não faz sentido para empréstimos.
- O campo de IOF continua visível na tela e ativado por padrão dependendo do uso, interferindo no valor final.

## Alterações Propostas

### 1. Ajustes na Interface (`index.php`)
- **Ocultar campos de IOF**: Na função `toggleModoOperacao()`, esconder as divs que contêm "Você Incorre Custo IOF?" e "Cobrar IOF do Cliente?" quando a modalidade for Empréstimo.
- **Enviar `valor_emprestimo` para o backend**: Modificar a construção do objeto `data` nas chamadas JavaScript (tanto para cálculo quanto para registro) para incluir `valor_emprestimo` quando a operação for empréstimo.

### 2. Ajustes no Motor de Cálculo (`calculate.php` e `registrar_operacao.php`)
- Ler as variáveis `tipoOperacao` e `valor_emprestimo` do payload.
- Se `tipoOperacao === 'emprestimo'`:
  - Forçar as flags `$cobrarIOF = false` e `$incorreIOF = false`.
  - Após o loop de processamento das parcelas, reescrever os totais para refletirem o empréstimo:
    - `$totalLiquidoPago = $valorEmprestimo;`
    - `$totalPresente = $valorEmprestimo;`
    - `$totalLucroLiquido = $totalOriginal - $valorEmprestimo;`
  - Isso garante que a UI e os registros mostrem exatamente o valor que o cliente tomou emprestado.

### 3. Adaptação do PDF do Cliente (`export_pdf_cliente.php`)
- Recuperar as variáveis `tipoOperacao` e `valorEmprestimo` (enviadas via POST).
- Aplicar a mesma lógica matemática do backend (ignorar IOF e cravar o valor líquido total no `valorEmprestimo` se aplicável).
- **Ajustes Visuais no PDF (Condicionais ao Empréstimo)**:
  - Título principal passa de "Resumo da Operação de Desconto" para "Simulação de Empréstimo".
  - O label "Cedente" muda para "Tomador de Empréstimo (Sacado)".
  - O label "Taxa de Desconto Nominal" muda para "Taxa de Juros".
  - O título "Detalhamento dos Títulos" muda para "Parcelas do Empréstimo".
  - Na tabela do PDF, a coluna "Valor Líquido" é removida ou ignorada (apresentando apenas Valor Original, Vencimento, Sacado e Dias), redistribuindo as larguras das colunas.

### 4. Adaptação do PDF Completo (`export_pdf.php`)
- Replicar a mesma lógica matemática e de nomenclatura acima no script principal de exportação para garantir a coerência caso o usuário gere o PDF de Análise Completa.

## Decisões e Premissas
- A melhor forma de garantir o "Total Líquido Pago" exato sem reescrever a complexa engenharia reversa de dias da Tabela Price no PHP é simplesmente adotar o valor inputado (`valorEmprestimo`) como o principal/valor presente da operação. O lucro da operação será simplesmente o valor total a receber (soma das parcelas) menos o valor emprestado.
- Para o PDF, a ocultação de "Valor Líquido Pago" nas linhas reflete a mesma decisão tomada anteriormente na UI.

## Passos de Verificação
- Criar uma simulação de Empréstimo de R$ 10.000 e confirmar que "Total Líquido Pago" e "Total Vl. Presente" exibem exatamente R$ 10.000,00, sem aplicar IOF.
- Clicar em "PDF Simulação Cliente" e verificar se o arquivo abre com a nova nomenclatura e se a tabela de parcelas omite o valor líquido por linha.