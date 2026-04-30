<?php
require 'vendor/autoload.php';
$m = new Mustache_Engine;
$tpl = "
| A | B |
|---|---|
{{#titulos}}
| {{a}} | {{b}} |
{{/titulos}}
";
$data = ['titulos' => [['a'=>1,'b'=>2], ['a'=>3,'b'=>4]]];
$md = $m->render($tpl, $data);
$pd = new Parsedown();
echo $pd->text($md);
