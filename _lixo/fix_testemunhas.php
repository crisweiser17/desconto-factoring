<?php
$files = glob("_contratos/*.md");
foreach($files as $file) {
    if (is_file($file)) {
        $content = file_get_contents($file);
        
        $content = preg_replace('/<br \/>\nNome:\s*__.*?<br \/>\nCPF:\s*__.*/m', "<br><br><br>____________________________________________________<br>\nNome: _____________________________________ CPF: ________________________", $content);
        
        // Let's also check if there are still any `\_` in the file.
        $content = str_replace('\_', '_', $content);
        
        file_put_contents($file, $content);
        echo "Fixed witness and any stray slashes in $file\n";
    }
}
