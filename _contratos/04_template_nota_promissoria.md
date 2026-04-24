# Template: Nota Promissória

**Arquivo:** `templates/nota_promissoria.md`  
**Uso:** Documento cambial emitido em conjunto com o Contrato de Mútuo.  
**Fundamento legal:** Decreto nº 2.044/1908 (Lei das Cambiais) e Decreto nº 57.663/1966 (Lei Uniforme de Genebra — LUG).

---

# NOTA PROMISSÓRIA

**Nº {{np.numero}} — Vinculada ao Contrato de Mútuo nº {{operacao.id}}**

---

**Valor: R$ {{operacao.valor_total_devido}}**

---

**Vencimento:** À vista / {{np.vencimento}}

**Local e data de emissão:** {{operacao.local}}, {{operacao.data_extenso}}.

---

Aos **{{np.data_vencimento_extenso}}**, ou quando esta Nota Promissória me/nos for apresentada, pagarei(emos) por esta única via de **NOTA PROMISSÓRIA** à 

**ACM EMPRESA SIMPLES DE CRÉDITO LTDA**,  
inscrita no CNPJ/MF sob o nº **63.530.897/0001-85**,  
com sede na Rua Abelardo Benedicto Liborio, nº 600, Loteamento Distrito Industrial Uninorte, Piracicaba/SP, CEP 13.413-071,  
ou à sua ordem,

a quantia de **R$ {{operacao.valor_total_devido}}** (**{{operacao.valor_total_devido_extenso}}**), em moeda corrente nacional.

**Praça de pagamento:** Piracicaba, Estado de São Paulo.

---

### EMITENTE:

**{{devedor.razao_social}}**  
CNPJ: {{devedor.cnpj}}  
Endereço: {{devedor.endereco_completo}}  

Representada por:  
**{{devedor.representante.nome}}**  
CPF: {{devedor.representante.cpf}}  
RG: {{devedor.representante.rg}}

\
\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_  
(assinatura do emitente)

---

### POR AVAL:

Nos termos do art. 30 e seguintes do Decreto nº 57.663/1966 (Lei Uniforme de Genebra), o(a) abaixo assinado(a) presta **AVAL** à presente Nota Promissória, responsabilizando-se solidariamente pelo pagamento integral do valor nela representado, como principal pagador(a), renunciando expressamente aos benefícios de ordem, divisão e excussão prévia.

**AVALISTA:**  
**{{avalista.nome}}**  
CPF: {{avalista.cpf}}  
RG: {{avalista.rg}}  
Endereço: {{avalista.endereco_completo}}

\
\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_  
(assinatura do avalista)

{{#avalista.casado}}
### ANUÊNCIA CONJUGAL:

**{{avalista.conjuge.nome}}**, cônjuge do(a) AVALISTA, manifesta expressa anuência ao aval prestado, nos termos do art. 1.647, inciso III, do Código Civil.

CPF: {{avalista.conjuge.cpf}}

\
\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_  
(assinatura do(a) cônjuge)
{{/avalista.casado}}

---

## OBSERVAÇÕES TÉCNICAS PARA GERAÇÃO DO PDF DA NP

1. **Formato:** A Nota Promissória deve ser gerada em página A4 única, orientação retrato.

2. **Conteúdo em uma única via:** Diferente do contrato, a NP é emitida em **via única**. A expressão "única via" deve constar no documento (já inclusa no template).

3. **Requisitos essenciais do LUG (art. 75) — todos presentes no template:**
   - [x] Denominação "Nota Promissória" inserida no texto
   - [x] Promessa pura e simples de pagar quantia determinada
   - [x] Época de pagamento (vencimento à vista ou data específica)
   - [x] Lugar do pagamento (praça)
   - [x] Nome do beneficiário (ACM ESC)
   - [x] Data e lugar de emissão
   - [x] Assinatura do emitente

4. **Vinculação pro solvendo:** A cláusula do Contrato de Mútuo (Cláusula 9ª) já estabelece que a NP vincula-se ao contrato em regime pro solvendo, ou seja, sua emissão não extingue a dívida original — serve para reforço e liquidez executiva.

5. **Tratamento do valor:** A NP deve registrar o **valor total da dívida** (principal + juros previstos para todo o período do contrato), que é o que o devedor efetivamente deverá pagar se levada a cabo a operação. Esse valor já está disponível no campo `{{operacao.valor_total_devido}}`.

6. **Vencimento:** Geralmente emitida "à vista" (para que possa ser cobrada a qualquer tempo em caso de vencimento antecipado) ou com data específica (ao final do contrato). A prática mais segura é "à vista" — combinada com a cláusula de vencimento antecipado do contrato.

7. **Preenchimento posterior autorizado:** A Cláusula 9.3 do Contrato de Mútuo autoriza expressamente o preenchimento posterior (Súmula 387 STF). O sistema deve emitir a NP já preenchida para evitar discussões.

8. **Protesto:** Para protesto em cartório, a NP deve ser levada no original, assinada de próprio punho OU com assinatura eletrônica reconhecida (desde que o cartório local aceite — muitos ainda exigem original físico). Orientar o cliente ACM a sempre imprimir e colher assinatura física também, como precaução, até que a jurisprudência sobre aceitação de NP eletrônica em protesto esteja mais consolidada.

9. **Prazo prescricional:** 3 anos a contar do vencimento para execução (LUG art. 70). Após esse prazo, a NP ainda serve como prova de dívida, mas perde força executiva cambial — daí a importância do Contrato de Mútuo como título paralelo (prazo de execução mais amplo).

---

## INSTRUÇÕES DE LAYOUT PARA O RENDERIZADOR (mPDF)

Recomendação de CSS para a geração do PDF da NP:

```css
@page { 
    size: A4 portrait; 
    margin: 2cm; 
}

body {
    font-family: "Times New Roman", serif;
    font-size: 12pt;
    line-height: 1.5;
    color: #000;
}

.np-header {
    text-align: center;
    font-size: 20pt;
    font-weight: bold;
    letter-spacing: 3px;
    margin-bottom: 20px;
    border-bottom: 2px solid #000;
    padding-bottom: 10px;
}

.np-valor {
    text-align: center;
    font-size: 16pt;
    font-weight: bold;
    margin: 20px 0;
    padding: 10px;
    border: 2px solid #000;
}

.np-texto {
    text-align: justify;
    text-indent: 2cm;
    margin: 15px 0;
}

.np-assinatura {
    margin-top: 60px;
    text-align: center;
}

.np-linha-assinatura {
    border-top: 1px solid #000;
    width: 70%;
    margin: 0 auto 5px auto;
}
```

---

## NOTA SOBRE ASSINATURA ELETRÔNICA DE NP

A assinatura eletrônica de Nota Promissória é juridicamente aceita desde a Lei 14.063/2020 e reafirmada pela Lei 14.620/2023, porém:

- Para **execução judicial**: OK, pacífico. Basta juntar o PDF com relatório da plataforma de assinatura (trilha de auditoria).
- Para **protesto em cartório**: depende do cartório local. Muitos ainda exigem título físico. Por isso, a orientação prática é: **assinar eletronicamente E imprimir/assinar fisicamente também**, arquivando o físico na ACM para eventual protesto.
- Para **circular o título (endosso/desconto futuro)**: melhor sempre ter o original físico. Mas para ESC, que geralmente mantém os títulos em carteira, isso é menos crítico.

O sistema deve permitir gerar a NP em PDF para assinatura eletrônica (fluxo principal) e, se desejado, gerar também uma versão imprimível com 2 cm de margem extra para colar em ficha de protocolo cartorial.
