# Regras de Negócio — Módulo de Contratos e Documentos
## Sistema de Operações da ACM Empresa Simples de Crédito LTDA

---

## 1. Árvore de Decisão para Geração de Contratos

O sistema deve classificar cada operação em **uma das duas naturezas jurídicas** antes de gerar documentos:

### 1.1. Natureza "EMPRÉSTIMO" (Mútuo Feneratício)
- O cliente recebe dinheiro da ACM.
- O cliente é o devedor direto perante a ACM.
- Paga com juros em parcelas ou prazo único.
- **Documentos gerados pelo sistema:**
  - Contrato de Mútuo Feneratício com Alienação Fiduciária de Veículo e Aval
  - Nota Promissória Vinculada
  - Instrumento de Aval (embutido no contrato)

### 1.2. Natureza "DESCONTO / CESSÃO DE CRÉDITO"
- O cliente cede à ACM um crédito que ele tem contra um terceiro (sacado).
- O devedor final do título é o sacado, não o cliente.
- A ACM paga o valor presente (valor de face menos deságio).
- **Documentos gerados pelo sistema:**
  - Contrato-Mãe de Cessão Onerosa de Créditos (emitido uma única vez por cliente)
  - Borderô de Operação (emitido a cada nova operação)

---

## 2. Regra de Validação de Tomador (pré-geração)

```
SE operacao.natureza == "EMPRESTIMO":
    SE cliente.tipo_pessoa == "PF" E cliente.possui_cnpj_mei == false:
        BLOQUEAR_GERACAO
        MENSAGEM: "A ACM ESC não pode realizar operações de mútuo com 
                   pessoa física sem CNPJ. Oriente o cliente a abrir MEI 
                   (gratuito no Portal do Empreendedor) ou converta esta 
                   operação para Desconto de Título."
    SE cliente.porte NOT IN ["MEI", "ME", "EPP"]:
        BLOQUEAR_GERACAO
        MENSAGEM: "LC 167/2019 restringe operações da ESC a MEI, 
                   Microempresas e Empresas de Pequeno Porte."
    SENÃO:
        GERAR: Contrato_Mutuo + Nota_Promissoria

SE operacao.natureza == "DESCONTO":
    VALIDAR: operacao.titulos existe e tem >= 1 título com sacado distinto do cedente
    SE já existe Contrato_Mae_Cessao ativo com este cliente:
        GERAR: apenas Borderô
    SENÃO:
        GERAR: Contrato_Mae_Cessao + Borderô
```

---

## 3. Dados Mínimos Obrigatórios

### 3.1. Para gerar Contrato de Mútuo

**Da MUTUANTE (ACM — fixo no sistema):**
- Razão social: ACM EMPRESA SIMPLES DE CRÉDITO LTDA
- CNPJ: 63.530.897/0001-85
- Endereço: Rua Abelardo Benedicto Liborio, nº 600, Loteamento Distrito Industrial Uninorte, Piracicaba/SP, CEP 13.413-071
- Representante legal (nome, CPF, RG, cargo)

**Do MUTUÁRIO (cliente):**
- Razão social / Nome empresarial
- CNPJ
- Porte (MEI / ME / EPP)
- Endereço completo
- Nome do representante legal, CPF, RG, nacionalidade, estado civil, profissão, endereço residencial

**Do AVALISTA (pessoa física — obrigatório):**
- Nome completo, CPF, RG, nacionalidade, estado civil, profissão, endereço
- Se casado: regime de bens + dados do cônjuge (para anuência em alienação fiduciária conforme CC art. 1.647)

**Da OPERAÇÃO:**
- Valor principal (capital emprestado)
- Número de parcelas
- Valor de cada parcela
- Data de vencimento da 1ª parcela e periodicidade
- Taxa de juros mensal pactuada
- Taxa de juros anual equivalente
- CET (Custo Efetivo Total)
- Data de assinatura
- Local de assinatura (Piracicaba/SP)

**Do VEÍCULO EM GARANTIA:**
- Marca, Modelo, Ano de fabricação, Ano do modelo
- Cor
- Chassi (17 caracteres)
- Placa
- RENAVAM
- Município/UF do emplacamento
- Valor de avaliação (para fins de garantia)

**Das TESTEMUNHAS (2 obrigatórias — CPC art. 784, III):**
- Nome completo e CPF de cada uma

### 3.2. Para gerar Contrato de Cessão + Borderô

**Da CESSIONÁRIA (ACM — fixo):** mesmos dados da Mutuante acima.

**Do CEDENTE (cliente):**
- Mesma estrutura do Mutuário. Pode ser PF ou PJ (qualquer porte).

**Do AVALISTA do Cedente (opcional, recomendado):**
- Mesmos dados do avalista no mútuo.

**Da OPERAÇÃO (Borderô):**
- Número sequencial da operação
- Data da operação
- Taxa de deságio aplicada (% ao mês)
- Lista de TÍTULOS, cada um contendo:
  - Número do título
  - Tipo (duplicata, NP, cheque, boleto etc.)
  - Sacado (nome, CNPJ/CPF)
  - Valor de face
  - Data de emissão
  - Data de vencimento
  - Valor presente (após deságio)
- Totais: valor total de face, total de deságio, valor líquido pago ao cedente

---

## 4. Convenções Técnicas para o Agente de IA (TRAE + Gemini)

### 4.1. Sintaxe de Placeholders

Todos os templates usam sintaxe Mustache/Handlebars:

- **Variável simples:** `{{variavel}}` ou `{{objeto.propriedade}}`
- **Loop/Array:** `{{#lista}} ... {{/lista}}`
- **Condicional:** `{{#condicao}} ... {{/condicao}}` / `{{^condicao}} ... {{/condicao}}` (negativo)

### 4.2. Hierarquia de Objetos Sugerida

```
contrato = {
  credor: { razao_social, cnpj, endereco, representante: { nome, cpf, rg, cargo } },
  devedor: { razao_social, cnpj, porte, endereco, representante: {...} },
  avalista: { nome, cpf, rg, nacionalidade, estado_civil, profissao, endereco,
              casado: bool, regime_casamento, conjuge: { nome, cpf } },
  operacao: { id, data, local, valor_principal, valor_total_devido,
              taxa_juros_mensal, taxa_juros_anual, cet,
              num_parcelas, valor_parcela, data_primeiro_vencimento,
              periodicidade, extenso_valor_principal, extenso_valor_parcela },
  veiculo: { marca, modelo, ano_fab, ano_mod, cor, chassi, placa, 
             renavam, municipio_emplacamento, uf, valor_avaliacao },
  testemunhas: [ { nome, cpf }, { nome, cpf } ]
}

borderô = {
  cessionario: {...ACM...},
  cedente: {...},
  avalista: {...opcional...},
  operacao: { id, data, taxa_desagio_mensal, total_face, total_desagio, 
              valor_liquido, extenso_valor_liquido },
  titulos: [
    { numero, tipo, sacado_nome, sacado_documento, valor_face, 
      data_emissao, data_vencimento, valor_presente }
  ]
}
```

### 4.3. Formatação de Valores

- **Moeda:** `R$ 100.000,00` (real brasileiro, 2 casas, vírgula decimal, ponto milhar)
- **Valor por extenso:** sempre gerar via função (ex: biblioteca `numero-por-extenso` em PHP) — templates esperam já o extenso pronto
- **Datas:** `25/04/2026` ou `25 de abril de 2026` (template especifica)
- **Percentuais:** `3,00%` (duas casas, vírgula)

### 4.4. Biblioteca de Geração de PDF (recomendação)

Para o stack PHP do cliente, a melhor opção para documentos jurídicos é **mPDF** (pacote `mpdf/mpdf`):

- Renderiza HTML+CSS com alta fidelidade
- Suporte nativo a UTF-8, caracteres portugueses, cedilhas, acentos
- Controle de quebra de página, cabeçalho/rodapé, numeração de páginas
- Suporta fontes customizadas (Times New Roman para documento jurídico)
- Exporta em PDF/A se necessário para arquivo legal

Alternativas:
- **DOMPDF:** mais simples, mas renderização inferior para layouts complexos
- **TCPDF:** poderoso mas API verbosa; bom para casos avançados
- **wkhtmltopdf:** excelente qualidade, mas dependência binária externa (pode complicar deploy)

### 4.5. Fluxo de UX no Sistema

1. Usuário entra na tela da operação (já existente)
2. Novo botão **"Gerar Contratos e Documentos"**
3. Sistema verifica:
   - Tipo da operação (empréstimo / desconto)
   - Validações de tomador (Regra 2)
   - Completude dos dados (Regra 3)
4. Se incompleto → exibe modal listando campos faltantes
5. Se completo → gera PDFs, salva em `/storage/contratos/{operacao_id}/`
6. Registra em `generated_contracts` (nova tabela — ver Schema SQL)
7. Exibe links de download + opção "Abrir plataforma de assinatura" (ClickSign/D4Sign/Autentique/ZapSign)

### 4.6. Schema SQL Sugerido (MySQL)

```sql
CREATE TABLE contract_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,         -- 'MUTUO_ESC', 'CESSAO_MAE', 'BORDERO', 'NP'
    name VARCHAR(255) NOT NULL,
    description TEXT,
    template_content LONGTEXT NOT NULL,        -- markdown/html com placeholders
    version VARCHAR(20) DEFAULT '1.0',
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE generated_contracts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    operation_id INT NOT NULL,                 -- FK para tabela operations existente
    template_code VARCHAR(50) NOT NULL,        -- referência ao template usado
    file_path VARCHAR(500) NOT NULL,           -- caminho do PDF gerado
    file_hash VARCHAR(64),                     -- SHA-256 para integridade
    status ENUM('generated', 'sent_to_signature', 'signed', 'cancelled') DEFAULT 'generated',
    signature_platform VARCHAR(50),            -- 'clicksign', 'd4sign', etc
    signature_document_id VARCHAR(255),        -- ID retornado pela plataforma
    signed_at TIMESTAMP NULL,
    metadata JSON,                             -- dados adicionais (URLs, tokens, etc.)
    created_by INT NOT NULL,                   -- FK para users
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (operation_id) REFERENCES operations(id),
    INDEX idx_operation (operation_id),
    INDEX idx_status (status)
);

-- Se não existirem, adicionar campos na tabela de clientes/operações:
ALTER TABLE clientes 
    ADD COLUMN tipo_pessoa ENUM('PF','PJ') NOT NULL,
    ADD COLUMN porte ENUM('MEI','ME','EPP','MEDIO','GRANDE') NULL,
    ADD COLUMN representante_nome VARCHAR(255),
    ADD COLUMN representante_cpf VARCHAR(14),
    ADD COLUMN representante_rg VARCHAR(30),
    ADD COLUMN representante_estado_civil VARCHAR(30);

ALTER TABLE operations 
    ADD COLUMN natureza ENUM('EMPRESTIMO','DESCONTO') NOT NULL,
    ADD COLUMN valor_principal DECIMAL(15,2),
    ADD COLUMN valor_total_devido DECIMAL(15,2),
    ADD COLUMN taxa_juros_mensal DECIMAL(6,4),
    ADD COLUMN cet_mensal DECIMAL(6,4);

CREATE TABLE operation_vehicles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    operation_id INT NOT NULL,
    marca VARCHAR(50),
    modelo VARCHAR(100),
    ano_fab INT,
    ano_mod INT,
    cor VARCHAR(30),
    chassi VARCHAR(17),
    placa VARCHAR(10),
    renavam VARCHAR(15),
    municipio_emplacamento VARCHAR(100),
    uf CHAR(2),
    valor_avaliacao DECIMAL(15,2),
    FOREIGN KEY (operation_id) REFERENCES operations(id)
);

CREATE TABLE operation_guarantors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    operation_id INT NOT NULL,
    nome VARCHAR(255),
    cpf VARCHAR(14),
    rg VARCHAR(30),
    nacionalidade VARCHAR(50) DEFAULT 'brasileiro(a)',
    estado_civil VARCHAR(30),
    profissao VARCHAR(100),
    endereco TEXT,
    regime_casamento VARCHAR(50),
    conjuge_nome VARCHAR(255),
    conjuge_cpf VARCHAR(14),
    tipo ENUM('AVALISTA','FIADOR','CONJUGE_ANUENTE') DEFAULT 'AVALISTA',
    FOREIGN KEY (operation_id) REFERENCES operations(id)
);
```

### 4.7. Estrutura de Pastas Sugerida

```
/app/Modules/Contracts/
├── Controllers/
│   └── ContractController.php
├── Services/
│   ├── ContractGenerator.php        (orquestrador)
│   ├── TemplateRenderer.php         (Mustache)
│   ├── PdfBuilder.php               (mPDF wrapper)
│   └── NumberToWords.php            (valor por extenso)
├── Validators/
│   ├── MutuoValidator.php
│   └── CessaoValidator.php
├── Templates/
│   ├── mutuo_esc.md
│   ├── cessao_mae.md
│   ├── bordero.md
│   └── nota_promissoria.md
└── Views/
    └── contracts.blade.php (ou phtml)

/storage/contratos/{operation_id}/
├── {operation_id}_mutuo.pdf
├── {operation_id}_np.pdf
├── {operation_id}_cessao.pdf
└── {operation_id}_bordero.pdf
```

### 4.8. Considerações sobre Assinatura Eletrônica

Base legal: **MP 2.200-2/2001** e **Lei 14.063/2020**.

Três tipos de assinatura eletrônica:
1. **Simples** — identificação por meios simples (ex: e-mail + código SMS)
2. **Avançada** — com atributos que permitam identificação unívoca do signatário
3. **Qualificada** — com certificado digital ICP-Brasil

Para contratos de mútuo com alienação fiduciária e aval, **recomendação forte: assinatura AVANÇADA ou QUALIFICADA** de todas as partes (mutuante, mutuário, avalista, cônjuge se aplicável, e 2 testemunhas).

As plataformas a seguir são compatíveis com o fluxo do sistema:

- **ClickSign** (https://clicksign.com) — mais popular no Brasil, API bem documentada
- **D4Sign** (https://d4sign.com.br) — bom custo-benefício, integração fácil
- **Autentique** (https://autentique.com.br) — boa UX, API REST
- **ZapSign** (https://zapsign.com.br) — econômica, assinatura via WhatsApp (útil para cliente final)

Requisitos do PDF gerado para boa compatibilidade:
- Formato PDF (não PDF/A obrigatório, mas ajuda)
- Campos de assinatura ao final (podem ser linhas com "_________________" + nome e CPF logo abaixo)
- Página de rosto identificável
- Sem senha/criptografia no PDF (plataformas requerem acesso livre)
- Resolução mínima 300 DPI
- Metadata: Title, Author, Subject preenchidos

### 4.9. Registro de Alienação Fiduciária de Veículo

Após assinatura, o **gravame deve ser registrado no DETRAN** da UF de emplacamento do veículo, em nome da ACM. Sem gravame, a alienação fiduciária perde eficácia perante terceiros (embora continue válida entre as partes).

Este passo é manual/administrativo — o sistema apenas **rastreia o status**:

```sql
ALTER TABLE operation_vehicles
    ADD COLUMN gravame_status ENUM('pendente','solicitado','registrado','cancelado') DEFAULT 'pendente',
    ADD COLUMN gravame_numero VARCHAR(50),
    ADD COLUMN gravame_data DATE;
```

---

## 5. Lógica de Geração de Nome de Arquivos

Convenção recomendada para o nome dos PDFs gerados:

```
{OPERATION_ID}_{TIPO}_{DATA}.pdf

Exemplos:
00042_MUTUO_20260424.pdf
00042_NP_20260424.pdf
00042_CESSAO_20260424.pdf
00042_BORDERO_20260424.pdf
```

---

## 6. Checklist Antes de Marcar Operação como "Contratos Gerados"

- [ ] Todos os placeholders do template foram preenchidos (nenhum `{{...}}` remanescente no PDF)
- [ ] Valores por extenso batem com os valores numéricos
- [ ] Datas são coerentes (emissão ≤ 1º vencimento)
- [ ] 2 testemunhas identificadas
- [ ] Dados do veículo completos (se aplicável)
- [ ] Dados do avalista completos (incluindo cônjuge, se aplicável)
- [ ] PDF abre corretamente e é legível
- [ ] Arquivo salvo em `/storage/contratos/{operation_id}/`
- [ ] Registro criado em `generated_contracts` com status = 'generated'
