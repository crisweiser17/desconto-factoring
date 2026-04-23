# Plano de Implementação: Refinamento de Empréstimos e Correção de PDF

## Resumo
Ajustar a interface de simulação para esconder campos irrelevantes para empréstimos ("Tipo de Pagamento", "Notificar Sacado", "Total IOF Calc."), adicionar suporte para informações de "Garantia", e corrigir os erros de função depreciada (`utf8_decode`) no PDF de análise completa que estão corrompendo a geração do arquivo.

## Análise do Estado Atual
- Ao selecionar "Empréstimo", o "Tipo de Pagamento" e "Notificar Sacado" continuam visíveis, mas não fazem sentido nesse contexto.
- O quadro de resultados mostra "Total IOF Calc.", o que é confuso pois o IOF foi desativado para empréstimos.
- Não há campo no banco de dados ou na tela para indicar se o empréstimo possui garantia.
- A exportação do PDF completo (`export_pdf.php`) está falhando no PHP 8.2+ porque usa `utf8_decode()`, o que gera "Deprecated" warnings, corrompendo o stream do FPDF e causando "Some data has already been output".

## Alterações Propostas

### 1. Banco de Dados
- Criar e executar um script SQL (`adicionar_garantia_operacao.sql`) para adicionar duas novas colunas na tabela `operacoes`:
  - `tem_garantia` (TINYINT/BOOLEAN, padrão 0)
  - `descricao_garantia` (TEXT, aceita NULL)

### 2. Frontend (`index.php`)
- **Novos Campos de Garantia**: Adicionar um select "Possui Garantia?" e um input de texto "Descrição da Garantia" dentro do bloco `emprestimoParamsSection`.
- **Upload de Garantias**: Atualizar o título da seção de upload (`arquivosSection`) de "Anexar Documentos" para "Anexar Documentos (Garantias, Contratos, etc.)" para deixar claro que o usuário pode fazer o upload das fotos/docs da garantia ali após o registro.
- **Ocultar Campos Irrelevantes**:
  - Envolver "Tipo de Pagamento" em uma `div` com ID `containerTipoPagamento`.
  - Envolver a checkbox de notificação em uma `div` com ID `containerNotificarSacado`.
  - Adicionar ID `containerResTotalIOF` na coluna do "Total IOF Calc.".
  - Na função `toggleModoOperacao()`, esconder esses três containers quando a operação for "Empréstimo".

### 3. Backend (`registrar_operacao.php`)
- Capturar `tem_garantia` e `descricao_garantia` do payload (JSON).
- Atualizar o `INSERT` na tabela `operacoes` para salvar esses dois novos campos.

### 4. Correção do PDF (`export_pdf.php`)
- Substituir todas as chamadas `utf8_decode($text)` por uma nova função customizada `pdfEncodeText($text)` (que utiliza `mb_convert_encoding($text, 'ISO-8859-1', 'UTF-8')`).
- Essa mesma estratégia já foi aplicada e funciona no `export_pdf_cliente.php`. Isso resolverá os warnings de depreciação e o erro fatal do FPDF.
- Adicionar a exibição da "Garantia" na seção "Parâmetros da Operação" do PDF, caso seja um empréstimo.

## Decisões e Premissas
- O sistema de upload de arquivos já existente é robusto e está vinculado à operação (ID da operação). Portanto, não precisamos criar um sistema de upload separado para garantias; basta orientar o usuário a usar o uploader padrão após o registro.
- O campo "Notificar Sacado" só faz sentido em antecipação de títulos de terceiros. Empréstimos são diretos, então ocultar a notificação previne o envio de e-mails indevidos.

## Passos de Verificação
- Alternar entre "Antecipação" e "Empréstimo" na tela e verificar se "Tipo de Pagamento", "Notificar Sacado" e "Total IOF Calc." somem e aparecem corretamente.
- Preencher um empréstimo com garantia, registrar e verificar no banco de dados se `tem_garantia` e `descricao_garantia` foram salvos.
- Clicar em "PDF Análise Completa" e confirmar que o download funciona sem erros de PHP.