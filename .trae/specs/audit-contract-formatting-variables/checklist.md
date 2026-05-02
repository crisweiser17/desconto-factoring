# Checklist

- [x] Os underlines dentro de instâncias Mustache (ex: `{{operacao.valor_principal}}`) não possuem barras invertidas (`\`) nos contratos base.
- [x] O hardcode `ACM EMPRESA SIMPLES DE CRÉDITO LTDA` foi removido e trocado por variáveis em `03_template_nota_promissoria.md`.
- [x] A emissão do contrato renderiza `[NÃO INFORMADO]` para propriedades vazias ao invés de fragmentos ilegíveis em `02a_template_mutuo_simples.md` (como testado via payload ou código da api_contratos.php).
- [x] O arquivo `01_template_antecipacao_recebiveis.md` será restaurado de um backup do Git logo antes de rodar os scripts, pois foi zerado. (Nota: Essa instrução foi adicionada durante a análise de escopo caso necessário).
- [x] A lógica em `api_contratos.php` foi testada ou revisada e reflete com segurança o preenchimento apropriado para Devedores PJ com atributos em branco.
