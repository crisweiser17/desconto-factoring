# Update Form Cedente Bank Details Spec

## Why
Para garantir conformidade nas operações, a conta bancária do cedente deve obrigatoriamente pertencer à própria empresa (Razão Social e CNPJ), evitando repasses a contas de terceiros. O representante legal da operação pode ser qualquer um dos sócios cadastrados.

## What Changes
- **Dados Bancários**: Remover o campo de seleção de titularidade (`#titular_selecao`).
- **Dados Bancários**: Preencher automaticamente os campos "Titular da Conta" (`#conta_titular`) e "CPF/CNPJ do Titular" (`#conta_documento`) com os valores informados em "Nome / Razão Social" (`#empresa`) e "CNPJ/CPF" (`#documento_principal`), respectivamente.
- **Dados Bancários**: Definir os campos `#conta_titular` e `#conta_documento` como leitura apenas (`readonly`).
- **Representante**: Confirmar/reforçar que a seleção do Representante é feita a partir da lista de sócios cadastrados.

## Impact
- Affected specs: N/A
- Affected code: `form_cedente.php`

## ADDED Requirements
### Requirement: Auto-fill Bank Details
The system SHALL automatically mirror the company name and document into the bank account holder fields.

#### Scenario: User types company name and document
- **WHEN** user inputs "Razão Social" and "CNPJ"
- **THEN** the bank details "Titular da Conta" and "CPF/CNPJ do Titular" update automatically to match, and remain read-only.

## MODIFIED Requirements
### Requirement: Bank Account Holder Selection
**Reason**: Security and compliance require the bank account to be in the name of the Cedente.
**Migration**: The dynamic dropdown for choosing a bank account holder (between company or partners) will be removed, forcing the details to the Cedente entity.
