# Refine Sacado e Cedente Rules Spec

## Why
O comportamento de alguns campos nos formulários de cadastro de Sacado e Cedente precisa de refinamentos adicionais para garantir maior integridade dos dados e melhorar a experiência do usuário. Precisamos restringir opções de porte, forçar que os dados do representante e da conta bancária venham de fontes específicas sem permitir edição manual, além de corrigir uma duplicação visual da seção "Sócios da Empresa" no `form_sacado.php`.

## What Changes
- Ocultar o campo "Porte" se o tipo selecionado for "Pessoa Física" e remover a opção "Pessoa Física" da lista de portes.
- Tornar os campos "Nome" e "CPF" do Representante como `readonly`. O preenchimento só pode ocorrer automaticamente quando um Sócio é selecionado.
- Os demais campos do Representante (RG, Nacionalidade, Estado Civil, Profissão, Endereço Completo) só devem ficar visíveis e editáveis *após* a seleção de um Sócio no dropdown.
- Alterar a lógica do titular da conta bancária: 
  - Se Pessoa Jurídica: Forçar que o titular seja a "Razão Social" e o documento o "CNPJ". (Não deve ser possível escolher a conta pessoal de um sócio).
  - Se Pessoa Física: Forçar que o titular seja o "Nome" da pessoa e o documento o "CPF".
  - Os campos Titular da Conta e CPF/CNPJ do Titular devem ser preenchidos dinamicamente de acordo com os dados principais da empresa/pessoa e devem ser definidos como `readonly`.
- Corrigir duplicação: Remover a seção duplicada "Sócios da Empresa" na parte inferior do arquivo `form_sacado.php`.

## Impact
- Affected specs: Cadastro de Sacados, Cadastro de Cedentes.
- Affected code: `form_sacado.php` e `form_cedente.php`.

## ADDED Requirements
### Requirement: Exibição Dinâmica dos Detalhes do Representante
O sistema SHALL ocultar os campos adicionais do Representante (RG, Nacionalidade, etc.) até que um Sócio válido seja escolhido no select. Ao escolher, os campos Nome e CPF do representante serão auto-preenchidos e bloqueados (readonly).

## MODIFIED Requirements
### Requirement: Regras de Porte
O sistema SHALL exibir o campo "Porte" apenas para Pessoas Jurídicas e a opção de "Pessoa Física" não existirá mais no dropdown.

### Requirement: Regras de Conta Bancária
O sistema SHALL forçar a conta bancária para a própria entidade cadastrada (Razão Social/CNPJ para PJ e Nome/CPF para PF). A opção de utilizar conta de sócio para recebimento em operações de PJ é bloqueada.

## REMOVED Requirements
### Requirement: Seleção de Sócio para Titularidade Bancária
**Reason**: Contas de pessoa jurídica não devem usar a conta pessoal de um sócio como conta de recebimento padrão do cadastro.
**Migration**: Remover o `<select>` de seleção de titular e implementar preenchimento automático via JavaScript observando os campos principais do formulário.