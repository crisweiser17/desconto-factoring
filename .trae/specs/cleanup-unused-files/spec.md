# Limpeza de Arquivos Não Utilizados Spec

## Why
O repositório acumulou diversos arquivos temporários, scripts de migração já executados, backups, testes e ferramentas de debug que não fazem parte do sistema em produção. Esses arquivos poluem a base de código, dificultam a manutenção e aumentam o tempo de deploy.

## What Changes
- Criar pasta `_lixo/` para armazenar temporariamente arquivos não necessários
- Mover para `_lixo/` arquivos que não são necessários para:
  - O sistema rodar em produção
  - A instalação do sistema
  - A atualização do sistema
- Preservar arquivos ativos do sistema, templates de contrato, bibliotecas e configurações

## Impact
- Affected specs: N/A
- Affected code: Raiz do projeto e arquivos soltos

## ADDED Requirements
### Requirement: Identificação e Movimentação de Arquivos
O sistema de organização de arquivos SHALL mover arquivos não essenciais para `_lixo/`.

#### Scenario: Arquivos de backup e dumps
- **WHEN** existirem arquivos de backup SQL ou dumps antigos
- **THEN** eles devem ser movidos para `_lixo/`

#### Scenario: Scripts de migração já executados
- **WHEN** existirem scripts de migração/atualização que já foram aplicados
- **THEN** eles devem ser movidos para `_lixo/`

#### Scenario: Arquivos de teste e debug
- **WHEN** existirem arquivos de teste, debug ou diagnóstico
- **THEN** eles devem ser movidos para `_lixo/`

#### Scenario: Scripts utilitários de uso único
- **WHEN** existirem scripts Python/PHP criados para correções pontuais
- **THEN** eles devem ser movidos para `_lixo/`

#### Scenario: Backups de arquivos PHP
- **WHEN** existirem arquivos `.old`, `.backup_bootstrap`
- **THEN** eles devem ser movidos para `_lixo/`

## MODIFIED Requirements
### Requirement: Preservação de Arquivos Essenciais
O sistema SHALL manter na raiz apenas arquivos necessários para funcionamento, instalação ou atualização.

#### Scenario: Arquivos do sistema ativo
- **WHEN** o arquivo for parte do sistema em produção (páginas PHP, APIs, funções)
- **THEN** ele deve permanecer na raiz

#### Scenario: Arquivos de instalação
- **WHEN** o arquivo for necessário para instalação (`installer.php`, `db_connection.example.php`)
- **THEN** ele deve permanecer na raiz

#### Scenario: Arquivos de atualização ativa
- **WHEN** o arquivo for o script de atualização principal (`update.php`)
- **THEN** ele deve permanecer na raiz

#### Scenario: Templates e contratos
- **WHEN** o arquivo for template de contrato ou parte da pasta `_contratos/`
- **THEN** ele deve permanecer onde está

#### Scenario: Bibliotecas e vendor
- **WHEN** o arquivo for parte de `vendor/`, `fpdf/`
- **THEN** ele deve permanecer onde está

#### Scenario: Uploads e arquivos de usuário
- **WHEN** o arquivo estiver em `uploads/`
- **THEN** ele deve permanecer onde está
