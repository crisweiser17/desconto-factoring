# Fix Missing Contract Variables Spec

## Why
A imagem enviada pelo usuário mostra que o PDF gerado para operações de "Mútuo Feneratício" apresenta os campos do Mutuário (Devedor) em branco.
A investigação revelou a raiz do problema:
1. Para operações do tipo "Empréstimo", o sistema cadastra o tomador do empréstimo como um "Sacado" (na tabela `recebiveis`) e define o campo `cedente_id` da tabela `operacoes` como `NULL`.
2. No arquivo `api_contratos.php`, a query que busca os dados da operação faz um `LEFT JOIN` com a tabela `cedentes` através de `cedente_id`. Como é nulo, todas as variáveis (`c.*`) retornam vazias.
3. Além disso, mesmo que a query busque na tabela `sacados`, esta tabela atualmente não possui as colunas referentes ao representante (`representante_nome`, `representante_cpf`, etc.) nem as colunas `porte` e `possui_cnpj_mei`, que foram previamente adicionadas apenas em `cedentes`.
4. A lógica que define `pessoa_juridica` (`$operacao['tipo_pessoa'] === 'PJ'`) é falha, pois no banco os valores são `JURIDICA` ou `FISICA`.
5. O documento principal do cliente fica armazenado na coluna `documento_principal`, enquanto `cnpj` e `cpf` frequentemente estão nulos.

## What Changes
- **Banco de Dados**: Criar script `update_sacados_columns.php` para adicionar as colunas `porte`, `possui_cnpj_mei` e todos os `representante_*` na tabela `sacados`.
- **Frontend (`form_sacado.php`)**: Adicionar no formulário de Sacados a aba/campos de "Dados do Representante" (Nome, CPF, RG, Nacionalidade, Estado Civil, Profissão, Endereço) e "Porte".
- **Backend (`salvar_sacado.php`)**: Atualizar a rotina de salvar (INSERT e UPDATE) para gravar as novas colunas.
- **Backend (`api_contratos.php`)**: 
  - Ajustar a query para buscar os dados do `sacado` caso `tipo_operacao` seja `emprestimo`.
  - Tratar a busca do `cnpj`/`cpf` fazendo fallback para a coluna `documento_principal`.
  - Corrigir a lógica do booleano `pessoa_juridica` para aceitar `'JURIDICA'` ou `'PJ'`.

## Impact
- Affected specs: Cadastro de Sacados, Geração de Contratos.
- Affected code: `form_sacado.php`, `salvar_sacado.php`, `api_contratos.php`.

## MODIFIED Requirements
### Requirement: Geração de Contratos de Empréstimo
O sistema DEVE ser capaz de gerar contratos preenchidos corretamente mesmo quando a operação for de "Empréstimo" (Mutuário = Sacado). A tabela de Sacados e a tela de edição de Sacados DEVEM permitir o cadastro do Representante Legal do sacado, preenchendo todos os placeholders exigidos pelo motor Mustache nos templates Markdown.