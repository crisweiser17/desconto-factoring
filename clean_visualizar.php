<?php
$content = file_get_contents('visualizar_cliente.php');

// Remove conjuge section block
$content = preg_replace('/<\?php if \(\$mostrarConjuge\): \?>[\s\S]*?<\?php endif; \?>/s', '', $content);

// Remove conjuge variables logic
$content = preg_replace('/\$labelConjuge =.*?;/', '', $content);
$content = preg_replace('/\$labelEstadoCivil =.*?;/', '', $content);
$content = preg_replace('/\$mostrarConjuge =.*?;/s', '', $content);

// Simplify FISICA variables
$content = preg_replace('/\$isPessoaFisica = \$tipoPessoa === \'FISICA\';/', '$isPessoaFisica = false;', $content);
$content = preg_replace('/\$tituloDadosPrincipais = \$isPessoaFisica \? \'Dados Pessoais\' : \'Dados da Empresa\';/', '$tituloDadosPrincipais = \'Dados da Empresa\';', $content);
$content = preg_replace('/\$labelNomeEmpresa = \$isPessoaFisica \? \'Nome\' : \'Razão Social\';/', '$labelNomeEmpresa = \'Razão Social\';', $content);

// Simplify CNPJ label
$content = preg_replace('/<\?php echo \$isPessoaFisica \? \'CPF\' : \'CNPJ\'; \?>/', 'CNPJ', $content);

// Remove "if ($isPessoaFisica)" block around badge
$content = preg_replace('/<\?php\s*if \(\$isPessoaFisica\) \{[\s\S]*?\} else \{[\s\S]*?\}\s*\?>/s', '<?php echo \'<span class="badge bg-primary">Pessoa Jurídica</span>\'; ?>', $content);

// Remove !$isPessoaFisica check for representante (always true for JURIDICA)
$content = preg_replace('/\$mostrarRepresentante = !\$isPessoaFisica && \(/', '$mostrarRepresentante = (', $content);

file_put_contents('visualizar_cliente.php', $content);
echo "Cleaned visualizar_cliente.php\n";
