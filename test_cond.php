<?php
$inputJSON = file_get_contents('mock_request.json');
$input = json_decode($inputJSON, TRUE);
var_dump(!$input);
var_dump(!isset($input['taxaMensal']));
var_dump(!isset($input['titulos']));
var_dump(!is_array($input['titulos']));
var_dump(!array_key_exists('cedente_id', $input));
var_dump(!array_key_exists('tomador_id', $input));
