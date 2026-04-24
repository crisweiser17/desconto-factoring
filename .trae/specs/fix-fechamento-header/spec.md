# Fix Fechamento Header Spec

## Why
A página de fechamento mensal (`fechamento.php`) está com o menu/cabeçalho com fundo branco e letras brancas, tornando-o ilegível e inconsistente com o padrão escuro (dark) do restante do site. O problema ocorre porque o arquivo `style.css` (incluído apenas nessa página) possui uma regra global para a classe `.container` que adiciona `background-color: #ffffff`, o que afeta o container interno do componente `<nav>` do menu.

## What Changes
- Remover a inclusão do arquivo `style.css` da página `fechamento.php`.
- Omitir as propriedades globais que quebram o layout e aproveitar as classes utilitárias nativas do Bootstrap que já estão aplicadas nos componentes (cards, backgrounds) da página de fechamento.

## Impact
- Affected specs: Fechamento Mensal UI.
- Affected code: `fechamento.php`.

## MODIFIED Requirements
### Requirement: UI do Fechamento Mensal
O sistema SHALL exibir o menu de navegação de forma consistente (fundo escuro e texto claro) com o resto da aplicação, sem sofrer sobrescritas indesejadas de CSS em seus containers internos.

#### Scenario: Visualização Correta do Menu
- **WHEN** o usuário acessa a página de Fechamento Mensal
- **THEN** o menu superior deve ser exibido na cor escura padrão (`bg-dark`) e o container principal da página deve se adaptar ao background do sistema (`bg-light`), melhorando o contraste e o design da página.