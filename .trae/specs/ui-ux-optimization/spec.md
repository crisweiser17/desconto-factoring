# UI/UX Optimization Spec

## Why
The current interface, particularly the "Detalhes da Operação" screen, is functional but lacks a professional, modern look. The data is presented in a single, long list which makes it harder to scan. Additionally, other key screens like "Listar Recebíveis" and "Gerenciar Operações" need visual refinements to improve readability, spacing, and overall user experience.

## What Changes
- **Detalhes da Operação**: Reorganize the "Dados da Operação" section into a 2-column grid. Improve the alignment of monetary values and status badges.
- **Listar Recebíveis / Operações**:
  - Optimize the layout of filters (more compact).
  - Use modern pill badges for statuses.
  - Ensure consistent right-alignment for currency columns and center-alignment for dates.
  - Add visual enhancements to empty states.
  - Adjust action buttons to be more compact and visually grouped.

## Impact
- Affected specs: UI/UX presentation of operations and receivables.
- Affected code: `detalhes_operacao.php`, `listar_recebiveis.php`, `listar_operacoes.php`.

## MODIFIED Requirements
### Requirement: Detalhes da Operação Presentation
The system SHALL display the operation details in a professional 2-column layout to reduce vertical scrolling and improve scannability.

### Requirement: Data Grids Presentation
The system SHALL display tabular data with proper alignment (currencies to the right, dates to the center) and use clear, color-coded pill badges for status indicators.
