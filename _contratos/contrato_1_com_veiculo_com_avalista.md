# INSTRUMENTO PARTICULAR DE CONTRATO DE MÚTUO FENERATÍCIO COM ALIENAÇÃO FIDUCIÁRIA DE VEÍCULO EM GARANTIA E AVAL

**Contrato nº {{operacao.id}}**

Pelo presente **INSTRUMENTO PARTICULAR DE CONTRATO DE MÚTUO FENERATÍCIO COM ALIENAÇÃO FIDUCIÁRIA DE VEÍCULO EM GARANTIA E AVAL** (doravante denominado "CONTRATO"), firmado em {{operacao.local}}, {{operacao.data_extenso}}, as partes abaixo qualificadas:

**MUTUANTE / CREDORA FIDUCIÁRIA:**
**ACM EMPRESA SIMPLES DE CRÉDITO LTDA**, pessoa jurídica de direito privado, constituída sob o regime da Lei Complementar nº 167/2019, inscrita no CNPJ/MF sob o nº **63.530.897/0001-85**, com sede na Rua Abelardo Benedicto Liborio, nº 600, Loteamento Distrito Industrial Uninorte, Município de Piracicaba, Estado de São Paulo, CEP 13.413-071, neste ato representada na forma de seu Contrato Social por {{credor.representante.nome}}, portador(a) da cédula de identidade RG nº {{credor.representante.rg}} e inscrito(a) no CPF/MF sob o nº {{credor.representante.cpf}}, doravante denominada simplesmente **"MUTUANTE"** ou **"CREDORA"**;

**MUTUÁRIO / DEVEDOR FIDUCIANTE:**
{{#devedor.pessoa_juridica}}
**{{devedor.razao_social}}**, {{devedor.descricao_juridica}}, inscrita no CNPJ/MF sob o nº **{{devedor.cnpj}}**, com sede em {{devedor.endereco_completo}}, neste ato representada por {{devedor.representante.nome}}, {{devedor.representante.nacionalidade}}, {{devedor.representante.estado_civil}}, portador(a) da cédula de identidade RG nº {{devedor.representante.rg}} e inscrito(a) no CPF/MF sob o nº {{devedor.representante.cpf}}, doravante denominado simplesmente **"MUTUÁRIO"** ou **"DEVEDOR"**;
{{/devedor.pessoa_juridica}}
{{^devedor.pessoa_juridica}}
**{{devedor.nome_completo}}**, {{devedor.nacionalidade}}, {{devedor.estado_civil}}, portador(a) da cédula de identidade RG nº {{devedor.rg}} e inscrito(a) no CPF/MF sob o nº **{{devedor.cpf}}**, residente e domiciliado(a) em {{devedor.endereco_completo}}, doravante denominado simplesmente **"MUTUÁRIO"**;
{{/devedor.pessoa_juridica}}

**AVALISTA / GARANTIDOR SOLIDÁRIO:**
**{{avalista.nome}}**, {{avalista.nacionalidade}}, {{avalista.estado_civil}}, {{avalista.profissao}}, portador(a) da cédula de identidade RG nº {{avalista.rg}} e inscrito(a) no CPF/MF sob o nº **{{avalista.cpf}}**, residente e domiciliado(a) em {{avalista.endereco_completo}}{{#avalista.casado}}, casado(a) sob o regime de {{avalista.regime_casamento}} com **{{avalista.conjuge.nome}}**, inscrito(a) no CPF/MF sob o nº {{avalista.conjuge.cpf}}, que comparece ao presente ato em outorga uxória/marital{{/avalista.casado}}, doravante denominado(a) simplesmente **"AVALISTA"**;

Têm entre si justo e contratado o presente Contrato de Mútuo Feneratício com Alienação Fiduciária de Veículo em Garantia e Aval, que se regerá pelas cláusulas e condições a seguir estipuladas, em conformidade com os artigos 586 a 592 do Código Civil Brasileiro, com a Lei Complementar nº 167/2019 e o Decreto-Lei nº 911/1969.

## CLÁUSULA 1ª — DO OBJETO E DO VALOR DO MÚTUO
**1.1.** A MUTUANTE entrega neste ato ao MUTUÁRIO, a título de mútuo feneratício, a quantia de **R$ {{operacao.valor_principal}}** ({{operacao.valor_principal_extenso}}), que o MUTUÁRIO declara receber em sua integralidade, dando plena quitação.

## CLÁUSULA 2ª — DO PRAZO E DA FORMA DE PAGAMENTO
**2.1.** O MUTUARIO obriga-se a pagar a obrigação em **{{operacao.num_parcelas}}** ({{operacao.num_parcelas_extenso}}) parcela(s) {{operacao.periodicidade}}, perfazendo o valor total de **R$ {{operacao.valor_total_devido}}** ({{operacao.valor_total_devido_extenso}}), conforme cronograma do **Anexo II**.

## CLÁUSULA 3ª — DOS JUROS REMUNERATÓRIOS
**3.1.** Sobre o valor principal incidirão juros remuneratórios à taxa de **{{operacao.taxa_juros_mensal}}% ao mês**, capitalizados mensalmente.

## CLÁUSULA 4ª — DOS ENCARGOS MORATÓRIOS
**4.1.** Em caso de atraso, incidirão: multa de 2%, juros de mora de 1% ao mês e correção monetária pelo IPCA.

## CLÁUSULA 5ª — DA GARANTIA REAL: ALIENAÇÃO FIDUCIÁRIA DE VEÍCULO
**5.1.** Em garantia do cumprimento das obrigações, o MUTUÁRIO aliena fiduciariamente à MUTUANTE o veículo: **{{veiculo.marca}} {{veiculo.modelo}}, Placa {{veiculo.placa}}, RENAVAM {{veiculo.renavam}}**.

## CLÁUSULA 6ª — DO AVAL
**6.1.** O AVALISTA declara-se garantidor solidário e principal pagador de todas as obrigações assumidas pelo MUTUÁRIO, renunciando ao benefício de ordem.

## CLÁUSULA 7ª — DA NOTA PROMISSÓRIA
**7.1.** O MUTUÁRIO emite, em favor da MUTUANTE, Nota Promissória no valor total da dívida como garantia adicional.

## CLÁUSULA 8ª — DO FORO
**8.1.** As partes elegem o Foro da Comarca de Piracicaba/SP.

{{operacao.local}}, {{operacao.data_extenso}}.

### MUTUANTE:

<br><br>
\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_<br>
**ACM EMPRESA SIMPLES DE CREDITO LTDA**<br>
CNPJ: 63.530.897/0001-85

### MUTUARIO:

<br><br>
\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_<br>
{{#devedor.pessoa_juridica}}**{{devedor.razao_social}}**{{/devedor.pessoa_juridica}}{{^devedor.pessoa_juridica}}**{{devedor.nome_completo}}**{{/devedor.pessoa_juridica}}<br>
{{devedor.documento_label}}: {{devedor.documento}}

{{#devedor.conjuge_assina}}
### CÔNJUGE DO MUTUÁRIO / DEVEDOR FIDUCIANTE (anuência):

<br><br>
\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_<br>
**{{devedor.conjuge.nome}}**<br>
CPF: {{devedor.conjuge.cpf}}
{{/devedor.conjuge_assina}}

### AVALISTA:

<br><br>
\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_<br>
**{{avalista.nome}}**<br>
CPF: {{avalista.cpf}}

{{#avalista.casado}}
### CÔNJUGE DO AVALISTA:

<br><br>
\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_<br>
**{{avalista.conjuge.nome}}**<br>
CPF: {{avalista.conjuge.cpf}}
{{/avalista.casado}}

### TESTEMUNHAS:

{{#testemunhas}}
<br><br>
\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_<br>
Nome: {{nome}} - CPF: {{cpf}}

{{/testemunhas}}

# ANEXO I — DADOS PARA PAGAMENTO

Os pagamentos deverão ser realizados em favor da **MUTUANTE**:

- **Banco:** {{credor.conta.banco}}
- **Agência:** {{credor.conta.agencia}}
- **Conta Corrente:** {{credor.conta.numero}}
- **PIX:** {{credor.conta.pix}}
- **Titular:** ACM EMPRESA SIMPLES DE CRÉDITO LTDA

# ANEXO II — CRONOGRAMA DE PAGAMENTOS

| Parcela | Data de Vencimento | Valor da Parcela | Amortizacao | Juros | Saldo Devedor |
|:---:|:---:|:---:|:---:|:---:|:---:|
{{#cronograma}}
| {{numero}} | {{data_vencimento}} | {{valor_parcela}} | {{valor_amortizacao}} | {{valor_juros}} | {{saldo_devedor}} |
{{/cronograma}}
