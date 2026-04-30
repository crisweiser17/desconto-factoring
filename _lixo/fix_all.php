<?php
$files = glob("_contratos/*.md");
foreach($files as $file) {
    if (is_file($file)) {
        $content = file_get_contents($file);
        
        // Match the signature lines
        $content = preg_replace('/<br \/>\n\\\\_\\\\_\\\\_[^\n]*<br \/>\n/', "<br><br><br>____________________________________________________<br>\n", $content);
        
        // Conjuge
        $content = preg_replace('/Assinatura:[\s\S]*?<div style="margin: 12px 0 6px 0; border-bottom: 1px solid #000; width: 100%; height: 18px;"><\/div>[\s]*Nome:\s*\\\\_[^\n]*<br \/>\nCPF:\s*\\\\_[^\n]*/', "<br><br><br>____________________________________________________<br>\nNome: _____________________________________ CPF: ________________________", $content);
        
        file_put_contents($file, $content);
        echo "Fixed $file\n";
    }
}
