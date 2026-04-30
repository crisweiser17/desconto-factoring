# NOTA PROMISSÓRIA

**Nº {{np.numero}} — Vinculada ao Contrato de Mútuo nº {{operacao.id}}**

***

**Valor: R$ {{operacao.valor_total_devido}}**

***

**Vencimento:** À vista / {{np.vencimento}}

**Local e data de emissão:** {{operacao.local}}, {{operacao.data_extenso}}.

***

Aos **{{np.data_vencimento_extenso}}**, ou quando esta Nota Promissória me/nos for apresentada, pagarei(emos) por esta única via de **NOTA PROMISSÓRIA** à

**{{credor.razao_social}}**,<br />
inscrita no CNPJ/MF sob o nº **{{credor.documento}}**,<br />
com sede em {{credor.endereco_completo}},<br />
ou à sua ordem,

a quantia de **R$ {{operacao.valor_total_devido}}** (**{{operacao.valor_total_devido_extenso}}**), em moeda corrente nacional.

**Praça de pagamento:** Piracicaba, Estado de São Paulo.

***

### EMITENTE:

**{{devedor.razao_social}}**<br />
CNPJ: {{devedor.cnpj}}<br />
Endereço: {{devedor.endereco_completo}}

Representada por:<br />
**{{devedor.representante.nome}}**<br />
CPF: {{devedor.representante.cpf}}<br />
RG: {{devedor.representante.rg}}

<br><br><br>____________________________________________________<br>
(assinatura do emitente)

***

{{#avalista}}
### POR AVAL:

Nos termos do art. 30 e seguintes do Decreto nº 57.663/1966 (Lei Uniforme de Genebra), o(a) abaixo assinado(a) presta **AVAL** à presente Nota Promissória, responsabilizando-se solidariamente pelo pagamento integral do valor nela representado, como principal pagador(a), renunciando expressamente aos benefícios de ordem, divisão e excussão prévia.

**AVALISTA:**<br />
**{{avalista.nome}}**<br />
CPF: {{avalista.cpf}}<br />
RG: {{avalista.rg}}<br />
Endereço: {{avalista.endereco_completo}}

<br><br><br>____________________________________________________<br>
(assinatura do avalista)

{{#avalista.casado}}

### ANUÊNCIA CONJUGAL:

**{{avalista.conjuge.nome}}**, cônjuge do(a) AVALISTA, manifesta expressa anuência ao aval prestado, nos termos do art. 1.647, inciso III, do Código Civil.

CPF: {{avalista.conjuge.cpf}}

<br><br><br>____________________________________________________<br>
(assinatura do(a) cônjuge) {{/avalista.casado}}
{{/avalista}}

***

