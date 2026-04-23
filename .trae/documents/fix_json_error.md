# Plano de Resolução: Erro na Inscrição de Empréstimo

## Resumo
Resolver o erro de JSON (`Unexpected token '<', "<br /> <b>"... is not valid JSON`) que impede o registro de operações de empréstimo. O erro é causado por um "PHP Warning: Undefined array key 'totalPresente'" no arquivo `registrar_operacao.php`, que corrompe a resposta JSON enviada para o frontend.

## Análise do Estado Atual
- Ao enviar o payload para `registrar_operacao.php`, o backend chama a função `calcularLucroOperacao()` (do arquivo `funcoes_lucro.php`) para recalcular os valores da operação antes de salvar no banco de dados.
- O arquivo `registrar_operacao.php` na linha 190 tenta acessar a chave `$resultadoCalculo['totalPresente']`.
- No entanto, a função `calcularLucroOperacao()` em `funcoes_lucro.php` não inicializa nem retorna a chave `totalPresente` no array `$resultado`.
- Além disso, para empréstimos, o `totalPresente` deve ser forçado a ser igual ao `valor_emprestimo`, assim como é feito no arquivo `calculate.php`.

## Alterações Propostas

1. **Editar `funcoes_lucro.php`:**
   - **O que:** Adicionar a chave `totalPresente` ao array de retorno da função `calcularLucroOperacao()`.
   - **Como:** 
     - Inicializar `'totalPresente' => 0` no array `$resultado`.
     - Acumular o valor no loop: `$resultado['totalPresente'] += $valorPresente;`.
   - **Por que:** Para garantir que a função centralizada de lucro retorne esse indicador, evitando o erro de chave indefinida (Undefined array key) no script que a consome.

2. **Editar `registrar_operacao.php`:**
   - **O que:** Forçar o `$totalPresente_recalc` a ser igual ao valor do empréstimo quando aplicável, mantendo o padrão do sistema.
   - **Como:** Adicionar `$totalPresente_recalc = $valorEmprestimo;` dentro do bloco `if ($tipoOperacao === 'emprestimo' && $valorEmprestimo > 0)`.
   - **Por que:** Manter a consistência com o script de simulação (`calculate.php`), cravando os valores presente e líquido no montante exato do empréstimo solicitado.

## Decisões e Premissas
- Assumimos que o campo `totalPresente` é necessário tanto no banco de dados (inserido na coluna `total_presente_calc` da tabela `operacoes`) quanto nos demais relatórios, justificando a sua inclusão direta no retorno de `calcularLucroOperacao()`.

## Passos de Verificação
- Após aplicar as correções, farei um teste simulando a submissão do formulário de empréstimo (ou acionando o endpoint `registrar_operacao.php` via CLI/Browser) para garantir que a resposta retorne um JSON válido e a operação seja inserida com sucesso no banco de dados.
