# Tipos de Contrato e Variáveis Disponíveis

Este documento consolida os tipos de contratos suportados pelo sistema de Factoring e todas as variáveis disponíveis (tags Mustache) para utilização nos templates (arquivos Markdown em `_contratos/`). Estas variáveis são injetadas pelo arquivo `api_contratos.php`.

---

## 📄 Tipos de Contrato

O sistema determina automaticamente qual template de contrato gerar com base na natureza da operação e nas garantias oferecidas:

### 1. Operações de Antecipação (Desconto de Recebíveis)
- **Cessão de Crédito / Desconto de Títulos:** `01_template_antecipacao_recebiveis.md`
  - Utilizado quando a natureza da operação é "DESCONTO".
  - Focado na cessão de recebíveis (duplicatas, cheques, notas promissórias, etc.).

### 2. Operações de Empréstimo (Mútuo)
Para empréstimos, a escolha do template depende da presença de Avalista e/se de Garantia Real (Veículo):
- **Mútuo Simples:** `02a_template_mutuo_simples.md` (Sem Garantia, Sem Avalista)
- **Mútuo com Aval:** `02b_template_mutuo_com_aval.md` (Sem Garantia, Com Avalista)
- **Mútuo com Garantia (Alienação Fiduciária):** `02c_template_mutuo_com_garantia.md` (Com Veículo, Sem Avalista)
- **Mútuo com Garantia e Aval:** `02d_template_mutuo_com_garantia_e_aval.md` (Com Veículo, Com Avalista)

### 3. Documentos Acessórios
- **Nota Promissória:** `03_template_nota_promissoria.md`
  - Anexada automaticamente ao final dos contratos de Empréstimo (Mútuo).

---

## 🧩 Variáveis Disponíveis (Tags Mustache)

As variáveis abaixo podem ser usadas nos templates no formato `{{nome_da_variavel}}` ou `{{#secao}} ... {{/secao}}` para listas e condicionais.

### 🏢 Credor (`{{#credor}} ... {{/credor}}`)
Informações da Factoring / ESC (configuradas no `config.json`).
- `{{representante.nome}}`: Nome do representante legal.
- `{{representante.nacionalidade}}`: Nacionalidade (padrão: brasileiro(a)).
- `{{representante.estado_civil}}`: Estado civil (padrão: casado(a)).
- `{{representante.rg}}`: RG do representante.
- `{{representante.cpf}}`: CPF do representante.
- `{{conta.banco}}`: Banco da empresa.
- `{{conta.agencia}}`: Agência bancária.
- `{{conta.numero}}`: Número da conta.
- `{{conta.tipo}}`: Tipo de conta.
- `{{conta.pix}}`: Chave PIX.
- `{{conta.titular}}`: Titular da conta.
- `{{conta.documento}}`: Documento (CNPJ/CPF) vinculado à conta.
- `{{endereco_completo}}`: Endereço completo da empresa.
- `{{email}}`: E-mail de contato.
- `{{whatsapp}}`: WhatsApp de contato.

### 👤 Cedente / Devedor (`{{#cedente}}` ou `{{#devedor}}`)
Para Antecipação usa-se Cedente, para Empréstimo usa-se Devedor. Ambos possuem a mesma estrutura.
- `{{pessoa_juridica}}`: Booleano (true/false) - Útil para condicionais `{{#pessoa_juridica}}...{{/pessoa_juridica}}` e `{{^pessoa_juridica}}...{{/pessoa_juridica}}`.
- `{{razao_social}}`: Razão Social (se PJ) ou Nome (se PF).
- `{{descricao_juridica}}`: Padrão: "pessoa jurídica de direito privado".
- `{{cnpj}}`: CNPJ (se PJ).
- `{{nome_completo}}`: Nome completo.
- `{{nacionalidade}}`: Nacionalidade.
- `{{estado_civil}}`: Estado civil.
- `{{profissao}}`: Profissão.
- `{{rg}}`: RG.
- `{{cpf}}`: CPF.
- `{{documento}}`: CNPJ ou CPF (dependendo se PJ ou PF).
- `{{documento_label}}`: "CNPJ" ou "CPF".
- `{{nome_exibicao}}`: Razão Social ou Nome Completo.
- `{{porte}}`: Porte da empresa (MEI, ME, EPP, etc).
- `{{endereco_completo}}`: Endereço estruturado e formatado.
- `{{email}}`: E-mail.
- `{{whatsapp}}`: WhatsApp.
- `{{casado}}`: Booleano.
- `{{conjuge_assina}}`: Booleano (se casado e requer assinatura do cônjuge).
- `{{conjuge.nome}}`: Nome do cônjuge.
- `{{conjuge.cpf}}`: CPF do cônjuge.
- `{{conta.banco}}`, `{{conta.agencia}}`, `{{conta.numero}}`, `{{conta.tipo}}`, `{{conta.pix}}`: Dados bancários.
- `{{representante.nome}}`, `{{representante.nacionalidade}}`, `{{representante.estado_civil}}`, `{{representante.profissao}}`, `{{representante.rg}}`, `{{representante.cpf}}`, `{{representante.endereco}}`: Dados do representante (se PJ).

### 🤝 Avalista (`{{#avalista}} ... {{/avalista}}`)
- `{{nome}}`: Nome completo.
- `{{nacionalidade}}`: Nacionalidade.
- `{{estado_civil}}`: Estado civil.
- `{{profissao}}`: Profissão.
- `{{rg}}`: RG.
- `{{cpf}}`: CPF.
- `{{endereco_completo}}`: Endereço.
- `{{email}}`: E-mail.
- `{{whatsapp}}`: WhatsApp.
- `{{casado}}`: Booleano.
- `{{regime_casamento}}`: Regime de bens.
- `{{conjuge.nome}}`: Nome do cônjuge.
- `{{conjuge.cpf}}`: CPF do cônjuge.

### 🚗 Veículo / Garantia (`{{#veiculo}} ... {{/veiculo}}`)
- `{{marca}}`: Marca.
- `{{modelo}}`: Modelo.
- `{{ano_fab}}`: Ano de fabricação.
- `{{ano_mod}}`: Ano do modelo.
- `{{cor}}`: Cor.
- `{{combustivel}}`: Combustível (padrão: Flex).
- `{{placa}}`: Placa.
- `{{renavam}}`: RENAVAM.
- `{{chassi}}`: Chassi.
- `{{municipio_registro}}`: Município de emplacamento.
- `{{uf}}`: Estado de emplacamento.
- `{{valor_avaliacao}}`: Valor da avaliação formatado (R$).
- `{{valor_avaliacao_extenso}}`: Valor da avaliação por extenso.

### 💼 Operação (`{{#operacao}} ... {{/operacao}}`)
Dados gerais do empréstimo.
- `{{id}}`: ID da operação.
- `{{local}}`: Padrão: Piracicaba/SP.
- `{{data_extenso}}`: Data atual por extenso.
- `{{valor_principal}}`: Valor líquido liberado.
- `{{valor_principal_extenso}}`: Valor líquido por extenso.
- `{{forma_liberacao}}`: Transferência Bancária (PIX).
- `{{valor_total_devido}}`: Valor de face total (com juros).
- `{{valor_total_devido_extenso}}`: Valor de face por extenso.
- `{{num_parcelas}}`: Número de parcelas.
- `{{num_parcelas_extenso}}`: Número de parcelas por extenso.
- `{{periodicidade}}`: "única" ou "variável".
- `{{valor_parcela}}`: Valor da parcela (ou "Variável").
- `{{valor_parcela_extenso}}`: Valor da parcela por extenso.
- `{{data_primeiro_vencimento}}`: Data do primeiro vencimento.
- `{{forma_pagamento}}`: Transferência Bancária (PIX).
- `{{taxa_juros_mensal}}`: Taxa de juros ao mês (%).
- `{{taxa_juros_mensal_extenso}}`: Taxa de juros a.m. por extenso.
- `{{taxa_juros_anual}}`: Taxa de juros ao ano (%).
- `{{taxa_juros_anual_extenso}}`: Taxa de juros a.a. por extenso.
- `{{cet}}`: Custo Efetivo Total mensal.
- `{{taxa_juros_atraso}}`: Juros de mora (% a.m.).
- `{{taxa_multa_atraso}}`: Multa por atraso (%).
- `{{total_juros}}`: Valor total dos juros em R$.
- `{{sistema_amortizacao}}`: "Pagamento Único" ou "Pagamento Variável".

### 📊 Borderô / Antecipação (`{{#bordero}} ... {{/bordero}}`)
Dados consolidados de recebíveis para operações de desconto.
- `{{id}}`: ID da operação.
- `{{data_extenso}}`, `{{local}}`: Data e Local.
- `{{total_face}}`: Valor bruto total dos títulos.
- `{{total_juros}}`: Valor descontado (juros).
- `{{total_liquido}}`: Valor líquido a receber.
- `{{total_titulos}}`: Quantidade de títulos.
- `{{taxa_desagio}}`: Taxa de desconto.
- `{{prazo_medio}}`: Prazo médio ponderado (em dias).
- `{{tarifas}}`: Soma de IOF e tarifas.
- `{{valor_liquido_extenso}}`: Valor líquido por extenso.

### 📋 Lista de Títulos (`{{#titulos}} ... {{/titulos}}`)
Lista iterável com os recebíveis da operação.
- `{{ordem}}`: Número sequencial.
- `{{numero}}`: Número do documento/título.
- `{{tipo}}`: Tipo do recebível (Duplicata, Cheque, etc).
- `{{sacado_nome}}`: Nome do pagador original.
- `{{sacado_documento}}`: CPF/CNPJ do sacado.
- `{{data_emissao}}`: Data de emissão.
- `{{data_vencimento}}`: Data de vencimento.
- `{{valor_face}}`: Valor original.
- `{{valor_liquido}}` / `{{valor_presente}}`: Valor após deságio.
- `{{juros}}`: Desconto aplicado no título.

### 📅 Cronograma (`{{#cronograma}} ... {{/cronograma}}`)
Lista iterável de parcelas do empréstimo.
- `{{numero}}`: Número da parcela.
- `{{data_vencimento}}`: Data de vencimento da parcela.
- `{{valor_parcela}}`: Valor da parcela.
- `{{valor_amortizacao}}`: Valor amortizado do principal.
- `{{valor_juros}}`: Valor dos juros da parcela.
- `{{saldo_devedor}}`: Saldo devedor restante.

### 📝 Nota Promissória (`{{#np}} ... {{/np}}`)
- `{{numero}}`: Número da NP (ex: 01/01).
- `{{vencimento}}`: Data de vencimento da última parcela.
- `{{data_vencimento_extenso}}`: Data de vencimento por extenso.

### ✍️ Testemunhas (`{{#testemunhas}} ... {{/testemunhas}}`)
Lista iterável com 2 espaços vazios por padrão.
- `{{nome}}`: Linha para assinatura do nome.
- `{{cpf}}`: Linha para o CPF.
