<?php
require_once 'auth_check.php';
require_once 'menu.php';
require_once 'db_connection.php'; // Garante que a conexão $pdo esteja disponível

// --- Função para ler o arquivo de configuração ---
function readConfig($filePath) {
    if (!file_exists($filePath)) {
        // Criar arquivo padrão se não existir
        $defaultConfig = [
            "default_taxa_mensal" => 5.00,
            "iof_adicional_rate" => 0.0038,
            "iof_diaria_rate" => 0.000082
        ];
        file_put_contents($filePath, json_encode($defaultConfig, JSON_PRETTY_PRINT));
        return $defaultConfig;
    }
    $configContent = file_get_contents($filePath);
    return json_decode($configContent, true);
}

// Caminho para o arquivo de configuração
$configFilePath = __DIR__ . '/config.json';
$appConfig = readConfig($configFilePath);

// Usar valores do config para preencher padrões
$defaultTaxaMensal = $appConfig['default_taxa_mensal'] ?? 5.00;

$cedentes = [];
$erro_cedentes = null;
$sacados = [];
$erro_sacados = null;

try {
    $stmt = $pdo->query("SELECT id, empresa as nome FROM cedentes ORDER BY empresa ASC");
    $cedentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao buscar Cedentes no DB: " . $e->getMessage());
    $erro_cedentes = "Erro ao carregar lista de cedentes.";
    $cedentes = [];
}

try {
    $stmt = $pdo->query("SELECT id, empresa as nome FROM sacados ORDER BY empresa ASC");
    $sacados = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao buscar Sacados no DB: " . $e->getMessage());
    $erro_sacados = "Erro ao carregar lista de sacados.";
    $sacados = [];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nova Simulação / Registro - Calculadora Desconto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- ADICIONANDO TAILWIND CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#007cba',
                        success: '#198754',
                        danger: '#dc3545',
                        warning: '#ffc107',
                        info: '#0dcaf0'
                    }
                }
            },
            corePlugins: {
                preflight: false, // Desativa o reset do Tailwind para não quebrar o Bootstrap do menu
            }
        }
    </script>

    <!-- ADICIONANDO ALPINE.JS -->
      <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
      
      <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
      <style>
          /* Estilos CSS Base */
          input#taxaMensal::-webkit-outer-spin-button, input#taxaMensal::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
          input#taxaMensal[type=number] { -moz-appearance: textfield; }
          input#taxaMensal { text-align: center; }
          .chart-wrapper { position: relative; max-height: 350px; margin-top: 20px; margin-bottom: 30px; }
          .chart-wrapper canvas { max-width: 100%; max-height: 100%; }
          /* Utilitários Tailwind mesclados */
          .tw-card { @apply bg-white rounded-lg border border-gray-200 shadow-sm p-4 mb-6; }
          .tw-label { @apply block text-sm font-medium text-gray-700 mb-1; }
          .tw-input { @apply block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm border p-2; }
          .tw-btn-primary { @apply inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500; }
          .tw-btn-secondary { @apply inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary; }
          
          .resultado-total-item { padding: 10px 12px; margin-bottom: 10px; text-align: center; }
          .result-row-1 .resultado-total-item .value { font-size: 1.1em; font-weight: normal; }
          .result-row-1 .resultado-total-item .label { font-size: 0.8em; }
          .result-row-2 .resultado-total-item .value { font-size: 1.3em; font-weight: bold; }
          .result-row-2 .resultado-total-item .label { font-size: 0.85em; }
          .value.profit { color: #198754; }
          .value.loss { color: #dc3545; }
          .cobrar-iof-wrapper.d-none { display: none !important; }
          .dias-restantes-cell { text-align: center; vertical-align: middle; font-style: italic; color: #6c757d; font-size: 0.9em;}
          /* NOVA CLASSE CSS PARA A NOVA COLUNA */
          .valor-pago-cell { text-align: right; vertical-align: middle; }
      </style>
  </head>
<body class="bg-gray-50 text-gray-900" x-data="calculadora()">

  <div class="container mt-4 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <h1 class="mb-4 text-3xl font-bold text-gray-900">Nova Simulação / Registro de Operação</h1>

         <form id="calculationForm" method="post" @submit.prevent>
          <fieldset class="tw-card">
              <legend class="float-none w-auto px-3 text-lg font-medium text-gray-900 border-b pb-2 mb-4 w-full">Parâmetros da Operação</legend>
              <div class="row g-3 align-items-end">
                  <div class="col-md-5">
                      <label for="cedente" class="tw-label">Cedente</label>
                      <select id="cedente" name="cedente_id" class="tw-input" x-model="params.cedenteId" @change="checkEncontroContas()">
                          <option value="" selected>-- Selecione (Obrigatório p/ Registrar) --</option>
                          <?php foreach ($cedentes as $cedente): ?>
                              <option value="<?php echo htmlspecialchars($cedente['id']); ?>"><?php echo htmlspecialchars($cedente['nome']); ?></option>
                          <?php endforeach; ?>
                      </select>
                  </div>
                  <div class="col-md-4">
                      <label for="taxaMensal" class="tw-label">Taxa de Desconto (% a.m.)</label>
                      <div class="flex rounded-md shadow-sm">
                          <button class="px-3 py-2 border border-gray-300 rounded-l-md bg-gray-50 text-gray-500 hover:bg-gray-100" type="button" @click="params.taxa = Math.max(0, params.taxa - 0.25); updateCalculations()">-</button>
                          <input type="number" class="tw-input !rounded-none text-center" id="taxaMensal" name="taxaMensal" step="0.25" min="0" x-model.number="params.taxa" @input.debounce.500ms="updateCalculations()" required>
                          <button class="px-3 py-2 border border-gray-300 bg-gray-50 text-gray-500 hover:bg-gray-100" type="button" @click="params.taxa += 0.25; updateCalculations()">+</button>
                          <button class="px-3 py-2 border border-blue-300 rounded-r-md bg-blue-600 text-white hover:bg-blue-700 flex items-center gap-1" type="button" title="Descobrir taxa a partir de um valor líquido alvo" data-bs-toggle="modal" data-bs-target="#descobrirTaxaModal">
                              <i class="bi bi-calculator"></i> Alvo
                          </button>
                      </div>
                  </div>
                  <div class="col-md-3">
                      <label for="data_operacao" class="tw-label">Data Base de Cálculo:</label>
                      <input type="date" class="tw-input" id="data_operacao" name="data_operacao" x-model="params.dataOperacao" @change="updateCalculations()" required>
                  </div>
              </div>
              <div class="row g-3 mt-1 align-items-end">
                  <div class="col-md-3">
                      <label for="tipoPagamento" class="tw-label">Tipo de Pagamento</label>
                      <select id="tipoPagamento" name="tipoPagamento" class="tw-input" x-model="params.tipoPagamento" @change="updateCalculations()" required>
                          <option value="direto">Pagamento Direto (Devedor Notificado)</option>
                          <option value="escrow">Pagamento via Conta Escrow</option>
                          <option value="indireto">Pagamento Indireto (Repasse via Cedente)</option>
                      </select>
                  </div>
                  <div class="col-md-3">
                      <label for="incorreIOF" class="tw-label">Você Incorre Custo IOF?</label>
                      <select id="incorreIOF" name="incorreIOF" class="tw-input" x-model="params.incorreIOF" @change="updateCalculations()">
                          <option value="Sim">Sim</option>
                          <option value="Nao">Não</option>
                      </select>
                  </div>
                  <div class="col-md-3" x-show="params.incorreIOF === 'Nao'" x-transition>
                      <label for="cobrarIOF" class="tw-label">Cobrar IOF do Cliente?</label>
                      <select id="cobrarIOF" name="cobrarIOF" class="tw-input" x-model="params.cobrarIOF" @change="updateCalculations()">
                          <option value="Sim">Sim (Taxa Extra)</option>
                          <option value="Nao">Não</option>
                      </select>
                  </div>
                  <div class="col-md-3">
                      <label for="notas" class="tw-label">Anotações da Operação</label>
                      <textarea class="tw-input" id="notas" name="notas" rows="1" x-model="params.notas" placeholder="Detalhes..."></textarea>
                  </div>
              </div>
          </fieldset>

          <fieldset class="border p-3 rounded mb-4">
            <legend class="float-none w-auto px-3 h6">Títulos a Descontar</legend>
            <div class="table-responsive">
                <table class="table" id="titulosTable">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">Valor Original (R$)</th>
                            <th scope="col">Data Vencimento</th>
                            <th scope="col">Sacado (Devedor)</th>
                            <th scope="col">Tipo Recebível</th>
                            <th scope="col" class="text-end">Valor Líquido Pago (R$)</th>
                            <th scope="col" class="text-center" style="width: 80px;">Dias Rest.</th>
                            <th scope="col" style="width: 50px;">Ação</th>
                        </tr>
                    </thead>
                    <tbody id="titulosBody">
                        <tr>
                            <td><input type="number" name="titulo_valor[]" class="form-control valor-original" step="0.01" min="0.01" placeholder="1000.00" required></td>
                            <td><input type="date" name="titulo_data[]" class="form-control data-vencimento" required></td>
                            <td>
                                <select name="titulo_sacado[]" class="form-select sacado-select">
                                    <option value="">-- Selecione Sacado --</option>
                                    <?php foreach ($sacados as $sacado): ?>
                                        <option value="<?php echo htmlspecialchars($sacado['id']); ?>"><?php echo htmlspecialchars($sacado['nome']); ?></option>
                                    <?php endforeach; ?>
                                    <?php if (empty($sacados) && $erro_sacados): ?>
                                        <option value="" disabled><?php echo htmlspecialchars($erro_sacados); ?></option>
                                    <?php elseif (empty($sacados)): ?>
                                        <option value="" disabled>Nenhum sacado cadastrado</option>
                                    <?php endif; ?>
                                </select>
                            </td>
                            <td>
                                <select name="titulo_tipo[]" class="form-select tipo-recebivel-select">
                                    <option value="duplicata" selected>Duplicata</option>
                                    <option value="cheque">Cheque</option>
                                    <option value="nota_promissoria">Nota Promissória</option>
                                    <option value="boleto">Boleto</option>
                                    <option value="fatura">Fatura</option>
                                    <option value="nota_fiscal">Nota Fiscal</option>
                                    <option value="outros">Outros</option>
                                </select>
                            </td>
                            <td class="valor-pago-cell">--</td>
                            <td class="dias-restantes-cell"></td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <button type="button" class="btn btn-outline-success btn-sm mt-2" id="addTituloBtn"><i class="bi bi-plus-circle"></i> Adicionar Título</button>
            
            <!-- Botão de Encontro de Contas -->
            <div class="mt-3">
                <button type="button" class="btn btn-sm" id="encontroContasBtn" style="display: none; background-color: #d2691e; color: white; border: 1px solid #b8621a;">
                    <i class="bi bi-arrow-left-right"></i> Listar Recebíveis Indiretos
                </button>
                <div id="compensacaoInfo" class="mt-2" style="display: none;">
                    <div class="alert alert-warning d-flex justify-content-between align-items-start">
                        <div>
                            <small>ID do Recebível: <span id="compensacaoRecebiveis"></span></small><br>
                            <small>Valor Original: <span id="compensacaoValorOriginal">R$ 0,00</span></small><br>
                            <strong>Compensação Aplicada: <span id="compensacaoValor">R$ 0,00</span></strong><br>
                            <small>Saldo Remanescente: <span id="compensacaoSaldoRemanescente">R$ 0,00</span></small><br>
                            <small>Valor Presente (Antecipado): <span id="compensacaoValorPresente" class="text-info fw-bold">R$ 0,00</span></small><br>
                            <small>Crédito por Antecipação: <span id="compensacaoRemuneracao" class="text-success fw-bold">R$ 0,00</span></small><br>
                            <small>Status: <span id="compensacaoStatus" class="badge bg-warning">Parcialmente Quitado</span></small>
                        </div>
                        <button type="button" class="btn btn-outline-danger btn-sm" id="removerCompensacaoBtn" title="Remover Compensação">
                            <i class="bi bi-x-circle"></i> Remover
                        </button>
                    </div>
                </div>
            </div>
          </fieldset>

          <!-- Seção de Upload de Arquivos -->
          <fieldset class="border p-3 rounded mb-4" id="arquivosSection" style="display: none;">
            <legend class="float-none w-auto px-3 h6">Anexar Documentos</legend>
            <div class="row">
                <div class="col-md-8">
                    <label for="arquivos" class="form-label">Selecionar Arquivos</label>
                    <input type="file" class="form-control" id="arquivos" name="arquivos[]" multiple
                           accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.doc,.docx,.xls,.xlsx,.txt">
                    <div class="form-text">
                        Tipos aceitos: PDF, JPG, PNG, GIF, WebP, DOC, DOCX, XLS, XLSX, TXT<br>
                        Tamanho máximo por arquivo: 10MB | Máximo: 20 arquivos por operação
                    </div>
                </div>
                <div class="col-md-4">
                    <label for="descricaoArquivos" class="form-label">Descrição (opcional)</label>
                    <textarea class="form-control" id="descricaoArquivos" rows="3"
                              placeholder="Descrição dos documentos anexados..."></textarea>
                </div>
            </div>
            <div id="arquivos-preview" class="mt-3" style="display: none;">
                <h6>Arquivos Selecionados:</h6>
                <div id="arquivos-list" class="list-group"></div>
            </div>
          </fieldset>

          <table style="display:none;">
              <tr id="tituloTemplateRow">
                  <td><input type="number" name="titulo_valor[]" class="form-control valor-original" step="0.01" min="0.01" placeholder="1000.00" required disabled></td>
                  <td><input type="date" name="titulo_data[]" class="form-control data-vencimento" required disabled></td>
                  <td>
                      <select name="titulo_sacado[]" class="form-select sacado-select" disabled>
                          <option value="">-- Selecione Sacado --</option>
                          <?php foreach ($sacados as $sacado): ?>
                              <option value="<?php echo htmlspecialchars($sacado['id']); ?>"><?php echo htmlspecialchars($sacado['nome']); ?></option>
                          <?php endforeach; ?>
                          <?php if (empty($sacados) && $erro_sacados): ?>
                              <option value="" disabled><?php echo htmlspecialchars($erro_sacados); ?></option>
                          <?php elseif (empty($sacados)): ?>
                              <option value="" disabled>Nenhum sacado cadastrado</option>
                          <?php endif; ?>
                      </select>
                  </td>
                  <td>
                      <select name="titulo_tipo[]" class="form-select tipo-recebivel-select" disabled>
                          <option value="duplicata" selected>Duplicata</option>
                          <option value="cheque">Cheque</option>
                          <option value="nota_promissoria">Nota Promissória</option>
                          <option value="boleto">Boleto</option>
                          <option value="fatura">Fatura</option>
                          <option value="nota_fiscal">Nota Fiscal</option>
                          <option value="outros">Outros</option>
                      </select>
                  </td>
                  <td class="valor-pago-cell">--</td>
                  <td class="dias-restantes-cell"></td>
                  <td><button type="button" class="btn btn-danger btn-sm remove-row-btn" title="Remover Título" disabled><i class="bi bi-trash"></i></button></td>
              </tr>
          </table>

          <div class="d-flex justify-content-center align-items-center flex-wrap gap-2 mt-4 mb-4">
              <button type="button" id="calculateBtn" class="btn btn-primary">Calcular Totais</button>
              <button type="button" id="exportPdfBtn" class="btn btn-secondary" disabled><i class="bi bi-file-earmark-pdf"></i> PDF Análise Completa</button>
              <button type="button" id="exportPdfClienteBtn" class="btn btn-outline-secondary" disabled><i class="bi bi-file-earmark-person"></i> PDF Simulação Cliente</button>
              <button type="button" id="registerBtn" class="btn btn-success" disabled><i class="bi bi-check-lg"></i> Registrar Operação</button>
          </div>
          <div id="register-feedback" class="text-center mt-2" style="min-height: 1.5em;"></div>

          <fieldset class="border p-3 rounded mb-4">
              <legend class="float-none w-auto px-3 h6">Resultados Totais da Operação</legend>
              <div id="error-message" class="alert alert-danger d-none" role="alert"></div>
              
              <!-- Primeira linha: Dados básicos da operação -->
              <div class="row g-2 justify-content-center result-row-1 mb-3">
                  <div class="col-auto col-md"><div class="resultado-total-item bg-light border rounded"><span class="label">Média Dias</span><span class="value" id="resMediaDias">--</span></div></div>
                  <div class="col-auto col-md"><div class="resultado-total-item bg-light border rounded"><span class="label">Total Original</span><span class="value" id="resTotalOriginal">--</span></div></div>
                  <div class="col-auto col-md"><div class="resultado-total-item bg-light border rounded"><span class="label">Total Vl. Presente</span><span class="value" id="resTotalPresente">--</span></div></div>
                  <div class="col-auto col-md"><div class="resultado-total-item bg-light border rounded"><span class="label">Total IOF Calc.</span><span class="value" id="resTotalIOF">--</span></div></div>
              </div>
              
              <!-- Segunda linha: Compensação e Antecipação -->
              <div class="row g-2 justify-content-center result-row-2 mb-3" id="compensacaoRow" style="display: none;">
                  <div class="col-auto col-md"><div class="resultado-total-item bg-warning border rounded"><span class="label">Antecipação</span><span class="value" id="resAntecipacao">--</span></div></div>
                  <div class="col-auto col-md"><div class="resultado-total-item bg-success text-white border rounded"><span class="label">Crédito por Antecipação</span><span class="value" id="resCreditoAntecipacao">--</span></div></div>
              </div>
              
              <!-- Terceira linha: Total Líquido Pago -->
              <div class="row g-2 justify-content-center result-row-3 mb-3">
                  <div class="col-auto col-md-6"><div class="resultado-total-item bg-primary text-white border rounded"><span class="label">Total Líquido Pago</span><span class="value" id="resTotalLiquido">--</span></div></div>
              </div>
              
              <!-- Quarta linha: Resultados financeiros -->
              <div class="row g-3 justify-content-center result-row-4">
                  <div class="col-md-4"><div class="resultado-total-item bg-light border rounded"><span class="label">Total Lucro Líquido</span><span class="value" id="resTotalLucro">--</span></div></div>
                  <div class="col-md-4"><div class="resultado-total-item bg-light border rounded"><span class="label">Margem Total (%)</span><span class="value" id="resMargemTotal">--</span></div></div>
                  <div class="col-md-4"><div class="resultado-total-item bg-light border rounded"><span class="label">Retorno Mensal (%)</span><span class="value" id="resRetornoMensal">--</span></div></div>
              </div>
          </fieldset>
          <fieldset class="border p-3 rounded mb-5">
              <legend class="float-none w-auto px-3 h6">Fluxo de Caixa (Saída, Retorno e Lucro por Mês)</legend>
              <div class="chart-wrapper"><canvas id="fluxoCaixaChart"></canvas></div>
          </fieldset>
          <input type="hidden" name="chartImageData" id="chartImageData">
          <input type="hidden" name="compensacao_data" id="compensacaoData">
      </form>
  </div>

  <!-- Modal Descobrir Taxa -->
  <div class="modal fade" id="descobrirTaxaModal" tabindex="-1" aria-labelledby="descobrirTaxaModalLabel" aria-hidden="true">
      <div class="modal-dialog">
          <div class="modal-content">
              <div class="modal-header">
                  <h5 class="modal-title" id="descobrirTaxaModalLabel">Descobrir Taxa por Valor Líquido</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                  <p>Insira o valor líquido total que você deseja pagar ao cliente para que o sistema calcule a taxa de desconto correspondente.</p>
                  <div class="mb-3">
                      <label for="valorAlvoInput" class="form-label">Valor Líquido Desejado (R$)</label>
                      <input type="text" class="form-control" id="valorAlvoInput" placeholder="Ex: R$ 22.000,00" data-mask="currency">
                  </div>
                  <div id="descobrirTaxaError" class="alert alert-danger d-none"></div>
              </div>
              <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                  <button type="button" class="btn btn-primary" id="calcularTaxaAlvoBtn">Calcular Taxa</button>
              </div>
          </div>
      </div>
  </div>

  <!-- Modal de Encontro de Contas -->
  <div class="modal fade" id="encontroContasModal" tabindex="-1" aria-labelledby="encontroContasModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
          <div class="modal-content">
              <div class="modal-header">
                  <h5 class="modal-title" id="encontroContasModalLabel">Encontro de Contas - Recebíveis Indiretos</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                  <!-- Card 1: Parâmetros de Cálculo -->
                  <div class="card mb-4">
                      <div class="card-header bg-primary text-white">
                          <h6 class="mb-0"><i class="bi bi-gear"></i> Parâmetros de Cálculo</h6>
                      </div>
                      <div class="card-body">
                          <div class="row">
                              <div class="col-md-3">
                                  <label for="taxaAntecipacao" class="form-label">Taxa de Antecipação (% a.m.)</label>
                                  <input type="number" class="form-control" id="taxaAntecipacao" step="0.25" min="0" value="2.00" placeholder="2.00">
                              </div>
                              <div class="col-md-3">
                                  <label for="valorCustomizadoCompensacao" class="form-label">Valor Customizado (R$)</label>
                                  <input type="text" class="form-control" id="valorCustomizadoCompensacao" placeholder="Ex: R$ 50.000,00" data-mask="currency">
                                  <small class="form-text text-muted">Deixe vazio para usar valor total</small>
                              </div>
                              <div class="col-md-3">
                                  <label class="form-label">Data Base de Cálculo</label>
                                  <div class="form-control-plaintext fw-bold text-info" id="dataBaseCalculoModal"><?php echo date('d/m/Y'); ?></div>
                                  <small class="form-text text-muted">Data base para cálculos</small>
                              </div>
                              <div class="col-md-3">
                                  <label class="form-label">Valor Presente (Antecipado)</label>
                                  <div class="form-control-plaintext fw-bold text-success" id="valorPresenteAntecipado">R$ 0,00</div>
                                  <small class="form-text text-muted">Valor que você receberá hoje</small>
                              </div>
                          </div>
                      </div>
                  </div>

                  <!-- Card 2: Recebíveis Disponíveis -->
                  <div class="card mb-4">
                      <div class="card-header bg-info text-white">
                          <h6 class="mb-0"><i class="bi bi-list-check"></i> Recebíveis Disponíveis</h6>
                      </div>
                      <div class="card-body">
                          <div id="recebiveisLoading" class="text-center">
                              <div class="spinner-border" role="status">
                                  <span class="visually-hidden">Carregando...</span>
                              </div>
                          </div>
                          
                          <div id="recebiveisContainer" style="display: none;">
                              <div class="table-responsive">
                                  <table class="table table-striped">
                                      <thead>
                                          <tr>
                                              <th>Selecionar</th>
                                              <th>ID</th>
                                              <th>Valor Nominal</th>
                                              <th>Data Vencimento</th>
                                              <th>Dias para Venc.</th>
                                              <th>Valor Presente</th>
                                          </tr>
                                      </thead>
                                      <tbody id="recebiveisTableBody">
                                          <!-- Recebíveis serão carregados aqui -->
                                      </tbody>
                                  </table>
                              </div>
                          </div>
                          
                          <div id="recebiveisError" class="alert alert-danger" style="display: none;"></div>
                      </div>
                  </div>

                  <!-- Card 3: Totais da Compensação -->
                  <div class="card mb-3">
                      <div class="card-header bg-success text-white">
                          <h6 class="mb-0"><i class="bi bi-calculator"></i> Totais da Compensação</h6>
                      </div>
                      <div class="card-body">
                          <div class="row">
                              <div class="col-md-6">
                                  <label class="form-label">Total da Compensação</label>
                                  <div class="form-control-plaintext fw-bold text-primary fs-5" id="valorTotalCompensacao">R$ 0,00</div>
                                  <small class="form-text text-muted">Valor nominal do recebível</small>
                              </div>
                              <div class="col-md-6">
                                  <label class="form-label">Crédito por Antecipação</label>
                                  <div class="form-control-plaintext fw-bold text-warning fs-5" id="creditoAntecipacaoModal">R$ 0,00</div>
                                  <small class="form-text text-muted">Custo da antecipação</small>
                              </div>
                          </div>
                      </div>
                  </div>

                  <div id="validationError" class="alert alert-warning" style="display: none;">
                      O valor da compensação excede o valor líquido da nova operação. Não é possível prosseguir.
                  </div>
              </div>
              <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                  <button type="button" class="btn btn-primary" id="aprovarCompensacaoBtn" disabled>Aprovar Compensação</button>
              </div>
          </div>
      </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
      // --- Funções para formatar moeda em JavaScript (MOVIDA PARA CIMA) ---
      function formatCurrencyJS(value) {
          if (typeof value === 'number') {
              return 'R$ ' + value.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
          }
          return '--';
      }

      // --- DOM Elements ---
      const form = document.getElementById('calculationForm');
      const cedenteSelect = document.getElementById('cedente');
      const tipoPagamentoSelect = document.getElementById('tipoPagamento');
      const taxaMensalInput = document.getElementById('taxaMensal');
      const taxaDecrementBtn = document.getElementById('taxaDecrementBtn');
      const taxaIncrementBtn = document.getElementById('taxaIncrementBtn');
      const dataOperacaoInput = document.getElementById('data_operacao');
      const incorreIOFSelect = document.getElementById('incorreIOF');
      const cobrarIOFSelect = document.getElementById('cobrarIOF');
      const cobrarIofWrapper = document.querySelector('.cobrar-iof-wrapper');
      const notasInput = document.getElementById('notas');
      const titulosBody = document.getElementById('titulosBody');
      const addTituloBtn = document.getElementById('addTituloBtn');
      const tituloTemplateRow = document.getElementById('tituloTemplateRow');
      const calculateBtn = document.getElementById('calculateBtn');
      const registerBtn = document.getElementById('registerBtn');
      const exportPdfBtn = document.getElementById('exportPdfBtn');
      const resMediaDias = document.getElementById('resMediaDias');
      const resTotalOriginal = document.getElementById('resTotalOriginal');
      const resTotalPresente = document.getElementById('resTotalPresente');
      const resTotalIOF = document.getElementById('resTotalIOF');
      const resTotalLiquido = document.getElementById('resTotalLiquido');
      const resTotalLucro = document.getElementById('resTotalLucro');
      const resMargemTotal = document.getElementById('resMargemTotal');
      const resRetornoMensal = document.getElementById('resRetornoMensal');
      const resAntecipacao = document.getElementById('resAntecipacao');
      const resCreditoAntecipacao = document.getElementById('resCreditoAntecipacao');
      const compensacaoRow = document.getElementById('compensacaoRow');
      const errorMessageDiv = document.getElementById('error-message');
      const chartCanvas = document.getElementById('fluxoCaixaChart');
      const chartImageDataInput = document.getElementById('chartImageData');
      const registerFeedback = document.getElementById('register-feedback');
      const exportPdfClienteBtn = document.getElementById('exportPdfClienteBtn');

      let myFluxoChart = null; let calculationResults = null; let lastInputDataForRegister = null;

      // --- Elementos do Encontro de Contas ---
      const encontroContasBtn = document.getElementById('encontroContasBtn');
      let encontroContasModal; // Será inicializado no DOMContentLoaded
      const taxaAntecipacaoInput = document.getElementById('taxaAntecipacao');
      const valorCustomizadoInput = document.getElementById('valorCustomizadoCompensacao');
      const valorTotalCompensacaoSpan = document.getElementById('valorTotalCompensacao');
      const valorPresenteAntecipado = document.getElementById('valorPresenteAntecipado');
      const recebiveisLoading = document.getElementById('recebiveisLoading');
      const recebiveisContainer = document.getElementById('recebiveisContainer');
      const recebiveisTableBody = document.getElementById('recebiveisTableBody');
      const recebiveisError = document.getElementById('recebiveisError');
      const validationError = document.getElementById('validationError');
      const aprovarCompensacaoBtn = document.getElementById('aprovarCompensacaoBtn');
      const compensacaoInfo = document.getElementById('compensacaoInfo');
      const compensacaoValor = document.getElementById('compensacaoValor');
      const compensacaoRecebiveis = document.getElementById('compensacaoRecebiveis');
      const compensacaoValorOriginal = document.getElementById('compensacaoValorOriginal');
      const compensacaoSaldoRemanescente = document.getElementById('compensacaoSaldoRemanescente');
      const compensacaoValorPresente = document.getElementById('compensacaoValorPresente');
      const compensacaoRemuneracao = document.getElementById('compensacaoRemuneracao');
      const compensacaoStatus = document.getElementById('compensacaoStatus');
      const removerCompensacaoBtn = document.getElementById('removerCompensacaoBtn');
      const compensacaoDataInput = document.getElementById('compensacaoData');

      let recebiveisDisponiveis = [];
      let compensacaoAtiva = null;

      // --- Funções Auxiliares ---
      const taxaStep=0.25; taxaDecrementBtn.addEventListener('click',()=>{let v=parseFloat(taxaMensalInput.value)||0;taxaMensalInput.value=Math.max(0,v-taxaStep).toFixed(2);clearResultsAndRegister();}); taxaIncrementBtn.addEventListener('click',()=>{let v=parseFloat(taxaMensalInput.value)||0;taxaMensalInput.value=(v+taxaStep).toFixed(2);clearResultsAndRegister();});
      addTituloBtn.addEventListener('click',()=>{const nR=tituloTemplateRow.cloneNode(true);nR.removeAttribute('id');nR.style.display='';nR.querySelectorAll('input,button,select').forEach(e=>e.disabled=false);nR.querySelectorAll('input').forEach(i=>i.value='');nR.querySelectorAll('select').forEach(s=>s.selectedIndex=0);const nDI=nR.querySelector('.data-vencimento');if(nDI)setFutureDate(nDI,30);const rB=nR.querySelector('.remove-row-btn');if(rB){rB.addEventListener('click',function(){this.closest('tr').remove();clearResultsAndRegister();})}addInputListeners(nR);titulosBody.appendChild(nR);clearResultsAndRegister();});
      function addInputListeners(rE){rE.querySelectorAll('input.valor-original,input.data-vencimento').forEach(i=>{i.addEventListener('change',clearResultsAndRegister);i.addEventListener('input',clearResultsAndRegister);});} addInputListeners(titulosBody.querySelector('tr'));
      function setFutureDate(iE,dA){if(!iE)return;try{const bDS=dataOperacaoInput.value;let bD=bDS?new Date(bDS+'T00:00:00'):new Date();if(isNaN(bD.getTime())){bD=new Date();}bD.setUTCHours(0,0,0,0);const fTS=bD.getTime()+dA*24*60*60*1000;const fD=new Date(fTS);iE.value=fD.toISOString().split('T')[0];}catch(e){console.error("Erro setFutureDate.",e);}} const fDI=titulosBody.querySelector('.data-vencimento');if(fDI&&!fDI.value){setFutureDate(fDI,30);}
      [taxaMensalInput,dataOperacaoInput,incorreIOFSelect,cobrarIOFSelect,tipoPagamentoSelect].forEach(el=>{el.addEventListener('change',clearResultsAndRegister);if(el.tagName==='INPUT'&&el.type==='number'){el.addEventListener('input',clearResultsAndRegister);}});
      cedenteSelect.addEventListener('change',()=>{registerFeedback.textContent='';registerFeedback.className='mt-2';cedenteSelect.classList.remove('is-invalid');checkEncontroContasVisibility();});
      function toggleCobrarIofVisibility(){if(incorreIOFSelect.value==='Nao'){cobrarIofWrapper.classList.remove('d-none');cobrarIOFSelect.disabled=false;}else{cobrarIofWrapper.classList.add('d-none');}} incorreIOFSelect.addEventListener('change',toggleCobrarIofVisibility);document.addEventListener('DOMContentLoaded',toggleCobrarIofVisibility);

      // --- Funções do Encontro de Contas ---
      async function checkEncontroContasVisibility() {
          if (!cedenteSelect.value) {
              encontroContasBtn.style.display = 'none';
              return;
          }
          
          try {
              const response = await fetch('buscar_recebiveis_indiretos.php', {
                  method: 'POST',
                  headers: {'Content-Type': 'application/json'},
                  body: JSON.stringify({cedente_id: cedenteSelect.value, check_only: true})
              });
              const result = await response.json();
              
              if (result.success && result.has_recebiveis) {
                  encontroContasBtn.style.display = 'inline-block';
              } else {
                  encontroContasBtn.style.display = 'none';
              }
          } catch (error) {
              console.error('Erro ao verificar recebíveis:', error);
              encontroContasBtn.style.display = 'none';
          }
      }

      async function carregarRecebiveisIndiretos() {
          if (!cedenteSelect.value) return;
          
          recebiveisLoading.style.display = 'block';
          recebiveisContainer.style.display = 'none';
          recebiveisError.style.display = 'none';
          
          try {
              const response = await fetch('buscar_recebiveis_indiretos.php', {
                  method: 'POST',
                  headers: {'Content-Type': 'application/json'},
                  body: JSON.stringify({cedente_id: cedenteSelect.value})
              });
              const result = await response.json();
              
              if (result.success) {
                  recebiveisDisponiveis = result.recebiveis;
                  renderizarRecebiveisTabela();
                  recebiveisContainer.style.display = 'block';
              } else {
                  recebiveisError.textContent = result.error || 'Erro ao carregar recebíveis';
                  recebiveisError.style.display = 'block';
              }
          } catch (error) {
              console.error('Erro ao carregar recebíveis:', error);
              recebiveisError.textContent = 'Erro de comunicação com o servidor';
              recebiveisError.style.display = 'block';
          } finally {
              recebiveisLoading.style.display = 'none';
          }
      }

      function renderizarRecebiveisTabela() {
          recebiveisTableBody.innerHTML = '';
          
          recebiveisDisponiveis.forEach(recebivel => {
              const row = document.createElement('tr');
              const saldoDisponivel = recebivel.saldo_disponivel || recebivel.valor_original;
              const valorPresente = calcularValorPresente(saldoDisponivel, recebivel.dias_para_vencimento);
              
              row.innerHTML = `
                  <td>
                      <input type="checkbox" class="form-check-input recebivel-checkbox"
                             data-id="${recebivel.id}" data-valor="${valorPresente}" data-valor-original="${saldoDisponivel}" data-dias="${recebivel.dias_para_vencimento}">
                  </td>
                  <td>${recebivel.id}</td>
                  <td>${formatCurrencyJS(parseFloat(saldoDisponivel))}</td>
                  <td>${new Date(recebivel.data_vencimento).toLocaleDateString('pt-BR')}</td>
                  <td>${recebivel.dias_para_vencimento}</td>
                  <td class="valor-presente">${formatCurrencyJS(valorPresente)}</td>
              `;
              
              recebiveisTableBody.appendChild(row);
          });
          
          // Adicionar event listeners aos checkboxes
          document.querySelectorAll('.recebivel-checkbox').forEach(checkbox => {
              checkbox.addEventListener('change', atualizarValorTotalCompensacao);
          });
      }

      function calcularValorPresente(valorOriginal, dias) {
          const taxa = parseFloat(taxaAntecipacaoInput.value) / 100;
          // Corrigido: usar a mesma fórmula do calculate.php
          // Valor presente = Valor / (1 + taxa)^(dias/30)
          const fatorDesconto = Math.pow(1 + taxa, dias / 30);
          return valorOriginal / fatorDesconto;
      }

      function atualizarValorTotalCompensacao() {
          let total = 0;
          let valorPresente = 0;
          const checkboxes = document.querySelectorAll('.recebivel-checkbox:checked');
          
          // Verificar se há valor customizado
          const valorCustomizado = parseValorMonetario(valorCustomizadoInput.value);
          const taxaAntecipacao = parseFloat(taxaAntecipacaoInput.value) / 100 || 0;
          
          if (valorCustomizado > 0 && checkboxes.length === 1) {
              // Usar valor customizado se especificado e apenas um recebível selecionado
              const checkbox = checkboxes[0];
              const valorOriginalRecebivel = parseFloat(checkbox.dataset.valorOriginal);
              
              // Validar se valor customizado não excede o valor original do recebível
              if (valorCustomizado > valorOriginalRecebivel) {
                  validationError.textContent = `O valor customizado (${formatCurrencyJS(valorCustomizado)}) não pode exceder o valor original do recebível (${formatCurrencyJS(valorOriginalRecebivel)}).`;
                  validationError.style.display = 'block';
                  aprovarCompensacaoBtn.disabled = true;
                  return;
              }
              
              total = valorCustomizado;
              // Calcular valor presente do valor customizado considerando os dias para vencimento
              const diasVencimento = parseInt(checkbox.dataset.dias);
              const fatorDesconto = Math.pow(1 + taxaAntecipacao, diasVencimento / 30);
              valorPresente = valorCustomizado / fatorDesconto;
          } else {
              // Usar valor total dos recebíveis selecionados
              checkboxes.forEach(checkbox => {
                  const valorOriginal = parseFloat(checkbox.dataset.valorOriginal);
                  const dias = parseInt(checkbox.dataset.dias);
                  const valorPresenteCalculado = calcularValorPresente(valorOriginal, dias);
                  total += valorOriginal;
                  valorPresente += valorPresenteCalculado;
              });
          }
          
          const creditoAntecipacao = total - valorPresente;
          
          valorTotalCompensacaoSpan.textContent = formatCurrencyJS(total);
          valorPresenteAntecipado.textContent = formatCurrencyJS(valorPresente);
          document.getElementById('creditoAntecipacaoModal').textContent = formatCurrencyJS(creditoAntecipacao);
          
          // Permitir compensação parcial - validar apenas se há recebíveis selecionados
          validationError.style.display = 'none';
          aprovarCompensacaoBtn.disabled = checkboxes.length === 0;
      }

      function aplicarCompensacao() {
          const checkboxes = document.querySelectorAll('.recebivel-checkbox:checked');
          const recebiveisSelecionados = [];
          let valorTotal = 0;
          
          // Verificar se há valor customizado
          const valorCustomizado = parseValorMonetario(valorCustomizadoInput.value);
          
          checkboxes.forEach(checkbox => {
              const id = checkbox.dataset.id;
              const valorOriginalRecebivel = parseFloat(checkbox.closest('tr').children[2].textContent.replace(/[^\d,]/g, '').replace(',', '.'));
              const diasVencimento = parseInt(checkbox.closest('tr').children[4].textContent);
              let valorCompensacao;
              
              if (valorCustomizado > 0 && checkboxes.length === 1) {
                  valorCompensacao = valorCustomizado;
              } else {
                  // Usar valor original do recebível para compensação, não o valor presente
                  valorCompensacao = valorOriginalRecebivel;
              }
              
              recebiveisSelecionados.push({
                  id, 
                  valor: valorCompensacao,
                  valor_original: valorOriginalRecebivel,
                  dias_vencimento: diasVencimento,
                  valor_customizado: valorCustomizado > 0 && checkboxes.length === 1
              });
              valorTotal += valorCompensacao;
          });
          
          compensacaoAtiva = {
              recebiveis: recebiveisSelecionados,
              valor_total: valorTotal,
              taxa_antecipacao: parseFloat(taxaAntecipacaoInput.value)
          };
          
          // Atualizar interface com detalhes
          compensacaoValor.textContent = formatCurrencyJS(valorTotal);
          
          // Para múltiplos recebíveis, mostrar lista de IDs
          if (recebiveisSelecionados.length === 1) {
              const primeiroRecebivel = recebiveisSelecionados[0];
              compensacaoRecebiveis.textContent = `#${primeiroRecebivel.id}`;
              compensacaoValorOriginal.textContent = formatCurrencyJS(primeiroRecebivel.valor_original);
              
              const saldoRemanescente = primeiroRecebivel.valor_original - valorTotal;
              compensacaoSaldoRemanescente.textContent = formatCurrencyJS(saldoRemanescente);
              
              // Calcular valor presente e crédito do cliente (custo da antecipação)
              const valorPresenteCompensacao = calcularValorPresente(valorTotal, primeiroRecebivel.dias_vencimento);
              const creditoCliente = valorTotal - valorPresenteCompensacao;
              
              // Preencher os novos campos
              compensacaoValorPresente.textContent = formatCurrencyJS(valorPresenteCompensacao);
              compensacaoRemuneracao.textContent = formatCurrencyJS(creditoCliente);
              
              // Determinar status
              if (saldoRemanescente > 0.01) {
                  compensacaoStatus.textContent = 'Parcialmente Quitado';
                  compensacaoStatus.className = 'badge bg-warning';
              } else {
                  compensacaoStatus.textContent = 'Totalmente Quitado';
                  compensacaoStatus.className = 'badge bg-success';
              }
          } else {
              // Múltiplos recebíveis selecionados
              const idsRecebíveis = recebiveisSelecionados.map(r => `#${r.id}`).join(', ');
              compensacaoRecebiveis.textContent = idsRecebíveis;
              
              // Calcular valor original total de todos os recebíveis
              const valorOriginalTotal = recebiveisSelecionados.reduce((sum, r) => sum + r.valor_original, 0);
              compensacaoValorOriginal.textContent = formatCurrencyJS(valorOriginalTotal);
              
              // Para múltiplos títulos, o saldo remanescente deve ser zero se todos forem totalmente compensados
              // ou a soma dos saldos individuais se houver compensação parcial
              let saldoRemanescente = 0;
              let todosQuitados = true;
              
              recebiveisSelecionados.forEach(recebivel => {
                  const saldoIndividual = recebivel.valor_original - recebivel.valor;
                  if (saldoIndividual > 0.01) {
                      saldoRemanescente += saldoIndividual;
                      todosQuitados = false;
                  }
              });
              
              compensacaoSaldoRemanescente.textContent = formatCurrencyJS(saldoRemanescente);
              
              // Calcular valor presente médio ponderado
              let valorPresenteTotal = 0;
              recebiveisSelecionados.forEach(recebivel => {
                  const valorPresenteIndividual = calcularValorPresente(recebivel.valor, recebivel.dias_vencimento);
                  valorPresenteTotal += valorPresenteIndividual;
              });
              
              const creditoCliente = valorTotal - valorPresenteTotal;
              
              // Preencher os novos campos
              compensacaoValorPresente.textContent = formatCurrencyJS(valorPresenteTotal);
              compensacaoRemuneracao.textContent = formatCurrencyJS(creditoCliente);
              
              // Determinar status para múltiplos títulos
              if (todosQuitados) {
                  compensacaoStatus.textContent = 'Totalmente Quitados';
                  compensacaoStatus.className = 'badge bg-success';
              } else {
                  compensacaoStatus.textContent = 'Parcialmente Quitados';
                  compensacaoStatus.className = 'badge bg-warning';
              }
          }
          
          compensacaoInfo.style.display = 'block';
          
          // Salvar dados para envio
          compensacaoDataInput.value = JSON.stringify(compensacaoAtiva);
          
          // Fechar modal
          encontroContasModal.hide();
          
          // Recalcular totais se necessário
          if (calculationResults) {
              updateCalculations();
          }
      }

      // Event Listeners do Encontro de Contas
      encontroContasBtn.addEventListener('click', () => {
          // Atualizar data base de cálculo no modal
          const dataBaseCalculo = document.getElementById('data_operacao').value;
          if (dataBaseCalculo) {
              const dataFormatada = new Date(dataBaseCalculo + 'T00:00:00').toLocaleDateString('pt-BR');
              document.getElementById('dataBaseCalculoModal').textContent = dataFormatada;
          }
          
          carregarRecebiveisIndiretos();
          encontroContasModal.show();
      });

      taxaAntecipacaoInput.addEventListener('input', () => {
          if (recebiveisDisponiveis.length > 0) {
              // Store currently checked checkboxes before re-rendering
              const checkedBefore = new Set();
              document.querySelectorAll('.recebivel-checkbox:checked').forEach(cb => {
                  checkedBefore.add(cb.dataset.id);
              });
              
              renderizarRecebiveisTabela();
              
              // Restore checked state after re-rendering
              document.querySelectorAll('.recebivel-checkbox').forEach(cb => {
                  if (checkedBefore.has(cb.dataset.id)) {
                      cb.checked = true;
                  }
              });
              
              atualizarValorTotalCompensacao();
          }
      });

      aprovarCompensacaoBtn.addEventListener('click', aplicarCompensacao);

      // Event listener para remoção de compensação
      removerCompensacaoBtn.addEventListener('click', function() {
          if (confirm('Tem certeza que deseja remover a compensação aplicada?')) {
              removerCompensacao();
          }
      });

      // Event listener para valor customizado
      valorCustomizadoInput.addEventListener('input', function() {
          aplicarMascaraMonetaria(this);
          atualizarValorTotalCompensacao();
      });

      // Verificar visibilidade do botão ao carregar a página
      document.addEventListener('DOMContentLoaded', checkEncontroContasVisibility);

      // Função para formatar valores em moeda
      function formatCurrencyJS(value) {
          return new Intl.NumberFormat('pt-BR', {
              style: 'currency',
              currency: 'BRL'
          }).format(value);
      }

      function aplicarMascaraMonetaria(input) {
          let valor = input.value.replace(/\D/g, '');
          valor = (valor / 100).toFixed(2) + '';
          valor = valor.replace('.', ',');
          valor = valor.replace(/(\d)(\d{3})(\d{3}),/g, '$1.$2.$3,');
          valor = valor.replace(/(\d)(\d{3}),/g, '$1.$2,');
          input.value = 'R$ ' + valor;
      }

      function parseValorMonetario(valor) {
          if (!valor) return 0;
          return parseFloat(valor.replace(/[^\d,]/g, '').replace(',', '.')) || 0;
      }

      function removerCompensacao() {
          compensacaoAtiva = null;
          compensacaoInfo.style.display = 'none';
          compensacaoDataInput.value = '';
          
          // Limpar campos do modal
          valorCustomizadoInput.value = '';
          
          // Recalcular totais se necessário
          if (calculationResults) {
              updateCalculations();
          }
      }

      // Inicializar modal do Bootstrap
      let descobrirTaxaModal;
      document.addEventListener('DOMContentLoaded', function() {
          encontroContasModal = new bootstrap.Modal(document.getElementById('encontroContasModal'));
          if (document.getElementById('descobrirTaxaModal')) {
              descobrirTaxaModal = new bootstrap.Modal(document.getElementById('descobrirTaxaModal'));
          }
      });

      // --- Descobrir Taxa por Valor Alvo ---
      const valorAlvoInput = document.getElementById('valorAlvoInput');
      const calcularTaxaAlvoBtn = document.getElementById('calcularTaxaAlvoBtn');
      const descobrirTaxaError = document.getElementById('descobrirTaxaError');

      if (valorAlvoInput) {
          valorAlvoInput.addEventListener('input', function() {
              aplicarMascaraMonetaria(this);
          });
      }

      if (calcularTaxaAlvoBtn) {
          calcularTaxaAlvoBtn.addEventListener('click', async () => {
              descobrirTaxaError.classList.add('d-none');
              const valorAlvo = parseValorMonetario(valorAlvoInput.value);
              
              if (valorAlvo <= 0) {
                  descobrirTaxaError.textContent = 'Insira um valor válido maior que zero.';
                  descobrirTaxaError.classList.remove('d-none');
                  return;
              }

              // Coletar dados da operação
              const titulos = [];
              let totalOriginal = 0;
              titulosBody.querySelectorAll('tr').forEach((row) => {
                  const vI = row.querySelector('.valor-original:not([disabled])');
                  const dI = row.querySelector('.data-vencimento:not([disabled])');
                  if (vI && vI.value && dI && dI.value) {
                      const v = parseFloat(vI.value);
                      titulos.push({ valorOriginal: v, dataVencimento: dI.value });
                      totalOriginal += v;
                  }
              });

              if (titulos.length === 0) {
                  descobrirTaxaError.textContent = 'Adicione ao menos um título com valor e data de vencimento preenchidos antes de calcular a taxa.';
                  descobrirTaxaError.classList.remove('d-none');
                  return;
              }

              const data = {
                  valorAlvo: valorAlvo,
                  data_operacao: dataOperacaoInput.value,
                  cobrarIOF: cobrarIOFSelect.value,
                  titulos: titulos
              };

              if (compensacaoAtiva && compensacaoAtiva.recebiveis && compensacaoAtiva.recebiveis.length > 0) {
                  data.compensacao_data = {
                      recebiveis: compensacaoAtiva.recebiveis,
                      valor_total: compensacaoAtiva.valor_total,
                      taxa_antecipacao: compensacaoAtiva.taxa_antecipacao
                  };
              }

              calcularTaxaAlvoBtn.disabled = true;
              calcularTaxaAlvoBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Calculando...';

              try {
                  const response = await fetch('descobrir_taxa.php', {
                      method: 'POST',
                      headers: { 'Content-Type': 'application/json' },
                      body: JSON.stringify(data)
                  });
                  
                  const result = await response.json();
                  
                  if (result.success) {
                      // Atualizar o input de taxa
                      taxaMensalInput.value = result.taxaMensal.toFixed(4); // Precisão extra
                      
                      // Fechar o modal
                      descobrirTaxaModal.hide();
                      
                      // Limpar o input do modal
                      valorAlvoInput.value = '';
                      
                      // Executar o cálculo principal para atualizar a tela
                      updateCalculations();
                  } else {
                      descobrirTaxaError.textContent = result.error || 'Erro ao calcular a taxa.';
                      descobrirTaxaError.classList.remove('d-none');
                  }
              } catch (error) {
                  descobrirTaxaError.textContent = 'Erro de comunicação com o servidor: ' + error.message;
                  descobrirTaxaError.classList.remove('d-none');
              } finally {
                  calcularTaxaAlvoBtn.disabled = false;
                  calcularTaxaAlvoBtn.textContent = 'Calcular Taxa';
              }
          });
      }

      // --- Calculate Button Action ---
      calculateBtn.addEventListener('click', updateCalculations);

      // --- Function to Update Calculations via AJAX ---
      async function updateCalculations() {
          errorMessageDiv.classList.add('d-none'); errorMessageDiv.textContent = ''; let valid = true; form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
          [taxaMensalInput, dataOperacaoInput].forEach(el=>{if(!el.value||(el.type==='number'&&parseFloat(el.value)<0)){el.classList.add('is-invalid');valid=false;}});
          const titulos = []; const rows = titulosBody.querySelectorAll('tr');
          if(rows.length===0){errorMessageDiv.textContent='Adicione título.';valid=false;} else {rows.forEach((row,index)=>{const vI=row.querySelector('.valor-original:not([disabled])');const dI=row.querySelector('.data-vencimento:not([disabled])');const sI=row.querySelector('.sacado-select:not([disabled])');const tI=row.querySelector('.tipo-recebivel-select:not([disabled])');let rV=true;if(!vI||!vI.value||parseFloat(vI.value)<=0){if(vI)vI.classList.add('is-invalid');rV=false;valid=false;}else{if(vI)vI.classList.remove('is-invalid');}if(!dI||!dI.value){if(dI)dI.classList.add('is-invalid');rV=false;valid=false;}else{if(dI)dI.classList.remove('is-invalid');}if(dI&&dI.value&&dataOperacaoInput.value&&dI.value<dataOperacaoInput.value){if(dI)dI.classList.add('is-invalid');errorMessageDiv.textContent='Venc. anterior à Data Op.';rV=false;valid=false;}if(vI&&dI&&rV){titulos.push({valorOriginal:parseFloat(vI.value),dataVencimento:dI.value,sacadoId:sI?sI.value||null:null,tipoRecebivel:tI?tI.value||'duplicata':'duplicata'});}});if(valid&&titulos.length===0&&rows.length>0){errorMessageDiv.textContent='Preencha títulos.';valid=false;}}
          if(!valid){if(!errorMessageDiv.textContent){errorMessageDiv.textContent='Verifique campos.';}errorMessageDiv.classList.remove('d-none');registerBtn.disabled=true;exportPdfBtn.disabled=true;return;}
          const data = { cedente_id:cedenteSelect.value,tipo_pagamento:tipoPagamentoSelect.value,taxaMensal:parseFloat(taxaMensalInput.value)||0,data_operacao:dataOperacaoInput.value,incorreIOF:incorreIOFSelect.value,cobrarIOF:cobrarIOFSelect.value,notas:notasInput.value.trim(),titulos:titulos };
          
          // Adicionar dados de compensação se houver
          if (compensacaoAtiva && compensacaoAtiva.recebiveis && compensacaoAtiva.recebiveis.length > 0) {
              data.compensacao_data = {
                  recebiveis: compensacaoAtiva.recebiveis,
                  valor_total: compensacaoAtiva.valor_total,
                  taxa_antecipacao: compensacaoAtiva.taxa_antecipacao
              };
          }
          calculateBtn.disabled = true; calculateBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Calc...'; registerBtn.disabled=true; exportPdfBtn.disabled=true;
          try {
              const response = await fetch('calculate.php', {method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json'},body:JSON.stringify(data)});
              if(!response.ok){let eTxt=`Erro HTTP ${response.status}`;try{eTxt=(await response.json()).error||eTxt;}catch(_){}throw new Error(eTxt);}
              const results = await response.json();

              if(results.error){throw new Error(results.error);}
              else {
                   resMediaDias.textContent=results.mediaPonderadaDias??'--';
                   resTotalOriginal.textContent=results.totalOriginal??'--';
                   resTotalPresente.textContent=results.totalPresente??'--';
                   resTotalIOF.textContent=results.totalIOF??'--';
                   
                   // Verificar se há compensação ativa e atualizar campos correspondentes
                   if (compensacaoAtiva && compensacaoAtiva.recebiveis && compensacaoAtiva.recebiveis.length > 0) {
                       const valorCompensacao = compensacaoAtiva.valor_total;
                       
                       // Calcular valor presente total considerando todos os recebíveis
                       let valorPresenteCompensacaoTotal = 0;
                       compensacaoAtiva.recebiveis.forEach(recebivel => {
                           const valorPresenteIndividual = calcularValorPresente(recebivel.valor, recebivel.dias_vencimento);
                           valorPresenteCompensacaoTotal += valorPresenteIndividual;
                       });
                       
                       const creditoCliente = valorCompensacao - valorPresenteCompensacaoTotal;
                       
                       // Mostrar linha de compensação
                       compensacaoRow.style.display = 'block';
                       resAntecipacao.textContent = formatCurrencyJS(valorCompensacao);
                       resCreditoAntecipacao.textContent = formatCurrencyJS(creditoCliente);
                       
                       // Ajustar Total Líquido Pago
                       const totalLiquidoAjustado = (results.totalLiquidoPagoNumerico || 0) - valorCompensacao + creditoCliente;
                       resTotalLiquido.textContent = formatCurrencyJS(totalLiquidoAjustado);
                   } else {
                       // Esconder linha de compensação
                       compensacaoRow.style.display = 'none';
                       resTotalLiquido.textContent = results.totalLiquidoPago ?? '--';
                   }
                   resTotalLucro.textContent=results.totalLucroLiquido??'--';
                   resMargemTotal.textContent=results.totalLucroPercentual??'--';
                   resRetornoMensal.textContent=results.retornoMensalFormatado??'--';

                   [resTotalLucro,resMargemTotal,resRetornoMensal].forEach(el=>{el.className='value';if(typeof results.isProfit!=='undefined'){el.classList.add(results.isProfit?'profit':'loss');}});

                   const currentRows = titulosBody.querySelectorAll('tr');
                   if (results.calculatedTitlesDetails && Array.isArray(results.calculatedTitlesDetails)) {
                       results.calculatedTitlesDetails.forEach((detail, index) => {
                           if (currentRows[index]) {
                               const diasCell = currentRows[index].querySelector('.dias-restantes-cell');
                               const valorPagoCell = currentRows[index].querySelector('.valor-pago-cell'); // Seleciona a nova célula

                               if(diasCell) diasCell.textContent = detail.dias ?? '-';
                               // PREENCHIMENTO CORRIGIDO DA NOVA CÉLULA:
                               if(valorPagoCell) valorPagoCell.textContent = formatCurrencyJS(detail.valor_liquido_calc_dinamico); // Usa a função para formatar e preencher
                           }
                       });
                       // Limpa células de dias/valor pago para linhas extras se o número de títulos diminuiu
                       for (let i = results.calculatedTitlesDetails.length; i < currentRows.length; i++) {
                           const diasCell = currentRows[i].querySelector('.dias-restantes-cell');
                           const valorPagoCell = currentRows[i].querySelector('.valor-pago-cell');
                           if(diasCell) diasCell.textContent = '';
                           if(valorPagoCell) valorPagoCell.textContent = '--'; // Garante que a nova célula seja limpa
                       }
                   } else {
                       currentRows.forEach(r => {
                           const diasCell = r.querySelector('.dias-restantes-cell');
                           const valorPagoCell = r.querySelector('.valor-pago-cell');
                           if(diasCell) diasCell.textContent = '';
                           if(valorPagoCell) valorPagoCell.textContent = '--';
                       });
                       console.warn("calculatedTitlesDetails ausente/inválido.");
                   }

                   // Chamada para updateChart com os novos arrays para o gráfico
                   // O updateChart agora espera 3 arrays de dados: emprestado, retornado, lucro
                   updateChart(
                       results.chartLabels || [],
                       results.chartDataCapitalEmprestado || [],
                       results.chartDataCapitalRetornado || [],
                       results.chartDataLucro || []
                   );

                   calculationResults=results;
                   lastInputDataForRegister=data;
                   registerBtn.disabled=false;
                   exportPdfBtn.disabled=false;
                   registerFeedback.textContent='';
                   registerFeedback.className='mt-2';
                   if(exportPdfClienteBtn) exportPdfClienteBtn.disabled = false;
              }
          } catch (error) {
              console.error('Erro cálculo:',error);
              errorMessageDiv.textContent=`Erro: ${error.message}.`;
              errorMessageDiv.classList.remove('d-none');
              clearResultsAndRegister();
              exportPdfBtn.disabled=true;
          } finally {
              calculateBtn.disabled=false;
              calculateBtn.textContent='Calcular Totais';
          }
      }

      // --- Funções clearResultsAndRegister e clearResults (com limpeza de dias) ---
      function clearResultsAndRegister(clearRegisterFeedback = true) {
         clearResults();
         calculationResults=null;
         lastInputDataForRegister=null;
         registerBtn.disabled=true;
         exportPdfBtn.disabled=true;
         if(exportPdfClienteBtn) exportPdfClienteBtn.disabled = true;
         if(clearRegisterFeedback){ registerFeedback.textContent=''; registerFeedback.className='mt-2';}
       }
      function clearResults() {
          resMediaDias.textContent='--';
          resTotalOriginal.textContent='--';
          resTotalPresente.textContent='--';
          resTotalIOF.textContent='--';
          resTotalLiquido.textContent='--';
          resTotalLucro.textContent='--';
          resMargemTotal.textContent='--';
          resRetornoMensal.textContent='--';
          resAntecipacao.textContent='--';
          resCreditoAntecipacao.textContent='--';
          compensacaoRow.style.display = 'none';
          [resTotalLucro,resMargemTotal,resRetornoMensal].forEach(el=>el.className='value');
          if(myFluxoChart){myFluxoChart.destroy();myFluxoChart=null;}
          // Removido: errorMessageDiv.classList.add('d-none'); - para não esconder mensagens de erro
          form.querySelectorAll('.is-invalid').forEach(el=>el.classList.remove('is-invalid'));
          titulosBody.querySelectorAll('.dias-restantes-cell').forEach(cell => { cell.textContent = ''; });
          titulosBody.querySelectorAll('.valor-pago-cell').forEach(cell => { cell.textContent = '--'; }); // Limpa a nova coluna também
      }

      // --- Lógica Registrar ---
      registerBtn.addEventListener('click', async () => {
          if (!lastInputDataForRegister) { registerFeedback.textContent = 'Erro: Calcule antes de registrar.'; registerFeedback.className = 'mt-2 alert alert-warning p-1 text-center'; return; }
          const currentCedenteId = cedenteSelect.value;
          if (!currentCedenteId) { registerFeedback.textContent = 'Erro: Selecione Cedente.'; registerFeedback.className = 'mt-2 alert alert-danger p-1 text-center'; cedenteSelect.classList.add('is-invalid'); return; }
          else { cedenteSelect.classList.remove('is-invalid'); lastInputDataForRegister.cedente_id = currentCedenteId; }
          lastInputDataForRegister.tipo_pagamento = tipoPagamentoSelect.value;
          lastInputDataForRegister.data_operacao = dataOperacaoInput.value;
          lastInputDataForRegister.incorreIOF = incorreIOFSelect.value;
          lastInputDataForRegister.cobrarIOF = cobrarIOFSelect.value;
          
          // Adiciona dados de compensação se houver
          if (compensacaoAtiva && compensacaoAtiva.recebiveis && compensacaoAtiva.recebiveis.length > 0) {
              lastInputDataForRegister.compensacao_data = {
                  recebiveis: compensacaoAtiva.recebiveis,
                  valor_total: compensacaoAtiva.valor_total,
                  taxa_antecipacao: compensacaoAtiva.taxa_antecipacao
              };
          } else {
              lastInputDataForRegister.compensacao_data = null;
          }
          
          registerBtn.disabled = true;
          registerFeedback.textContent = 'Registrando...';
          registerFeedback.className = 'mt-2 text-muted';
          try {
               const response = await fetch('registrar_operacao.php', { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: JSON.stringify(lastInputDataForRegister) });
               if (!response.ok) { let eTxt = `Erro HTTP ${response.status}`; try { eTxt = (await response.json()).error || eTxt; } catch (_) {} throw new Error(eTxt); }
               const result = await response.json();
               if (result.success) {
                   registerFeedback.textContent = `Operação ${result.operacao_id ? '#' + result.operacao_id + ' ' : ''}registrada!`;
                   registerFeedback.className = 'mt-2 alert alert-success p-1 text-center';
                   // Limpa dados de compensação após registro bem-sucedido
                   compensacaoAtiva = null;
                   document.getElementById('compensacaoInfo').style.display = 'none';
                   
                   // Mostrar seção de upload de arquivos
                   if (result.operacao_id) {
                       mostrarSecaoArquivos(result.operacao_id);
                   }
               }
               else { registerFeedback.textContent = `Erro: ${result.error || 'Desconhecido'}`; registerFeedback.className = 'mt-2 alert alert-danger p-1 text-center'; registerBtn.disabled = false; }
          } catch (error) {
              console.error("Erro registro:", error);
              registerFeedback.textContent = `Erro comunicação: ${error.message}`;
              registerFeedback.className = 'mt-2 alert alert-danger p-1 text-center';
              registerBtn.disabled = false;
          }
      });

      // --- Funcionalidade de Upload de Arquivos ---
      const arquivosInput = document.getElementById('arquivos');
      const arquivosSection = document.getElementById('arquivosSection');
      const arquivosPreview = document.getElementById('arquivos-preview');
      const arquivosList = document.getElementById('arquivos-list');
      const descricaoArquivos = document.getElementById('descricaoArquivos');

      // Mostrar seção de arquivos após registro bem-sucedido
      function mostrarSecaoArquivos(operacaoId) {
          arquivosSection.style.display = 'block';
          arquivosSection.dataset.operacaoId = operacaoId;
          
          // Adicionar botão de upload
          if (!document.getElementById('uploadArquivosBtn')) {
              const uploadBtn = document.createElement('button');
              uploadBtn.type = 'button';
              uploadBtn.id = 'uploadArquivosBtn';
              uploadBtn.className = 'btn btn-primary mt-3';
              uploadBtn.innerHTML = '<i class="bi bi-cloud-upload"></i> Enviar Arquivos';
              uploadBtn.addEventListener('click', uploadArquivos);
              arquivosSection.appendChild(uploadBtn);
          }
      }

      // Preview dos arquivos selecionados
      if (arquivosInput) {
          arquivosInput.addEventListener('change', function() {
              const files = this.files;
              if (files.length === 0) {
                  arquivosPreview.style.display = 'none';
                  return;
              }

              arquivosList.innerHTML = '';
              let totalSize = 0;
              let hasErrors = false;

              for (let i = 0; i < files.length; i++) {
                  const file = files[i];
                  totalSize += file.size;
                  
                  const listItem = document.createElement('div');
                  listItem.className = 'list-group-item d-flex justify-content-between align-items-center';
                  
                  let statusClass = 'text-success';
                  let statusIcon = 'bi-check-circle';
                  let statusText = 'OK';
                  
                  // Validações
                  if (file.size > 10 * 1024 * 1024) { // 10MB
                      statusClass = 'text-danger';
                      statusIcon = 'bi-x-circle';
                      statusText = 'Muito grande (>10MB)';
                      hasErrors = true;
                  }
                  
                  const allowedTypes = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'doc', 'docx', 'xls', 'xlsx', 'txt'];
                  const extension = file.name.split('.').pop().toLowerCase();
                  if (!allowedTypes.includes(extension)) {
                      statusClass = 'text-danger';
                      statusIcon = 'bi-x-circle';
                      statusText = 'Tipo não permitido';
                      hasErrors = true;
                  }

                  listItem.innerHTML = `
                      <div>
                          <strong>${file.name}</strong><br>
                          <small class="text-muted">${formatFileSize(file.size)}</small>
                      </div>
                      <span class="${statusClass}">
                          <i class="bi ${statusIcon}"></i> ${statusText}
                      </span>
                  `;
                  
                  arquivosList.appendChild(listItem);
              }

              // Mostrar total
              const totalItem = document.createElement('div');
              totalItem.className = 'list-group-item bg-light';
              totalItem.innerHTML = `<strong>Total: ${files.length} arquivo(s) - ${formatFileSize(totalSize)}</strong>`;
              arquivosList.appendChild(totalItem);

              arquivosPreview.style.display = 'block';
              
              // Habilitar/desabilitar botão de upload
              const uploadBtn = document.getElementById('uploadArquivosBtn');
              if (uploadBtn) {
                  uploadBtn.disabled = hasErrors || files.length === 0;
              }
          });
      }

      // Função para formatar tamanho de arquivo
      function formatFileSize(bytes) {
          if (bytes >= 1073741824) {
              return (bytes / 1073741824).toFixed(2) + ' GB';
          } else if (bytes >= 1048576) {
              return (bytes / 1048576).toFixed(2) + ' MB';
          } else if (bytes >= 1024) {
              return (bytes / 1024).toFixed(2) + ' KB';
          } else {
              return bytes + ' bytes';
          }
      }

      // Função para upload dos arquivos
      async function uploadArquivos() {
          const operacaoId = arquivosSection.dataset.operacaoId;
          const files = arquivosInput.files;
          
          if (!operacaoId || files.length === 0) {
              alert('Selecione arquivos para enviar.');
              return;
          }

          const uploadBtn = document.getElementById('uploadArquivosBtn');
          const originalText = uploadBtn.innerHTML;
          uploadBtn.disabled = true;
          uploadBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Enviando...';

          try {
              const formData = new FormData();
              formData.append('operacao_id', operacaoId);
              
              for (let i = 0; i < files.length; i++) {
                  formData.append('arquivos[]', files[i]);
                  formData.append('descricao[]', descricaoArquivos.value || '');
              }

              const response = await fetch('upload_arquivos.php', {
                  method: 'POST',
                  body: formData
              });

              const result = await response.json();

              if (result.success) {
                  alert(`${result.total_uploaded} arquivo(s) enviado(s) com sucesso!`);
                  
                  // Limpar formulário
                  arquivosInput.value = '';
                  descricaoArquivos.value = '';
                  arquivosPreview.style.display = 'none';
                  
                  // Recarregar lista de arquivos se estiver na página de detalhes
                  if (typeof carregarArquivosOperacao === 'function') {
                      carregarArquivosOperacao(operacaoId);
                  }
              } else {
                  let errorMsg = result.error || 'Erro desconhecido no upload.';
                  if (result.errors && result.errors.length > 0) {
                      errorMsg += '\n\nDetalhes:\n' + result.errors.join('\n');
                  }
                  alert('Erro no upload:\n' + errorMsg);
              }

          } catch (error) {
              console.error('Erro no upload:', error);
              alert('Erro de comunicação durante o upload: ' + error.message);
          } finally {
              uploadBtn.disabled = false;
              uploadBtn.innerHTML = originalText;
          }
      }

      // --- Função updateChart (AGORA COM TRÊS BARRAS E LABELS DE MÊS) ---
      // Esta é a versão da função updateChart que deve estar no seu arquivo.
      // Substitua a versão existente no seu index.php por esta.
      function updateChart(labels, dataEmprestado, dataRetornado, dataLucro) {
          if (myFluxoChart) { myFluxoChart.destroy(); myFluxoChart = null; }

          // Validação robusta para garantir que todos os arrays de dados são válidos e consistentes
          if (!labels || !Array.isArray(labels) || labels.length === 0 ||
              !dataEmprestado || !Array.isArray(dataEmprestado) || dataEmprestado.length !== labels.length ||
              !dataRetornado || !Array.isArray(dataRetornado) || dataRetornado.length !== labels.length ||
              !dataLucro || !Array.isArray(dataLucro) || dataLucro.length !== labels.length
          ) {
              console.warn("Dados inválidos ou inconsistentes para o gráfico. Gráfico não será renderizado.");
              if(chartCanvas){ const ctx = chartCanvas.getContext('2d'); if(ctx) ctx.clearRect(0, 0, chartCanvas.width, chartCanvas.height); }
              return;
          }

          if (!chartCanvas) { console.error("Canvas do gráfico não encontrado!"); return; }
          const ctx = chartCanvas.getContext('2d'); if (!ctx) { console.error("Contexto 2D do gráfico não obtido."); return; }
          try {
              myFluxoChart = new Chart(ctx, {
                  type: 'bar',
                  data: {
                      labels: labels, // 'labels' agora contém os nomes dos meses
                      datasets: [
                          {
                              label: 'Capital Emprestado (Saída)',
                              data: dataEmprestado,
                              backgroundColor: 'rgba(108, 117, 125, 0.7)', // Cinza
                              borderColor: 'rgba(108, 117, 125, 1)',
                              borderWidth: 1,
                              order: 3 // Ordem para empilhamento ou exibição
                          },
                          {
                              label: 'Capital Retornado (Entrada)',
                              data: dataRetornado,
                              backgroundColor: 'rgba(0, 123, 255, 0.7)', // Azul
                              borderColor: 'rgba(0, 123, 255, 1)',
                              borderWidth: 1,
                              order: 2
                          },
                          {
                              label: 'Lucro Líquido',
                              data: dataLucro,
                              backgroundColor: 'rgba(25, 135, 84, 0.7)', // Verde
                              borderColor: 'rgba(25, 135, 84, 1)',
                              borderWidth: 1,
                              order: 1
                          }
                      ]
                  },
                  options: {
                      responsive: true,
                      maintainAspectRatio: false,
                      scales: {
                          y: {
                              beginAtZero: true,
                              ticks: {
                                  callback: function(value) {
                                      return value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL', minimumFractionDigits: 0, maximumFractionDigits: 0 });
                                  }
                              }
                          },
                          x: {
                              // O Chart.js usará os rótulos diretamente do array 'labels'.
                          }
                      },
                      plugins: {
                          legend: {
                              display: true,
                              position: 'top',
                          },
                          tooltip: {
                              callbacks: {
                                  title: function(context) {
                                      return context[0].label; // Título é o nome do mês
                                  },
                                  label: function(context) {
                                      let label = context.dataset.label || '';
                                      if (label) { label += ': '; }
                                      if (context.parsed.y !== null) {
                                          label += context.parsed.y.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
                                      }
                                      return label;
                                  }
                              }
                          }
                      }
                  }
              });
          } catch (chartError) {
              console.error("Erro Chart.js ao criar o gráfico:", chartError);
              errorMessageDiv.textContent='Erro ao renderizar gráfico: '+chartError.message;
              errorMessageDiv.classList.remove('d-none');
          }
      }

      // --- Lógica REVISADA e SIMPLIFICADA para Exportar PDF ---
      exportPdfBtn.addEventListener('click', function() {
          // 1. Verifica se o botão está habilitado
          if (exportPdfBtn.disabled) {
              console.log("[PDF Click] Botão desabilitado.");
              return; // Sai se desabilitado
          }
          console.log("[PDF Click] Botão clicado. Gerando imagem...");

          // 2. Prepara o campo de dados da imagem (TENTA gerar)
          chartImageDataInput.value = ''; // Limpa valor antigo
          if (myFluxoChart && chartCanvas) { // Verifica se gráfico existe
              try {
                  if (myFluxoChart.isDestroyed) { // Se o gráfico foi destruído (por clearResults), tenta recriá-lo para capturar a imagem
                      // Antes de tentar capturar a imagem, é crucial que `calculationResults` tenha os dados necessários
                      if(calculationResults) {
                           updateChart(
                               calculationResults.chartLabels || [],
                               calculationResults.chartDataCapitalEmprestado || [],
                               calculationResults.chartDataCapitalRetornado || [],
                               calculationResults.chartDataLucro || []
                           );
                      } else {
                          console.warn("[PDF Click] calculationResults não disponível para recriar gráfico.");
                          throw new Error("Dados do gráfico não disponíveis.");
                      }
                  }
                  chartImageDataInput.value = myFluxoChart.toBase64Image('image/png', 1.0);
                  // Valida se a string base64 foi gerada corretamente
                  if (!chartImageDataInput.value || !chartImageDataInput.value.startsWith('data:image/png;base64,')) {
                      console.error("[PDF Click] Erro: Dados base64 inválidos gerados pelo Chart.js.");
                      alert("Erro ao gerar imagem do gráfico para o PDF. O PDF será gerado sem o gráfico.");
                      chartImageDataInput.value = ''; // Garante limpeza se inválido
                      // Deixa o submit prosseguir sem a imagem neste caso
                  } else {
                      console.log("[PDF Click] Imagem Base64 gerada OK.");
                  }
              } catch (e) {
                  console.error("[PDF Click] Erro CATASTRÓFICO ao gerar Base64:", e);
                  alert("Erro inesperado ao preparar gráfico para PDF. O PDF será gerado sem o gráfico.");
                  chartImageDataInput.value = ''; // Garante campo vazio
                  // Deixa o submit prosseguir sem a imagem
              }
          } else {
              console.log("[PDF Click] Gráfico não renderizado na tela. Imagem não será enviada.");
              chartImageDataInput.value = ''; // Garante campo vazio
          }

          // 3. Define os atributos do formulário para o envio do PDF
          const originalAction = form.action;
          const originalTarget = form.target;
          form.action = 'export_pdf.php';   // Define action para o script PHP
          form.target = '_blank';           // Define target para nova aba

          // 4. Submete o formulário programaticamente
          console.log("[PDF Click] Submetendo formulário para export_pdf.php...");
          form.submit(); // Envia o formulário AGORA

          // 5. Restaura os atributos originais após um pequeno delay
          setTimeout(() => {
              // console.log("[PDF Click] Resetando action/target do form.");
              form.action = originalAction;
              form.target = originalTarget;
          }, 500); // Meio segundo de delay
      });

      // --- Listener para Botão PDF Cliente (type="button") ---
if (exportPdfClienteBtn) { // Verifica se o botão existe no HTML
     exportPdfClienteBtn.addEventListener('click', function() {
         if (exportPdfClienteBtn.disabled) {
             console.log("[PDF Cliente Click] Botão desabilitado.");
             return;
         }
         console.log("[PDF Cliente Click] Clicado.");
         chartImageDataInput.value = ''; // Garante que o campo da imagem vá vazio

         // Define action/target e submete manualmente
         const originalAction = form.action;
         const originalTarget = form.target;
         form.action = 'export_pdf_cliente.php'; // Script correto para simulações
         form.target = '_blank';               // Nova aba

         console.log("[PDF Cliente Click] Submetendo para export_pdf_cliente.php...");
         form.submit(); // Envia

         // Restaura action/target originais após um delay
         setTimeout(() => {
             form.action = originalAction;
             form.target = originalTarget;
         }, 500);
     });
 }

   </script>

</body>
</html>
