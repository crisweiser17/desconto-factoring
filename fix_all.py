import os

with open('index_html.php', 'r') as f:
    html_part = f.read()

with open('index.php', 'r') as f:
    full_old = f.read()

script_start = full_old.find('<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>')

script_part = full_old[script_start:]

new_content = html_part + '\n  ' + script_part

with open('index.php', 'w') as f:
    f.write(new_content)

print("Done fixing index.php")
