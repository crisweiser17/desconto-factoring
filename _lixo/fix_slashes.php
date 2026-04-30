<?php
$files = glob("_contratos/*.md");
foreach($files as $file) {
    if (is_file($file)) {
        $content = file_get_contents($file);
        
        // Remove trailing backslash at end of lines and replace with <br />
        $content = preg_replace('/\\\\\s*\n/', "<br />\n", $content);
        
        // Remove lone backslashes on a line (used as spacers)
        $content = preg_replace('/^\\\\\s*$/m', "<br />", $content);
        
        // Fix the newlines inside sentences that were using backslash followed by a space
        $content = preg_replace('/\\\\\s/', " ", $content);
        
        // In 03_template_nota_promissoria, fix specifically if they still exist
        $content = str_replace("],\n", "],\n", $content);

        file_put_contents($file, $content);
        echo "Fixed slashes in $file\n";
    }
}
