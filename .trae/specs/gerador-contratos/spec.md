# Gerador de Contratos - Regras de Negócio e Templates Oficiais

## Por que
O usuário percebeu que a versão simplificada anterior não estava seguindo as regras estritas de negócio definidas no documento `01_regras_de_negocio.md` nem utilizando os templates Markdown oficiais (`02_template_contrato_mutuo.md`, `03_template_cessao_bordero.md`, `04_template_nota_promissoria.md`). É imperativo que o sistema obedeça à legislação da ESC (LC 167/2019) e preencha corretamente todas as variáveis jurídicas exigidas nos templates.

## O que muda
- **Banco de Dados (Schema Completo)**: 
  - Adição de campos em `clientes` (tipo_pessoa, porte, dados do representante).
  - Adição de campos em `operacoes` (natureza, num_parcelas, valor_parcela, etc.).
  - Criação das tabelas `operation_vehicles` (garantia de veículo) e `operation_guarantors` (avalistas).
- **Frontend (`detalhes_operacao.php`)**: 
  - O Modal de "Gerar Contratos" agora será um formulário completo. Nele o operador seleciona a **Natureza da Operação** (Empréstimo ou Desconto) e, se for Empréstimo, preenche/confirma os dados da Garantia (Veículo e Avalista).
- **Backend (`api_contratos.php`)**:
  - Implementação rigorosa das validações (ex: Bloquear Mútuo para PF sem CNPJ; Bloquear Desconto com Sacado = Cedente).
  - Mapeamento completo do array de dados (credor, devedor, operacao, avalista, veiculo) exigido pelos templates oficiais do mPDF/Mustache.
  - Geração dos arquivos corretos dependendo da natureza (Mútuo + NP para empréstimos; Cessão + Borderô para descontos).

## Impacto
- Affected code:
  - `setup_contratos_full.php` (Novo script de DB)
  - `detalhes_operacao.php` (Modal de geração expandido)
  - `api_contratos.php` (Lógica pesada de validação e renderização dos templates específicos)

## ADDED Requirements
### Requirement: Regras de Negócio Oficiais
O sistema DEVE seguir a Árvore de Decisão do arquivo `01_regras_de_negocio.md`:
- **Empréstimo**: Exige validação de porte (MEI/ME/EPP). Gera `02_template_contrato_mutuo.md` e `04_template_nota_promissoria.md`.
- **Desconto**: Exige sacado diferente de cedente. Gera `03_template_cessao_bordero.md`.

### Requirement: Preenchimento de Variáveis Mustache
O sistema DEVE processar os templates originais sem alterá-los, fornecendo um array estruturado (`credor`, `devedor`, `avalista`, `veiculo`, `operacao`) compatível com as tags `{{devedor.razao_social}}`, `{{veiculo.placa}}`, etc.