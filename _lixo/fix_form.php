<?php
$content = file_get_contents('form_cliente.php');

$pattern = '/\/\/ Função para atualizar máscara do documento principal[\s\S]*?\/\/ Máscaras iniciais/';
$replacement = '// Função para atualizar máscara do documento principal
            function updateDocumentoMask() {
                const documentoInput = $(\'#documento_principal\');
                const documentoLabel = $(\'#documento_label\');
                
                documentoLabel.text(\'CNPJ\');
                documentoInput.inputmask(\'remove\');
                documentoInput.attr(\'placeholder\', \'00.000.000/0000-00\');
                documentoInput.inputmask("99.999.999/9999-99", {
                    clearIncomplete: true,
                    placeholder: "_"
                });
                
                // Mostrar e habilitar card de sócios e representante
                $(\'#socios_card\').show();
                $(\'#socios_card\').find(\'input, button\').prop(\'disabled\', false);
                $(\'#representante_card\').show();
                $(\'#representante_card\').find(\'input, select, button\').prop(\'disabled\', false);
                
                // Mostrar campo porte
                $(\'#porte\').closest(\'div[class^="col-"]\').show();
            }

            // Máscaras iniciais';

$content = preg_replace($pattern, $replacement, $content);
file_put_contents('form_cliente.php', $content);
