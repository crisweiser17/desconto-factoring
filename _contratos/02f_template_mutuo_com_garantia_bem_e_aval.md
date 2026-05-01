# INSTRUMENTO PARTICULAR DE CONTRATO DE MÚTUO FENERATÍCIO COM ALIENAÇÃO FIDUCIÁRIA DE BEM MÓVEL EM GARANTIA E AVAL

**Contrato nº {{operacao.id}}**

Pelo presente **INSTRUMENTO PARTICULAR DE CONTRATO DE MÚTUO FENERATÍCIO COM ALIENAÇÃO FIDUCIÁRIA DE BEM MÓVEL EM GARANTIA E AVAL** (doravante denominado "CONTRATO"), firmado em {{operacao.local}}, {{operacao.data_extenso}}, as partes abaixo qualificadas:

**MUTUANTE / CREDORA FIDUCIÁRIA:**<br />
**{{credor.razao_social}}**, pessoa jurídica de direito privado, constituída sob o regime da Lei Complementar nº 167/2019, inscrita no CNPJ/MF sob o nº **{{credor.documento}}**, com sede em {{credor.endereco_completo}}, neste ato representada na forma de seu Contrato Social por {{credor.representante.nome}}, {{credor.representante.nacionalidade}}, {{credor.representante.estado_civil}}, portador(a) da cédula de identidade RG nº {{credor.representante.rg}} e inscrito(a) no CPF/MF sob o nº {{credor.representante.cpf}}, doravante denominada simplesmente **"MUTUANTE"** ou **"CREDORA"**;

**MUTUÁRIO / DEVEDOR FIDUCIANTE:**<br />
**{{devedor.razao_social}}**, {{devedor.descricao_juridica}}, inscrita no CNPJ/MF sob o nº **{{devedor.cnpj}}**, enquadrada como **{{devedor.porte}}** nos termos da Lei Complementar nº 123/2006, com sede em {{devedor.endereco_completo}}, neste ato representada por {{devedor.representante.nome}}, {{devedor.representante.nacionalidade}}, {{devedor.representante.estado_civil}}, {{devedor.representante.profissao}}, portador(a) da cédula de identidade RG nº {{devedor.representante.rg}} e inscrito(a) no CPF/MF sob o nº {{devedor.representante.cpf}}, residente e domiciliado(a) em {{devedor.representante.endereco}}, doravante denominado simplesmente **"MUTUÁRIO"** ou **"DEVEDOR"**;

**AVALISTA / GARANTIDOR SOLIDÁRIO:**<br />
**{{avalista.nome}}**, {{avalista.nacionalidade}}, {{avalista.estado_civil}}, {{avalista.profissao}}, portador(a) da cédula de identidade RG nº {{avalista.rg}} e inscrito(a) no CPF/MF sob o nº **{{avalista.cpf}}**, residente e domiciliado(a) em {{avalista.endereco_completo}}{{#avalista.casado}}, casado(a) sob o regime de {{avalista.regime_casamento}} com **{{avalista.conjuge.nome}}**, inscrito(a) no CPF/MF sob o nº {{avalista.conjuge.cpf}}, que comparece ao presente ato em outorga uxória/marital, nos termos do art. 1.647, inciso III, do Código Civil{{/avalista.casado}}, doravante denominado(a) simplesmente **"AVALISTA"**;

Têm entre si justo e contratado o presente Contrato de Mútuo Feneratício com Alienação Fiduciária de Bem Móvel em Garantia e Aval, que se regerá pelas cláusulas e condições a seguir estipuladas, em conformidade com os artigos 586 a 592, 818 a 839 e 1.361 a 1.368-B do Código Civil Brasileiro, com a Lei Complementar nº 167/2019 e demais legislação aplicável.

***

## CONSIDERANDOS

**CONSIDERANDO** que a MUTUANTE é Empresa Simples de Crédito (ESC), constituída nos termos da Lei Complementar nº 167/2019, tendo por objeto social a realização de operações de empréstimo, financiamento e desconto de títulos de crédito com recursos exclusivamente próprios, destinadas a microempreendedores individuais, microempresas e empresas de pequeno porte;

**CONSIDERANDO** que o MUTUÁRIO declara e comprova, por documentação hábil anexa, possuir o enquadramento legal de **{{devedor.porte}}**, conforme Lei Complementar nº 123/2006;

**CONSIDERANDO** que o MUTUÁRIO solicitou à MUTUANTE a concessão de empréstimo em dinheiro, mediante a constituição de garantia real sobre bem móvel infungível e garantia fidejussória por meio de aval;

**CONSIDERANDO** que a MUTUANTE aprovou a operação com base em análise de crédito própria do MUTUÁRIO e do AVALISTA, e na qualidade do bem oferecido em garantia, e concorda em realizar o mútuo nas condições abaixo;

**AS PARTES** resolvem celebrar o presente CONTRATO, que se regerá pelas cláusulas seguintes:

***

## CLÁUSULA 1ª — DO OBJETO E DO VALOR DO MÚTUO

**1.1.** A MUTUANTE entrega neste ato ao MUTUÁRIO, a título de mútuo feneratício, a quantia de **R$ {{operacao.valor_principal}}** ({{operacao.valor_principal_extenso}}), que o MUTUÁRIO declara receber em sua integralidade, por meio de {{operacao.forma_liberacao}}, dando por este ato plena, rasa e geral quitação da entrega do capital.

**1.2.** O valor ora mutuado será destinado pelo MUTUÁRIO para fomento de sua atividade empresarial, podendo ser aplicado conforme critério exclusivo do MUTUÁRIO, nos limites de seu objeto social.

**1.3.** O valor total da dívida, considerando-se o capital e os encargos pactuados neste Contrato, pago na forma ordinária prevista na Cláusula 2ª, totaliza **R$ {{operacao.valor_total_devido}}** ({{operacao.valor_total_devido_extenso}}).

***

## CLÁUSULA 2ª — DO PRAZO E DA FORMA DE PAGAMENTO

**2.1.** O MUTUÁRIO obriga-se a pagar à MUTUANTE o valor mutuado acrescido dos juros remuneratórios pactuados na Cláusula 3ª, em **{{operacao.num_parcelas}}** ({{operacao.num_parcelas_extenso}}) parcelas {{operacao.periodicidade}}, no valor individual de **R$ {{operacao.valor_parcela}}** ({{operacao.valor_parcela_extenso}}) cada uma.

**2.2.** A primeira parcela vencerá em **{{operacao.data_primeiro_vencimento}}** e as subsequentes em igual dia dos meses seguintes, até a liquidação integral da dívida, conforme cronograma de pagamentos que integra o presente Contrato como **Anexo I**.

**2.3.** Os pagamentos deverão ser realizados exclusivamente através de {{operacao.forma_pagamento}}, de titularidade da MUTUANTE:

- **Banco:** {{credor.conta.banco}}
- **Agência:** {{credor.conta.agencia}}
- **Conta:** {{credor.conta.numero}}
- **Titular:** {{credor.conta.titular}} — CPF/CNPJ {{credor.conta.documento}}
- **Chave PIX:** {{credor.conta.pix}}

**2.4.** Caberá ao MUTUÁRIO enviar comprovante de pagamento à MUTUANTE em até 24 (vinte e quatro) horas da efetivação, sob pena de a MUTUANTE considerar o pagamento não realizado até a efetiva confirmação na conta.

**2.5.** A quitação individual de cada parcela não implica em quitação das parcelas anteriores, cabendo ao MUTUÁRIO conservar todos os comprovantes, sendo válida como prova de quitação geral apenas a quitação expressa, por escrito, emitida pela MUTUANTE.

***

## CLÁUSULA 3ª — DOS JUROS REMUNERATÓRIOS

**3.1.** Sobre o valor principal incidirão juros remuneratórios à taxa efetiva de **{{operacao.taxa_juros_mensal}}% ({{operacao.taxa_juros_mensal_extenso}} por cento) ao mês**, equivalente à taxa efetiva de **{{operacao.taxa_juros_anual}}% ({{operacao.taxa_juros_anual_extenso}} por cento) ao ano**, computados de forma capitalizada, livremente pactuada entre as partes nos termos do art. 591 do Código Civil.

**3.2.** Para fins de transparência e atendimento ao princípio da boa-fé contratual, declaram as partes que o Custo Efetivo Total (CET) da presente operação é de **{{operacao.cet}}% ao mês**, englobando os juros remuneratórios e eventuais encargos operacionais, conforme memória de cálculo no **Anexo I**.

**3.3.** As partes declaram estar cientes e concordar que, em razão da natureza jurídica da MUTUANTE como Empresa Simples de Crédito, nos termos da Lei Complementar nº 167/2019, a limitação da taxa de juros prevista no Decreto nº 22.626/1933 (Lei da Usura) não se aplica à presente operação, sendo livres as partes para pactuar a taxa estabelecida na Cláusula 3.1.

***

## CLÁUSULA 4ª — DOS ENCARGOS MORATÓRIOS

**4.1.** Em caso de atraso no pagamento de qualquer parcela, no todo ou em parte, incidirão sobre o valor em mora os seguintes encargos, cumulativamente:

a) **Multa moratória** não compensatória de **{{operacao.taxa_multa_atraso}}% ({{operacao.taxa_multa_atraso_extenso}} por cento)** sobre o valor devido;

b) **Juros de mora** à taxa de **{{operacao.taxa_juros_atraso}}% ({{operacao.taxa_juros_atraso_extenso}} por cento) ao mês**, *pro rata die*, a contar da data do vencimento;

c) **Correção monetária** pelo Índice Nacional de Preços ao Consumidor Amplo (IPCA), divulgado pelo IBGE, ou, na sua falta, por índice que o venha a substituir;

d) **Honorários advocatícios** de **10% (dez por cento)** sobre o valor total do débito, em caso de cobrança extrajudicial, e conforme arbitrados pelo juízo em caso de cobrança judicial.

**4.2.** O MUTUÁRIO e o AVALISTA responderão, ainda, pelas despesas de protesto, notificações, custas processuais, diligências de localização de bens e outras despesas incorridas pela MUTUANTE para o recebimento do crédito.

***

## CLÁUSULA 5ª — DO VENCIMENTO ANTECIPADO

**5.1.** A MUTUANTE poderá declarar o vencimento antecipado de toda a dívida, tornando-a imediatamente líquida, certa e exigível, independentemente de notificação ou interpelação, judicial ou extrajudicial, nas seguintes hipóteses:

a) Atraso no pagamento de qualquer parcela por mais de **10 (dez) dias corridos**;

b) Pedido de recuperação judicial, extrajudicial ou decretação de falência do MUTUÁRIO ou do AVALISTA;

c) Protesto de títulos ou inclusão do MUTUÁRIO ou AVALISTA em cadastros de inadimplentes (SERASA, SPC, CCF) em valor superior a R$ 5.000,00 (cinco mil reais);

d) Descumprimento de qualquer outra obrigação prevista neste Contrato;

e) Alienação, oneração, transferência, cessão ou constituição de qualquer ônus sobre o BEM dado em alienação fiduciária, sem prévia e expressa autorização da MUTUANTE;

f) Falsidade, omissão ou inexatidão das declarações prestadas pelo MUTUÁRIO ou AVALISTA;

g) Descaracterização do porte empresarial do MUTUÁRIO que o torne inelegível para operações com ESC;

h) Sinistro, perda total, deterioração substancial, apreensão ou constrição judicial do BEM sem substituição por garantia equivalente em prazo de até 30 (trinta) dias;

i) Remoção do BEM do local de guarda informado neste Contrato sem prévia comunicação e autorização escrita da MUTUANTE;

j) Falecimento ou incapacitação do AVALISTA sem substituição imediata por garantidor aprovado pela MUTUANTE;

k) Dissolução, liquidação, cisão ou extinção do MUTUÁRIO.

**5.2.** Verificada qualquer das hipóteses acima, ficará automaticamente vencida toda a dívida, com todos os encargos pactuados, facultada à MUTUANTE a cobrança imediata, extrajudicial ou judicial, e a execução das garantias prestadas.

***

## CLÁUSULA 6ª — DA GARANTIA REAL: ALIENAÇÃO FIDUCIÁRIA DE BEM MÓVEL

**6.1.** Em garantia do pontual e integral cumprimento das obrigações assumidas neste Contrato, o MUTUÁRIO, na qualidade de **DEVEDOR FIDUCIANTE**, aliena fiduciariamente à MUTUANTE, na qualidade de **CREDORA FIDUCIÁRIA**, nos termos dos artigos 1.361 a 1.368-B do Código Civil Brasileiro, o bem móvel infungível de sua propriedade, abaixo identificado e caracterizado (doravante denominado simplesmente **"BEM"**):

### Identificação do BEM:

- **Tipo / Categoria:** {{bem.tipo}}
- **Descrição detalhada:** {{bem.descricao_detalhada}}
- **Identificador(es) único(s) (número de série, patrimônio, IMEI, lote, ou outra marcação inequívoca):** {{bem.identificadores}}
- **Local de guarda do BEM:** {{bem.local_guarda}}
- **Valor atribuído ao BEM (avaliação):** R$ {{bem.valor_avaliacao}} ({{bem.valor_avaliacao_extenso}})
  {{#bem.documentos_origem}}
- **Documentos de origem do BEM (nota fiscal, recibo, contrato de compra, ou equivalente):** {{bem.documentos_origem}}
  {{/bem.documentos_origem}}

**6.2.** A descrição constante do item 6.1 é considerada parte integrante e indissociável deste Contrato, sendo que qualquer divergência entre o BEM efetivamente entregue em garantia e a descrição aqui constante autoriza a MUTUANTE a recusar a operação ou a declarar seu vencimento antecipado, conforme o caso.

**6.3.** Com a presente alienação fiduciária, a propriedade resolúvel e a posse indireta do BEM transferem-se à MUTUANTE, permanecendo o MUTUÁRIO com a posse direta, na qualidade de **fiel depositário**, com todos os ônus e encargos civis e penais inerentes a esta condição, podendo responder, em caso de descumprimento das obrigações de depositário, inclusive nas esferas cível e criminal aplicáveis (arts. 168, 168-A e correlatos do Código Penal).

**6.4.** O MUTUÁRIO obriga-se a:

a) **Levar o presente Contrato a registro no Cartório de Títulos e Documentos** competente do domicílio do MUTUÁRIO, em até **15 (quinze) dias úteis** da data da assinatura, às suas expensas, entregando à MUTUANTE cópia da certidão de registro, conforme exigência do art. 1.361, §1º, do Código Civil, para que a alienação fiduciária produza efeitos perante terceiros;

b) Manter o BEM em perfeito estado de conservação, funcionamento e uso, arcando integralmente com todas as despesas de manutenção, reparos, peças, insumos, impostos, taxas e encargos de qualquer natureza relacionados ao BEM;

c) Quando o valor do BEM ou as condições da operação justificarem, conforme decisão da MUTUANTE, **contratar e manter vigente apólice de seguro patrimonial** que cubra danos materiais, incêndio, alagamento, roubo e furto, indicando a MUTUANTE como **beneficiária até o limite do saldo devedor**, e entregar à MUTUANTE cópia autenticada da apólice em até 15 (quinze) dias da contratação;

d) **Não alienar, ceder, transferir, alugar, locar, emprestar, gravar com qualquer ônus, dar em comodato ou em qualquer outra forma de cessão de uso a terceiros**, sem prévia e expressa autorização escrita da MUTUANTE;

e) **Manter o BEM no local de guarda indicado no item 6.1**, comunicando previamente e por escrito à MUTUANTE qualquer alteração definitiva ou prolongada do local, sob pena de vencimento antecipado;

f) **Permitir a inspeção do BEM** pela MUTUANTE ou por seus prepostos, em data e local previamente comunicados, com antecedência mínima de 48 (quarenta e oito) horas, ficando o MUTUÁRIO obrigado a facilitar o acesso e a apresentação do BEM;

g) Comunicar imediatamente à MUTUANTE, por escrito, qualquer evento que afete o BEM, incluindo dano, sinistro, roubo, furto, deterioração substancial, perda total, apreensão ou constrição judicial.

**6.5.** No caso de inadimplemento, caracterizada a mora pela notificação do MUTUÁRIO via Cartório de Títulos e Documentos ou por carta registrada com aviso de recebimento, poderá a MUTUANTE:

a) Consolidar a propriedade plena e a posse direta do BEM em seu nome, nos termos do art. 1.364 do Código Civil, mediante a integralização da dívida ou pelo procedimento legal cabível;

b) **Promover a venda do BEM**, judicial ou extrajudicialmente, independentemente de leilão, hasta pública, avaliação prévia ou qualquer outra medida judicial, conforme art. 1.364 do Código Civil, aplicando o produto da venda no pagamento do seu crédito e das despesas decorrentes;

c) **Cobrar do MUTUÁRIO e do AVALISTA, solidariamente, o saldo devedor remanescente**, caso o produto da venda seja insuficiente para quitar integralmente a dívida e seus acessórios, com todos os encargos moratórios, podendo executar o presente Contrato como título executivo extrajudicial;

d) Restituir ao MUTUÁRIO eventual saldo remanescente, se houver, após a quitação integral do débito e despesas de cobrança e venda.

**6.6.** Verificada a inadimplência, fica a MUTUANTE expressamente autorizada a ajuizar **ação de busca e apreensão** do BEM, ou outra medida judicial equivalente, para reaver a posse direta, valendo-se de todos os meios legais cabíveis para a satisfação de seu crédito.

**6.7.** Declara o MUTUÁRIO que o BEM é de sua legítima propriedade, livre e desembaraçado de quaisquer ônus, gravames, penhoras, alienações fiduciárias anteriores ou direitos de terceiros, responsabilizando-se civil e criminalmente pela veracidade desta declaração, sob pena de configuração dos crimes previstos nos artigos 168, 171 e 297 do Código Penal.

**6.8.** O MUTUÁRIO declara, ainda, que o BEM existe, está em sua posse direta no local indicado, encontra-se em condições de uso e funcionamento, e que sua descrição neste Contrato corresponde fielmente ao bem efetivamente oferecido em garantia.

***

## CLÁUSULA 7ª — DO AVAL E DA GARANTIA FIDEJUSSÓRIA

**7.1.** O(A) AVALISTA, devidamente qualificado(a) no preâmbulo deste Contrato, declara-se garantidor solidário, principal pagador e responsável por todas as obrigações, principais e acessórias, assumidas pelo MUTUÁRIO neste Contrato e na Nota Promissória vinculada, renunciando expressamente aos benefícios de ordem, divisão, excussão prévia e demais benefícios previstos nos artigos 827, 828, 829 e 830 do Código Civil.

**7.2.** A responsabilidade do(a) AVALISTA permanecerá íntegra até a efetiva, integral e definitiva quitação do Contrato, não sendo afetada por qualquer moratória, prorrogação, repactuação, renegociação ou parcelamento concedido pela MUTUANTE ao MUTUÁRIO, ainda que sem prévia consulta ou aviso ao AVALISTA, nem pela eventual execução, venda ou perda da garantia real prestada nos termos da Cláusula 6ª.

**7.3.** O(A) AVALISTA declara conhecer e aceitar integralmente todos os termos deste Contrato, tendo sido esclarecido(a) quanto aos valores, prazos, encargos, hipóteses de vencimento antecipado e demais condições, inclusive quanto à descrição e às condições do BEM dado em garantia.

{{#avalista.casado}}
**7.4.** O(A) cônjuge do(a) AVALISTA, devidamente qualificado(a) no preâmbulo, comparece ao presente ato e manifesta sua **expressa e irrestrita anuência** ao aval prestado, nos termos do art. 1.647, inciso III, do Código Civil, em vista do regime de bens de seu casamento ({{avalista.regime_casamento}}).
{{/avalista.casado}}

***

## CLÁUSULA 8ª — DA CONFISSÃO DE DÍVIDA

**8.1.** O MUTUÁRIO e o(a) AVALISTA, por este instrumento, reconhecem e confessam expressamente a dívida ora constituída como **líquida, certa e exigível**, nos exatos termos e valores pactuados, comprometendo-se solidariamente pelo seu pagamento, na forma e prazos estabelecidos neste Contrato.

**8.2.** O presente Contrato, firmado pelas partes e por 2 (duas) testemunhas, constitui **título executivo extrajudicial**, nos termos do art. 784, inciso III, do Código de Processo Civil, dispensando qualquer procedimento prévio de cognição para sua execução em caso de inadimplemento.

***

## CLÁUSULA 9ª — DA NOTA PROMISSÓRIA VINCULADA

**9.1.** Para conferir maior liquidez e segurança ao crédito ora constituído, o MUTUÁRIO emite, neste ato, em favor da MUTUANTE, **Nota Promissória** no valor de **R$ {{operacao.valor_total_devido}}** ({{operacao.valor_total_devido_extenso}}), correspondente ao valor total da dívida, com vencimento à vista, **avalizada pelo(a) AVALISTA**, que integra o presente Contrato como **Anexo II**.

**9.2.** A Nota Promissória vincula-se ao presente Contrato na modalidade **pro solvendo**, ou seja, sua emissão e entrega à MUTUANTE não implicam em novação da dívida, permanecendo válidas e exigíveis todas as obrigações previstas neste Contrato, podendo a MUTUANTE optar por executar o título cambial ou o Contrato, conforme lhe convier.

**9.3.** A Nota Promissória poderá ser preenchida pela MUTUANTE com a data e o valor atualizado do débito em caso de inadimplemento, nos termos da Súmula 387 do Supremo Tribunal Federal.

***

## CLÁUSULA 10ª — DA COBRANÇA, PROTESTO E CADASTROS

**10.1.** Vencida e não paga qualquer parcela, fica a MUTUANTE expressamente autorizada a:

a) Levar o presente Contrato e/ou a Nota Promissória a protesto extrajudicial;

b) Incluir o nome do MUTUÁRIO e do AVALISTA em cadastros de proteção ao crédito (SERASA, SPC, SCPC, Boa Vista, entre outros), independentemente de prévia comunicação além da legalmente exigida;

c) Compartilhar informações da operação com bureaus de crédito e com o Cadastro Positivo (Lei 12.414/2011), conforme autorização expressa do MUTUÁRIO firmada em documento específico.

***

## CLÁUSULA 11ª — DAS COMUNICAÇÕES

**11.1.** Todas as comunicações entre as partes relativas a este Contrato serão consideradas válidas quando enviadas para os seguintes endereços:

- **MUTUANTE:** {{credor.endereco_completo}} — E-mail: {{credor.email}} — WhatsApp: {{credor.whatsapp}}
- **MUTUÁRIO:** {{devedor.endereco_completo}} — E-mail: {{devedor.email}} — WhatsApp: {{devedor.whatsapp}}
- **AVALISTA:** {{avalista.endereco_completo}} — E-mail: {{avalista.email}} — WhatsApp: {{avalista.whatsapp}}

**11.2.** Qualquer mudança de endereço ou meio de contato deverá ser comunicada à outra parte por escrito, sob pena de se considerarem válidas as comunicações enviadas aos endereços acima informados.

***

## CLÁUSULA 12ª — DAS DISPOSIÇÕES GERAIS

**12.1.** Este Contrato obriga as partes, seus herdeiros e sucessores a qualquer título.

**12.2.** A tolerância de uma parte quanto ao descumprimento de qualquer cláusula pela outra não importará em novação ou renúncia ao direito de exigir o cumprimento, a qualquer tempo.

**12.3.** Caso qualquer cláusula deste Contrato seja declarada nula ou ineficaz, permanecerão íntegras e eficazes as demais disposições.

**12.4.** Este Contrato poderá ser assinado **eletronicamente**, nos termos da Medida Provisória nº 2.200-2/2001 e da Lei nº 14.063/2020, sendo consideradas válidas e eficazes as assinaturas realizadas por meio de plataformas de assinatura eletrônica reconhecidas.

**12.5.** O MUTUÁRIO e o AVALISTA autorizam expressamente a MUTUANTE a tratar seus dados pessoais e empresariais para fins da operação, nos termos da Lei Geral de Proteção de Dados (Lei nº 13.709/2018), podendo compartilhá-los com bureaus de crédito, órgãos de proteção ao crédito, cartórios, advogados, assessorias de cobrança e autoridades legais, sempre que necessário para o cumprimento das obrigações contratuais ou legais.

***

## CLÁUSULA 13ª — DO FORO

**13.1.** As partes elegem o Foro da Comarca de **Piracicaba/SP** para dirimir quaisquer dúvidas ou controvérsias oriundas deste Contrato, com renúncia expressa a qualquer outro, por mais privilegiado que seja.

***

E, por estarem justas e contratadas, as partes firmam o presente Contrato em **{{operacao.num_vias}} ({{operacao.num_vias_extenso}}) vias de igual teor e forma**, na presença de 2 (duas) testemunhas abaixo assinadas.

**{{operacao.local}}, {{operacao.data_extenso}}.**

***

<div class="signature-block">
<p class="sig-role">MUTUANTE:</p>
<div class="sig-space"></div>
<div class="sig-line"></div>
<p class="sig-name"><strong>{{credor.razao_social}}</strong><br />
CNPJ: {{credor.documento}}<br />
p.p.: {{credor.representante.nome}}<br />
CPF: {{credor.representante.cpf}}</p>
</div>

<div class="signature-block">
<p class="sig-role">MUTUÁRIO / DEVEDOR FIDUCIANTE:</p>
<div class="sig-space"></div>
<div class="sig-line"></div>
<p class="sig-name"><strong>{{devedor.razao_social}}</strong><br />
CNPJ: {{devedor.cnpj}}<br />
p.p.: {{devedor.representante.nome}}<br />
CPF: {{devedor.representante.cpf}}</p>
</div>

{{#devedor.conjuge_assina}}
<div class="signature-block">
<p class="sig-role">CÔNJUGE:</p>
<div class="sig-space"></div>
<div class="sig-line"></div>
<p class="sig-name">Nome: ______________________________________<br />
CPF: ______________________________</p>
</div>
{{/devedor.conjuge_assina}}

<div class="signature-block">
<p class="sig-role">AVALISTA / GARANTIDOR SOLIDÁRIO:</p>
<div class="sig-space"></div>
<div class="sig-line"></div>
<p class="sig-name"><strong>{{avalista.nome}}</strong><br />
CPF: {{avalista.cpf}}<br />
RG: {{avalista.rg}}</p>
</div>

{{#avalista.casado}}
<div class="signature-block">
<p class="sig-role">CÔNJUGE DO AVALISTA (anuência):</p>
<div class="sig-space"></div>
<div class="sig-line"></div>
<p class="sig-name"><strong>{{avalista.conjuge.nome}}</strong><br />
CPF: {{avalista.conjuge.cpf}}</p>
</div>
{{/avalista.casado}}

<div class="signature-block">
<p class="sig-role">TESTEMUNHA 1:</p>
<div class="sig-space"></div>
<div class="sig-line"></div>
<p class="sig-name">Nome: {{testemunhas.0.nome}}<br />
CPF: {{testemunhas.0.cpf}}</p>
</div>

<div class="signature-block">
<p class="sig-role">TESTEMUNHA 2:</p>
<div class="sig-space"></div>
<div class="sig-line"></div>
<p class="sig-name">Nome: {{testemunhas.1.nome}}<br />
CPF: {{testemunhas.1.cpf}}</p>
</div>

***

# ANEXO I — CRONOGRAMA DE PAGAMENTOS E MEMÓRIA DE CÁLCULO

**Contrato nº {{operacao.id}}**

| Dados da Operação            | Valor                                                                          |
| ---------------------------- | ------------------------------------------------------------------------------ |
| Capital Mutuado              | R$ {{operacao.valor_principal}}                                               |
| Taxa de Juros Remuneratórios | {{operacao.taxa_juros_mensal}}% a.m. / {{operacao.taxa_juros_anual}}% a.a. |
| Custo Efetivo Total (CET)    | {{operacao.cet}}% a.m.                                                         |
| Número de Parcelas           | {{operacao.num_parcelas}}                                                     |
| Valor da Parcela             | R$ {{operacao.valor_parcela}}                                                 |
| Valor Total da Dívida        | R$ {{operacao.valor_total_devido}}                                           |
| Total de Juros               | R$ {{operacao.total_juros}}                                                   |
| Sistema de Amortização       | {{operacao.sistema_amortizacao}}                                              |

### Cronograma de Vencimentos:

|     Parcela     |  Data de Vencimento  | Valor da Parcela (R$) |       Amortização (R$) |       Juros (R$) | Saldo Devedor (R$) |
| :-------------: | :------------------: | --------------------: | ---------------------: | ---------------: | -----------------: |
{{#cronograma}}
| {{numero}} | {{data_vencimento}} | {{valor_parcela}} | {{valor_amortizacao}} | {{valor_juros}} | {{saldo_devedor}} |
{{/cronograma}}
