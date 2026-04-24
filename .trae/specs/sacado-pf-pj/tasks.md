# Tasks
- [x] Task 1: Corrigir validação no backend de `salvar_sacado.php` e `form_sacado.php`
  - [x] Garantir que o JS oculte ou exiba o `<div class="card">` dos Sócios dependendo do `tipo_pessoa` escolhido (em `form_sacado.php`).
  - [x] Opcionalmente, garantir que o formulário submeta corretamente `tipo_pessoa` no `salvar_sacado.php`. (Remover disabled, verificar campos).
- [x] Task 2: Implementar Nome e Versão do Aplicativo nas Configurações (`config.php`)
  - [x] Adicionar os campos "Nome do Aplicativo" (editável) e "Versão do Aplicativo" (readonly com valor "5.2 de abril de 2026") na interface.
  - [x] Ler e gravar `app_name` (default: "Factoring 5.1") e `app_version` (default: "5.2 de abril de 2026") no `config.json`.
- [x] Task 3: Atualizar o cabeçalho (`menu.php`)
  - [x] Alterar a exibição hardcoded ("Factoring 5.1" / "03.2026") para os valores recuperados do `config.json` através do `$currentConfig`.

# Task Dependencies
- Task 3 depende de Task 2.