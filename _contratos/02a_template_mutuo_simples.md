# INSTRUMENTO PARTICULAR DE CONTRATO DE MÚTUO FENERATÍCIO

**Contrato nº {{operacao.id}}**

Pelo presente **INSTRUMENTO PARTICULAR DE CONTRATO DE MÚTUO FENERATÍCIO** (doravante denominado "CONTRATO"), firmado em {{operacao.local}}, {{operacao.data\_extenso}}, as partes abaixo qualificadas:

**MUTUANTE / CREDORA:**\
**{{credor.razao\_social}}**, pessoa jurídica de direito privado, constituída sob o regime da Lei Complementar nº 167/2019, inscrita no CNPJ/MF sob o nº **{{credor.documento}}**, com sede em {{credor.endereco\_completo}}, neste ato representada na forma de seu Contrato Social por {{credor.representante.nome}}, {{credor.representante.nacionalidade}}, {{credor.representante.estado\_civil}}, portador(a) da cédula de identidade RG nº {{credor.representante.rg}} e inscrito(a) no CPF/MF sob o nº {{credor.representante.cpf}}, doravante denominada simplesmente **"MUTUANTE"** ou **"CREDORA"**;

**MUTUÁRIO / DEVEDOR:**\
**{{devedor.razao\_social}}**, {{devedor.descricao\_juridica}}, inscrita no CNPJ/MF sob o nº **{{devedor.cnpj}}**, enquadrada como **{{devedor.porte}}** nos termos da Lei Complementar nº 123/2006, com sede em {{devedor.endereco\_completo}}, neste ato representada por {{devedor.representante.nome}}, {{devedor.representante.nacionalidade}}, {{devedor.representante.estado\_civil}}, {{devedor.representante.profissao}}, portador(a) da cédula de identidade RG nº {{devedor.representante.rg}} e inscrito(a) no CPF/MF sob o nº {{devedor.representante.cpf}}, residente e domiciliado(a) em {{devedor.representante.endereco}}, doravante denominado simplesmente **"MUTUÁRIO"** ou **"DEVEDOR"**;

Têm entre si justo e contratado o presente Contrato de Mútuo Feneratício, que se regerá pelas cláusulas e condições a seguir estipuladas, em conformidade com os artigos 586 a 592 do Código Civil Brasileiro e com a Lei Complementar nº 167/2019.

***

## CONSIDERANDOS

**CONSIDERANDO** que a MUTUANTE é Empresa Simples de Crédito (ESC), constituída nos termos da Lei Complementar nº 167/2019, tendo por objeto social a realização de operações de empréstimo, financiamento e desconto de títulos de crédito com recursos exclusivamente próprios, destinadas a microempreendedores individuais, microempresas e empresas de pequeno porte;

**CONSIDERANDO** que o MUTUÁRIO declara e comprova, por documentação hábil anexa, possuir o enquadramento legal de **{{devedor.porte}}**, conforme Lei Complementar nº 123/2006;

**CONSIDERANDO** que o MUTUÁRIO solicitou à MUTUANTE a concessão de empréstimo em dinheiro, na modalidade simples (sem constituição de garantia real ou fidejussória);

**CONSIDERANDO** que a MUTUANTE aprovou a operação com base em análise de crédito própria e concorda em realizar o mútuo nas condições abaixo;

**AS PARTES** resolvem celebrar o presente CONTRATO, que se regerá pelas cláusulas seguintes:

***

## CLÁUSULA 1ª — DO OBJETO E DO VALOR DO MÚTUO

**1.1.** A MUTUANTE entrega neste ato ao MUTUÁRIO, a título de mútuo feneratício, a quantia de **R$ {{operacao.valor\_principal}}** ({{operacao.valor\_principal\_extenso}}), que o MUTUÁRIO declara receber em sua integralidade, por meio de {{operacao.forma\_liberacao}}, dando por este ato plena, rasa e geral quitação da entrega do capital.

**1.2.** O valor ora mutuado será destinado pelo MUTUÁRIO para fomento de sua atividade empresarial, podendo ser aplicado conforme critério exclusivo do MUTUÁRIO, nos limites de seu objeto social.

**1.3.** O valor total da dívida, considerando-se o capital e os encargos pactuados neste Contrato, pago na forma ordinária prevista na Cláusula 2ª, totaliza **R$ {{operacao.valor\_total\_devido}}** ({{operacao.valor\_total\_devido\_extenso}}).

***

## CLÁUSULA 2ª — DO PRAZO E DA FORMA DE PAGAMENTO

**2.1.** O MUTUÁRIO obriga-se a pagar à MUTUANTE o valor mutuado acrescido dos juros remuneratórios pactuados na Cláusula 3ª, em **{{operacao.num\_parcelas}}** ({{operacao.num\_parcelas\_extenso}}) parcelas {{operacao.periodicidade}}, no valor individual de **R$ {{operacao.valor\_parcela}}** ({{operacao.valor\_parcela\_extenso}}) cada uma.

**2.2.** A primeira parcela vencerá em **{{operacao.data\_primeiro\_vencimento}}** e as subsequentes em igual dia dos meses seguintes, até a liquidação integral da dívida, conforme cronograma de pagamentos que integra o presente Contrato como **Anexo I**.

**2.3.** Os pagamentos deverão ser realizados exclusivamente através de {{operacao.forma\_pagamento}}, de titularidade da MUTUANTE:

- **Banco:** {{credor.conta.banco}}
- **Agência:** {{credor.conta.agencia}}
- **Conta:** {{credor.conta.numero}}
- **Titular:** {{credor.conta.titular}} — CPF/CNPJ {{credor.conta.documento}}
- **Chave PIX:** {{credor.conta.pix}}

**2.4.** Caberá ao MUTUÁRIO enviar comprovante de pagamento à MUTUANTE em até 24 (vinte e quatro) horas da efetivação, sob pena de a MUTUANTE considerar o pagamento não realizado até a efetiva confirmação na conta.

**2.5.** A quitação individual de cada parcela não implica em quitação das parcelas anteriores, cabendo ao MUTUÁRIO conservar todos os comprovantes, sendo válida como prova de quitação geral apenas a quitação expressa, por escrito, emitida pela MUTUANTE.

***

## CLÁUSULA 3ª — DOS JUROS REMUNERATÓRIOS

**3.1.** Sobre o valor principal incidirão juros remuneratórios à taxa efetiva de **{{operacao.taxa\_juros\_mensal}}% ({{operacao.taxa\_juros\_mensal\_extenso}} por cento) ao mês**, equivalente à taxa efetiva de **{{operacao.taxa\_juros\_anual}}% ({{operacao.taxa\_juros\_anual\_extenso}} por cento) ao ano**, computados de forma capitalizada, livremente pactuada entre as partes nos termos do art. 591 do Código Civil.

**3.2.** Para fins de transparência e atendimento ao princípio da boa-fé contratual, declaram as partes que o Custo Efetivo Total (CET) da presente operação é de **{{operacao.cet}}% ao mês**, englobando os juros remuneratórios e eventuais encargos operacionais, conforme memória de cálculo no **Anexo I**.

**3.3.** As partes declaram estar cientes e concordar que, em razão da natureza jurídica da MUTUANTE como Empresa Simples de Crédito, nos termos da Lei Complementar nº 167/2019, a limitação da taxa de juros prevista no Decreto nº 22.626/1933 (Lei da Usura) não se aplica à presente operação, sendo livres as partes para pactuar a taxa estabelecida na Cláusula 3.1.

***

## CLÁUSULA 4ª — DOS ENCARGOS MORATÓRIOS

**4.1.** Em caso de atraso no pagamento de qualquer parcela, no todo ou em parte, incidirão sobre o valor em mora os seguintes encargos, cumulativamente:

a) **Multa moratória** não compensatória de **{{operacao.taxa\_multa\_atraso}}% ({{operacao.taxa\_multa\_atraso\_extenso}} por cento)** sobre o valor devido;

b) **Juros de mora** à taxa de **{{operacao.taxa\_juros\_atraso}}% ({{operacao.taxa\_juros\_atraso\_extenso}} por cento) ao mês**, *pro rata die*, a contar da data do vencimento;

c) **Correção monetária** pelo Índice Nacional de Preços ao Consumidor Amplo (IPCA), divulgado pelo IBGE, ou, na sua falta, por índice que o venha a substituir;

d) **Honorários advocatícios** de **10% (dez por cento)** sobre o valor total do débito, em caso de cobrança extrajudicial, e conforme arbitrados pelo juízo em caso de cobrança judicial.

**4.2.** O MUTUÁRIO responderá, ainda, pelas despesas de protesto, notificações, custas processuais, diligências de localização de bens e outras despesas incorridas pela MUTUANTE para o recebimento do crédito.

***

## CLÁUSULA 5ª — DO VENCIMENTO ANTECIPADO

**5.1.** A MUTUANTE poderá declarar o vencimento antecipado de toda a dívida, tornando-a imediatamente líquida, certa e exigível, independentemente de notificação ou interpelação, judicial ou extrajudicial, nas seguintes hipóteses:

a) Atraso no pagamento de qualquer parcela por mais de **10 (dez) dias corridos**;

b) Pedido de recuperação judicial, extrajudicial ou decretação de falência do MUTUÁRIO;

c) Protesto de títulos ou inclusão do MUTUÁRIO em cadastros de inadimplentes (SERASA, SPC, CCF) em valor superior a R$ 5.000,00 (cinco mil reais);

d) Descumprimento de qualquer outra obrigação prevista neste Contrato;

e) Falsidade, omissão ou inexatidão das declarações prestadas pelo MUTUÁRIO;

f) Descaracterização do porte empresarial do MUTUÁRIO que o torne inelegível para operações com ESC;

g) Dissolução, liquidação, cisão ou extinção do MUTUÁRIO.

**5.2.** Verificada qualquer das hipóteses acima, ficará automaticamente vencida toda a dívida, com todos os encargos pactuados, facultada à MUTUANTE a cobrança imediata, extrajudicial ou judicial.

***

## CLÁUSULA 6ª — DA CONFISSÃO DE DÍVIDA

**6.1.** O MUTUÁRIO, por este instrumento, reconhece e confessa expressamente a dívida ora constituída como **líquida, certa e exigível**, nos exatos termos e valores pactuados, comprometendo-se pelo seu pagamento, na forma e prazos estabelecidos neste Contrato.

**6.2.** O presente Contrato, firmado pelas partes e por 2 (duas) testemunhas, constitui **título executivo extrajudicial**, nos termos do art. 784, inciso III, do Código de Processo Civil, dispensando qualquer procedimento prévio de cognição para sua execução em caso de inadimplemento.

***

## CLÁUSULA 7ª — DA NOTA PROMISSÓRIA VINCULADA

**7.1.** Para conferir maior liquidez e segurança ao crédito ora constituído, o MUTUÁRIO emite, neste ato, em favor da MUTUANTE, **Nota Promissória** no valor de **R$ {{operacao.valor\_total\_devido}}** ({{operacao.valor\_total\_devido\_extenso}}), correspondente ao valor total da dívida, com vencimento à vista, que integra o presente Contrato como **Anexo II**.

**7.2.** A Nota Promissória vincula-se ao presente Contrato na modalidade **pro solvendo**, ou seja, sua emissão e entrega à MUTUANTE não implicam em novação da dívida, permanecendo válidas e exigíveis todas as obrigações previstas neste Contrato, podendo a MUTUANTE optar por executar o título cambial ou o Contrato, conforme lhe convier.

**7.3.** A Nota Promissória poderá ser preenchida pela MUTUANTE com a data e o valor atualizado do débito em caso de inadimplemento, nos termos da Súmula 387 do Supremo Tribunal Federal.

***

## CLÁUSULA 8ª — DA COBRANÇA, PROTESTO E CADASTROS

**8.1.** Vencida e não paga qualquer parcela, fica a MUTUANTE expressamente autorizada a:

a) Levar o presente Contrato e/ou a Nota Promissória a protesto extrajudicial;

b) Incluir o nome do MUTUÁRIO em cadastros de proteção ao crédito (SERASA, SPC, SCPC, Boa Vista, entre outros), independentemente de prévia comunicação além da legalmente exigida;

c) Compartilhar informações da operação com bureaus de crédito e com o Cadastro Positivo (Lei 12.414/2011), conforme autorização expressa do MUTUÁRIO firmada em documento específico.

***

## CLÁUSULA 9ª — DAS COMUNICAÇÕES

**9.1.** Todas as comunicações entre as partes relativas a este Contrato serão consideradas válidas quando enviadas para os seguintes endereços:

- **MUTUANTE:** {{credor.endereco\_completo}} — E-mail: {{credor.email}} — WhatsApp: {{credor.whatsapp}}
- **MUTUÁRIO:** {{devedor.endereco\_completo}} — E-mail: {{devedor.email}} — WhatsApp: {{devedor.whatsapp}}

**9.2.** Qualquer mudança de endereço ou meio de contato deverá ser comunicada à outra parte por escrito, sob pena de se considerarem válidas as comunicações enviadas aos endereços acima informados.

***

## CLÁUSULA 10ª — DAS DISPOSIÇÕES GERAIS

**10.1.** Este Contrato obriga as partes, seus herdeiros e sucessores a qualquer título.

**10.2.** A tolerância de uma parte quanto ao descumprimento de qualquer cláusula pela outra não importará em novação ou renúncia ao direito de exigir o cumprimento, a qualquer tempo.

**10.3.** Caso qualquer cláusula deste Contrato seja declarada nula ou ineficaz, permanecerão íntegras e eficazes as demais disposições.

**10.4.** Este Contrato poderá ser assinado **eletronicamente**, nos termos da Medida Provisória nº 2.200-2/2001 e da Lei nº 14.063/2020, sendo consideradas válidas e eficazes as assinaturas realizadas por meio de plataformas de assinatura eletrônica reconhecidas.

**10.5.** O MUTUÁRIO autoriza expressamente a MUTUANTE a tratar seus dados pessoais e empresariais para fins da operação, nos termos da Lei Geral de Proteção de Dados (Lei nº 13.709/2018), podendo compartilhá-los com bureaus de crédito, órgãos de proteção ao crédito, cartórios, advogados, assessorias de cobrança e autoridades legais, sempre que necessário para o cumprimento das obrigações contratuais ou legais.

***

## CLÁUSULA 11ª — DO FORO

**11.1.** As partes elegem o Foro da Comarca de **Piracicaba/SP** para dirimir quaisquer dúvidas ou controvérsias oriundas deste Contrato, com renúncia expressa a qualquer outro, por mais privilegiado que seja.

***

E, por estarem justas e contratadas, as partes firmam o presente Contrato em **{{operacao.num\_vias}} ({{operacao.num\_vias\_extenso}}) vias de igual teor e forma**, na presença de 2 (duas) testemunhas abaixo assinadas.

**{{operacao.local}}, {{operacao.data\_extenso}}.**

***

### MUTUANTE:

\
\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\
**{{credor.razao\_social}}**\
CNPJ: {{credor.documento}}\
p.p.: {{credor.representante.nome}}\
CPF: {{credor.representante.cpf}}

### MUTUÁRIO / DEVEDOR:

\
\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\
**{{devedor.razao\_social}}**\
CNPJ: {{devedor.cnpj}}\
p.p.: {{devedor.representante.nome}}\
CPF: {{devedor.representante.cpf}}

{{#devedor.conjuge\_assina}}

### CÔNJUGE:

Assinatura:

<div style="margin: 12px 0 6px 0; border-bottom: 1px solid #000; width: 100%; height: 18px;"></div>

Nome: \_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\
CPF:  \_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_
\
{{/devedor.conjuge\_assina}}

### TESTEMUNHAS:

\
\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\
Nome: {{testemunhas.0.nome}}\
CPF: {{testemunhas.0.cpf}}

\
\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\
Nome: {{testemunhas.1.nome}}\
CPF: {{testemunhas.1.cpf}}

***

# ANEXO I — CRONOGRAMA DE PAGAMENTOS E MEMÓRIA DE CÁLCULO

**Contrato nº {{operacao.id}}**

| Dados da Operação            | Valor                                                                          |
| ---------------------------- | ------------------------------------------------------------------------------ |
| Capital Mutuado              | R$ {{operacao.valor\_principal}}                                               |
| Taxa de Juros Remuneratórios | {{operacao.taxa\_juros\_mensal}}% a.m. / {{operacao.taxa\_juros\_anual}}% a.a. |
| Custo Efetivo Total (CET)    | {{operacao.cet}}% a.m.                                                         |
| Número de Parcelas           | {{operacao.num\_parcelas}}                                                     |
| Valor da Parcela             | R$ {{operacao.valor\_parcela}}                                                 |
| Valor Total da Dívida        | R$ {{operacao.valor\_total\_devido}}                                           |
| Total de Juros               | R$ {{operacao.total\_juros}}                                                   |
| Sistema de Amortização       | {{operacao.sistema\_amortizacao}}                                              |

### Cronograma de Vencimentos:

|     Parcela     |  Data de Vencimento  | Valor da Parcela (R$) |       Amortização (R$) |       Juros (R$) | Saldo Devedor (R$) |
| :-------------: | :------------------: | --------------------: | ---------------------: | ---------------: | -----------------: |
| {{#cronograma}} |        <br />        |                <br /> |                 <br /> |           <br /> |             <br /> |
|    {{numero}}   | {{data\_vencimento}} |    {{valor\_parcela}} | {{valor\_amortizacao}} | {{valor\_juros}} | {{saldo\_devedor}} |
| {{/cronograma}} |        <br />        |                <br /> |                 <br /> |           <br /> |             <br /> |

***

# ANEXO II — NOTA PROMISSÓRIA

(Documento emitido em separado. Ver arquivo: `NP_Contrato_{{operacao.id}}.pdf`)
