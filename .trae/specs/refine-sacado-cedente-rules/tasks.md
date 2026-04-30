# Tasks

- [x] Task 1: Limpeza Visual e Campo Porte
  - [x] SubTask 1.1: No `form_sacado.php`, localizar e remover a segunda seção (inferior) de "Sócios da Empresa" que está duplicada.
  - [x] SubTask 1.2: Em `form_sacado.php` e `form_cedente.php`, remover a opção "Pessoa Física" (value "PF") do `<select id="porte">`.
  - [x] SubTask 1.3: Em `form_sacado.php` e `form_cedente.php`, atualizar o JavaScript para ocultar/desabilitar a div que contém o campo "Porte" quando o "Tipo de Pessoa" for alterado para "FISICA", e exibi-lo quando for "JURIDICA".

- [x] Task 2: Refatoração da Seção do Representante
  - [x] SubTask 2.1: Em `form_sacado.php` e `form_cedente.php`, adicionar `readonly` nos inputs `representante_nome` e `representante_cpf`.
  - [x] SubTask 2.2: Adicionar uma div englobando os campos "RG, Nacionalidade, Estado Civil, Profissão, Endereço Completo" do Representante e escondê-la por padrão.
  - [x] SubTask 2.3: Atualizar o evento do `<select id="representante_socio_select">` no JavaScript: quando um sócio válido for escolhido, os inputs readonly são preenchidos e a div dos campos adicionais é exibida. Se voltar para a opção "Selecione...", a div é ocultada novamente e os campos são limpos.

- [x] Task 3: Refatoração da Titularidade Bancária
  - [x] SubTask 3.1: Em `form_sacado.php` e `form_cedente.php`, remover o `<select id="titular_selecao">` da seção de Dados Bancários, que antes permitia escolher um sócio.
  - [x] SubTask 3.2: Adicionar o atributo `readonly` nos campos `conta_titular` e `conta_documento`.
  - [x] SubTask 3.3: Implementar uma função JavaScript que monitore os campos "Razão Social/Nome" (`#empresa`) e "Documento Principal" (`#documento_principal`). Sempre que eles forem alterados, copiar seus valores para `conta_titular` e `conta_documento` automaticamente, independente do tipo de pessoa selecionado.

# Task Dependencies
- [Task 2] depends on [Task 1]
- [Task 3] depends on [Task 1]