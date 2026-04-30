# Update New Contract Templates Spec

## Why
O departamento jurídico atualizou os templates de contratos (agora na pasta raiz de `_contratos/`) e moveu os antigos para `_contratos/antigos/`. O sistema precisa ser atualizado para carregar os novos arquivos de acordo com o tipo de operação (Antecipação ou Empréstimo) e as condições de garantia (com/sem veículo, com/sem avalista). Além disso, para todas as operações de empréstimo (mútuo), o sistema deve gerar e anexar obrigatoriamente a Nota Promissória ao final do contrato.

## What Changes
- Atualizar a lógica de seleção de templates no `api_contratos.php` para usar os novos arquivos (01, 02a, 02b, 02c, 02d).
- Anexar automaticamente o conteúdo do `03_template_nota_promissoria.md` ao final dos contratos de empréstimo, separando com uma quebra de página.
- Adicionar lógica com Expressão Regular (`preg_replace`) para remover os blocos de metadados dos novos templates markdown (instruções até a linha divisória `---`) antes da renderização.
- Popular as variáveis do objeto `np` (Nota Promissória) no array `$data` preparado pelo Mustache.

## Impact
- Affected specs: `update-contract-template-selection` (substituído por este)
- Affected code: `api_contratos.php`

## MODIFIED Requirements
### Requirement: Geração de Contratos
O sistema DEVE selecionar dinamicamente o template correto baseado na natureza da operação e suas garantias.
- **Cessão de Crédito**: Usa `01_template_antecipacao_recebiveis.md`
- **Empréstimo**: Usa variações do template 02 (a, b, c, d) baseado na presença de Garantia Real (Veículo) e Avalista.

## ADDED Requirements
### Requirement: Geração de Nota Promissória Acoplada
O sistema DEVE concatenar a nota promissória em operações de empréstimo.
- **WHEN** a operação é de mútuo (empréstimo)
- **THEN** o sistema anexa o `03_template_nota_promissoria.md` ao final do documento
- **AND** popula `np.numero`, `np.vencimento` (data da última parcela) e `np.data_vencimento_extenso`.

### Requirement: Limpeza de Metadados
O sistema DEVE remover as instruções de cabeçalho dos arquivos Markdown.
- **WHEN** o arquivo é lido
- **THEN** remove todo o texto do início até a primeira linha contendo apenas `---`.