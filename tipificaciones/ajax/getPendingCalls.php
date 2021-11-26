<?php
error_reporting(0);

//conexion a la BD
$upOne = realpath(__DIR__ . '/..');
require $upOne.'/DB/SACManager.class.php';
$SACManager = SACManager::singleton();

$llamadas_pendientes = getPendingCalls();

if(sizeof($llamadas_pendientes) == 0)
	exit(json_encode(array("success" => false, "message" => "No hay llamadas pendientes")));

exit(json_encode(array("success" => true, "llamadas_pendientes" => $llamadas_pendientes)));

/*Recursiva: retorna un arreglo ascoiativo id, title y children en caso que corresponda*/
function getPendingCalls()
{
	$query = "SELECT * FROM ClientesPorLlamar WHERE status = 0 ORDER BY fecha_solicitud ASC";

	$SACManager = SACManager::singleton();
	$pending_calls = $SACManager->execQuery($query);

	$all_pending_calls = array();
	
	foreach ($pending_calls as $pending_call) {
		$curr_call = array();
		$curr_call["id"] = $pending_call["id"];
		$curr_call["nombre"] = $pending_call["nombre"];
		$curr_call["telefono"] = $pending_call["telefono"];
		$curr_call["fecha_solicitud"] = $pending_call["fecha_solicitud"];
		$curr_call["compañia"] = $pending_call["compañia"];
		$all_pending_calls[] = $curr_call;
	}

	return $all_pending_calls;

}


?>