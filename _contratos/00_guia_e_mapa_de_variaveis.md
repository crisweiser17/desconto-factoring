# Guia Operacional — Os 5 Contratos do Sistema ACM
## Mapa de Variáveis e Decisão por Contrato

---

## 📋 Os 5 Contratos

| # | Arquivo | Tipo de Operação | Tomador | Garantia | Avalista |
|---|---|---|---|---|---|
| 1 | `01_template_antecipacao_recebiveis.md` | Cessão de crédito (desconto de títulos) | Qualquer (PF/PJ) | n/a | Opcional |
| 2 | `02a_template_mutuo_simples.md` | Empréstimo simples | MEI/ME/EPP | ❌ | ❌ |
| 3 | `02b_template_mutuo_com_aval.md` | Empréstimo c/ aval | MEI/ME/EPP | ❌ | ✅ |
| 4 | `02c_template_mutuo_com_garantia.md` | Empréstimo c/ AF veículo | MEI/ME/EPP | ✅ | ❌ |
| 5 | `02d_template_mutuo_com_garantia_e_aval.md` | Empréstimo c/ AF + aval | MEI/ME/EPP | ✅ | ✅ |

**Documento auxiliar:** `03_template_nota_promissoria.md` — emitida junto com qualquer um dos contratos 2-5 (mútuo). Tem condicional para avalista.

---

## 🔀 Lógica de Decisão para o Sistema

```
SE operacao.natureza == "DESCONTO":
    → Gerar template 01 (antecipacao_recebiveis)

SE operacao.natureza == "EMPRESTIMO":
    Validar: cliente.porte IN ('MEI','ME','EPP')
    
    Decidir por flags:
    SE NÃO tem_garantia E NÃO tem_avalista:
        → Gerar 02a + NP
    SE NÃO tem_garantia E tem_avalista:
        → Gerar 02b + NP (com aval)
    SE tem_garantia E NÃO tem_avalista:
        → Gerar 02c + NP
    SE tem_garantia E tem_avalista:
        → Gerar 02d + NP (com aval)
```

Sugestão de campos novos em `operations`:

```sql
ALTER TABLE operations
    ADD COLUMN tem_garantia TINYINT(1) DEFAULT 0,
    ADD COLUMN tem_avalista TINYINT(1) DEFAULT 0;
```

---

## 🗺️ Mapa de Variáveis por Contrato

### Variáveis comuns a TODOS os contratos de empréstimo (02a, 02b, 02c, 02d)

```
credor:
  representante: { nome, cpf, rg, nacionalidade, estado_civil }
  conta: { banco, agencia, numero, pix }
  endereco_completo
  email
  whatsapp

devedor:
  razao_social
  cnpj
  porte                          # 'MEI' | 'ME' | 'EPP'
  descricao_juridica             # 'microempreendedor individual' | 'sociedade empresária limitada' | etc.
  endereco_completo
  email
  whatsapp
  representante: { nome, cpf, rg, nacionalidade, estado_civil, profissao, endereco }

operacao:
  id
  data_extenso                   # '24 de abril de 2026'
  local                          # 'Piracicaba/SP'
  valor_principal                # '50.000,00'
  valor_principal_extenso
  valor_total_devido
  valor_total_devido_extenso
  num_parcelas
  num_parcelas_extenso
  valor_parcela
  valor_parcela_extenso
  data_primeiro_vencimento
  periodicidade                  # 'mensais e consecutivas'
  taxa_juros_mensal
  taxa_juros_mensal_extenso
  taxa_juros_anual
  taxa_juros_anual_extenso
  cet
  total_juros
  forma_liberacao                # 'transferência PIX' | 'TED' | etc.
  forma_pagamento                # 'boleto bancário, PIX ou transferência'
  sistema_amortizacao            # 'PRICE (parcelas fixas)' | 'SAC' | etc.
  num_vias
  num_vias_extenso

testemunhas: [
  { nome, cpf },                 # testemunha 1
  { nome, cpf }                  # testemunha 2
]

cronograma: [                    # Anexo I do contrato
  { numero, data_vencimento, valor_parcela, valor_amortizacao, valor_juros, saldo_devedor }
]
```

### Variáveis específicas — 02b (com aval) e 02d (com garantia + aval)

Adicionar à hierarquia acima:

```
avalista:
  nome
  cpf
  rg
  nacionalidade
  estado_civil
  profissao
  endereco_completo
  email
  whatsapp
  casado: bool                   # se true, ativa bloco condicional de anuência
  regime_casamento               # 'comunhão parcial de bens' | 'comunhão universal' | 'separação total'
  conjuge: { nome, cpf }         # presente apenas se casado=true
```

### Variáveis específicas — 02c (com garantia) e 02d (com garantia + aval)

Adicionar à hierarquia acima:

```
veiculo:
  marca
  modelo
  ano_fab
  ano_mod
  cor
  combustivel                    # 'Flex' | 'Gasolina' | 'Diesel' | 'Híbrido' | 'Elétrico'
  chassi                         # 17 caracteres
  placa
  renavam
  municipio_emplacamento
  uf
  valor_avaliacao
  valor_avaliacao_extenso
```

### Variáveis exclusivas — 01 (antecipação de recebíveis)

```
credor:                          # mesmos dados do credor acima, simplificado
  representante: { nome, cpf, rg, nacionalidade, estado_civil }
  email
  whatsapp

cedente:
  pessoa_juridica: bool          # FLAG que escolhe bloco condicional
  
  # Se PJ:
  razao_social
  cnpj
  descricao_juridica
  representante: { nome, cpf, rg, nacionalidade, estado_civil, profissao, endereco }
  
  # Se PF:
  nome_completo
  cpf
  rg
  nacionalidade
  estado_civil
  profissao
  
  # Comum:
  endereco_completo
  email
  whatsapp
  conta: { banco, agencia, numero, tipo, titular, documento, pix }

avalista: {...opcional, mesma estrutura...} | null

operacao:
  id
  data
  data_extenso
  local
  total_titulos
  total_face
  taxa_desagio_mensal
  taxa_desagio_mensal_extenso
  prazo_medio                    # dias
  total_desagio
  possui_tarifas: bool
  tarifas
  valor_liquido
  valor_liquido_extenso
  forma_pagamento
  num_vias
  num_vias_extenso

titulos: [                       # array dos recebíveis cedidos
  {
    ordem,
    numero,                      # número/identificador do título
    tipo,                        # 'duplicata' | 'np' | 'cheque' | 'boleto' | 'fatura'
    sacado_nome,
    sacado_documento,            # CNPJ/CPF do sacado (≠ cedente!)
    data_emissao,
    data_vencimento,
    valor_face,
    valor_presente               # após deságio
  }
]

testemunhas: [
  { nome, cpf },
  { nome, cpf }
]
```

---

## 🛢️ Schema SQL Necessário

### Campos em tabelas existentes

```sql
-- clientes (pessoa que toma o empréstimo ou cede recebível)
ALTER TABLE clientes 
    ADD COLUMN tipo_pessoa ENUM('PF','PJ') NOT NULL DEFAULT 'PJ',
    ADD COLUMN porte ENUM('MEI','ME','EPP','MEDIO','GRANDE','PF') NULL,
    ADD COLUMN descricao_juridica VARCHAR(100),
    ADD COLUMN possui_cnpj_mei TINYINT(1) DEFAULT 0,
    ADD COLUMN representante_nome VARCHAR(255),
    ADD COLUMN representante_cpf VARCHAR(14),
    ADD COLUMN representante_rg VARCHAR(30),
    ADD COLUMN representante_nacionalidade VARCHAR(50) DEFAULT 'brasileiro(a)',
    ADD COLUMN representante_estado_civil VARCHAR(30),
    ADD COLUMN representante_profissao VARCHAR(100),
    ADD COLUMN representante_endereco TEXT;

-- operations (nova natureza + flags)
ALTER TABLE operations 
    ADD COLUMN natureza ENUM('EMPRESTIMO','DESCONTO') NOT NULL,
    ADD COLUMN tem_garantia TINYINT(1) DEFAULT 0,
    ADD COLUMN tem_avalista TINYINT(1) DEFAULT 0,
    ADD COLUMN valor_principal DECIMAL(15,2),
    ADD COLUMN valor_total_devido DECIMAL(15,2),
    ADD COLUMN taxa_juros_mensal DECIMAL(6,4),
    ADD COLUMN taxa_juros_anual DECIMAL(6,4),
    ADD COLUMN cet_mensal DECIMAL(6,4),
    ADD COLUMN num_parcelas INT,
    ADD COLUMN valor_parcela DECIMAL(15,2),
    ADD COLUMN data_primeiro_vencimento DATE,
    ADD COLUMN periodicidade VARCHAR(30) DEFAULT 'mensais e consecutivas',
    ADD COLUMN sistema_amortizacao VARCHAR(30) DEFAULT 'PRICE',
    ADD COLUMN taxa_desagio_mensal DECIMAL(6,4),
    ADD COLUMN total_face DECIMAL(15,2),
    ADD COLUMN total_desagio DECIMAL(15,2),
    ADD COLUMN valor_liquido DECIMAL(15,2),
    ADD COLUMN prazo_medio_dias INT;
```

### Tabelas auxiliares

```sql
-- Títulos cedidos (operações de antecipação)
CREATE TABLE operation_titles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    operation_id INT NOT NULL,
    ordem INT NOT NULL,
    numero VARCHAR(50),
    tipo VARCHAR(30),
    sacado_nome VARCHAR(255),
    sacado_documento VARCHAR(18),
    data_emissao DATE,
    data_vencimento DATE,
    valor_face DECIMAL(15,2),
    valor_presente DECIMAL(15,2),
    status ENUM('pendente','vencido','pago','inadimplente') DEFAULT 'pendente',
    data_pagamento DATE NULL,
    FOREIGN KEY (operation_id) REFERENCES operations(id)
);

-- Veículos (operações de mútuo com garantia)
CREATE TABLE operation_vehicles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    operation_id INT NOT NULL,
    marca VARCHAR(50),
    modelo VARCHAR(100),
    ano_fab INT,
    ano_mod INT,
    cor VARCHAR(30),
    combustivel VARCHAR(30),
    chassi VARCHAR(17),
    placa VARCHAR(10),
    renavam VARCHAR(15),
    municipio_emplacamento VARCHAR(100),
    uf CHAR(2),
    valor_avaliacao DECIMAL(15,2),
    gravame_status ENUM('pendente','solicitado','registrado','cancelado') DEFAULT 'pendente',
    gravame_numero VARCHAR(50),
    gravame_data DATE,
    FOREIGN KEY (operation_id) REFERENCES operations(id)
);

-- Avalistas (mútuos com aval ou antecipações com aval opcional)
CREATE TABLE operation_guarantors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    operation_id INT NOT NULL,
    nome VARCHAR(255) NOT NULL,
    cpf VARCHAR(14) NOT NULL,
    rg VARCHAR(30),
    nacionalidade VARCHAR(50) DEFAULT 'brasileiro(a)',
    estado_civil VARCHAR(30),
    profissao VARCHAR(100),
    endereco TEXT,
    email VARCHAR(100),
    whatsapp VARCHAR(20),
    casado TINYINT(1) DEFAULT 0,
    regime_casamento VARCHAR(50),
    conjuge_nome VARCHAR(255),
    conjuge_cpf VARCHAR(14),
    tipo ENUM('AVALISTA','FIADOR') DEFAULT 'AVALISTA',
    FOREIGN KEY (operation_id) REFERENCES operations(id)
);

-- Testemunhas (todos os contratos)
CREATE TABLE operation_witnesses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    operation_id INT NOT NULL,
    ordem TINYINT NOT NULL,
    nome VARCHAR(255) NOT NULL,
    cpf VARCHAR(14) NOT NULL,
    email VARCHAR(100),
    FOREIGN KEY (operation_id) REFERENCES operations(id)
);

-- Templates (versionamento)
CREATE TABLE contract_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    template_content LONGTEXT NOT NULL,
    version VARCHAR(20) DEFAULT '1.0',
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Contratos gerados (auditoria + assinatura)
CREATE TABLE generated_contracts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    operation_id INT NOT NULL,
    template_code VARCHAR(50) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_hash VARCHAR(64),
    status ENUM('generated','sent_to_signature','signed','cancelled') DEFAULT 'generated',
    signature_platform VARCHAR(50),
    signature_document_id VARCHAR(255),
    signed_at TIMESTAMP NULL,
    metadata JSON,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (operation_id) REFERENCES operations(id),
    INDEX idx_operation (operation_id),
    INDEX idx_status (status)
);
```

---

## 🔧 Códigos de Template (para o seed)

Quando popular `contract_templates`, use estes códigos:

| Código | Arquivo .md |
|---|---|
| `ANTECIPACAO_RECEBIVEIS` | `01_template_antecipacao_recebiveis.md` |
| `MUTUO_SIMPLES` | `02a_template_mutuo_simples.md` |
| `MUTUO_COM_AVAL` | `02b_template_mutuo_com_aval.md` |
| `MUTUO_COM_GARANTIA` | `02c_template_mutuo_com_garantia.md` |
| `MUTUO_COM_GARANTIA_E_AVAL` | `02d_template_mutuo_com_garantia_e_aval.md` |
| `NOTA_PROMISSORIA` | `03_template_nota_promissoria.md` |

---

## 🎯 Mudanças do Service Orquestrador

```php
class ContractGeneratorService
{
    public function generateForOperation(int $operationId): array
    {
        $op = $this->dataService->loadFullOperation($operationId);
        
        if ($op->natureza === 'DESCONTO') {
            return $this->generateAntecipacao($op);
        }
        
        // Empréstimo: escolhe variante por flags
        $templateCode = $this->resolveMutuoTemplate($op);
        return $this->generateMutuo($op, $templateCode);
    }
    
    private function resolveMutuoTemplate($op): string
    {
        return match (true) {
            !$op->tem_garantia && !$op->tem_avalista => 'MUTUO_SIMPLES',
            !$op->tem_garantia &&  $op->tem_avalista => 'MUTUO_COM_AVAL',
             $op->tem_garantia && !$op->tem_avalista => 'MUTUO_COM_GARANTIA',
             $op->tem_garantia &&  $op->tem_avalista => 'MUTUO_COM_GARANTIA_E_AVAL',
        };
    }
    
    private function generateMutuo($op, string $templateCode): array
    {
        $this->mutuoValidator->validate($op);
        $data = $this->dataService->buildMutuoContext($op);
        
        // Gera contrato principal
        $contratoHtml = $this->renderer->render($templateCode, $data);
        $contratoPath = $this->pdfBuilder->buildPdf(
            $contratoHtml,
            $op->id . '_' . $templateCode . '_' . date('Ymd') . '.pdf',
            $op->id
        );
        $this->register($op->id, $templateCode, $contratoPath);
        
        // Gera NP vinculada (com ou sem aval)
        $npHtml = $this->renderer->render('NOTA_PROMISSORIA', $data);
        $npPath = $this->pdfBuilder->buildPdf(
            $npHtml,
            $op->id . '_NP_' . date('Ymd') . '.pdf',
            $op->id
        );
        $this->register($op->id, 'NOTA_PROMISSORIA', $npPath);
        
        return ['contrato' => $contratoPath, 'np' => $npPath];
    }
    
    private function generateAntecipacao($op): array
    {
        $this->cessaoValidator->validate($op);
        $data = $this->dataService->buildCessaoContext($op);
        
        $html = $this->renderer->render('ANTECIPACAO_RECEBIVEIS', $data);
        $path = $this->pdfBuilder->buildPdf(
            $html,
            $op->id . '_ANTECIPACAO_' . date('Ymd') . '.pdf',
            $op->id
        );
        $this->register($op->id, 'ANTECIPACAO_RECEBIVEIS', $path);
        
        return ['contrato' => $path];
    }
}
```

---

## ✅ Validações por Tipo

### Para qualquer mútuo (02a, 02b, 02c, 02d):
- Cliente é PJ com porte MEI/ME/EPP (LC 167/2019)
- Capital, prazo, taxa, parcelas e cronograma preenchidos
- 2 testemunhas

### Adicional para 02b e 02d (tem avalista):
- Avalista cadastrado com CPF, RG, endereço
- Se casado, dados do cônjuge presentes (CC art. 1.647)

### Adicional para 02c e 02d (tem garantia):
- Veículo cadastrado com chassi, placa, RENAVAM
- Município/UF de emplacamento preenchidos
- Pós-assinatura: averbar gravame no DETRAN em até 10 dias úteis

### Para antecipação (01):
- Pelo menos 1 título
- Sacado de cada título ≠ cedente
- Documentos de origem arquivados (NF, contrato, etc.)
- Conta bancária do cedente para crédito do líquido

---

## 📂 Estrutura Final de Arquivos no Sistema

```
app/Modules/Contracts/Templates/
├── 01_template_antecipacao_recebiveis.md
├── 02a_template_mutuo_simples.md
├── 02b_template_mutuo_com_aval.md
├── 02c_template_mutuo_com_garantia.md
├── 02d_template_mutuo_com_garantia_e_aval.md
└── 03_template_nota_promissoria.md
```
