# Correção do Bloco de Assinatura do Cônjuge nos Contratos de Mútuo

## Why
O usuário seleciona a opção "Cônjuge vai Assinar? = Sim" na geração do contrato de mútuo, mas o espaço para assinatura do cônjuge não aparece no PDF gerado. Isso ocorre porque a variável `casado` não é definida no array `devedor` (montado em `montarParteContrato()`), fazendo com que a expressão `$parteContrato['casado'] && $conjuge_assina` sempre retorne `false`.

Além disso, o campo "Estado Civil" no cadastro de clientes é um text-input livre, o que permite valores inconsistentes. O modal de geração de contrato não desabilita a opção "Cônjuge vai Assinar?" quando o cliente não é casado/união estável.

## What Changes
- **form_cliente.php**: Alterar o campo `representante_estado_civil` de `<input type="text">` para `<select>` com opções padronizadas.
- **salvar_cliente.php**: Garantir que o valor do estado civil seja salvo corretamente (não requer mudanças estruturais, pois já usa bindParam).
- **api_contratos.php**: 
  - Adicionar a propriedade `casado` ao array retornado por `montarParteContrato()`, derivada do campo `estado_civil` do devedor.
  - A propriedade deve ser `true` quando `estado_civil` for "Casado(a)" ou "União Estável".
- **detalhes_operacao.php**: 
  - Adicionar lógica JavaScript para verificar o estado civil do cliente (devedor) ao abrir o modal de geração de contrato.
  - Se o estado civil NÃO for "Casado(a)" ou "União Estável", o select "Cônjuge vai Assinar?" deve ficar read-only com fundo cinza (`bg-light`) e valor forçado para "Não".
  - Se for casado/união estável, o select deve ficar habilitado normalmente.
- Verificar se os 6 templates de mútuo (`02a` a `02f`) já possuem o bloco `{{#devedor.conjuge_assina}}` correto.

## Impact
- Affected specs: `update-spouse-signature-block`, `add-spouse-signature-option-mutuo`
- Affected code:
  - `form_cliente.php` (campo estado civil do representante)
  - `salvar_cliente.php` (compatibilidade com select)
  - `api_contratos.php` (função `montarParteContrato` e lógica de `conjuge_assina`)
  - `detalhes_operacao.php` (modal de geração de contrato - lógica JS para habilitar/desabilitar "Cônjuge vai Assinar?")
  - `_contratos/02a_template_mutuo_simples.md`
  - `_contratos/02b_template_mutuo_com_aval.md`
  - `_contratos/02c_template_mutuo_com_garantia.md`
  - `_contratos/02d_template_mutuo_com_garantia_e_aval.md`
  - `_contratos/02e_template_mutuo_com_garantia_bem.md`
  - `_contratos/02f_template_mutuo_com_garantia_bem_e_aval.md`

## ADDED Requirements

### Requirement: Campo Estado Civil como Select no Cadastro de Clientes
O sistema DEVE apresentar o campo "Estado Civil" do representante como um `<select>` com as seguintes opções:
- Solteiro(a)
- Casado(a) / União Estável
- Separado(a)
- Divorciado(a)
- Viúvo(a)

#### Scenario: Cadastro de novo cliente
- **WHEN** o usuário acessa o formulário de cadastro/edição de cliente
- **THEN** o campo "Estado Civil" deve ser um dropdown select com as opções acima
- **AND** o valor salvo deve ser exatamente uma das opções acima

### Requirement: Definição da propriedade `casado` no Devedor
O sistema DEVE definir a propriedade `devedor.casado` como `true` quando o estado civil do devedor for "Casado(a)" ou "União Estável", e `false` caso contrário.

#### Scenario: Devedor casado
- **WHEN** o devedor tiver `estado_civil` igual a "Casado(a)" ou "Casado(a) / União Estável"
- **THEN** `devedor.casado` deve ser `true`
- **AND** se `conjuge_assina` também for `true`, o bloco de assinatura do cônjuge deve ser renderizado

#### Scenario: Devedor solteiro
- **WHEN** o devedor tiver `estado_civil` diferente de "Casado(a)" ou "União Estável"
- **THEN** `devedor.casado` deve ser `false`
- **AND** o bloco de assinatura do cônjuge NÃO deve ser renderizado, mesmo que `conjuge_assina` seja `true`

### Requirement: Controle de Habilitação do Campo "Cônjuge vai Assinar?"
O sistema DEVE controlar a habilitação do campo "Cônjuge vai Assinar?" no modal de geração de contrato com base no estado civil do devedor.

#### Scenario: Cliente não é casado/união estável
- **WHEN** o estado civil do devedor for diferente de "Casado(a)" ou "União Estável"
- **THEN** o select "Cônjuge vai Assinar?" deve estar desabilitado (`disabled`)
- **AND** deve ter fundo cinza (`bg-light`)
- **AND** o valor deve ser forçado para "Não"

#### Scenario: Cliente é casado/união estável
- **WHEN** o estado civil do devedor for "Casado(a)" ou "União Estável"
- **THEN** o select "Cônjuge vai Assinar?" deve estar habilitado normalmente
- **AND** o usuário pode selecionar "Sim" ou "Não"

## MODIFIED Requirements
### Requirement: Lógica de conjuge_assina
A lógica que define `$parteContrato['conjuge_assina']` em `api_contratos.php` deve continuar funcionando como está, mas agora com a garantia de que `$parteContrato['casado']` está sempre definida.

## REMOVED Requirements
Nenhum requisito será removido.
