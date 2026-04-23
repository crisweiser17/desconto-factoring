# Escopo do Sistema Factoring 5.1

## 1. Resumo do Software
O **Factoring 5.1** é um sistema web especializado na gestão de operações de factoring e antecipação de recebíveis. Seu principal objetivo é facilitar e automatizar o cálculo de deságios (descontos) sobre títulos de crédito (duplicatas, cheques, boletos, etc.), fornecendo uma visão clara da rentabilidade de cada operação. O sistema atende gestores financeiros e factorings, permitindo desde a simulação comercial para os clientes (Cedentes) até o controle completo do ciclo de vida dos recebíveis, encontro de contas, notificações de cobrança e relatórios gerenciais detalhados.

---

## 2. Módulos e Suas Funções

### 2.1. Módulo de Operações (Simulação e Registro)
É o coração do sistema, responsável por toda a parte matemática e de negociação com o cliente.
*   **Calculadora de Desconto:** Cálculo automático do valor presente de um ou mais títulos com base na taxa de desconto mensal (% a.m.), número de dias até o vencimento e IOF.
*   **Gestão de Títulos:** Inserção de múltiplos títulos por operação (duplicata, cheque, nota promissória, boleto, fatura, nota fiscal), associando cada um a um Sacado (devedor).
*   **Descobrir Taxa Alvo (Engenharia Reversa):** Funcionalidade que permite inserir o valor líquido que se deseja pagar ao cliente e o sistema calcula qual a taxa de juros necessária para atingir esse valor.
*   **Encontro de Contas (Compensação):** Permite utilizar recebíveis indiretos em aberto do Cedente para abater no valor de uma nova operação (quitação parcial ou total com cálculos de crédito de antecipação).
*   **Tipos de Pagamento:** Suporte para registro de pagamento Direto (devedor notificado), Indireto (repasse via cedente) ou via Conta Escrow.
*   **Upload de Documentos:** Anexação de arquivos (PDFs, imagens, planilhas) diretamente na operação (limite de múltiplos arquivos por operação).
*   **Notificações Automatizadas:** Envio automático de e-mail para os Sacados informando sobre a cessão de crédito após o registro da operação.
*   **Exportação de PDF:** Geração de dois tipos de relatórios PDF: uma *Análise Completa* (para uso interno) e uma *Simulação para Cliente* (versão comercial sem dados sensíveis de lucro).
*   **Gráfico de Fluxo de Caixa:** Visualização gráfica imediata do capital emprestado (saída), capital retornado (entrada) e lucro líquido projetado.

### 2.2. Módulo de Gestão de Operações e Recebíveis
Focado no acompanhamento das operações já registradas.
*   **Gerenciar Operações:** Listagem de todas as operações com filtros avançados (por cedente, status, valor, período). Exibe métricas de totais originais, líquidos, lucro, média de dias e saldo em aberto.
*   **Gerenciar Recebíveis:** Controle individualizado de cada título gerado. Acompanhamento de status de pagamento (*Em Aberto*, *Recebido*, *Parcialmente Compensado*, *Com Problema*).
*   **Detalhamento:** Visualização aprofundada de uma operação específica, permitindo edições, download de anexos e ações de cobrança.

### 2.3. Módulo de Cadastro (CRM Básico)
Responsável por manter a base de dados de clientes e devedores.
*   **Gestão de Cedentes (Vendedores):** Cadastro completo das empresas que vendem os recebíveis, incluindo dados bancários, histórico e informações de contato.
*   **Gestão de Sacados (Devedores):** Cadastro dos emissores dos títulos, essencial para análise de risco, controle de limite de crédito e envio de notificações.

### 2.4. Módulo de Relatórios e Dashboards
Fornece inteligência de negócios e suporte à tomada de decisão.
*   **Relatório Geral (Dashboard):** Visão macro da saúde financeira, consolidação de lucros, volume operado e inadimplência.
*   **Relatório por Cedente:** Análise de rentabilidade e volume agrupada por cliente (quem traz mais lucro, quem tem mais devoluções).
*   **Relatório por Sacado:** Análise de exposição de risco por devedor (concentração de crédito num único sacado).
*   **Contas a Pagar:** Gestão do fluxo de saídas e obrigações financeiras da própria empresa de factoring.
*   **Exportações:** Capacidade de extrair dados para planilhas (CSV) ou integrações de calendário (ICS).

### 2.5. Módulo de Configurações e Segurança
Administração da plataforma.
*   **Configurações Gerais:** Definição de parâmetros globais de cálculo (taxa de juros padrão, alíquotas de IOF diário e adicional, etc.).
*   **Gerenciar Usuários:** Controle de acesso ao sistema, criação de credenciais de login, senhas e permissões para a equipe interna.
*   **Autenticação (Login/Logout):** Sistema de sessão segura protegendo dados financeiros sensíveis.