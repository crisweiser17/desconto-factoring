# Seleção Dinâmica de Template de Contrato Spec

## Why
Atualmente, o sistema utiliza um único template estático (`03_template_cessao_bordero.md`) para gerar os contratos de Desconto (Borderô). No entanto, o usuário preparou 4 versões distintas do contrato de Cessão (Borderô) que variam de acordo com a presença ou não de Garantia (Veículo) e de Avalista. O sistema precisa ser inteligente o suficiente para identificar quais opções foram preenchidas no formulário modal e carregar o arquivo `.md` correspondente para gerar o PDF, garantindo que as cláusulas jurídicas reflitam a realidade da operação.

## What Changes
- Atualização do arquivo `api_contratos.php` na rotina de determinação de qual template carregar.
- Inserção de lógica condicional para verificar:
  - Se a operação tem Avalista (nome do avalista preenchido no POST).
  - Se a operação tem Garantia/Veículo (`tem_garantia === 'Sim'` e chassi/marca preenchidos).
- Mapeamento dessas condições para os 4 novos arquivos disponibilizados:
  1. `contrato_1_com_veiculo_com_avalista.md`
  2. `contrato_2_sem_veiculo_sem_avalista.md`
  3. `contrato_3_com_veiculo_sem_avalista.md`
  4. `contrato_4_sem_veiculo_com_avalista.md`

## Impact
- Affected specs: Nenhuma
- Affected code: `api_contratos.php`

## ADDED Requirements
### Requirement: Seleção Condicional de Template de Desconto
O sistema SHALL carregar um template de contrato diferente baseado nos dados submetidos pelo usuário no momento da geração.

#### Scenario: Contrato com Veículo e Sem Avalista
- **WHEN** o usuário seleciona "Sim" para garantia, preenche os dados do veículo, mas deixa os dados do avalista em branco e clica em Gerar
- **THEN** o sistema deverá utilizar o template `contrato_3_com_veiculo_sem_avalista.md`.