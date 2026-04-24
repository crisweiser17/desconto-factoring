# Guia: Quando Usar Cada Tipo de Contrato
## ACM Empresa Simples de Crédito — Matriz de Decisão Operacional

---

## 🎯 A Pergunta Fundamental

Antes de gerar qualquer documento, responda **uma única pergunta**:

> **"O dinheiro da ACM vai sair para cobrir um débito que o cliente TEM COM A ACM, ou para COMPRAR um crédito que o cliente TEM CONTRA UM TERCEIRO?"**

- Se o cliente **é o devedor final** da ACM → **EMPRÉSTIMO** → Contrato de Mútuo
- Se o cliente **vende um crédito** dele contra outra pessoa → **DESCONTO** → Contrato de Cessão + Borderô

Essa distinção é o coração da arquitetura. Tudo parte dela.

---

## 📋 Quando usar cada trilho

### TRILHO 1 — Contrato de Mútuo Feneratício + Nota Promissória

**Use quando a operação é:**
- Empréstimo em dinheiro
- Capital de giro para o cliente
- Financiamento de veículo, maquinário, reforma, qualquer necessidade do próprio cliente
- O cliente recebe o dinheiro e se compromete a devolver em parcelas (ou parcela única) com juros

**Tomador obrigatório:** MEI, ME ou EPP.
- Pessoa física comum **não pode**. Se o cliente for PF sem CNPJ, o operador deve:
  1. Orientá-lo a abrir MEI (gratuito, 10 min no [gov.br/mei](https://www.gov.br/mei))
  2. Ou converter para operação de desconto (se houver crédito real contra terceiro)
  3. Ou recusar a operação

**Garantias:**
- **Obrigatória:** Alienação fiduciária de veículo + Aval solidário (com anuência do cônjuge, se casado)
- A alienação fiduciária deve ser **averbada no DETRAN** em até 10 dias úteis

**Juros:** Taxa real de mercado pactuada livremente entre as partes, com fundamento na LC 167/2019 (regime especial da ESC que afasta a aplicação do Decreto 22.626/33).

**Documentos gerados pelo sistema:**
1. Contrato de Mútuo (com cláusulas de aval, alienação fiduciária, vencimento antecipado e confissão de dívida)
2. Nota Promissória vinculada, avalizada

---

### TRILHO 2 — Contrato de Cessão Onerosa + Borderô

**Use quando a operação é:**
- Antecipação de recebíveis (duplicatas, boletos, faturas de serviços a receber)
- Desconto de notas promissórias emitidas por terceiros em favor do cliente
- Desconto de cheques pré-datados
- Qualquer crédito que o cliente tenha contra um **sacado/devedor distinto dele mesmo**

**Tomador (cedente):** QUALQUER UM — PF ou PJ, de qualquer porte.
- A ESC pode fazer desconto de título mesmo com PF, porque a operação tem natureza jurídica de cessão (não mútuo) e o devedor final da ACM é o sacado, não o cedente.

**Teste de validade da operação:**
- O título cedido deve ter **sacado distinto do cedente**
- Deve haver **operação subjacente real** (nota fiscal, contrato, ordem de serviço)
- O pagamento deve ir **para a conta do cedente**, não para terceiro que depois repassa

**Garantias (opcionais, recomendadas):**
- Responsabilidade do cedente pela solvência (CC art. 296 — pro solvendo) → já embutida
- Aval pode ser incluído para reforço (recomendado para operações de maior valor)

**Remuneração:** Deságio pactuado. Não é juros — é desconto do valor futuro. Não se submete à Lei da Usura (operação de natureza diversa).

**Documentos gerados pelo sistema:**
1. **Na 1ª operação com o cliente:** Contrato-Mãe de Cessão Onerosa + Borderô da operação
2. **Em operações subsequentes:** Apenas o Borderô (o Contrato-Mãe já está vigente)

---

## 🔀 Tabela de Decisão Rápida

| Situação na Triagem | Contrato a Gerar | Tomador Aceito |
|---|---|---|
| Cliente MEI quer R$ 30k para capital de giro, dá carro em garantia | Mútuo + NP | MEI/ME/EPP |
| Cliente ME tem 5 duplicatas a receber e quer antecipar | Cessão + Borderô | Qualquer |
| Cliente PF comum quer R$ 50k em dinheiro com carro em garantia | ❌ **Recusar ou orientar abrir MEI** | — |
| Cliente PF tem uma NP emitida por um terceiro real e quer descontar | Cessão + Borderô | Qualquer |
| Cliente EPP quer financiar um equipamento novo | Mútuo + NP | MEI/ME/EPP |
| Cliente PJ grande (faturamento > R$ 4,8mi) quer empréstimo | ❌ **Recusar** (ESC só atende MEI/ME/EPP) | — |
| Empresa grande traz boletos de clientes dela para antecipar | Cessão + Borderô | Qualquer (desconto não tem restrição de porte) |

---

## ⚠️ Sinais de Alerta para Simulação

Em qualquer operação de **DESCONTO**, verifique sempre se existe crédito real contra terceiro. Rejeite a operação se:

- ❌ O "sacado" do título é suspeito de ser um laranja ou pessoa próxima ao cedente
- ❌ O "produto" objeto da venda não existe fisicamente ou não tem valor de mercado compatível
- ❌ O cedente vai usar o dinheiro recebido para "repassar" ao sacado
- ❌ O valor do título está muito acima do valor de mercado do produto/serviço
- ❌ Não há documentação fiscal que comprove a operação subjacente

Um esquema de simulação pode transformar a operação em mútuo travestido — com risco de revisão judicial dos "juros" aplicados, descaracterização da ESC, e no limite, caracterização de agiotagem.

---

## 🔐 Checklist Pré-Geração (qualquer operação)

- [ ] Identificado o tipo (Mútuo ou Desconto)
- [ ] Tomador validado conforme a regra do trilho
- [ ] Dados cadastrais completos (ambas as partes + avalista, se houver)
- [ ] Se Mútuo: veículo identificado, com todos os campos DETRAN preenchidos
- [ ] Se Desconto: títulos listados com sacado, valor, vencimento e documentos de origem
- [ ] Taxa/deságio pactuados e documentados
- [ ] Duas testemunhas identificadas (CPC art. 784, III — requisito para força executiva)
- [ ] Dados bancários corretos para liberação do valor

---

## 🗂️ Fluxo Operacional Resumido

```
1. Cliente entra em contato/sistema
       ↓
2. Operador identifica: EMPRÉSTIMO ou DESCONTO?
       ↓
3. Tipifica tomador → Valida elegibilidade
       ↓
4. Cadastra operação no sistema (dados completos)
       ↓
5. Clica "Gerar Contratos e Documentos"
       ↓
6. Sistema gera PDFs apropriados
       ↓
7. Operador baixa PDFs e sobe em plataforma de assinatura
   (ClickSign / D4Sign / Autentique / ZapSign)
       ↓
8. Colhe assinaturas de todas as partes + 2 testemunhas
       ↓
9. Se Mútuo: averba alienação fiduciária no DETRAN
       ↓
10. Libera o valor ao cliente
       ↓
11. Marca operação como "Contratada" no sistema
```
