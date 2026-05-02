# Plano: installer2.php — Replicador para Servidor Online

## Contexto

Após diversas alterações no sistema (remoção de sacado, junção em clientes, novos contratos, etc.), o usuário precisa replicar a versão local atual em um servidor online que ainda roda a versão antiga. O instalador deve:

1. Criar/recriar o banco de dados com a estrutura EXATA da versão local
2. Inserir apenas 1 cliente e 1 operação de exemplo
3. Criar o arquivo `db_connection.php` com as credenciais do servidor online
4. Inserir dados mínimos de configuração (usuário admin, templates de contrato)

---

## Estrutura do Banco Local (18 tabelas)

Tabelas identificadas no dump real:

1. `clientes` — dados unificados (antigos cedentes/sacados)
2. `clientes_socios` — sócios dos clientes
3. `compensacoes` — compensações de encontro de contas
4. `contract_templates` — templates de contratos (Markdown)
5. `despesas` — despesas do sistema
6. `distribuicao_lucros` — distribuição de lucros
7. `generated_contracts` — contratos gerados
8. `master_cession_contracts` — contratos de cessão master
9. `operacao_anotacoes` — anotações nas operações
10. `operacao_arquivos` — arquivos anexados
11. `operacao_arquivos_log` — log de arquivos
12. `operacao_documentos` — documentos gerados
13. `operacoes` — operações (antecipação/emprestimo)
14. `operation_guarantors` — avalistas/fiadores
15. `operation_vehicles` — veículos em garantia
16. `operation_witnesses` — testemunhas
17. `recebiveis` — títulos/recebíveis
18. `usuarios` — usuários do sistema

---

## Passos de Implementação

### Passo 1: Gerar dump da estrutura (schema-only) do banco local
- Usar `mysqldump --no-data` para extrair DDL completa das 18 tabelas
- Incluir FKs, índices, comentários, engines, collations
- Salvar em arquivo temporário ou variável PHP no installer

### Passo 2: Criar o arquivo `installer2.php`

#### 2.1 Interface HTML (Bootstrap 5)
- Formulário com campos: host, usuário, senha, nome do banco
- Validação básica no frontend
- Botão "Instalar Sistema"

#### 2.2 Lógica PHP de Instalação (POST)

**Fase A — Conexão e Validação**
- Conectar ao MySQL do servidor online com PDO
- Verificar se o banco existe (ou criar se permitido)
- Testar permissões de escrita

**Fase B — Criar Estrutura (DDL)**
- Desabilitar `foreign_key_checks`
- Executar `DROP TABLE IF EXISTS` para todas as 18 tabelas (na ordem correta respeitando FKs)
- Executar `CREATE TABLE` para todas as 18 tabelas com estrutura exata do dump
- Reabilitar `foreign_key_checks`

**Fase C — Inserir Dados Mínimos**

| Tabela | Dados |
|--------|-------|
| `usuarios` | 1 usuário admin (email: admin, senha: Qazwsx123@ hashada) |
| `clientes` | 1 cliente de exemplo (PJ fictícia) |
| `clientes_socios` | 1 sócio para o cliente de exemplo |
| `operacoes` | 1 operação de exemplo (antecipacao, valores fictícios) |
| `recebiveis` | 1-2 recebíveis para a operação de exemplo |
| `contract_templates` | Templates de contrato atuais (01, 02a-02f, 03) lidos de `_contratos/` |
| `config.json` | Configurações padrão (taxa IOF, etc.) |

**Fase D — Criar db_connection.php**
- Gerar arquivo com as credenciais informadas no formulário
- Usar template idêntico ao atual

**Fase E — Feedback de Sucesso**
- Mensagem de conclusão
- Link para index.php
- Aviso para excluir installer2.php

### Passo 3: Embutir schema no próprio PHP

Como o arquivo precisa ser autocontido para upload no servidor online:
- O schema DDL completo será embutido como string/arrays no próprio `installer2.php`
- Isso evita depender de arquivo `.sql` externo que pode não ser enviado

### Passo 4: Templates de Contrato

- Ler os arquivos `.md` de `_contratos/` (01_template_antecipacao_recebiveis.md, 02a-02f, 03)
- Inserir na tabela `contract_templates` com os códigos e conteúdo

### Passo 5: Testar Localmente

- Executar installer2.php no localhost
- Verificar se todas as 18 tabelas foram criadas
- Verificar se dados mínimos foram inseridos
- Verificar se db_connection.php foi gerado corretamente

---

## Fluxo de Uso no Servidor Online

1. Usuário faz backup do servidor antigo
2. Envia todos os arquivos PHP da versão local (via FTP/Git)
3. Acessa `installer2.php` no navegador
4. Preenche credenciais do banco MySQL do servidor
5. Clica "Instalar"
6. Sistema recria banco com estrutura nova + dados mínimos
7. Usuário apaga `installer2.php` e começa a usar

---

## Arquivos a Criar/Modificar

| Arquivo | Ação | Descrição |
|---------|------|-----------|
| `installer2.php` | Criar | Instalador completo com schema embutido |
| `_contratos/*.md` | Ler | Templates de contrato para insert |

---

## Considerações de Segurança

- O installer2.php deve ser deletado após uso
- Senha do admin padrão deve ser alterada no primeiro login
- Não expor credenciais do banco em logs ou mensagens de erro
- Usar prepared statements para todos os inserts

---

## Dados de Exemplo a Inserir

### Cliente (PJ)
- Nome: Empresa Exemplo LTDA
- CNPJ: 11.222.333/0001-44
- Endereço fictício em São Paulo/SP

### Operação
- Tipo: antecipacao
- Taxa: 5%
- Valor total: R$ 10.000,00
- Data: data atual

### Recebíveis
- 1 recebível de R$ 10.000,00
- Vencimento: 30 dias

### Usuário
- Email: admin
- Senha: Qazwsx123@ (hash com password_hash)
