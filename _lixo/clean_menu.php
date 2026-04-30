<?php
$content = file_get_contents('menu.php');

$content = preg_replace('/\'listar_sacados.php\' => \'<i class="bi bi-person-badge"><\/i> Sacados \(Devedores\)\',\n\s*\'listar_cedentes.php\' => \'<i class="bi bi-building"><\/i> Cedentes \(Vendedores\)\'/', '\'listar_clientes.php\' => \'<i class="bi bi-people"></i> Clientes\'', $content);

file_put_contents('menu.php', $content);
