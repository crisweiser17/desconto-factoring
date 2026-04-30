# Unify PJ Registration Spec

## Why
Atualmente, o sistema separa o cadastro de "Cedentes" e "Sacados", e suporta Pessoa Física (PF) e Pessoa Jurídica (PJ). Para simplificar e evitar duplicidade de dados, o sistema passará a ter um cadastro único de "Pessoas Jurídicas" (Clientes/Empresas). Em qualquer operação (Desconto ou Empréstimo), tanto o cedente quanto o sacado serão selecionados a partir desta lista unificada. O cadastro de Pessoa Física (PF) não será mais necessário.

## What Changes
- **BREAKING**: Remover a separação física/lógica entre tabelas e formulários de `cedentes` e `sacados`. Eles serão unificados em uma única base de "Clientes" ou "Pessoas Jurídicas".
- **BREAKING**: Remover o suporte para cadastro de Pessoa Física (PF) no fluxo principal.
- Criar uma página unificada de Cadastro de Pessoa Jurídica.
- Campos obrigatórios para o cadastro de PJ:
  - Dados da Empresa (CNPJ, Razão Social, Nome Fantasia, etc.)
  - Endereço da Empresa
  - Sócios da Empresa (Adicionar Nome e CPF)
  - Dados do Representante Legal (Nome, CPF, RG, Nacionalidade, Estado Civil e Profissão)
  - Dados Bancários
- Não coletar dados do cônjuge no cadastro inicial. (O estado civil será coletado para o representante legal e futuramente para avalistas, mas sem obrigatoriedade de assinatura do cônjuge).
- Atualizar as telas de operações (Desconto e Empréstimo) para que os selects de Cedente e Sacado listem todas as Pessoas Jurídicas cadastradas.

## Impact
- Affected specs: Criação de Operações, Gerenciamento de Cedentes/Sacados, Geração de Contratos.
- Affected code: `cadastro_cedente.php`, `cadastro_sacado.php` (serão substituídos por um cadastro unificado), schema do banco de dados (mesclagem de cedentes/sacados), formulários de operação (`nova_operacao.php`, `novo_emprestimo.php`, etc.).

## ADDED Requirements
### Requirement: Cadastro Unificado de PJ
O sistema DEVE fornecer uma única página para registrar Pessoas Jurídicas. Esta entidade atuará como Cedente, Sacado ou ambos, dependendo da operação selecionada.

#### Scenario: Cadastrando uma nova PJ
- **WHEN** o usuário acessa a página de cadastro
- **THEN** ele visualiza os campos para Dados da Empresa, Endereço, Sócios, Representante Legal e Dados Bancários. Não há opção de PF. Não são exigidos dados do cônjuge.

## MODIFIED Requirements
### Requirement: Seleção de partes nas operações
O sistema DEVE listar todas as Pessoas Jurídicas cadastradas ao selecionar um Cedente ou Sacado em uma operação.

## REMOVED Requirements
### Requirement: Cadastro separado de Cedente/Sacado e PF
**Reason**: Simplificar o sistema, unificando os atores e removendo complexidade desnecessária (PF).
**Migration**: Dados existentes de cedentes e sacados devem ser migrados para a nova estrutura unificada (se aplicável), ou o sistema passará a usar a estrutura unificada daqui para frente.
