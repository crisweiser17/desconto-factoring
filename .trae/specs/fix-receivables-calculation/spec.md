# Fix Receivables Calculation Spec

## Por que (Why)
O cálculo de antecipação de recebíveis deixou de funcionar porque o arquivo `funcoes_lucro.php` foi erroneamente deletado durante uma tarefa anterior de refatoração de relatórios (`update-reports-loan-support`). Este arquivo não era apenas para relatórios, mas continha funções centrais para o backend da aplicação, o que estava causando um Erro Fatal (HTTP 500) no script `calculate.php` e travando a interface ao clicar no botão "Calcular Totais".

## O que muda (What Changes)
- **Restauração de Arquivo:** O arquivo `funcoes_lucro.php` foi restaurado a partir do histórico do Git para o seu estado original.
- **Prevenção:** Documentar que o arquivo é uma dependência essencial de `calculate.php`, `registrar_operacao.php` e `funcoes_compensacao.php`.

## Impacto (Impact)
- Especificações afetadas: Fluxo de Nova Simulação (Antecipação e Empréstimo).
- Código afetado: `funcoes_lucro.php` (restaurado).

## Requisitos ADICIONADOS (ADDED Requirements)
### Requisito: Correção do Erro 500 na Calculadora
O sistema DEVE processar corretamente as requisições AJAX para `calculate.php` sem falhar por dependências ausentes.

#### Cenário: Calculando a Antecipação com Sucesso
- **QUANDO** o usuário preenche os campos de Antecipação de Recebíveis e clica em "Calcular Totais"
- **ENTÃO** o sistema acessa `calculate.php`, que por sua vez importa `funcoes_lucro.php` com sucesso e retorna o JSON com os cálculos.
