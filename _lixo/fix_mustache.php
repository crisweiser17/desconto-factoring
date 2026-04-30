<?php
$files = glob("_contratos/*.md");
foreach($files as $file) {
    if (is_file($file)) {
        $content = file_get_contents($file);
        $content = preg_replace_callback('/\{\{([^\}]+)\}\}/', function($matches) {
            return '{{' . str_replace('\\_', '_', $matches[1]) . '}}';
        }, $content);
        file_put_contents($file, $content);
        echo "Fixed $file\n";
    }
}
