<?php
$content = file_get_contents('form_cliente.php');

// Remove conjuge section
$content = preg_replace('/<!-- Dados do Cônjuge -->.*?<\/div>\s*<\/div>\s*<\/div>/s', '', $content);

// Remove conjuge fields from default array
$content = preg_replace('/\'conjuge_[^\']+\'\s*=>\s*\'\',\s*/', '', $content);

// Remove married/conjuge js logic
$content = preg_replace('/\/\/ Toggle para campos do cônjuge.*?\n\s*\}\);\n/s', '', $content);

// Remove conjuge CPF mask and validation
$content = preg_replace('/\$\(\'#conjuge_cpf\'\)\.inputmask.*?\n/s', '', $content);
$content = preg_replace('/\/\/ Validar Cônjuge CPF.*?\}\n\s*/s', '', $content);

// Remove married field from db struct
$content = preg_replace('/\'casado\'\s*=>\s*0,\s*/', '', $content);
$content = preg_replace('/\'regime_casamento\'\s*=>\s*\'\',\s*/', '', $content);

file_put_contents('form_cliente.php', $content);
echo "Cleaned form_cliente.php\n";
