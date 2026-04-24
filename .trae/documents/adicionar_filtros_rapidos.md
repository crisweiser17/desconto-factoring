# Plano de Implementação: Filtros Rápidos e Destaque de Inadimplentes

## Análise do Estado Atual
A página `listar_recebiveis.php` exibe uma tabela de recebíveis e possui um formulário de filtros avançados (status, datas, tipo de pagamento/operação). 
Atualmente, não há destaque visual específico para recebíveis inadimplentes (vencidos e não recebidos), e não existem atalhos de "um clique" para as filtragens mais comuns.

## Mudanças Propostas

### 1. Destaque Visual para Inadimplentes (Fundo Vermelho Claro)
- **Arquivos afetados:** `listar_recebiveis.php` e `atualizar_status.php`
- **O que será feito:** 
  - Modificaremos a função `getTableRowClass($status)` para `getTableRowClass($status, $data_vencimento)`.
  - Se o `$status` não for um status de recebido/compensado E a `$data_vencimento` for menor que a data de hoje (`date('Y-m-d')`), a linha receberá a classe CSS `table-danger` (que aplica o fundo vermelho claro do Bootstrap) acompanhada de negrito.
  - Atualizaremos o `atualizar_status.php` para também buscar a `data_vencimento` no banco de dados e aplicar essa mesma lógica. Isso garante que, se você alterar o status de um recebível via AJAX (ex: de "Recebido" de volta para "Em Aberto"), ele ficará vermelho instantaneamente caso esteja vencido.

### 2. Criação dos Botões de Filtro Rápido
- **Arquivos afetados:** `listar_recebiveis.php` e `exportar_csv.php`
- **O que será feito:**
  - Adicionaremos um bloco com botões (estilo *pills* ou botões com contorno) logo acima do painel de "Filtros" avançados.
  - Implementaremos o parâmetro `quick_filter` na URL para gerenciar os atalhos.
  - Os filtros sugeridos e implementados serão:
    1. **Todos** (Remove o filtro rápido)
    2. **Inadimplentes** (Vencidos e não recebidos)
    3. **A Receber** (Não recebidos com vencimento >= hoje)
    4. **Recebidos** (Status Recebido, Compensado ou Totalmente Compensado)
    5. **Vencendo nos próx. 7 dias** (Não recebidos, vencendo entre hoje e +7 dias)
    6. *Sugestão Extra 1:* **Vencendo Hoje** (Ideal para a rotina diária de cobrança)
    7. *Sugestão Extra 2:* **Problemas** (Lista rápida de recebíveis sinalizados com problema)
  - Incluiremos a lógica SQL no PHP para adicionar as condições dinâmicas (`WHERE`) baseadas no filtro rápido escolhido.
  - Adicionaremos campos ocultos (`<input type="hidden" name="quick_filter">`) nos formulários de busca e filtros avançados, e incluiremos o filtro rápido nos links de paginação/ordenação, para que você não perca o filtro ao navegar ou pesquisar.
  - O arquivo `exportar_csv.php` será atualizado para também reconhecer o `quick_filter`, garantindo que o CSV gerado seja exatamente a lista que você está visualizando.

## Suposições e Decisões
- Os filtros rápidos atuarão *em conjunto* com a busca por texto. Ou seja, você poderá clicar em "Inadimplentes" e depois digitar o nome de um cliente na busca para ver apenas os inadimplentes daquele cliente.
- A cor `table-danger` do Bootstrap já é um vermelho claro suave que se encaixa bem no layout atual sem quebrar a legibilidade.

## Passos de Verificação
- [ ] Clicar em cada aba de filtro rápido e verificar se a listagem atualiza corretamente.
- [ ] Verificar visualmente se os recebíveis com data anterior a hoje e não recebidos estão com fundo vermelho.
- [ ] Testar a paginação, busca e ordenação enquanto um filtro rápido está ativo.
- [ ] Alterar o status de um recebível via botão na tabela (AJAX) e garantir que a cor da linha atualize de acordo com as novas regras.
- [ ] Gerar um arquivo `.csv` com um filtro rápido ativado e confirmar se os dados conferem.