import os
import re

files = [
    'api_contratos.php',
    'api_dashboard_financeiro.php',
    'dashboard_financeiro.php',
    'detalhes_operacao.php',
    'editar_operacao.php',
    'export_pdf.php',
    'export_pdf_cliente.php',
    'listar_operacoes.php',
    'listar_recebiveis.php',
    'notificar_sacados.php',
    'preview_notificacao_sacados.php'
]

for file in files:
    path = os.path.join('/Users/crisweiser/Downloads/Projetos IDE/Descontos Factoring', file)
    if not os.path.exists(path):
        continue
        
    with open(path, 'r', encoding='utf-8') as f:
        content = f.read()
        
    # Replace JOIN cedentes -> JOIN clientes
    content = re.sub(r'\bJOIN\s+cedentes\b', 'JOIN clientes', content)
    content = re.sub(r'\bFROM\s+cedentes\b', 'FROM clientes', content)
    content = re.sub(r'\bUPDATE\s+cedentes\b', 'UPDATE clientes', content)
    
    # Replace JOIN sacados -> JOIN clientes
    content = re.sub(r'\bJOIN\s+sacados\b', 'JOIN clientes', content)
    content = re.sub(r'\bFROM\s+sacados\b', 'FROM clientes', content)
    content = re.sub(r'\bUPDATE\s+sacados\b', 'UPDATE clientes', content)
    
    with open(path, 'w', encoding='utf-8') as f:
        f.write(content)

print("Replacement complete.")