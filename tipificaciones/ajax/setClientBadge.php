<?php

error_reporting(0);

//conexion a la BD
$upOne = realpath(__DIR__ . '/..');
require $upOne.'/DB/SACManager.class.php';
$SACManager = SACManager::singleton();

if(!isset($_POST["id_badge"]) || !isset($_POST["domain"]) || !isset($_POST["value"]) || !isset($_POST["operador"])){
	exit(json_encode(array("success" => false, "message"=> "No se enviaron los datos correctos a la solicitud")));
}

$badge_id = $_POST["id_badge"];
$domain = $_POST["domain"];
$value = $_POST["value"];
$operador = $_POST["operador"];
date_default_timezone_set("America/Santiago");
$fecha = date("Y/m/d H:i:s");

$query = "INSERT INTO DistintivoCliente (dominio,id_distintivo,valor,operador,fecha) 
				VALUES ('$domain','$badge_id','$value','$operador','$fecha') 
				ON DUPLICATE KEY UPDATE valor='$value', operador='$operador', fecha='$fecha'";
if($SACManager->execQuery($query)){
	exit(json_encode(array("success" => true, "message"=> "Badge $badge_id seteado a $value ($domain)", "motivo"=>"$operador lo asignó manualmente el día $fecha")));
}
else{
	exit(json_encode(array("success" => false, "message"=> "Error al actualizar badge $badge_id a valor $value ($domain)<br>$query")));
}

?>