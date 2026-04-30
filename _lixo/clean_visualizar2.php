<?php
$content = file_get_contents('visualizar_cliente.php');

// Remove conjuge and regime_casamento fields from representante card
$content = preg_replace('/<div class="col-md-4">\s*<div class="info-label">Casado\?<\/div>[\s\S]*?<div class="col-md-6">\s*<div class="info-label">Profissão<\/div>\s*<div class="info-value"><\?php echo htmlspecialchars\(\$cliente\[\'conjuge_profissao\'\] \?\? \'-\'\); \?><\/div>\s*<\/div>/s', '', $content);

file_put_contents('visualizar_cliente.php', $content);
