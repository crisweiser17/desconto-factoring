# Fix Template Variables Spec

## Why
As variáveis dos templates de documentos (especificamente do "bordero") não estão sendo preenchidas corretamente. O sistema utiliza a sintaxe do Mustache com variáveis como `{{#cedente.pessoa_juridica}}`, `{{cedente.razao_social}}`, `{{contrato_mae.id}}`, entre outras, no arquivo `_contratos/03_template_cessao_bordero.md`. No entanto, o arquivo `api_contratos.php` responsável por fornecer os dados ao Mustache envia apenas as chaves `credor`, `devedor`, `avalista`, `operacao`, `veiculo`, etc. Como o array de dados não contém as chaves `cedente`, `contrato_mae` ou `bordero`, o template renderiza esses campos em branco.

Temos todos os dados necessários no banco de dados e eles já são buscados na query (em `$operacao` via JOIN com `cedentes`), o que falta é apenas o mapeamento correto desses dados para a estrutura que o template espera.

## What Changes
- Adicionar o objeto `cedente` ao array `$data` no arquivo `api_contratos.php`.
- O objeto `cedente` deve conter a chave `pessoa_juridica` como booleano (verdadeiro se `tipo_pessoa === 'PJ'`, falso se `PF`).
- Mapear os dados de PJ (`razao_social`, `descricao_juridica`, `cnpj`, `endereco_completo`, `representante`) e de PF (`nome_completo`, `nacionalidade`, `estado_civil`, `profissao`, `rg`, `cpf`, `endereco_completo`) para dentro do objeto `cedente`.
- Adicionar os objetos `contrato_mae` e `bordero` ao array `$data`, preenchendo as variáveis de `id`, `local` e `data_extenso`.

## Impact
- Affected specs: Geração de Contratos (api_contratos.php)
- Affected code: `api_contratos.php` (linha 150-250)

## MODIFIED Requirements
### Requirement: Payload de Dados para Geração de Contrato
O payload de dados (`$data`) enviado ao Mustache DEVE incluir os objetos `cedente`, `contrato_mae` e `bordero` junto aos objetos já existentes (como `devedor`, `operacao`, etc.) para satisfazer plenamente os placeholders utilizados nos templates de Cessão/Borderô. O objeto `cedente` DEVE diferenciar entre `pessoa_juridica` (boolean) para satisfazer a renderização condicional do Mustache.