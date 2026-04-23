# Plano de Alteração: Cor do Ícone de Antecipação

## Resumo
Alterar a cor de fundo do ícone (badge) que representa operações de "Antecipação" na lista de operações. O objetivo é mudar de um fundo cinza claro (`bg-light`) para um fundo verde (`bg-success`), conforme solicitado pelo usuário.

## Análise do Estado Atual
No arquivo `listar_operacoes.php`, a coluna que exibe o tipo de operação utiliza um `span` do Bootstrap para formatar o ícone:
- **Empréstimo**: Usa `<span class="badge bg-warning text-dark" ...>`
- **Antecipação**: Usa `<span class="badge bg-light text-dark border" ...>`

## Alterações Propostas

1. **Editar `listar_operacoes.php`:**
   - **O que:** Modificar o código HTML do badge de Antecipação.
   - **Como:** Substituir a linha que contém `<span class="badge bg-light text-dark border" title="Antecipação"><i class="bi bi-arrow-return-left"></i></span>` por `<span class="badge bg-success text-white" title="Antecipação"><i class="bi bi-arrow-return-left"></i></span>`.
   - **Por que:** Para atender ao pedido de tornar o fundo verde. O uso da classe `bg-success` no Bootstrap aplica a cor verde, e `text-white` garante um bom contraste com o fundo escuro, removendo também a borda (`border`) e o texto escuro (`text-dark`) que eram usados no tema claro anterior.

## Premissas e Decisões
- O sistema utiliza classes utilitárias do Bootstrap 5.
- A cor "verde" no Bootstrap corresponde à classe `bg-success`.
- O contraste de legibilidade exige que o ícone interno seja claro quando o fundo é verde (portanto, usa-se `text-white` ou a cor padrão clara definida pelo `bg-success`).

## Passos de Verificação
- Verificar se a sintaxe PHP em `listar_operacoes.php` continua correta executando `php -l listar_operacoes.php`.
- Carregar a página "Lista de Operações" no navegador para confirmar que os ícones de operações de Antecipação agora aparecem verdes.