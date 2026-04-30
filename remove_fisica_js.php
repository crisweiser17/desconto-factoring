<?php
$content = file_get_contents('form_cliente.php');

// Replace JS logic for FISICA/JURIDICA
$content = preg_replace('/function updateDocumentoMask\(\) \{[\s\S]*?\}\);/', 'function updateDocumentoMask() {
                const documentoInput = $(\'#documento_principal\');
                documentoInput.inputmask(\'remove\');
                documentoInput.attr(\'placeholder\', \'00.000.000/0000-00\');
                documentoInput.inputmask("99.999.999/9999-99", {
                    removeMaskOnSubmit: false,
                    autoUnmask: true,
                    unmaskAsNumber: true,
                    placeholder: "_"
                });
            }', $content);

$content = preg_replace('/const expectedLength = tipoPessoa === \'FISICA\' \? 11 : 14;\s*const documentoTipo = tipoPessoa === \'FISICA\' \? \'CPF\' : \'CNPJ\';\s*let valid = true;\s*if \(documento\.length !== expectedLength\) \{\s*valid = false;\s*\} else if \(tipoPessoa === \'FISICA\' && !isValidCPF\(documento\)\) \{\s*valid = false;\s*\} else if \(tipoPessoa === \'JURIDICA\' && !isValidCNPJ\(documento\)\) \{/', 'const expectedLength = 14;
                const documentoTipo = \'CNPJ\';
                let valid = true;
                if (documento.length !== expectedLength) {
                    valid = false;
                } else if (!isValidCNPJ(documento)) {', $content);

file_put_contents('form_cliente.php', $content);
echo "Cleaned FISICA JS from form_cliente.php\n";
