# PROMPT CONSOLIDADO PARA TRAE IDE (Gemini)
## Implementação do Módulo de Contratos e Documentos no Sistema ACM

> **Uso:** Copie TODO o conteúdo abaixo e cole como instrução inicial no seu agente TRAE. Os 4 arquivos de templates mencionados (`mutuo_esc.md`, `cessao_mae.md`, `bordero.md`, `nota_promissoria.md`) devem ser colocados na pasta `app/Modules/Contracts/Templates/` do projeto antes do agente começar.

---

# CONTEXTO DO PROJETO

Estou desenvolvendo um módulo de **Contratos e Documentos** para o sistema interno da **ACM Empresa Simples de Crédito LTDA** (CNPJ 63.530.897/0001-85, sede em Piracicaba/SP). O sistema já existe e gerencia operações financeiras da empresa. Agora preciso adicionar a funcionalidade de geração automática de contratos em PDF a partir dos dados das operações cadastradas.

**Stack técnica:**
- Backend: PHP 8.1+
- Frontend: Alpine.js + Tailwind CSS
- Database: MySQL 8
- Princípio: no-build-step development (sem webpack/npm para frontend)

---

# OBJETIVO DO MÓDULO

Para cada operação financeira cadastrada no sistema, o operador deve ser capaz de clicar em um botão **"Gerar Contratos e Documentos"** que, automaticamente:

1. Identifica o tipo da operação (empréstimo ou desconto de recebíveis)
2. Valida se todos os dados necessários estão completos
3. Valida a elegibilidade do tomador conforme regulação da ESC (LC 167/2019)
4. Gera os PDFs apropriados para cada tipo de operação
5. Salva os PDFs em pasta específica da operação
6. Registra metadados no banco de dados
7. Disponibiliza links para download pelo operador

---

# REGRAS DE NEGÓCIO (CRÍTICAS)

## Regra 1: Tipo de Operação determina os documentos

| Natureza da Operação | Documentos Gerados |
|---|---|
| `EMPRESTIMO` (Mútuo Feneratício) | Contrato de Mútuo + Nota Promissória |
| `DESCONTO` (Cessão de Crédito) | Contrato-Mãe de Cessão (1ª vez) + Borderô (sempre) |

## Regra 2: Validação de Tomador para Mútuo

A ACM é uma Empresa Simples de Crédito (ESC) regulada pela Lei Complementar 167/2019, que **restringe suas operações de empréstimo a MEI, ME e EPP**. Portanto:

```php
if ($operacao->natureza === 'EMPRESTIMO') {
    if ($cliente->tipo_pessoa === 'PF') {
        throw new ValidationException(
            'Operação não permitida: a ACM ESC não pode realizar empréstimo para pessoa física. '
            . 'Oriente o cliente a abrir MEI (gratuito em gov.br/mei).'
        );
    }
    
    if (!in_array($cliente->porte, ['MEI', 'ME', 'EPP'])) {
        throw new ValidationException(
            'LC 167/2019 restringe operações da ESC a MEI, ME e EPP. '
            . 'Cliente com porte "'.$cliente->porte.'" não é elegível.'
        );
    }
}
```

## Regra 3: Validação de Operação de Desconto

```php
if ($operacao->natureza === 'DESCONTO') {
    if ($cliente->tipo_pessoa === 'PF') {
        throw new ValidationException('A ACM ESC não pode realizar operações de desconto com pessoa física. O Cedente deve ser obrigatoriamente Pessoa Jurídica.');
    }

    if (empty($operacao->titulos)) {
        throw new ValidationException('Operação de desconto requer pelo menos 1 título.');
    }
    
    foreach ($operacao->titulos as $titulo) {
        if ($titulo->sacado_documento === $cliente->documento) {
            throw new ValidationException(
                'Título '.$titulo->numero.': o sacado não pode ser o próprio cedente. '
                . 'Isso caracterizaria mútuo travestido, não cessão de crédito.'
            );
        }
    }
}
```

## Regra 4: Contrato-Mãe de Cessão é emitido uma única vez por cliente

```php
if ($operacao->natureza === 'DESCONTO') {
    $contratoMae = ContratoMae::where('cliente_id', $cliente->id)
        ->where('status', 'ativo')
        ->first();
    
    if (!$contratoMae) {
        // Gera Contrato-Mãe + Borderô
        $this->gerarContratoMae($operacao);
        $this->gerarBordero($operacao);
    } else {
        // Gera apenas Borderô (aditivo ao Contrato-Mãe)
        $this->gerarBordero($operacao, $contratoMae);
    }
}
```

---

# SCHEMA SQL — NOVAS TABELAS E ALTERAÇÕES

Implementar os seguintes scripts (em ordem):

```sql
-- 1. Nova tabela para armazenar templates (caso queiramos versionamento)
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

-- 2. Tabela de contratos gerados
CREATE TABLE generated_contracts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    operation_id INT NOT NULL,
    template_code VARCHAR(50) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_hash VARCHAR(64),
    status ENUM('generated', 'sent_to_signature', 'signed', 'cancelled') DEFAULT 'generated',
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

-- 3. Tabela de Contrato-Mãe de Cessão
CREATE TABLE master_cession_contracts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    file_path VARCHAR(500),
    signed_at TIMESTAMP NULL,
    status ENUM('rascunho','ativo','encerrado') DEFAULT 'rascunho',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id)
);

-- 4. Ajustes na tabela clientes (se já não existirem)
ALTER TABLE clientes 
    ADD COLUMN IF NOT EXISTS tipo_pessoa ENUM('PF','PJ') NOT NULL DEFAULT 'PJ',
    ADD COLUMN IF NOT EXISTS porte ENUM('MEI','ME','EPP','MEDIO','GRANDE','PF') NULL,
    ADD COLUMN IF NOT EXISTS representante_nome VARCHAR(255),
    ADD COLUMN IF NOT EXISTS representante_cpf VARCHAR(14),
    ADD COLUMN IF NOT EXISTS representante_rg VARCHAR(30),
    ADD COLUMN IF NOT EXISTS representante_nacionalidade VARCHAR(50) DEFAULT 'brasileiro(a)',
    ADD COLUMN IF NOT EXISTS representante_estado_civil VARCHAR(30),
    ADD COLUMN IF NOT EXISTS representante_profissao VARCHAR(100),
    ADD COLUMN IF NOT EXISTS representante_endereco TEXT;

-- 5. Ajustes na tabela operations
ALTER TABLE operations 
    ADD COLUMN IF NOT EXISTS natureza ENUM('EMPRESTIMO','DESCONTO') NOT NULL,
    ADD COLUMN IF NOT EXISTS valor_principal DECIMAL(15,2),
    ADD COLUMN IF NOT EXISTS valor_total_devido DECIMAL(15,2),
    ADD COLUMN IF NOT EXISTS taxa_juros_mensal DECIMAL(6,4),
    ADD COLUMN IF NOT EXISTS taxa_juros_anual DECIMAL(6,4),
    ADD COLUMN IF NOT EXISTS cet_mensal DECIMAL(6,4),
    ADD COLUMN IF NOT EXISTS num_parcelas INT,
    ADD COLUMN IF NOT EXISTS valor_parcela DECIMAL(15,2),
    ADD COLUMN IF NOT EXISTS data_primeiro_vencimento DATE,
    ADD COLUMN IF NOT EXISTS periodicidade VARCHAR(20) DEFAULT 'mensais',
    ADD COLUMN IF NOT EXISTS taxa_desagio_mensal DECIMAL(6,4);

-- 6. Veículos em garantia
CREATE TABLE IF NOT EXISTS operation_vehicles (
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (operation_id) REFERENCES operations(id)
);

-- 7. Garantidores (avalistas/fiadores)
CREATE TABLE IF NOT EXISTS operation_guarantors (
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (operation_id) REFERENCES operations(id)
);

-- 8. Testemunhas
CREATE TABLE IF NOT EXISTS operation_witnesses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    operation_id INT NOT NULL,
    ordem TINYINT NOT NULL,
    nome VARCHAR(255) NOT NULL,
    cpf VARCHAR(14) NOT NULL,
    email VARCHAR(100),
    FOREIGN KEY (operation_id) REFERENCES operations(id)
);
```

---

# ARQUITETURA DE CÓDIGO

## Estrutura de Pastas

```
app/Modules/Contracts/
├── Controllers/
│   └── ContractController.php
├── Services/
│   ├── ContractGeneratorService.php       # Orquestrador principal
│   ├── TemplateRendererService.php        # Renderização Mustache
│   ├── PdfBuilderService.php              # Wrapper mPDF
│   ├── NumberToWordsService.php           # Valor por extenso
│   └── OperationDataService.php           # Agregação de dados da operação
├── Validators/
│   ├── MutuoValidator.php
│   └── CessaoValidator.php
├── Templates/
│   ├── mutuo_esc.md                       # (copiar conteúdo do arquivo 02)
│   ├── cessao_mae.md                      # (copiar Parte 1 do arquivo 03)
│   ├── bordero.md                         # (copiar Parte 2 do arquivo 03)
│   └── nota_promissoria.md                # (copiar conteúdo do arquivo 04)
├── Routes/
│   └── contracts.php
└── Views/
    └── contract_generation_modal.blade.php (ou .phtml)

storage/app/contratos/
└── {operation_id}/
    ├── {operation_id}_MUTUO_YYYYMMDD.pdf
    ├── {operation_id}_NP_YYYYMMDD.pdf
    ├── {operation_id}_CESSAO_YYYYMMDD.pdf
    └── {operation_id}_BORDERO_YYYYMMDD.pdf
```

## Dependências Composer a instalar

```bash
composer require mpdf/mpdf              # Geração de PDF
composer require mustache/mustache      # Renderização de templates
```

## Classe principal: ContractGeneratorService

```php
<?php
namespace App\Modules\Contracts\Services;

class ContractGeneratorService
{
    public function __construct(
        private TemplateRendererService $renderer,
        private PdfBuilderService $pdfBuilder,
        private OperationDataService $dataService,
        private MutuoValidator $mutuoValidator,
        private CessaoValidator $cessaoValidator
    ) {}
    
    public function generateForOperation(int $operationId): array
    {
        $operation = $this->dataService->loadFullOperation($operationId);
        $generatedFiles = [];
        
        match ($operation->natureza) {
            'EMPRESTIMO' => $generatedFiles = $this->generateMutuoDocuments($operation),
            'DESCONTO'   => $generatedFiles = $this->generateCessaoDocuments($operation),
        };
        
        return $generatedFiles;
    }
    
    private function generateMutuoDocuments($operation): array
    {
        $this->mutuoValidator->validate($operation);
        
        $data = $this->dataService->buildMutuoContext($operation);
        
        // 1. Contrato de Mútuo
        $contratoHtml = $this->renderer->render('mutuo_esc', $data);
        $contratoPdfPath = $this->pdfBuilder->buildPdf(
            html: $contratoHtml,
            filename: $operation->id . '_MUTUO_' . date('Ymd') . '.pdf',
            operationId: $operation->id
        );
        
        // 2. Nota Promissória
        $npHtml = $this->renderer->render('nota_promissoria', $data);
        $npPdfPath = $this->pdfBuilder->buildPdf(
            html: $npHtml,
            filename: $operation->id . '_NP_' . date('Ymd') . '.pdf',
            operationId: $operation->id
        );
        
        // 3. Registra no banco
        $this->registerGeneratedContract($operation->id, 'MUTUO_ESC', $contratoPdfPath);
        $this->registerGeneratedContract($operation->id, 'NP', $npPdfPath);
        
        return [
            'mutuo' => $contratoPdfPath,
            'nota_promissoria' => $npPdfPath
        ];
    }
    
    private function generateCessaoDocuments($operation): array
    {
        $this->cessaoValidator->validate($operation);
        
        $data = $this->dataService->buildCessaoContext($operation);
        $result = [];
        
        // 1. Verifica se já existe Contrato-Mãe ativo
        $contratoMae = MasterCessionContract::where('cliente_id', $operation->cliente_id)
            ->where('status', 'ativo')
            ->first();
        
        if (!$contratoMae) {
            $contratoMaeHtml = $this->renderer->render('cessao_mae', $data);
            $contratoMaePath = $this->pdfBuilder->buildPdf(
                html: $contratoMaeHtml,
                filename: $operation->id . '_CESSAO_MAE_' . date('Ymd') . '.pdf',
                operationId: $operation->id
            );
            
            MasterCessionContract::create([
                'cliente_id' => $operation->cliente_id,
                'file_path' => $contratoMaePath,
                'status' => 'rascunho',  // Muda para 'ativo' após assinatura
            ]);
            
            $this->registerGeneratedContract($operation->id, 'CESSAO_MAE', $contratoMaePath);
            $result['cessao_mae'] = $contratoMaePath;
        }
        
        // 2. Sempre gera Borderô
        $borderoHtml = $this->renderer->render('bordero', $data);
        $borderoPath = $this->pdfBuilder->buildPdf(
            html: $borderoHtml,
            filename: $operation->id . '_BORDERO_' . date('Ymd') . '.pdf',
            operationId: $operation->id
        );
        
        $this->registerGeneratedContract($operation->id, 'BORDERO', $borderoPath);
        $result['bordero'] = $borderoPath;
        
        return $result;
    }
    
    private function registerGeneratedContract(int $operationId, string $templateCode, string $filePath): void
    {
        GeneratedContract::create([
            'operation_id' => $operationId,
            'template_code' => $templateCode,
            'file_path' => $filePath,
            'file_hash' => hash_file('sha256', storage_path('app/' . $filePath)),
            'status' => 'generated',
            'created_by' => auth()->id(),
        ]);
    }
}
```

## Classe TemplateRendererService (usando Mustache)

```php
<?php
namespace App\Modules\Contracts\Services;

use Mustache_Engine;
use Mustache_Loader_FilesystemLoader;
use Parsedown;

class TemplateRendererService
{
    private Mustache_Engine $mustache;
    private Parsedown $markdown;
    
    public function __construct()
    {
        $this->mustache = new Mustache_Engine([
            'loader' => new Mustache_Loader_FilesystemLoader(
                app_path('Modules/Contracts/Templates'),
                ['extension' => '.md']
            ),
            'escape' => fn($value) => htmlspecialchars($value, ENT_QUOTES, 'UTF-8'),
        ]);
        
        $this->markdown = new Parsedown();
        $this->markdown->setMarkupEscaped(false);
    }
    
    public function render(string $templateName, array $data): string
    {
        // 1. Renderiza placeholders Mustache
        $rendered = $this->mustache->render($templateName, $data);
        
        // 2. Converte Markdown para HTML
        $html = $this->markdown->text($rendered);
        
        // 3. Envolve em HTML completo com CSS para PDF
        return $this->wrapInHtmlTemplate($html);
    }
    
    private function wrapInHtmlTemplate(string $body): string
    {
        $css = $this->getDefaultCss();
        
        return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <style>{$css}</style>
</head>
<body>
{$body}
</body>
</html>
HTML;
    }
    
    private function getDefaultCss(): string
    {
        return <<<CSS
@page {
    size: A4 portrait;
    margin: 2.5cm 2cm 2.5cm 2.5cm;
}

body {
    font-family: "Times New Roman", serif;
    font-size: 11pt;
    line-height: 1.5;
    color: #000;
    text-align: justify;
}

h1 {
    font-size: 14pt;
    text-align: center;
    font-weight: bold;
    text-transform: uppercase;
    margin: 20px 0;
}

h2 {
    font-size: 12pt;
    font-weight: bold;
    margin: 15px 0 10px 0;
    text-transform: uppercase;
}

h3 {
    font-size: 11pt;
    font-weight: bold;
    margin: 10px 0 5px 0;
}

p { margin: 8px 0; }

strong { font-weight: bold; }

table {
    width: 100%;
    border-collapse: collapse;
    margin: 15px 0;
    font-size: 10pt;
}

table th, table td {
    border: 1px solid #000;
    padding: 6px 8px;
    vertical-align: top;
}

table th {
    background-color: #f0f0f0;
    font-weight: bold;
    text-align: center;
}

hr {
    border: none;
    border-top: 1px solid #000;
    margin: 20px 0;
}

.assinatura {
    margin-top: 40px;
    page-break-inside: avoid;
}
CSS;
    }
}
```

## Classe PdfBuilderService

```php
<?php
namespace App\Modules\Contracts\Services;

use Mpdf\Mpdf;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;

class PdfBuilderService
{
    public function buildPdf(string $html, string $filename, int $operationId): string
    {
        $defaultConfig = (new ConfigVariables())->getDefaults();
        $fontDirs = $defaultConfig['fontDir'];
        
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'P',
            'margin_top' => 25,
            'margin_bottom' => 25,
            'margin_left' => 25,
            'margin_right' => 20,
            'default_font' => 'times',
        ]);
        
        $mpdf->SetTitle('Contrato ACM - Operação ' . $operationId);
        $mpdf->SetAuthor('ACM Empresa Simples de Crédito LTDA');
        $mpdf->SetCreator('Sistema ACM');
        
        // Rodapé com paginação
        $mpdf->SetHTMLFooter('<div style="text-align:center; font-size:8pt; color:#666;">
            ACM Empresa Simples de Crédito LTDA - CNPJ 63.530.897/0001-85 | 
            Piracicaba/SP | Página {PAGENO} de {nbpg}
        </div>');
        
        $mpdf->WriteHTML($html);
        
        $relativePath = 'contratos/' . $operationId . '/' . $filename;
        $fullPath = storage_path('app/' . $relativePath);
        
        // Garante diretório
        if (!is_dir(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }
        
        $mpdf->Output($fullPath, 'F');
        
        return $relativePath;
    }
}
```

## Rota e Controller

```php
// routes/contracts.php
Route::middleware(['auth'])->group(function() {
    Route::post('/operations/{id}/generate-contracts', 
        [ContractController::class, 'generate'])->name('contracts.generate');
    Route::get('/contracts/{id}/download', 
        [ContractController::class, 'download'])->name('contracts.download');
});
```

```php
// app/Modules/Contracts/Controllers/ContractController.php
<?php
namespace App\Modules\Contracts\Controllers;

use App\Modules\Contracts\Services\ContractGeneratorService;

class ContractController
{
    public function __construct(
        private ContractGeneratorService $generator
    ) {}
    
    public function generate(int $id)
    {
        try {
            $files = $this->generator->generateForOperation($id);
            
            return response()->json([
                'success' => true,
                'files' => $files,
                'message' => 'Contratos gerados com sucesso.'
            ]);
        } catch (\App\Exceptions\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'validation',
                'message' => $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            logger()->error('Erro ao gerar contrato', [
                'operation_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'server',
                'message' => 'Erro interno ao gerar contratos.'
            ], 500);
        }
    }
    
    public function download(int $id)
    {
        $contract = GeneratedContract::findOrFail($id);
        // Validar autorização
        return response()->download(storage_path('app/' . $contract->file_path));
    }
}
```

## View (Alpine.js + Tailwind)

```blade
{{-- Em qualquer tela de operação, inserir este botão --}}
<div x-data="contractGenerator({{ $operation->id }})">
    <button @click="generate" 
            :disabled="loading"
            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:bg-gray-400">
        <span x-show="!loading">📄 Gerar Contratos e Documentos</span>
        <span x-show="loading">Gerando...</span>
    </button>
    
    <div x-show="error" class="mt-2 p-3 bg-red-100 text-red-800 rounded" x-text="errorMessage"></div>
    
    <div x-show="files" class="mt-4 space-y-2">
        <template x-for="(path, type) in files" :key="type">
            <a :href="`/contracts/download/${path}`" 
               class="block px-3 py-2 bg-green-100 text-green-800 rounded hover:bg-green-200"
               target="_blank">
                📥 Download <span x-text="type"></span>
            </a>
        </template>
    </div>
</div>

<script>
function contractGenerator(operationId) {
    return {
        loading: false,
        error: false,
        errorMessage: '',
        files: null,
        
        async generate() {
            this.loading = true;
            this.error = false;
            this.files = null;
            
            try {
                const response = await fetch(`/operations/${operationId}/generate-contracts`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    this.files = data.files;
                } else {
                    this.error = true;
                    this.errorMessage = data.message;
                }
            } catch (err) {
                this.error = true;
                this.errorMessage = 'Erro de conexão. Tente novamente.';
            } finally {
                this.loading = false;
            }
        }
    };
}
</script>
```

---

# PLACEHOLDERS DOS TEMPLATES — Mapeamento de Dados

O `OperationDataService` deve construir o contexto (array associativo) que será passado ao Mustache. Estrutura:

```php
// Para Contrato de Mútuo
$context = [
    'credor' => [
        'representante' => [
            'nome' => 'Nome do Representante ACM',
            'cpf' => '000.000.000-00',
            'rg' => '00.000.000-0',
            'nacionalidade' => 'brasileiro',
            'estado_civil' => 'casado',
        ],
        'conta' => [
            'banco' => 'Banco Inter',
            'agencia' => '0001',
            'numero' => '12345-6',
            'pix' => '63530897000185',
        ],
        'endereco_completo' => 'Rua Abelardo Benedicto Liborio, 600, Loteamento Distrito Industrial Uninorte, Piracicaba/SP, CEP 13.413-071',
        'email' => 'contato@acmesc.com.br',
        'whatsapp' => '(19) 99999-9999',
    ],
    'devedor' => [
        'razao_social' => 'MARIA DA SILVA 12345678900',
        'descricao_juridica' => 'microempreendedor individual',
        'cnpj' => '12.345.678/0001-90',
        'porte' => 'MEI',
        'endereco_completo' => 'Rua X, 100, Bairro Y, Piracicaba/SP, CEP 13.400-000',
        'email' => 'maria@email.com',
        'whatsapp' => '(19) 98888-8888',
        'representante' => [
            'nome' => 'Maria da Silva',
            'cpf' => '123.456.789-00',
            'rg' => '12.345.678-9',
            'nacionalidade' => 'brasileira',
            'estado_civil' => 'solteira',
            'profissao' => 'comerciante',
            'endereco' => 'Rua X, 100, Piracicaba/SP',
        ],
    ],
    'avalista' => [
        'nome' => 'João da Silva',
        'cpf' => '987.654.321-00',
        'rg' => '98.765.432-1',
        'nacionalidade' => 'brasileiro',
        'estado_civil' => 'casado',
        'profissao' => 'engenheiro',
        'endereco_completo' => 'Rua Z, 200, Piracicaba/SP, CEP 13.400-111',
        'email' => 'joao@email.com',
        'whatsapp' => '(19) 97777-7777',
        'casado' => true,
        'regime_casamento' => 'comunhão parcial de bens',
        'conjuge' => [
            'nome' => 'Ana da Silva',
            'cpf' => '111.222.333-44',
        ],
    ],
    'operacao' => [
        'id' => '00042',
        'local' => 'Piracicaba/SP',
        'data_extenso' => '24 de abril de 2026',
        'valor_principal' => '50.000,00',
        'valor_principal_extenso' => 'cinquenta mil reais',
        'valor_total_devido' => '75.000,00',
        'valor_total_devido_extenso' => 'setenta e cinco mil reais',
        'num_parcelas' => 24,
        'num_parcelas_extenso' => 'vinte e quatro',
        'valor_parcela' => '3.125,00',
        'valor_parcela_extenso' => 'três mil cento e vinte e cinco reais',
        'data_primeiro_vencimento' => '24/05/2026',
        'periodicidade' => 'mensais e consecutivas',
        'taxa_juros_mensal' => '3,00',
        'taxa_juros_mensal_extenso' => 'três',
        'taxa_juros_anual' => '42,58',
        'taxa_juros_anual_extenso' => 'quarenta e dois vírgula cinquenta e oito',
        'cet' => '3,15',
        'total_juros' => '25.000,00',
        'forma_liberacao' => 'transferência bancária (TED/PIX) à conta de titularidade do MUTUÁRIO',
        'forma_pagamento' => 'boleto bancário, PIX ou transferência para a conta abaixo',
        'sistema_amortizacao' => 'PRICE (parcelas fixas)',
        'num_vias' => 2,
        'num_vias_extenso' => 'duas',
    ],
    'veiculo' => [
        'marca' => 'Chevrolet',
        'modelo' => 'Onix LT 1.0',
        'ano_fab' => 2022,
        'ano_mod' => 2023,
        'cor' => 'Branca',
        'combustivel' => 'Flex',
        'chassi' => '9BGKS48Y0NG123456',
        'placa' => 'ABC1D23',
        'renavam' => '12345678901',
        'municipio_emplacamento' => 'Piracicaba',
        'uf' => 'SP',
        'valor_avaliacao' => '65.000,00',
        'valor_avaliacao_extenso' => 'sessenta e cinco mil reais',
    ],
    'testemunhas' => [
        ['nome' => 'Pedro Santos', 'cpf' => '555.666.777-88'],
        ['nome' => 'Carlos Oliveira', 'cpf' => '999.888.777-66'],
    ],
    'cronograma' => [
        ['numero' => 1, 'data_vencimento' => '24/05/2026', 'valor_parcela' => '3.125,00', 'valor_amortizacao' => '1.625,00', 'valor_juros' => '1.500,00', 'saldo_devedor' => '48.375,00'],
        // ... 24 linhas
    ],
];
```

Para Borderô, a estrutura é diferente — veja template `bordero.md`.

---

# NÚMERO POR EXTENSO

Implementar ou instalar biblioteca PHP de conversão. Opção recomendada:

```bash
composer require luccasmaso/extenso
```

```php
use LuccasMaso\Extenso\Extenso;

$valor = 75000.00;
$extenso = new Extenso($valor);
echo $extenso->toString(); 
// "setenta e cinco mil reais"
```

---

# ENTREGA ESPERADA

Gere TODO o código necessário para implementar este módulo:

1. **Migrations SQL** (arquivo por migration)
2. **Models Eloquent** (ou ActiveRecord conforme arquitetura existente): `Operation`, `Cliente`, `GeneratedContract`, `MasterCessionContract`, `OperationVehicle`, `OperationGuarantor`, `OperationWitness`
3. **Services completos**: `ContractGeneratorService`, `TemplateRendererService`, `PdfBuilderService`, `OperationDataService`, `NumberToWordsService`
4. **Validators**: `MutuoValidator`, `CessaoValidator`
5. **Controller**: `ContractController`
6. **Routes**
7. **Views/Blade** com o botão de geração
8. **Templates** nas pastas corretas (instrução: copiar conteúdo dos 4 arquivos .md fornecidos)
9. **Script de seed** para popular `contract_templates` com os conteúdos dos templates
10. **Testes unitários básicos** (PHPUnit) para os Validators e o Service

Para cada arquivo criado, explique brevemente o que ele faz. Use PSR-12 para PHP. Comentários em português. Valide retornos e trate exceções adequadamente. Não use frameworks pesados além do que já está no projeto — mantenha no espírito "no-build-step".

---

**Fim do prompt. Comece implementando as migrations SQL.**
