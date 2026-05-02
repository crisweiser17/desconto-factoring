# Plano: Comparar Contratos Ativos com Contratos Antigos

## Objetivo
Comparar os contratos ativos na pasta `_contratos/` com os contratos antigos em `_contratos/antigos/` e determinar qual versão é a mais recente para ser atualizada.

## Análise Atual
### Contratos Ativos (modificados em 30 de abril e 1 de maio de 2026):
1. **Empréstimo**:
   - `02a_template_mutuo_simples.md` - Mútuo simples (sem garantia, sem avalista)
   - `02b_template_mutuo_com_aval.md` - Mútuo com aval (sem garantia)
   - `02c_template_mutuo_com_garantia.md` - Mútuo com garantia de veículo (sem avalista)
   - `02d_template_mutuo_com_garantia_e_aval.md` - Mútuo com garantia e aval
   - `02e_template_mutuo_com_garantia_bem.md` - Mútuo com bem móvel em garantia
   - `02f_template_mutuo_com_garantia_bem_e_aval.md` - Mútuo com bem móvel e aval

2. **Desconto/Antecipação**:
   - `01_template_antecipacao_recebiveis.md` - Contrato de cessão de créditos

3. **Nota Promissória**:
   - `03_template_nota_promissoria.md` - Nota promissória para empréstimos

### Contratos Antigos (modificados em 29 de abril de 2026):
1. **Empréstimo**:
   - `02_template_contrato_mutuo.md` - Template antigo de mútuo com garantia de veículo e aval
   - `contrato_1_com_veiculo_com_avalista.md`
   - `contrato_2_sem_veiculo_sem_avalista.md`
   - `contrato_3_com_veiculo_sem_avalista.md`
   - `contrato_4_sem_veiculo_com_avalista.md`

2. **Desconto/Antecipação**:
   - `03_template_cessao_bordero.md` - Template antigo de cessão de borderô

3. **Nota Promissória**:
   - `04_template_nota_promissoria.md` - Template antigo de nota promissória

### API de Contratos (`api_contratos.php`)
- Modificado em 30 de abril de 2026
- Usa templates ativos da pasta `_contratos/`
- Não usa templates da pasta `_contratos/antigos/`

## Análise de Diferenças

### 1. Estrutura de Templates
**Contratos Antigos**:
- Template único para mútuo com garantia de veículo e aval (`02_template_contrato_mutuo.md`)
- Template único para cessão de borderô (`03_template_cessao_bordero.md`)

**Contratos Ativos**:
- Templates especializados por tipo de operação:
  - 6 templates para diferentes combinações de mútuo (com/sem garantia, com/sem aval)
  - 1 template para antecipação de recebíveis
  - 1 template para nota promissória

### 2. Variáveis Mustache
**Contratos Antigos**:
- Usam variáveis como `{{devedor.conjuge.nome}}` e `{{devedor.conjuge.cpf}}`
- Usam `{{credor.razao_social}}` com valor fixo "ACM EMPRESA SIMPLES DE CRÉDITO LTDA"

**Contratos Ativos**:
- Usam variáveis mais genéricas e flexíveis
- Usam `{{credor.razao_social}}` dinâmico do config.json
- Incluem variáveis adicionais para bem móvel

### 3. Cláusulas e Conteúdo
**Contratos Antigos**:
- Mais detalhados em cláusulas específicas (ex: alienação fiduciária detalhada)
- Mais textos legais específicos

**Contratos Ativos**:
- Mais modulares e adaptáveis
- Mantêm essência legal mas com estrutura mais flexível

## Determinação de Versão Mais Recente
**Conclusão**: Os **contratos ativos** são os mais recentes e devem ser usados.

**Justificativa**:
1. **Datas de modificação**: Contratos ativos modificados em 30 de abril e 1 de maio 2026 vs. contratos antigos em 29 de abril 2026
2. **Implementação no código**: `api_contratos.php` (modificado em 30 de abril 2026) usa exclusivamente os templates ativos da pasta `_contratos/`
3. **Estrutura mais moderna**: Templates ativos são especializados e mais flexíveis
4. **Manutenção ativa**: Apenas os templates ativos estão sendo mantidos e atualizados

## Ações Recomendadas
1. **Manter contratos ativos** como versão principal
2. **Mover contratos antigos** para pasta de arquivamento (se já não estiverem)
3. **Documentar diferenças** para referência futura
4. **Verificar se há funcionalidades exclusivas** nos templates antigos que precisem ser migradas

## Próximos Passos
1. Confirmar que `api_contratos.php` não referencia nenhum template antigo
2. Verificar se há alguma operação que use templates antigos
3. Decidir se mantém contratos antigos como referência ou remove completamente