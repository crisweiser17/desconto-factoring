# Tasks

- [x] Task 1: Replace Markdown line breaks (`\`) with HTML line breaks (`<br />`) in body text of templates.
  - **Description**: Search through all `_contratos/*.md` files for trailing backslashes `\` at the end of lines or backslashes used to space lines, and replace them with standard HTML `<br />`. Special focus on `03_template_nota_promissoria.md` text where `CNPJ/MF sob o nº **{{credor.documento}}**,\` is appearing.
  - **Prompt**: "Find instances where a literal `\` is placed at the end of line or right before a line break, particularly in `_contratos/03_template_nota_promissoria.md` and `01_template_antecipacao_recebiveis.md`, and replace them with `<br />`."

- [x] Task 2: Redesign the Signature Blocks spaces and elements across templates.
  - **Description**: Target the `### MUTUANTE:`, `### MUTUÁRIO / DEVEDOR:`, `### CÔNJUGE:`, e `### TESTEMUNHAS:` sections across `01_template_...`, `02a_` to `02f_`, and `03_...`. Remove the `\ \_\_\_\_\_` constructs. Replace them with: `<br><br><br>____________________________________________________<br>` followed by the bold name and properties separated by `<br>`.
  - **Prompt**: "Write a PHP script to intelligently find signature blocks with `\_\_\_\_` or just trailing `\` in the signature section and replace them with a cleanly spaced HTML equivalent, adding at least `<br><br><br>` before the signature line block for Mutuante, Mutuário, Cônjuge, and Testemunhas."

- [x] Task 3: Align and normalize Witness (Testemunhas) fields.
  - **Description**: Ensure the witness fields also have plenty of space to sign and don't render backslashes or escaped underscores.
  - **Prompt**: "Search for `\ Nome:` and `\ CPF:` in the Testemunhas block and transform it into `<br><br>_________________________________________<br>Nome: _________________ CPF: ______________` to give enough signing area without printing `\`."

# Task Dependencies
- All tasks can be executed sequentially or parallel, primarily via a regex or PHP script replacing the formatting. Task 2 and 3 should be done after Task 1 ensures no floating `\` strings are left behind.