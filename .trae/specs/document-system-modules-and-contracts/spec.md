# Document System Modules and Contracts Spec

## Why
O usuário solicitou uma documentação abrangente do sistema que inclua uma descrição completa de todos os módulos, um resumo do que cada módulo faz, os tipos de contrato existentes e uma lista completa de todas as variáveis presentes nos contratos. Essa documentação servirá como referência fundamental para o entendimento do projeto, manutenção do sistema e elaboração de novos templates de contrato.

## What Changes
- Criação de um documento de arquitetura e módulos (`docs/modulos_do_sistema.md` ou similar) descrevendo as partes principais do sistema (ex: Cedentes, Sacados, Operações, Recebimentos, Contratos, etc.).
- Criação ou expansão da documentação de contratos (`_contratos/tipos_e_variaveis.md` ou atualizando os existentes) com o resumo dos tipos de contrato suportados (Mútuo, Nota Promissória, Borderô, etc.).
- Listagem completa de todas as variáveis do sistema utilizadas para o preenchimento dos contratos (já extraídas da `api_contratos.php` e do contexto da aplicação).

## Impact
- Affected specs: Nenhuma especificação de código alterada.
- Affected code: Nenhum código-fonte será modificado. Apenas arquivos Markdown de documentação serão adicionados ao projeto.

## ADDED Requirements
### Requirement: Documentação do Sistema
O sistema SHALL possuir documentação interna atualizada sobre seus módulos principais.

#### Scenario: Consulta de Funcionalidades
- **WHEN** um desenvolvedor ou administrador precisar entender a finalidade de um módulo
- **THEN** ele poderá ler a documentação de módulos e obter um resumo de suas responsabilidades.

### Requirement: Documentação de Contratos
O sistema SHALL possuir um guia claro sobre os contratos suportados e suas variáveis.

#### Scenario: Criação de Template
- **WHEN** um usuário for criar ou editar um template de contrato
- **THEN** ele terá acesso à lista completa de variáveis disponíveis (Credor, Cedente, Operação, Avalista, etc.) e aos tipos de contrato em que se aplicam.