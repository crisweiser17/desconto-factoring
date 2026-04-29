# Adicionar Opção de Assinatura do Cônjuge do Sacado/Devedor

## Why
Atualmente, o sistema exige e imprime a assinatura do cônjuge do Avalista (por força de lei), mas não tem uma opção para imprimir a assinatura do cônjuge do Sacado/Devedor principal. Embora não seja estritamente obrigatório por lei para o devedor em empréstimos de bens móveis (como veículos), é uma prática de mercado altamente recomendada para evitar futuras disputas judiciais sobre a alienação de bens comuns. O usuário solicitou que seja adicionada uma opção explícita no momento de gerar o contrato para decidir se o cônjuge assinará ou não.

## What Changes
- Adicionar uma nova opção no formulário/modal de geração de contratos (em `detalhes_operacao.php`) chamada "Cônjuge vai Assinar? (Recomendado)", com as opções "Sim" ou "Não".
- Capturar essa escolha em `api_contratos.php` ao gerar o contrato e incluí-la nos dados enviados para os templates (ex: `devedor.conjuge_assina`).
- Atualizar os templates de contratos de mútuo (e outros relevantes que usam o bloco de assinaturas do devedor) para exibir condicionalmente o bloco de assinatura do cônjuge do Devedor/Sacado caso a opção tenha sido marcada como "Sim" e ele seja casado/união estável.

## Impact
- Affected specs: N/A
- Affected code: 
  - `detalhes_operacao.php` (UI do modal)
  - `api_contratos.php` (Processamento do POST e repasse para o template)
  - `_contratos/02_template_contrato_mutuo.md` (e os outros `_contratos/contrato_*_com_veiculo*.md` / `_sem_veiculo*.md`)
  - `_contratos/variaveis_disponiveis.md` (Documentação da variável)

## ADDED Requirements
### Requirement: Opção de Assinatura do Cônjuge no Modal
A interface de geração de contrato DEVE exibir uma opção para o usuário escolher se o cônjuge do Sacado/Devedor vai assinar o contrato.

#### Scenario: Geração de contrato
- **WHEN** o usuário abre o modal "Gerar Contratos"
- **THEN** ele deve ver um campo de seleção "Cônjuge vai Assinar? (Recomendado)"

### Requirement: Impressão Condicional no Contrato
O sistema DEVE imprimir o bloco de assinatura para o cônjuge do Mutuário apenas se a opção estiver marcada como "Sim".

#### Scenario: Contrato com assinatura do cônjuge
- **WHEN** o usuário seleciona "Sim" para "Cônjuge vai Assinar?"
- **THEN** o PDF gerado deve conter o bloco de assinatura "CÔNJUGE DO MUTUÁRIO / DEVEDOR FIDUCIANTE (anuência)" com o nome e CPF do cônjuge.
