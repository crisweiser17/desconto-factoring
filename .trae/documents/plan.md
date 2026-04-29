# Plano de Implementação

## Resumo
Este plano aborda três solicitações principais: corrigir o hiperlink do Cedente na tela de detalhes da operação, remover as linhas em branco na tabela de relação de títulos nos contratos gerados e aprimorar a validação e máscaras de e-mail e documentos (CPF/CNPJ) nos cadastros de Sacado e Cedente (tanto no frontend quanto no backend).

## Análise do Estado Atual
1. **Hyperlink do Cedente:** No arquivo `detalhes_operacao.php` (próximo à linha 502), o nome do Cedente está com um link apontando para `form_cedente.php` (tela de edição), quando o esperado seria apontar para o perfil (`visualizar_cedente.php`), mantendo o padrão já adotado para o Sacado.
2. **Linhas em branco na Relação de Títulos:** O arquivo markdown base para essa tabela (`_contratos/03_template_cessao_bordero.md`) define o loop de títulos (`{{#titulos}}`) como linhas vazias da tabela com `<br />`. O parser do Mustache aceita blocos puros sem quebrar o formato de tabela Markdown se as tags estiverem isoladas em linhas únicas.
3. **Validação e Máscaras (Sacado/Cedente):** 
    - Atualmente, as validações de CPF/CNPJ em `form_sacado.php` e `form_cedente.php` validam apenas a quantidade de dígitos. A validação real (cálculo dos dígitos verificadores) não está implementada no JS nem no PHP.
    - Alguns campos como `representante_cpf` e `conta_documento` carecem de inicialização completa de máscaras.
    - A validação de e-mail já existe parcialmente via regex no JS e `filter_var` no PHP, mas deve ser garantida e robustecida no momento do `submit`.

## Alterações Propostas

### 1. Ajuste de Hyperlink (detalhes_operacao.php)
- Substituir o link `<a href="form_cedente.php?id=...">` por `<a href="visualizar_cedente.php?id=...">` na exibição do Cedente/Tomador.

### 2. Correção na Relação de Títulos (_contratos/03_template_cessao_bordero.md)
- Modificar o template da tabela para retirar as linhas vazias de `<br />`. A nova estrutura será:
  ```markdown
  |       #      | Título     | Tipo     | Sacado (Nome)    | Sacado (CNPJ/CPF)     |    Data Emissão   |    Data Vencimento   | Valor de Face (R$) | Valor Presente (R$) |
  | :----------: | :--------- | :------- | :--------------- | :-------------------- | :---------------: | :------------------: | -----------------: | ------------------: |
  {{#titulos}}
  |   {{ordem}}  | {{numero}} | {{tipo}} | {{sacado\_nome}} | {{sacado\_documento}} | {{data\_emissao}} | {{data\_vencimento}} |    {{valor\_face}} | {{valor\_presente}} |
  {{/titulos}}
  ```

### 3. Máscaras e Validações nos Cadastros
- **`functions.php`**: Adicionar as funções `validaCPF($cpf)` e `validaCNPJ($cnpj)` contendo a lógica de cálculo de dígito verificador.
- **`salvar_sacado.php` e `salvar_cedente.php`**: Implementar a checagem no backend utilizando as novas funções de `functions.php` para o Documento Principal, além de estender a validação (quando preenchidos) para CPF do cônjuge, representante e sócios. Confirmar a validação do e-mail.
- **`form_sacado.php` e `form_cedente.php`**:
  - Adicionar as funções `isValidCPF(cpf)` e `isValidCNPJ(cnpj)` no JavaScript.
  - Atualizar o listener de `blur` e a verificação do `submit` do formulário para usar essas funções matemáticas em vez de checar apenas o `length`.
  - Garantir que todos os campos de CPF (Cônjuge, Representante, Sócios) e o `conta_documento` possuam a máscara correspondente, adicionando `$('.cpf-mask').inputmask(...)` e implementando a lógica dinâmica de máscara para o documento da conta (pode ser CPF ou CNPJ).

## Premissas e Decisões
- Alterar as tags `{{#titulos}}` para uma linha exclusiva funciona sem quebrar a tabela graças à maneira como o `Mustache_Engine` e o `Parsedown` são executados de forma sequencial no `api_contratos.php`.
- O código de validação JavaScript impedirá submissões de dados falsos, e o código backend garantirá segurança e integridade no banco de dados.

## Passos para Verificação
1. Navegar até `detalhes_operacao.php?id=59` e clicar no Cedente, certificando-se de que leva ao perfil (`visualizar_cedente.php`).
2. Gerar um novo contrato (ou realizar preview de impressão) usando o template `03_template_cessao_bordero.md` e verificar se a tabela não possui mais as linhas em branco.
3. Tentar cadastrar e editar um Sacado/Cedente com CPFs e CNPJs de formatos incorretos/inválidos e confirmar que o formulário acusa erro em tempo real.
4. Tentar submeter o formulário de cadastro ignorando o front-end e confirmar que o backend (PHP) rejeita.