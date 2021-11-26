<?php

$http_origin = $_SERVER['HTTP_ORIGIN'];
$allowed_domains = array('http://localhost', 'https://operaciones.hosting.cl');
if (in_array($http_origin, $allowed_domains))
{  
    header("Access-Control-Allow-Origin: $http_origin");
}


header('Access-Control-Allow-Methods: POST');

if(!isset($_POST["servidor"]) || !isset($_POST["inicio"]) || !isset($_POST["fin"])){
	exit(json_encode(array("Exitosa" => false, "Mensaje" => "Parametros invalidos", "Parametros" => json_encode($_POST))));
}

$servidor = $_POST["servidor"];
$inicio = $_POST["inicio"];
$fin = $_POST["fin"];

if(!validateDate($inicio) || !validateDate($fin)){
	exit(json_encode(array("Exitosa" => false, "Mensaje" => "Fechas invalidas")));
}

$xml = file_get_contents("http://apihosting.centinelaweb.cl/api/Uptime/Detalle/$servidor/$inicio/$fin");

echo $xml;

function validateDate($date)
{
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

?>