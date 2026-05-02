# Auditoria e Correção de Formatação de Contratos Spec

## Why
Os contratos gerados estão apresentando múltiplos erros de formatação. O principal motivo dos valores monetários não aparecerem é que os arquivos Markdown estão usando escape de underline (`\_`) dentro das variáveis Mustache (ex: `{{operacao.valor\_total\_devido}}`), o que impede a correspondência com as propriedades no PHP (`valor_total_devido`). Além disso, existem dados de qualificação hardcoded (como o nome da empresa Credora), e a falta de dados preenchidos está gerando falhas visuais onde pontuações e formatações markdown ficam "órfãs" (ex: `**, ,**`). O objetivo é estabilizar completamente a geração dos contratos ativos.

## What Changes
- **Remoção de Escape Inválido no Mustache**: Substituir todos os `\_` por `_` dentro das chaves `{{ }}` nos templates `.md`. O escape de sublinhado só deve ser utilizado no contexto do Parsedown fora das tags Mustache.
- **Campos Dinâmicos para Mutuante/Credor**: Substituir as ocorrências hardcoded de `ACM EMPRESA SIMPLES DE CRÉDITO LTDA` e seu CNPJ por `{{credor.razao_social}}` e `{{credor.documento}}` nos templates (especialmente em `03_template_nota_promissoria.md`).
- **Resiliência a Campos Vazios (Fallback)**: Atualizar o arquivo `api_contratos.php` e a função de normalização para que os campos essenciais de qualificação (nome, CPF/CNPJ, endereço, nacionalidade, estado civil) recebam um fallback de `[NÃO INFORMADO]` quando estiverem em branco. Isso impede formatações Markdown quebradas ao montar o layout `**{{nome}}**, {{nacionalidade}}, ...`.
- **Validação de Variáveis de Valores e Arrays**: Assegurar que o cronograma e os totais de operação no Anexo I sejam impressos corretamente, corrigindo as definições e escopos da variável `{{#cronograma}}`.

## Impact
- Affected specs: `geracao_contratos_mutuo_feneraticio`, `emissao_nota_promissoria`
- Affected code: `_contratos/02a_template_mutuo_simples.md` ao `02f`, `_contratos/03_template_nota_promissoria.md` e o motor `api_contratos.php`.

## ADDED/MODIFIED Requirements

### Requirement: Variáveis Mustache Sanetizadas
**Quando** o sistema passar texto pelo Mustache, **então** ele não deve possuir tags de escape markdown (`\`) contidas no interior num identificador, para garantir a reflexão exata das variáveis injetadas.

### Requirement: Qualificação Resiliente
**Quando** dados opcionais de qualificação do cliente/credor não existirem no banco, **então** os identificadores no PDF devem exibir a tag clara de `[NÃO INFORMADO]` ao invés de deixarem grandes espaços em branco com pontuações adjacentes quebradas.

### Requirement: Resposta à Sugestão de Verificação (Extra)
O maior ofensor dessa instabilidade na "dança das variáveis" foi aplicar regras de formatação Parsedown a escopos Mustache. Como verificação sugerida: rodar um regex de validação nos arquivos `.md` buscando por `\{\{.*?\\_.*?\}\}`.
