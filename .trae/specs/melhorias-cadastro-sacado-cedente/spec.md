# Melhorias no Cadastro de Sacado e Cedente Spec

## Why
O processo de cadastro de sacados e cedentes precisa ser mais fluido e organizado. Atualmente, o preenchimento do titular da conta bancária e dos representantes é totalmente manual. Para Pessoa Física, o representante é a própria pessoa, gerando redundância. Para Pessoa Jurídica, o representante deve obrigatoriamente ser um dos sócios. Além disso, precisamos registrar "Anotações" gerais na empresa e padronizar o tipo de chave PIX. O cadastro de Cedente também deve suportar a escolha entre PF/PJ assim como Sacado.

## What Changes
- **Anotações**: Adição do campo `anotacoes` (TEXT) no banco de dados e nos formulários (na seção "Dados da Empresa") para Sacados e Cedentes.
- **Tipo de Chave PIX**: Adição do campo `conta_pix_tipo` (VARCHAR) no banco com as opções (CPF, CNPJ, EMAIL, TELEFONE, CHAVE ALEATÓRIA) e um select no formulário, ao lado do campo de chave PIX.
- **Titular da Conta Bancária**: Substituir a digitação manual de Nome e Documento do titular por um `<select>` dinâmico que permite escolher entre a própria "Empresa" ou um dos "Sócios" cadastrados. O select vai preencher automaticamente ou substituir os campos de titular.
- **Seção Sócios Movida**: Em ambos os formulários, a seção "Sócios" será movida para ficar logo abaixo de "Dados da Empresa".
- **Representante Dinâmico**:
  - Para Pessoa Física: Ocultar a seção "Dados do Representante" e, ao salvar, copiar os dados principais (nome, CPF, etc.) para os campos do representante.
  - Para Pessoa Jurídica: Na seção "Dados do Representante", exibir apenas um `<select>` listando os sócios cadastrados. O sócio escolhido terá seus dados copiados para os campos de representante no banco de dados.
- **Cedente PF/PJ**: Liberar a opção de escolher o "Tipo de Pessoa" no `form_cedente.php` e remover o valor fixo `JURIDICA` no `salvar_cedente.php`, permitindo Cedentes Pessoa Física, e adicionar a estrutura de representantes que já existe no Sacado.
- **BREAKING**: A estrutura do formulário mudará de campos de texto livre no representante para um dropdown (PJ) ou oculto (PF).

## Impact
- Affected specs: Cadastro de Sacados, Cadastro de Cedentes
- Affected code:
  - `form_sacado.php` e `form_cedente.php`
  - `salvar_sacado.php` e `salvar_cedente.php`
  - `visualizar_sacado.php` e `visualizar_cedente.php` (para mostrar anotações e tipo PIX)
  - Banco de Dados (tabelas `sacados` e `cedentes`)

## ADDED Requirements
### Requirement: Tipo de Chave PIX e Anotações
O sistema SHALL exibir um campo `Anotações` em "Dados da Empresa" e um campo dropdown para o `Tipo de Chave PIX`.

### Requirement: Cedente como PF/PJ
O sistema SHALL permitir que o Cedente seja cadastrado como Pessoa Física ou Jurídica.

## MODIFIED Requirements
### Requirement: Seleção do Titular da Conta Bancária
O sistema SHALL fornecer um dropdown listando a empresa e os sócios atuais para seleção rápida do titular da conta bancária, preenchendo automaticamente o nome e o documento.

### Requirement: Lógica de Representante PF/PJ
O sistema SHALL tratar o representante automaticamente:
- **WHEN** Tipo de Pessoa é Física, **THEN** ocultar a seção de representante e assumir que o representante é o próprio cadastrado.
- **WHEN** Tipo de Pessoa é Jurídica, **THEN** exigir a escolha do representante através de um dropdown com os sócios adicionados na seção de sócios.

## REMOVED Requirements
### Requirement: Digitação Livre de Representante
**Reason**: Representantes devem ser os próprios sócios (PJ) ou a própria pessoa (PF).
**Migration**: Ocultar os campos manuais e substituí-los por lógica dinâmica.