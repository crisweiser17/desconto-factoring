<?php
$files = glob("_contratos/*.md");
foreach($files as $file) {
    if (is_file($file)) {
        $content = file_get_contents($file);
        
        // This regex looks for `<br />\n\_\_\_\_...<br />` and replaces it with `<br><br><br>____________________________________________________<br>`
        // Note: the original markdown had \_\_\_ we already fixed slashes but maybe some \_ remain. 
        // Let's just match the underscores part.
        
        $content = preg_replace('/<br \/>\n\\\\?_\\\\?_\\\\?_.*?<br \/>/s', "<br><br><br>____________________________________________________<br>", $content);
        
        // for cases without the trailing <br />
        $content = preg_replace('/<br \/>\n\\\\?_\\\\?_\\\\?_[^\n]*\n/s', "<br><br><br>____________________________________________________<br>\n", $content);

        // Also fix the CÔNJUGE line:
        // Assinatura:
        // <div ...></div>
        $content = pr<?php
$files = glob("_contratos/*.md");
foreach($files er$filtoforeach($files as $file) {
    ihe    if (is_file($file)) {, = file___