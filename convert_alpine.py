import re

with open('index.php', 'r') as f:
    content = f.read()

# Find the start of the script tag
script_start = content.find('<script>\n      // --- Funções para formatar moeda em JavaScript')

if script_start != -1:
    print("Found script start at", script_start)
else:
    print("Could not find script start")
