<?php
$content = file_get_contents('salvar_cliente.php');

// Remove conjuge variables
$content = preg_replace('/\$conjuge_.*?=\s*trim\(\$_POST\[\'conjuge_.*?\'\]\s*\?\?\s*\'\'\);\n/', '', $content);
$content = preg_replace('/\$casado.*?;\n/', '', $content);
$content = preg_replace('/\$regime_casamento.*?;\n/', '', $content);

// Remove FISICA logic
$content = preg_replace('/\/\/ Se for FISICA, forçar dados do representante\s*if \(\$tipoPessoa === \'FISICA\'\) \{\s*\$representante_nome = \$nome;\s*\$representante_cpf = \$documentoPrincipal;\s*\}/s', '', $content);

$content = preg_replace('/\$expectedLength = \(\$tipoPessoa === \'FISICA\'\) \? 11 : 14;/', '$expectedLength = 14;', $content);
$content = preg_replace('/\$documentoTipo = \(\$tipoPessoa === \'FISICA\'\) \? \'CPF\' : \'CNPJ\';/', '$documentoTipo = \'CNPJ\';', $content);

$content = preg_replace('/if \(\$tipoPessoa === \'FISICA\' && !validaCPF\(\$documentoPrincipal\)\) \{[\s\S]*?\} else if \(\$tipoPessoa === \'JURIDICA\' && !validaCNPJ\(\$documentoPrincipal\)\)/', 'if (!validaCNPJ($documentoPrincipal))', $content);

// Remove Cônjuge CPF validation
$content = preg_replace('/\/\/ Validação de Cônjuge CPF[\s\S]*?\/\/ Validação da Conta Documento/s', '// Validação da Conta Documento', $content);

// Update SQL Update Statement
$content = preg_replace('/conjuge_nome = :conjuge_nome,\s*/', '', $content);
$content = preg_replace('/conjuge_cpf = :conjuge_cpf,\s*/', '', $content);
$content = preg_replace('/conjuge_rg = :conjuge_rg,\s*/', '', $content);
$content = preg_replace('/conjuge_nacionalidade = :conjuge_nacionalidade,\s*/', '', $content);
$content = preg_replace('/conjuge_profissao = :conjuge_profissao,\s*/', '', $content);
$content = preg_replace('/casado = :casado,\s*/', '', $content);
$content = preg_replace('/regime_casamento = :regime_casamento,\s*/', '', $content);

// Update SQL Insert Statement
$content = preg_replace('/casado, regime_casamento, conjuge_nome, conjuge_cpf, conjuge_rg, conjuge_nacionalidade, conjuge_profissao,\s*/', '', $content);
$content = preg_replace('/:casado, :regime_casamento, :conjuge_nome, :conjuge_cpf, :conjuge_rg, :conjuge_nacionalidade, :conjuge_profissao,\s*/', '', $content);

// Remove bindParams
$content = preg_replace('/\$stmt->bindParam\(\':conjuge_.*?\n/', '', $content);
$content = preg_replace('/\$stmt->bindParam\(\':casado\'.*?\n/', '', $content);
$content = preg_replace('/\$stmt->bindParam\(\':regime_casamento\'.*?\n/', '', $content);

file_put_contents('salvar_cliente.php', $content);
echo "Cleaned salvar_cliente.php\n";
