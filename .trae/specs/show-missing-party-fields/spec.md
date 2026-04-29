# Visualização Completa de Sacados e Cedentes Spec

## Why
As páginas de visualização de sacados e cedentes não exibem todos os campos já disponíveis nos formulários e persistidos no banco. Isso dificulta a conferência cadastral sem precisar entrar em modo de edição.

## What Changes
- Exibir nas páginas `visualizar_sacado.php` e `visualizar_cedente.php` os campos cadastrais disponíveis que hoje não aparecem.
- Alinhar os blocos de visualização com a lógica de `tipo_pessoa`, mostrando seções condicionais apenas quando fizer sentido.
- Exibir dados bancários completos de sacados e cedentes usando os nomes de campos já adotados nos formulários.
- Exibir dados complementares pessoais e de representante quando existirem, incluindo cônjuge e contato via WhatsApp.

## Impact
- Affected specs: cadastro de sacados, cadastro de cedentes, visualização cadastral
- Affected code: `visualizar_sacado.php`, `visualizar_cedente.php`

## ADDED Requirements
### Requirement: Visualização de sacado deve refletir os campos cadastrados
The system SHALL exibir na tela de visualização do sacado os campos relevantes já disponíveis no cadastro, respeitando o tipo de pessoa e omitindo apenas campos vazios quando a ausência da informação for mais clara que um placeholder.

#### Scenario: Sacado pessoa jurídica com dados completos
- **WHEN** o usuário abrir `visualizar_sacado.php?id=<id>` para um sacado pessoa jurídica com dados de contato, representante, conta bancária e sócios preenchidos
- **THEN** a tela deverá mostrar esses blocos de informação de forma legível
- **AND** os dados bancários deverão incluir titular, documento do titular, banco, agência, conta, tipo de conta e chave PIX
- **AND** os dados do representante deverão incluir nome, CPF, RG, nacionalidade, estado civil, profissão e endereço quando preenchidos

#### Scenario: Sacado pessoa física com dados pessoais
- **WHEN** o usuário abrir a visualização de um sacado pessoa física
- **THEN** a tela deverá apresentar rótulos compatíveis com pessoa física
- **AND** não deverá exibir a seção de sócios quando ela não se aplicar
- **AND** deverá exibir os dados de cônjuge quando o cadastro indicar casamento e houver informações preenchidas

### Requirement: Visualização de cedente deve refletir os campos cadastrados
The system SHALL exibir na tela de visualização do cedente os campos relevantes já disponíveis no cadastro, incluindo os dados bancários completos e os campos complementares hoje ausentes.

#### Scenario: Cedente com dados bancários e pessoais preenchidos
- **WHEN** o usuário abrir `visualizar_cedente.php?id=<id>` para um cedente com WhatsApp, dados do cônjuge e conta bancária preenchidos
- **THEN** a tela deverá mostrar essas informações sem exigir acesso ao formulário de edição
- **AND** os dados bancários deverão usar os mesmos conceitos do formulário, incluindo titular, documento do titular, banco, agência, conta, tipo de conta e chave PIX

#### Scenario: Cedente pessoa física
- **WHEN** o usuário abrir a visualização de um cedente pessoa física
- **THEN** a tela deverá ajustar os rótulos principais para pessoa física
- **AND** deverá evitar exibir seções societárias quando não se aplicarem

## MODIFIED Requirements
### Requirement: Telas de visualização cadastral
As telas de visualização cadastral de partes devem servir como espelho fiel dos dados salvos no cadastro, priorizando leitura e conferência sem necessidade de entrar em edição.

## REMOVED Requirements
