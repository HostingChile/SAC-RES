<?php

require(__DIR__."/../db/SACManager.class.php");

if(!isset($_POST["typification_ids"]) || !isset($_POST["detail"]) || !isset($_POST["operator"])){
	exit(json_encode(array("success" => false, "message" => "No se recibieron los parámetros correctos")));
}

$typification_ids = $_POST["typification_ids"];
$operador = addslashes($_POST["operator"]);
$detail = addslashes($_POST["detail"]);
$today = date("Y-m-d H:i:s");

$SACManager = SACManager::singleton();

//create a new RevisionTipificacion
$query = "INSERT INTO RevisionTipificacion (operador, fecha, comentario) VALUES ('$operador', '$today', '$detail')";
if( ! $SACManager->execQuery($query) ){
	exit(json_encode(array("success" => false, "message" => "Error con la BD: ".mysql_error())));
}
$revision_typification_id = $SACManager->lastInsertId();

$typification_ids_string = implode(",", $typification_ids);
$query = "UPDATE Tipificacion SET id_revision = '$revision_typification_id' WHERE id IN ($typification_ids_string)";
if( ! $SACManager->execQuery($query) ){
	exit(json_encode(array("success" => false, "message" => "Error con la BD: ".mysql_error())));
}
exit(json_encode(array("success" => true)));

?>