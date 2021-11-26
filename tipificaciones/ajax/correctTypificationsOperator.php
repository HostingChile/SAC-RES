<?php
error_reporting(0);

try{
	//conexion a la BD
	$upOne = realpath(__DIR__ . '/..');
	require $upOne.'/DB/SACManager.class.php';
	$SACManager = SACManager::singleton();

	$queries = array(
		'UPDATE Tipificacion SET operador = "Daniel Román" WHERE operador = "Daniel Rom?n";',
		'UPDATE Tipificacion SET operador = "Lina Quiñones" WHERE operador = "Lina Qui?ones";',
		'UPDATE Tipificacion SET operador = "Elías Gonzalez" WHERE operador = "El?as Gonzalez";',
		'UPDATE Tipificacion SET operador = "María José Gonzalez" WHERE operador = "Mar?a Jos? Gonzalez";',
		'UPDATE Tipificacion SET operador = "Nicolás Portales" WHERE operador = "Nicol?s Portales";',
	);

	$affected_rows = 0;

	foreach ($queries as $query) {
		$SACManager->execQuery($query);
		$affected_rows += $SACManager->affected_rows();
	}

	exit(json_encode(array("success" => true, "affected_records" => $affected_rows)));
}
catch(Exception $e){
	exit(json_encode(array("success" => false, "message" => $e->getMessage())));
}


?>