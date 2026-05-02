<?php
// menu.php

// Read config to get app_name and app_version
$menuConfigFilePath = __DIR__ . '/config.json';
$menuAppConfig = [];
if (file_exists($menuConfigFilePath)) {
    $menuAppConfig = json_decode(file_get_contents($menuConfigFilePath), true) ?: [];
}
$menuAppName = $menuAppConfig['app_name'] ?? 'Factoring';
$menuAppVersion = $menuAppConfig['app_version'] ?? '5.2 de abril de 2026';

// Detecta qual página está sendo exibida
$currentPage = basename($_SERVER['PHP_SELF']);

// Define os links do menu, usando uma estrutura para dropdown
// Adicionados ícones (Bootstrap Icons) como parte do label para simplicidade
$menuItems = [
    'index.php' => '<i class="bi bi-calculator"></i> Nova Simulação',
    // Estrutura para o dropdown de Operações (agora incluindo Recebíveis)
    'operacoes_dropdown' => [ // Chave para o dropdown principal
        'label' => 'Operações',
        'icon' => 'bi-journal-text',
        'pages' => [ // Páginas que ativam este dropdown
            'listar_operacoes.php',
            'detalhes_operacao.php', // Detalhes de operação também ativa o menu "Operações"
            'listar_recebiveis.php', // Listar recebíveis agora ativa o menu "Operações"
            'form_operacao.php' // Se você tiver um form para adicionar/editar operações
        ],
        'items' => [ // Itens dentro do dropdown
            'listar_operacoes.php' => '<i class="bi bi-list-ul"></i> Gerenciar Operações',
            'listar_recebiveis.php' => '<i class="bi bi-list-check"></i> Gerenciar Recebíveis' // Recebíveis movido para cá
            // Se houver um formulário para adicionar operação, adicione aqui
            // 'form_operacao.php' => '<i class="bi bi-plus-circle"></i> Nova Operação'
        ]
    ],
    // Estrutura para o dropdown Comercial (Leads e Clientes)
    'comercial_dropdown' => [
        'label' => 'Comercial',
        'icon' => 'bi-funnel-fill',
        'pages' => [
            'listar_leads.php',
            'kanban_leads.php',
            'form_lead.php',
            'salvar_lead.php',
            'excluir_lead.php',
            'arquivar_lead.php',
            'converter_lead.php',
            'atualizar_estagio_lead.php',
            'listar_clientes.php',
            'form_cliente.php',
            'visualizar_cliente.php',
            'salvar_cliente.php',
            'excluir_cliente.php'
        ],
        'items' => [
            'kanban_leads.php'    => '<i class="bi bi-kanban"></i> Esteira de Venda',
            'form_lead.php'       => '<i class="bi bi-plus-circle"></i> Novo Lead',
            '_divider1'           => '---',
            'form_cliente.php'    => '<i class="bi bi-person-plus"></i> Novo Cliente',
            'listar_clientes.php' => '<i class="bi bi-people"></i> Clientes'
        ]
    ],
    // Estrutura para o dropdown de Relatórios
    'relatorio_dropdown' => [
        'label' => 'Relatórios',
        'icon' => 'bi-graph-up',
        'pages' => [
            'dashboard_financeiro.php',
            'fechamento.php',
            'relatorio_visitas.php'
        ],
        'items' => [
            'dashboard_financeiro.php' => '<i class="bi bi-graph-up"></i> Relatório Geral Financeiro',
            'fechamento.php' => '<i class="bi bi-wallet2"></i> Fechamento Mensal',
            'relatorio_visitas.php' => '<i class="bi bi-bar-chart-line-fill"></i> Visitas por Usuário'
        ]
    ],
    // Estrutura para o dropdown de Configurações
    'config_dropdown' => [
        'label' => 'Configurações',
        'icon' => 'bi-gear-fill',
        'pages' => [
            'config.php',
            'listar_usuarios.php',
            'form_usuario.php'
        ],
        'items' => [
            'config.php' => '<i class="bi bi-gear-fill"></i> Configurações Gerais',
            'listar_usuarios.php' => '<i class="bi bi-people-fill"></i> Gerenciar Usuários'
        ]
    ]
];
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
  <div class="container">
    <a class="navbar-brand" href="index.php">
        <i class="bi bi-calculator-fill me-2"></i><?php echo htmlspecialchars($menuAppName); ?>
        <small class="text-secondary ms-2" style="font-size: 0.7em;"><?php echo htmlspecialchars($menuAppVersion); ?></small>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="mainNavbar">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
        <?php
        foreach ($menuItems as $keyOrUrl => $item):
            // Verifica se é um item de dropdown
            if (is_array($item) && isset($item['items'])) {
                $dropdownLabel = $item['label'];
                $dropdownIcon = $item['icon'] ?? '';
                $dropdownPages = $item['pages'] ?? [];
                $dropdownSubItems = $item['items'];

                // Verifica se alguma das páginas filhas ou a página principal do dropdown está ativa
                $isDropdownActive = false;
                foreach ($dropdownPages as $page) {
                    if ($currentPage == $page) {
                        $isDropdownActive = true;
                        break;
                    }
                }
                // Adicional: Se o $keyOrUrl for uma página e estiver ativa, ativa o dropdown (caso exista um link principal)
                if (!$isDropdownActive && !is_numeric($keyOrUrl) && $currentPage == $keyOrUrl) {
                    $isDropdownActive = true;
                }

                $activeClass = $isDropdownActive ? 'active fw-bold' : '';
        ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?php echo $activeClass; ?>" href="#" id="navbarDropdown_<?php echo $keyOrUrl; ?>" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <?php if ($dropdownIcon): ?><i class="bi <?php echo $dropdownIcon; ?> me-1"></i><?php endif; ?>
                        <?php echo $dropdownLabel; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-dark" aria-labelledby="navbarDropdown_<?php echo $keyOrUrl; ?>">
                        <?php foreach ($dropdownSubItems as $url => $label):
                                if ($label === '---'): ?>
                                    <li><hr class="dropdown-divider"></li>
                        <?php   else:
                                    $childActiveClass = ($currentPage == $url) ? 'active' : '';
                        ?>
                                <li><a class="dropdown-item <?php echo $childActiveClass; ?>" href="<?php echo $url; ?>"><?php echo $label; ?></a></li>
                        <?php   endif;
                                endforeach; ?>

                    </ul>
                </li>
        <?php
            } else { // É um link simples
                $url = $keyOrUrl;
                $label = $item;
                $isActive = ($currentPage == $url);

                // Lógica para ativar links principais mesmo em páginas "filhas" (se necessário)
                // O index.php é ativado por ele mesmo e por calcular_desconto.php e form_operacao.php
                if ($url == 'index.php' && in_array($currentPage, ['index.php', 'calcular_desconto.php', 'form_operacao.php'])) {
                    $isActive = true;
                }
                // NOVO: Ativa o item "Configurações" quando em config.php
                if ($url == 'config.php' && $currentPage == 'config.php') { //
                    $isActive = true; //
                }

                $activeClass = $isActive ? 'active fw-bold' : '';
                $ariaCurrent = $isActive ? 'aria-current="page"' : '';
        ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo $activeClass; ?>" <?php echo $ariaCurrent; ?> href="<?php echo $url; ?>">
                        <?php echo $label; ?>
                    </a>
                </li>

        <?php
            } // Fim do if/else
        endforeach; // Fim do loop principal
        ?>
        <li class="nav-item">
            <a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right"></i> Sair</a>
        </li>
      </ul>
    </div>
  </div>
</nav>
