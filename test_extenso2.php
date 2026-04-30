<?php
require_once 'vendor/autoload.php';
use WGenial\NumeroPorExtenso\NumeroPorExtenso;
$extenso = new NumeroPorExtenso();
echo $extenso->converter(25, false, false);
