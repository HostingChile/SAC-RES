<?php

if(!isset($_GET["date_from"]) || !isset($_GET["date_to"]) || !isset($_GET["operator"])){
	exit(json_encode(array("success" => false, "message"=> "Parámetros inválidos")));
}

$date_from = $_GET["date_from"];
$date_to = $_GET["date_to"];
$operator = $_GET["operator"];
$omitted_brands = isset($_GET["omitted_brands"]) ? explode(',', $_GET["omitted_brands"]) : array();
$omitted_days = isset($_GET["omitted_days"]) ? explode(',', $_GET["omitted_days"]) : array(); //this brands should be omitted only in chats, not phone calls

//conexion a la BD
error_reporting(0);
$upOne = realpath(__DIR__ . '/..');
require $upOne.'/DB/SACManager.class.php';
$SACManager = SACManager::singleton();

//if especial para tener en cuenta que las llamadas se empezaron a guardar desde 2017-04-26
$extra_if = "";
if($date_from == "2017-04-01"){
	$extra_if = " AND (T.fecha >= '2017-04-26' AND T.id_tipocontacto != 1 OR T.id_tipocontacto = 1) ";
}


//obtengo las tipifciaciones
$query_tipificacion = "
	SELECT
		SUM(IF(T.id_tipocontacto = 1, 1, 0)) as chats,
		SUM(IF(T.id_tipocontacto != 1, 1, 0)) as calls,
		SUM(IF( (CT.nombre = 'Contacto Fallido' OR CT.nombre = 'Transferencia a Central Telefónica') AND id_tipocontacto != 1, 1, 0)) as phone_failed_contacts
	FROM Tipificacion T 
	LEFT JOIN Compania C ON T.id_marca = C.id
	LEFT JOIN CategoriaTipificacion CT ON T.id_categoria = CT.id
	WHERE 
		DATE(T.fecha) >= '$date_from' AND 
		DATE(T.fecha) <= '$date_to' AND 
		T.operador = '$operator' 
		$extra_if
";
foreach ($omitted_brands as $omitted_brand) {
	$query_tipificacion .= "  AND ( C.nombre != '$omitted_brand' OR T.id_tipocontacto != 1) ";
}
foreach ($omitted_days as $omitted_day) {
	$query_tipificacion .= "  AND DATE(T.fecha) != '$omitted_day' ";
}


$result = $SACManager->execQuery($query_tipificacion);
if($result === false){
	exit(json_encode(array("success" => false, "message"=> "Error al obtener tipificaciones:<br>$query_tipificacion")));
}

$typifications = array();
foreach ($result as $db_typification) {
	$typifications = $db_typification;
}


exit(json_encode(array("success" => true, "typifications"=> $typifications)));

?>