# Variáveis Disponíveis para Templates de Contrato

Este documento lista exaustivamente todas as variáveis (tags Mustache) disponíveis para utilização nos templates de contrato (`.md`).
Para utilizar uma variável no seu template, envolva-a em chaves duplas, por exemplo: `{{credor.representante.nome}}`.
Para blocos de listas (como testemunhas e cronograma), utilize as seções Mustache: `{{#testemunhas}} ... {{/testemunhas}}`.

## 1. Credor (`{{credor}}`)
Informações sobre a empresa credora.
- `{{credor.email}}`: E-mail do credor.
- `{{credor.whatsapp}}`: WhatsApp do credor.
- `{{credor.representante.nome}}`: Nome do representante do credor.
- `{{credor.representante.nacionalidade}}`: Nacionalidade do representante.
- `{{credor.representante.estado_civil}}`: Estado civil do representante.
- `{{credor.representante.rg}}`: RG do representante.
- `{{credor.representante.cpf}}`: CPF do representante.
- `{{credor.conta.banco}}`: Banco da conta do credor.
- `{{credor.conta.agencia}}`: Agência da conta do credor.
- `{{credor.conta.numero}}`: Número da conta do credor.
- `{{credor.conta.tipo}}`: Tipo da conta do credor (ex: Corrente, Poupança).
- `{{credor.conta.pix}}`: Chave PIX do credor.
- `{{credor.conta.titular}}`: Nome do titular da conta do credor.
- `{{credor.conta.documento}}`: CPF/CNPJ do titular da conta do credor.

## 2. Cedente (`{{cedente}}`)
Informações sobre o cedente da operação.
- `{{cedente.pessoa_juridica}}`: Booleano (true/false). Pode ser usado em condicionais `{{#cedente.pessoa_juridica}}...{{/cedente.pessoa_juridica}}`.
- `{{cedente.razao_social}}`: Razão social ou nome do cedente.
- `{{cedente.descricao_juridica}}`: Descrição da natureza jurídica (ex: "pessoa jurídica de direito privado").
- `{{cedente.cnpj}}`: CNPJ do cedente (ou CPF se não for PJ).
- `{{cedente.nome_completo}}`: Nome completo do cedente.
- `{{cedente.nacionalidade}}`: Nacionalidade do cedente.
- `{{cedente.estado_civil}}`: Estado civil do cedente.
- `{{cedente.profissao}}`: Profissão do cedente.
- `{{cedente.rg}}`: RG do cedente.
- `{{cedente.cpf}}`: CPF do cedente (ou CNPJ).
- `{{cedente.endereco_completo}}`: Endereço completo formatado (Endereço, Cidade - Estado).
- `{{cedente.email}}`: E-mail do cedente.
- `{{cedente.whatsapp}}`: WhatsApp do cedente.
- `{{cedente.casado}}`: Booleano (true/false). Pode ser usado em condicionais `{{#cedente.casado}}...{{/cedente.casado}}`.
- `{{cedente.regime_casamento}}`: Regime de casamento do cedente.

### 2.1 Cônjuge do Cedente
- `{{cedente.conjuge.nome}}`: Nome do cônjuge.
- `{{cedente.conjuge.cpf}}`: CPF do cônjuge.

### 2.2 Conta do Cedente
- `{{cedente.conta.banco}}`: Banco da conta.
- `{{cedente.conta.agencia}}`: Agência da conta.
- `{{cedente.conta.numero}}`: Número da conta.
- `{{cedente.conta.pix}}`: Chave PIX da conta.

### 2.3 Representante do Cedente
- `{{cedente.representante.nome}}`: Nome do representante do cedente.
- `{{cedente.representante.nacionalidade}}`: Nacionalidade do representante.
- `{{cedente.representante.estado_civil}}`: Estado civil do representante.
- `{{cedente.representante.profissao}}`: Profissão do representante.
- `{{cedente.representante.rg}}`: RG do representante.
- `{{cedente.representante.cpf}}`: CPF do representante.
- `{{cedente.representante.endereco}}`: Endereço do representante.

## 3. Contrato Mãe (`{{contrato_mae}}`)
- `{{contrato_mae.id}}`: ID da operação/contrato.
- `{{contrato_mae.local}}`: Local da emissão (ex: Piracicaba/SP).
- `{{contrato_mae.data_extenso}}`: Data atual por extenso.

## 4. Borderô (`{{bordero}}`)
- `{{bordero.id}}`: ID da operação/borderô.
- `{{bordero.local}}`: Local da emissão (ex: Piracicaba/SP).
- `{{bordero.data_extenso}}`: Data atual por extenso.
- `{{bordero.total_titulos}}`: Valor total de face dos títulos (R$).
- `{{bordero.taxa_desagio}}`: Taxa de deságio/fator (%).
- `{{bordero.total_desagio}}`: Valor total do deságio (R$).
- `{{bordero.prazo_medio}}`: Prazo médio dos títulos (dias).
- `{{bordero.tarifas}}`: Valor total das tarifas (R$).
- `{{bordero.valor_liquido_extenso}}`: Valor líquido liberado por extenso.
- `{{bordero.forma_pagamento}}`: Forma de pagamento.

## 5. Devedor (`{{devedor}}`)
Informações sobre o devedor (Sacado no caso de Empréstimo, ou Cedente dependendo da operação).
- `{{devedor.razao_social}}`: Razão social ou nome da empresa.
- `{{devedor.descricao_juridica}}`: Descrição jurídica (ex: "pessoa jurídica de direito privado").
- `{{devedor.cnpj}}`: CNPJ do devedor (ou CPF).
- `{{devedor.porte}}`: Porte da empresa (MEI, ME, EPP).
- `{{devedor.endereco_completo}}`: Endereço completo formatado.
- `{{devedor.email}}`: E-mail do devedor.
- `{{devedor.whatsapp}}`: WhatsApp do devedor.
- `{{devedor.casado}}`: Booleano (true/false). Pode ser usado em condicionais `{{#devedor.casado}}...{{/devedor.casado}}`.
- `{{devedor.conjuge_assina}}`: Booleano (true/false). Pode ser usado em condicionais `{{#devedor.conjuge_assina}}...{{/devedor.conjuge_assina}}`.
- `{{devedor.regime_casamento}}`: Regime de casamento do devedor.

### 5.1 Cônjuge do Devedor
- `{{devedor.conjuge.nome}}`: Nome do cônjuge.
- `{{devedor.conjuge.cpf}}`: CPF do cônjuge.

### 5.2 Conta do Devedor
- `{{devedor.conta.banco}}`: Banco da conta.
- `{{devedor.conta.agencia}}`: Agência da conta.
- `{{devedor.conta.numero}}`: Número da conta.
- `{{devedor.conta.pix}}`: Chave PIX da conta.

### 5.3 Representante do Devedor
- `{{devedor.representante.nome}}`: Nome do representante do devedor.
- `{{devedor.representante.nacionalidade}}`: Nacionalidade do representante.
- `{{devedor.representante.estado_civil}}`: Estado civil do representante.
- `{{devedor.representante.profissao}}`: Profissão do representante.
- `{{devedor.representante.rg}}`: RG do representante.
- `{{devedor.representante.cpf}}`: CPF do representante.
- `{{devedor.representante.endereco}}`: Endereço do representante.

## 6. Avalista (`{{avalista}}`)
- `{{avalista.nome}}`: Nome do avalista.
- `{{avalista.nacionalidade}}`: Nacionalidade do avalista.
- `{{avalista.estado_civil}}`: Estado civil do avalista.
- `{{avalista.profissao}}`: Profissão do avalista.
- `{{avalista.rg}}`: RG do avalista.
- `{{avalista.cpf}}`: CPF do avalista.
- `{{avalista.endereco_completo}}`: Endereço completo do avalista.
- `{{avalista.email}}`: E-mail do avalista.
- `{{avalista.whatsapp}}`: WhatsApp do avalista.
- `{{avalista.casado}}`: Booleano (true/false). Pode ser usado em condicionais `{{#avalista.casado}}...{{/avalista.casado}}`.
- `{{avalista.regime_casamento}}`: Regime de casamento do avalista.

### 6.1 Cônjuge do Avalista
- `{{avalista.conjuge.nome}}`: Nome do cônjuge.
- `{{avalista.conjuge.cpf}}`: CPF do cônjuge.

## 7. Operação (`{{operacao}}`)
Informações financeiras e detalhes da operação.
- `{{operacao.id}}`: ID da operação.
- `{{operacao.local}}`: Local (ex: Piracicaba/SP).
- `{{operacao.data_extenso}}`: Data atual por extenso.
- `{{operacao.valor_principal}}`: Valor líquido pago (R$).
- `{{operacao.valor_principal_extenso}}`: Valor líquido pago por extenso.
- `{{operacao.forma_liberacao}}`: Forma de liberação dos recursos.
- `{{operacao.comprovante_liberacao}}`: Comprovante da liberação.
- `{{operacao.valor_total_devido}}`: Valor total original (R$).
- `{{operacao.valor_total_devido_extenso}}`: Valor total original por extenso.
- `{{operacao.num_parcelas}}`: Número de parcelas (numérico).
- `{{operacao.num_parcelas_extenso}}`: Número de parcelas por extenso.
- `{{operacao.periodicidade}}`: Periodicidade do pagamento.
- `{{operacao.valor_parcela}}`: Valor da parcela (R$).
- `{{operacao.valor_parcela_extenso}}`: Valor da parcela por extenso.
- `{{operacao.data_primeiro_vencimento}}`: Data de vencimento da primeira parcela.
- `{{operacao.forma_pagamento}}`: Forma de pagamento.
- `{{operacao.taxa_juros_mensal}}`: Taxa de juros mensal (%).
- `{{operacao.taxa_juros_mensal_extenso}}`: Taxa de juros mensal por extenso.
- `{{operacao.taxa_juros_anual}}`: Taxa de juros anual equivalente (%).
- `{{operacao.taxa_juros_anual_extenso}}`: Taxa de juros anual equivalente por extenso.
- `{{operacao.cet}}`: Custo Efetivo Total (%).
- `{{operacao.total_juros}}`: Valor total dos juros (R$).
- `{{operacao.sistema_amortizacao}}`: Sistema de amortização.
- `{{operacao.num_vias}}`: Número de vias do contrato (numérico).
- `{{operacao.num_vias_extenso}}`: Número de vias do contrato por extenso.

## 8. Veículo (Garantia) (`{{veiculo}}`)
- `{{veiculo.marca}}`: Marca do veículo.
- `{{veiculo.modelo}}`: Modelo do veículo.
- `{{veiculo.ano_fab}}`: Ano de fabricação.
- `{{veiculo.ano_mod}}`: Ano do modelo.
- `{{veiculo.cor}}`: Cor do veículo.
- `{{veiculo.combustivel}}`: Combustível.
- `{{veiculo.placa}}`: Placa.
- `{{veiculo.renavam}}`: Renavam.
- `{{veiculo.chassi}}`: Chassi do veículo.
- `{{veiculo.municipio_registro}}`: Município de registro do veículo.
- `{{veiculo.uf}}`: UF de registro do veículo.
- `{{veiculo.valor_avaliacao}}`: Valor de avaliação (R$).
- `{{veiculo.valor_avaliacao_extenso}}`: Valor de avaliação por extenso.

## 9. Testemunhas (`{{#testemunhas}}`)
Lista de testemunhas. Uso:
```mustache
{{#testemunhas}}
Nome: {{nome}}
CPF: {{cpf}}
{{/testemunhas}}
```
- `{{nome}}`: Nome da testemunha.
- `{{cpf}}`: CPF da testemunha.

## 10. Cronograma (`{{#cronograma}}`)
Lista das parcelas do cronograma de pagamento. Uso:
```mustache
{{#cronograma}}
Parcela: {{numero}} | Vencimento: {{data_vencimento}} | Valor: R$ {{valor_parcela}}
{{/cronograma}}
```
- `{{numero}}`: Número da parcela.
- `{{data_vencimento}}`: Data de vencimento da parcela.
- `{{valor_parcela}}`: Valor total da parcela (R$).
- `{{valor_amortizacao}}`: Valor de amortização do principal (R$).
- `{{valor_juros}}`: Valor dos juros da parcela (R$).
- `{{saldo_devedor}}`: Saldo devedor após o pagamento (R$).

## 11. Títulos (`{{#titulos}}`)
Lista iterável de títulos (usado em borderôs). Uso:
```mustache
{{#titulos}}
Ordem: {{ordem}} | Número: {{numero}} | Tipo: {{tipo}} | Sacado: {{sacado_nome}} ({{sacado_documento}})
Emissão: {{data_emissao}} | Vencimento: {{data_vencimento}} | Valor Face: R$ {{valor_face}} | Valor Líq: R$ {{valor_liquido}}
Valor Presente: R$ {{valor_presente}} | Juros: R$ {{juros}}
{{/titulos}}
```
- `{{ordem}}`: Número sequencial do título.
- `{{numero}}`: Número/Identificador do título.
- `{{tipo}}`: Tipo do título (ex: DUP, CHQ).
- `{{sacado_nome}}`: Nome do sacado.
- `{{sacado_documento}}`: CPF ou CNPJ do sacado.
- `{{data_emissao}}`: Data de emissão do título.
- `{{data_vencimento}}`: Data de vencimento do título.
- `{{valor_face}}`: Valor de face original (R$).
- `{{valor_liquido}}`: Valor líquido (R$).
- `{{valor_presente}}`: Valor presente (R$).
- `{{juros}}`: Valor dos juros/deságio do título (R$).
