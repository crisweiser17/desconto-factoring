# Plano: Remover Completamente Dados Legados do Cônjuge

## Situação Atual
1. A tabela `clientes` possui colunas legadas: `casado`, `conjuge_nome`, `conjuge_cpf`, `conjuge_rg`, `conjuge_nacionalidade`, `conjuge_profissao`, `regime_casamento`.
2. O formulário de cadastro de cliente (`form_cliente.php`) já removeu esses campos (via `remove_pf_conjuge.php`).
3. A API de contratos (`api_contratos.php`) busca esses dados do banco quando gera contratos.
4. Os templates de contrato usam `{{devedor.conjuge.nome}}` e `{{devedor.conjuge.cpf}}` que vêm desses dados legados.
5. Há 2 registros com dados de cônjuge no banco (um deles é "EsposaBruno", CPF 323.211.231-12).

## Problema
Quando o checkbox "cônjuge assina" é marcado na tela de geração de contrato, o sistema preenche automaticamente com dados legados desatualizados. Como removemos esses campos do cadastro, eles nunca serão atualizados.

## Objetivo do Usuário
1. **Remover completamente as colunas legadas do banco de dados** da tabela `clientes`.
2. **Remover as variáveis Mustache** `{{devedor.conjuge.nome}}` e `{{devedor.conjuge.cpf}}` dos templates.
3. **Manter o checkbox "cônjuge assina"** funcionando, mas quando marcado, mostrar espaço em branco para preenchimento manual posterior de nome e CPF.

## Plano de Ação Direto

### Fase 1: Remover Colunas do Banco de Dados
1. **Backup dos dados** (opcional, mas recomendado):
   - Exportar dados atuais das colunas a serem removidas
   
2. **Remover colunas da tabela `clientes`**:
   - `casado`
   - `regime_casamento`
   - `conjuge_nome`
   - `conjuge_cpf`
   - `conjuge_rg`
   - `conjuge_nacionalidade`
   - `conjuge_profissao`

3. **Verificar dependências**:
   - Confirmar que nenhum outro código depende dessas colunas

### Fase 2: Atualizar `api_contratos.php`
1. **Remover referências aos campos de cônjuge**:
   - Na função `montarParteContrato()`, remover o array `'conjuge'` (linhas 173-177)
   - Remover `'casado'` do array retornado (linha 173)
   
2. **Manter compatibilidade**:
   - Retornar array `'conjuge'` vazio `['nome' => '', 'cpf' => '']` para não quebrar templates durante transição
   - Ou remover completamente e ajustar templates primeiro

### Fase 3: Atualizar Templates de Contrato
1. **Remover variáveis Mustache do cônjuge do devedor**:
   - Localizar e remover `{{devedor.conjuge.nome}}` e `{{devedor.conjuge.cpf}}` de todos os templates
   - Substituir por espaços em branco ou texto explicativo "A ser preenchido posteriormente"
   
2. **Manter estrutura do checkbox**:
   - O bloco `{{#devedor.conjuge_assina}}...{{/devedor.conjuge_assina}}` deve permanecer
   - Dentro dele, colocar linhas para preenchimento manual:
     ```
     Nome: ____________________________________________________
     CPF:  ____________________________________________________
     ```

3. **Tratar cônjuge do avalista** (se aplicável):
   - Verificar se há `{{avalista.conjuge.nome}}` e `{{avalista.conjuge.cpf}}`
   - Decidir se mantém (dados do avalista ainda podem ser coletados) ou remove também

### Fase 4: Testes
1. **Testar remoção das colunas**:
   - Executar ALTER TABLE e confirmar sucesso
   - Verificar que outras funcionalidades não quebram

2. **Testar geração de contrato**:
   - Com checkbox "cônjuge assina" marcado: deve mostrar espaços em branco
   - Com checkbox desmarcado: não deve mostrar seção do cônjuge
   - Verificar que layout não quebra

3. **Testar fluxo completo**:
   - Cadastro de cliente (sem campos de cônjuge)
   - Criação de operação
   - Geração de contrato com/sem cônjuge assinando

## Cronograma Estimado
1. Fase 1: 15 minutos
2. Fase 2: 15 minutos  
3. Fase 3: 30 minutos
4. Fase 4: 20 minutos

**Total: ~1.5 horas**

## Riscos e Mitigações
1. **Perda de dados legados**: Backup antes de remover colunas
2. **Quebra de templates**: Fazer mudanças gradualmente, testando cada template
3. **Erro no ALTER TABLE**: Executar em ambiente de teste primeiro

## Próximos Passos Imediatos
1. Criar backup do banco
2. Executar ALTER TABLE para remover colunas
3. Atualizar `api_contratos.php`
4. Atualizar templates um por um
5. Testar cada template individualmente