# Refatoração de Cadastros de Sacados e Cedentes Spec

## Por quê
O usuário solicitou simplificar e corrigir o modelo de dados e as regras de negócio para os cadastros de Sacados e Cedentes:
1. **Sacados**: Devem permitir cadastro de Pessoa Física e Jurídica, mas sem a necessidade da flag confusa "Possui CNPJ MEI". Se for MEI, ele será cadastrado corretamente como Pessoa Jurídica com o porte "MEI".
2. **Cedentes**: Como operações de ESC exigem que o cliente (Cedente) seja Pessoa Jurídica (MEI, ME, EPP), o cadastro de Cedentes deve ser restrito exclusivamente a Pessoas Jurídicas. Além disso, é necessário adicionar o campo de "Porte" (que faltava) no cadastro de Cedente.

## O que muda
- **Sacados**:
  - Remoção do checkbox `possui_cnpj_mei` de todas as telas (`form_sacado.php`, `visualizar_sacado.php`).
- **Cedentes**:
  - Restrição para apenas Pessoa Jurídica (remoção do select `tipo_pessoa` e fixação em `JURIDICA`).
  - Adição do menu select de `Porte` (MEI, ME, EPP, Médio, Grande) em `form_cedente.php` e `visualizar_cedente.php`.
- **Backend & Banco de Dados**:
  - Remoção do processamento e da coluna `possui_cnpj_mei` das tabelas `sacados` e `cedentes` (via script de migração).
  - Atualização do script `salvar_cedente.php` para receber e salvar o `porte` e forçar `tipo_pessoa = 'JURIDICA'`.
  - Simplificação das regras em `api_contratos.php` (remoção de referências a `possui_cnpj_mei`).
- **Documentação**:
  - Atualização das regras de negócio no arquivo markdown (`_contratos/01_regras_de_negocio.md`).

## Impacto
- Afeta as telas de criação/edição e visualização de Sacados e Cedentes.
- Afeta a API de geração de contratos (`api_contratos.php`).
- Afeta o banco de dados (remoção de colunas, garantia da coluna `porte` em `cedentes`).

## REQUISITOS MODIFICADOS
### Requisito: Cadastro de Sacado
- **QUANDO** o usuário cadastrar um Sacado
- **ENTÃO** ele pode escolher entre Física ou Jurídica e selecionar o Porte. A opção "Possui CNPJ MEI" não deve existir.

### Requisito: Cadastro de Cedente
- **QUANDO** o usuário cadastrar um Cedente
- **ENTÃO** o sistema deve forçar que seja uma Pessoa Jurídica (CNPJ) e exigir a seleção do Porte (MEI, ME, EPP, etc). Não deve ser possível cadastrar Pessoa Física.