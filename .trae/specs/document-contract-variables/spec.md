# Listagem de Variáveis de Contrato Spec

## Why
O usuário solicitou uma lista completa de todas as variáveis disponíveis no sistema para a geração de contratos (Contrato de Mútuo, Cessão com Borderô, Nota Promissória, etc.). Isso é fundamental para facilitar a criação, edição e manutenção de templates em formato Markdown utilizando a engine Mustache, evitando erros de formatação e ausência de dados.

## What Changes
- Criação de um documento descritivo `_contratos/variaveis_disponiveis.md` que listará estruturadamente todos os objetos e suas propriedades enviadas pela `api_contratos.php` para o Mustache.
- A lista abrangerá dados do Credor, Cedente, Devedor/Sacado, Avalista, Operação, Veículo, Testemunhas e Cronograma.

## Impact
- Affected specs: Nenhuma especificação de código alterada.
- Affected code: Nenhum código-fonte será modificado. Apenas adição de documentação na pasta `_contratos`.

## ADDED Requirements
### Requirement: Documentação de Variáveis de Template
O sistema SHALL possuir um arquivo de documentação claro que instrua o usuário ou administrador sobre quais variáveis Mustache podem ser utilizadas na elaboração dos templates de contrato.

#### Scenario: Consulta de Variáveis
- **WHEN** o usuário precisar adicionar uma nova cláusula no contrato e precisar do nome de uma variável
- **THEN** o usuário poderá consultar o arquivo `_contratos/variaveis_disponiveis.md` e encontrar a sintaxe correta (ex: `{{operacao.valor_principal}}`).
