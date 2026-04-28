# Update Contract Modal Fields Spec

## Why
O modal de "Gerar Contratos" atual exige o preenchimento de todos os campos de garantia (avalista e veículo) para operações de empréstimo, não oferece formatação visual (máscaras) para CPF/CNPJ, inclui dados de veículo excessivos e possui opções inválidas para o porte do cliente (MÉDIO e GRANDE) perante as regras da LC 167/2019. Precisamos tornar o preenchimento mais flexível (opção com/sem garantia), mais seguro e alinhado com a legislação.

*Observação sobre o Porte LTDA*: "LTDA" (Sociedade Limitada) é a *Natureza Jurídica* da empresa, não o seu *Porte*. Uma LTDA pode ter o porte de Microempresa (ME) ou Empresa de Pequeno Porte (EPP). Para a ESC (Empresa Simples de Crédito), a lei permite apenas operações com portes MEI, ME e EPP.

## What Changes
- **MODIFIED**: No modal, quando a natureza for `EMPRESTIMO`, exibir uma pergunta/toggle "Possui Garantia?". Se "Sim", exibir os campos do avalista e do veículo; se "Não", ocultá-los e remover a obrigatoriedade (`required`).
- **MODIFIED**: Adicionar máscara de CPF/CNPJ nos inputs correspondentes do modal (ex: CPF do avalista, CPF do cônjuge).
- **REMOVED**: Remover os campos `Município de Emplacamento`, `UF` e `Chassi` da seção de dados do Veículo no modal e no backend, pois o resto dos dados já o define bem.
- **REMOVED**: Remover as opções "MÉDIO" e "GRANDE" do campo "Porte do Cliente", mantendo apenas MEI, ME e EPP, pois são os únicos permitidos pela LC 167/2019. Adicionar um texto explicativo sobre "LTDA".

## Impact
- Affected code:
  - `detalhes_operacao.php` (Frontend: Modal HTML e JavaScript para exibição condicional e máscaras).
  - `api_contratos.php` (Backend: Remoção das variáveis antigas de veículo).

## ADDED Requirements
### Requirement: Máscaras de Entrada
O sistema DEVE aplicar automaticamente máscaras visuais (ex: `000.000.000-00` ou `00.000.000/0001-00`) aos campos de CPF e CNPJ inseridos no modal "Gerar Contratos" para evitar erros de digitação.

### Requirement: Toggle de Garantia
O sistema DEVE permitir a geração de contratos de Empréstimo sem garantias associadas, desde que o usuário selecione explicitamente "Não" na opção "Possui Garantia?".

#### Scenario: Empréstimo sem Garantia
- **WHEN** usuário seleciona Natureza "EMPRESTIMO"
- **THEN** exibe a opção "Possui Garantia?" (Sim/Não).
- **WHEN** usuário seleciona "Não"
- **THEN** as seções de "Dados do Avalista" e "Dados do Veículo" são ocultadas e seus campos deixam de ser obrigatórios para a geração do PDF.

## MODIFIED Requirements
### Requirement: Porte do Cliente
O dropdown de Porte do Cliente (`modalPorteCliente`) DEVE conter apenas "MEI", "ME" e "EPP".

## REMOVED Requirements
### Requirement: Dados Detalhados do Veículo
**Reason**: Município de emplacamento, UF e Chassi são informações burocráticas excessivas para a geração deste contrato, já que a Placa e Renavam definem o veículo.
**Migration**: Ocultar os campos no frontend e não processar estas variáveis no backend `api_contratos.php`.
