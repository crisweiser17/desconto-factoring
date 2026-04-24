# Tasks
- [x] Task 1: Atualizar a listagem de recebíveis no menu "Associar a".
  - [x] SubTask 1.1: Localizar a seção `<select id="anotacaoRecebivelId">` em `detalhes_operacao.php`.
  - [x] SubTask 1.2: Modificar a formatação do `<option>` para incluir `formatHtmlDate($r['data_vencimento'])` e `formatHtmlCurrency($r['valor_original'])`.
  - [x] SubTask 1.3: Assegurar que os helpers de formatação estejam disponíveis ou processá-los com PHP nativo antes da exibição.
- [x] Task 2: Validar o layout do menu dropdown.
  - [x] SubTask 2.1: Testar o modal para confirmar se os dados de Vencimento e Valor aparecem corretamente na lista.

# Task Dependencies
- Task 2 depende da Task 1.