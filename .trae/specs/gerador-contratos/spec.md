# Gerador de Contratos Spec

## Por que
A ACM Empresa Simples de Crédito LTDA precisa de um módulo para gerar automaticamente contratos em PDF (Mútuo, Nota Promissória, Contrato-Mãe de Cessão e Borderô) baseados nas operações cadastradas. Isso evitará trabalho manual, garantirá que as regras regulatórias (LC 167/2019) sejam cumpridas, e registrará todos os contratos emitidos no sistema.

## O que muda
- Novas tabelas no banco de dados para templates, contratos gerados, contratos-mãe, veículos, garantidores e testemunhas.
- Alterações nas tabelas `clientes` e `operacoes` para comportar novos metadados financeiros e cadastrais.
- Instalação de dependências do Composer (`mpdf`, `mustache`, `parsedown`) para renderizar os templates Markdown e gerar os PDFs.
- Nova estrutura orientada a objetos (Services, Validators, Renderers) adaptada para o PHP puro (sem framework Laravel), mas mantendo a organização de pastas (`app/Modules/Contracts/...`).
- Criação de uma API endpoint (`api_gerar_contratos.php`) para o Frontend chamar a geração e fazer o download dos arquivos.
- **BREAKING**: Operações de EMPRÉSTIMO não poderão ser feitas para Pessoas Físicas (PF) sem CNPJ MEI, nem para clientes fora do porte MEI/ME/EPP (Regra LC 167/2019).
- **BREAKING**: Operações de DESCONTO não poderão ter o Sacado igual ao Cedente.

## Impacto
- Affected specs: `detalhes_operacao.php` (para adicionar o botão "Gerar Contratos e Documentos").
- Affected code:
  - Novo diretório: `app/Modules/Contracts/`
  - Novo diretório de armazenamento: `storage/app/contratos/`
  - Dependências (`composer.json`, `vendor/`)
  - Banco de Dados (`setup_contracts.php`)

## ADDED Requirements
### Requirement: Regra 1 - Documentos por Tipo de Operação
O sistema DEVE gerar:
- EMPRÉSTIMO: Contrato de Mútuo + Nota Promissória.
- DESCONTO: Contrato-Mãe de Cessão (apenas na 1ª vez) + Borderô (sempre).

### Requirement: Regra 2 - Validação de Tomador (Mútuo)
O sistema DEVE bloquear EMPRÉSTIMOS para Pessoa Física sem CNPJ e para portes diferentes de MEI, ME e EPP.

### Requirement: Regra 3 - Validação de Sacado (Desconto)
O sistema DEVE bloquear DESCONTOS sem recebíveis ou onde o Sacado é o próprio Cedente.

### Requirement: Regra 4 - Contrato-Mãe Único
O sistema DEVE verificar a tabela `master_cession_contracts`. Se não existir um contrato-mãe ativo para o cliente, gerar um novo. Se existir, gerar apenas o Borderô.

#### Scenario: Sucesso na geração de Mútuo
- **WHEN** operador clica em "Gerar Contratos" em uma operação de Empréstimo de um MEI.
- **THEN** o sistema valida as regras, compila o HTML via Mustache/Markdown, gera os PDFs via mPDF, salva os metadados no banco e retorna os links de download.
