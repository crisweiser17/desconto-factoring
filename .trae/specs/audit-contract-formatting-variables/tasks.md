# Tasks

- [x] Task 1: Audit and fix Mustache variable escapes in all contract templates.
  - **Description**: Search and replace the invalid markdown escaped underscores (`\_`) inside Mustache variables (`{{...}}`) with standard underscores (`_`) across all active templates in the `_contratos/` folder (especially `02a` through `02f` and `03_template_nota_promissoria.md`).
  - **Prompt**: "Use SearchReplace to find `\_` inside `{{` and `}}` markers and change them to `_`. For example, `{{operacao.valor\_total\_devido}}` becomes `{{operacao.valor_total_devido}}`. Also fix `{{operacao.taxa\_juros\_mensal}}` and similar matches."

- [x] Task 2: Remove hardcoded Creditor information from Notice templates.
  - **Description**: Replace literal "ACM EMPRESA SIMPLES DE CRÉDITO LTDA" and its CNPJ / Address with `{{credor.razao_social}}`, `{{credor.documento}}` and `{{credor.endereco_completo}}`.
  - **Prompt**: "Review `_contratos/03_template_nota_promissoria.md` and replace hardcoded data block 'ACM EMPRESA...' at line ~19-21 with `{{credor.razao_social}}`, CNPJ `{{credor.documento}}`, Address `{{credor.endereco_completo}}`."

- [x] Task 3: Enhance `api_contratos.php` to prevent empty fields from breaking string layouts.
  - **Description**: Update the fallback approach in `api_contratos.php` so empty credentials don't render empty. Update the helper `normalizarCampoContrato()` or the usage payload block specifically so variables mapped internally (like `razao_social`, `cnpj`, `cpf`, `rg`, `endereco_completo`, `estado_civil`, `nacionalidade`, `profissao`) use `[NÃO INFORMADO]` instead of `''`.
  - **Prompt**: "In `api_contratos.php`, locate `montarParteContrato()` and change fallback values from `''` or `'-'` to `[NÃO INFORMADO]` where appropriate, or assure that the array payloads don't propagate raw empty strings that break the final PDF text format."

- [x] Task 4: Verify `ANEXO I` table mappings in Markdown templates.
  - **Description**: Ensure `{{#cronograma}}` array table structures don't have broken newlines or escapes that Parsedown misinterprets.
  - **Prompt**: "Validate `02a` to `02f` ensuring `{{#cronograma}}` and `{{/cronograma}}` have correct markdown spacing above and below them without breaking the table, strictly following our project core memory rules (`Mustache Iterators with Parsedown Tables`)."

# Task Dependencies
- Task 1 is independent.
- Task 2 is independent.
- Task 3 is independent.
- Task 4 depends on Task 1 (mustache syntax must be healthy first).
