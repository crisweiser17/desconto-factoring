# Tasks
- [x] Task 1: Levantar o mapeamento de campos faltantes nas visualizações de sacados e cedentes
  - [x] Comparar `visualizar_sacado.php` com `form_sacado.php` para identificar campos persistidos e não exibidos
  - [x] Comparar `visualizar_cedente.php` com `form_cedente.php` para identificar campos persistidos e não exibidos
- [x] Task 2: Atualizar `visualizar_sacado.php` para exibir os campos adicionais de cadastro
  - [x] Exibir informações complementares de contato e dados bancários com os nomes corretos dos campos
  - [x] Exibir dados de representante e de cônjuge quando existirem
  - [x] Ajustar exibição condicional de sócios e rótulos conforme `tipo_pessoa`
- [x] Task 3: Atualizar `visualizar_cedente.php` para exibir os campos adicionais de cadastro
  - [x] Corrigir o bloco de dados bancários para ler os campos salvos no cadastro
  - [x] Exibir campos adicionais como WhatsApp e dados de cônjuge quando existirem
  - [x] Ajustar exibição condicional de sócios e rótulos conforme `tipo_pessoa`
- [x] Task 4: Validar as telas atualizadas
  - [x] Executar verificação de sintaxe PHP nos arquivos alterados
  - [x] Abrir preview local e conferir as páginas de visualização de sacado e cedente no navegador
  - [x] Revisar diagnósticos e corrigir eventuais erros simples introduzidos pela mudança

# Task Dependencies
- Task 2 depends on Task 1
- Task 3 depends on Task 1
- Task 4 depends on Task 2
- Task 4 depends on Task 3
