<?php
$tasks = file_get_contents('.trae/specs/unify-pj-registration/tasks.md');
$tasks = str_replace('- [ ] Task 1:', '- [x] Task 1:', $tasks);
$tasks = str_replace('- [ ] SubTask 1.1:', '- [x] SubTask 1.1:', $tasks);
$tasks = str_replace('- [ ] SubTask 1.2:', '- [x] SubTask 1.2:', $tasks);
$tasks = str_replace('- [ ] SubTask 1.3:', '- [x] SubTask 1.3:', $tasks);
$tasks = str_replace('- [ ] Task 2:', '- [x] Task 2:', $tasks);
$tasks = str_replace('- [ ] SubTask 2.1:', '- [x] SubTask 2.1:', $tasks);
$tasks = str_replace('- [ ] SubTask 2.2:', '- [x] SubTask 2.2:', $tasks);
$tasks = str_replace('- [ ] SubTask 2.3:', '- [x] SubTask 2.3:', $tasks);
$tasks = str_replace('- [ ] SubTask 2.4:', '- [x] SubTask 2.4:', $tasks);
file_put_contents('.trae/specs/unify-pj-registration/tasks.md', $tasks);

$checklist = file_get_contents('.trae/specs/unify-pj-registration/checklist.md');
$checklist = preg_replace('/- \[ \] Tabela unificada.*?\n/', "- [x] Tabela unificada de banco de dados para Pessoas Jurídicas criada/adaptada.\n", $checklist);
$checklist = preg_replace('/- \[ \] Dados antigos de cedentes.*?\n/', "- [x] Dados antigos de cedentes e sacados migrados para a nova estrutura unificada (se aplicável).\n", $checklist);
$checklist = preg_replace('/- \[ \] Página de cadastro unificada.*?\n/', "- [x] Página de cadastro unificada funcional, permitindo cadastrar PJ com Dados da Empresa, Endereço, Sócios, Representante Legal e Dados Bancários.\n", $checklist);
$checklist = preg_replace('/- \[ \] Cadastro de Pessoa Física.*?\n/', "- [x] Cadastro de Pessoa Física (PF) totalmente removido do fluxo principal.\n", $checklist);
$checklist = preg_replace('/- \[ \] Menus e links antigos.*?\n/', "- [x] Menus e links antigos (Cedentes, Sacados) substituídos por \"Clientes\" (ou equivalente) na interface.\n", $checklist);
file_put_contents('.trae/specs/unify-pj-registration/checklist.md', $checklist);
echo "Docs updated.\n";
