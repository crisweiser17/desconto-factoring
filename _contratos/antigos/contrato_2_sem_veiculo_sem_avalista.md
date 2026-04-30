# INSTRUMENTO PARTICULAR DE CONTRATO DE MÚTUO FENERATÍCIO

**Contrato nº {{operacao.id}}**

Pelo presente **INSTRUMENTO PARTICULAR DE CONTRATO DE MÚTUO FENERATÍCIO** (doravante denominado "CONTRATO"), firmado em {{operacao.local}}, {{operacao.data_extenso}}, as partes abaixo qualificadas:

**MUTUANTE:**
**ACM EMPRESA SIMPLES DE CRÉDITO LTDA**, pessoa jurídica de direito privado, constituída sob o regime da Lei Complementar nº 167/2019, inscrita no CNPJ/MF sob o nº **63.530.897/0001-85**, com sede na Rua Abelardo Benedicto Liborio, nº 600, Loteamento Distrito Industrial Uninorte, Município de Piracicaba, Estado de São Paulo, CEP 13.413-071, neste ato representada na forma de seu Contrato Social por {{credor.representante.nome}}, portador(a) da cédula de identidade RG nº {{credor.representante.rg}} e inscrito(a) no CPF/MF sob o nº {{credor.representante.cpf}}, doravante denominada simplesmente **"MUTUANTE"**;

**MUTUÁRIO:**
{{#devedor.pessoa_juridica}}
**{{devedor.razao_social}}**, {{devedor.descricao_juridica}}, inscrita no CNPJ/MF sob o nº **{{devedor.cnpj}}**, com sede em {{devedor.endereco_completo}}, neste ato representada por {{devedor.representante.nome}}, {{devedor.representante.nacionalidade}}, {{devedor.representante.estado_civil}}, portador(a) da cédula de identidade RG nº {{devedor.representante.rg}} e inscrito(a) no CPF/MF sob o nº {{devedor.representante.cpf}}, doravante denominado simplesmente **"MUTUÁRIO"**;
{{/devedor.pessoa_juridica}}
{{^devedor.pessoa_juridica}}
**{{devedor.nome_completo}}**, {{devedor.nacionalidade}}, {{devedor.estado_civil}}, portador(a) da cédula de identidade RG nº {{devedor.rg}} e inscrito(a) no CPF/MF sob o nº **{{devedor.cpf}}**, residente e domiciliado(a) em {{devedor.endereco_completo}}, doravante denominado simplesmente **"MUTUÁRIO"**;
{{/devedor.pessoa_juridica}}

Têm entre si justo e contratado o presente Contrato de Mútuo Feneratício, que se regerá pelas cláusulas e condições a seguir estipuladas, em conformidade com os artigos 586 a 592 do Código Civil Brasileiro e a Lei Complementar nº 167/2019.

## CLÁUSULA 1ª — DO OBJETO E DO VALOR DO MÚTUO
**1.1.** A MUTUANTE entrega neste ato ao MUTUÁRIO, a título de mútuo feneratício, a quantia de **R$ {{operacao.valor_principal}}** ({{operacao.valor_principal_extenso}}), que o MUTUÁRIO declara receber em sua integralidade, dando plena quitação.

## CLÁUSULA 2ª — DO PRAZO E DA FORMA DE PAGAMENTO
**2.1.** O MUTUARIO obriga-se a pagar a obrigação em **{{operacao.num_parcelas}}** ({{operacao.num_parcelas_extenso}}) parcela(s) {{operacao.periodicidade}}, perfazendo o valor total de **R$ {{operacao.valor_total_devido}}** ({{operacao.valor_total_devido_extenso}}), conforme cronograma do **Anexo II**.

## CLÁUSULA 3ª — DOS JUROS REMUNERATÓRIOS
**3.1.** Sobre o valor principal incidirão juros remuneratórios à taxa de **{{operacao.taxa_juros_mensal}}% ao mês**, capitalizados mensalmente.

## CLÁUSULA 4ª — DOS ENCARGOS MORATÓRIOS
**4.1.** Em caso de atraso, incidirão: multa de 2%, juros de mora de 1% ao mês e correção monetária pelo IPCA.

## CLÁUSULA 5ª — DA NOTA PROMISSÓRIA
**5.1.** O MUTUÁRIO emite, em favor da MUTUANTE, Nota Promissória no valor total da dívida como garantia do cumprimento das obrigações.

## CLÁUSULA 6ª — DO FORO
**6.1.** As partes elegem o Foro da Comarca de Piracicaba/SP.

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
