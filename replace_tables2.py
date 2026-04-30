import os
import re

files = [
    'api_fechamento.php',
    'buscar_recebiveis_indiretos.php',
    'envia_alertas.php',
    'exportar_csv.php',
    'gera_analise_interna_pdf.php',
    'gerar_recibo_cliente.php'
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