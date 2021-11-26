<?php

$tipif = $_POST["detalle_tipificacion"];

//valido la info
$errors = array();
if(trim($tipif["operador"]) == ""){
	$errors[] = "No hay operador logueado";
}
if(trim($tipif["id_tipocontacto"]) == ""){
	$errors[] = "Seleccione un medio de contacto";
}
if(trim($tipif["nombre_cliente"]) == ""){
	$errors[] = "Ingrese el nombre del cliente";
}
if($tipif["id_marca"] === 'undefined'){
	$errors[] = "Ingrese la marca de hosting del cliente";
}
if(trim($tipif["dominio"]) == "" && $tipif["no_cliente"] === "false"){
	$errors[] = "Ingrese el dominio del cliente";
}
if(trim($tipif["problema_solucionado"]) == ""){
	$errors[] = "Ingrese si el problema fue solucionado o no";
}
if(trim($tipif["detalle"]) == ""){
	$errors[] = "Ingrese el detalle de la atención";
}
if(trim($tipif["id_categoria"]) == ""){
	$errors[] = "Seleccione una categoría";
}
if(trim($tipif["intervino_operaciones"]) == ""){
	$errors[] = "Ingrese si operaciones intervino en la solución de este problema";
}

if(sizeof($errors)>0){
	exit(json_encode(array("success" => false, "message" => "".implode("\n", $errors))));
}

//conexion a la BD
error_reporting(0);
$upOne = realpath(__DIR__ . '/..');
require $upOne.'/DB/SACManager.class.php';
$SACManager = SACManager::singleton();

date_default_timezone_set('America/Santiago');
$fecha_actual = date('Y/m/d H:i:s');
//agrego la tipificacion
$tipif["operador"] = addslashes($tipif["operador"]);
$tipif["nombre_cliente"] = addslashes($tipif["nombre_cliente"]);
$tipif["dominio"] = addslashes($tipif["dominio"]);
$tipif["detalle"] = addslashes($tipif["detalle"]);
$tipif["nueva_subcategoria"] = addslashes( trim(  strtolower($tipif["nueva_subcategoria"] ) ) );
$tipif["nombre_plan"] = addslashes($tipif["nombre_plan"]);
$tipif["problema_solucionado"] = $tipif["problema_solucionado"] == "si" ? 1 : 0;
$tipif["intervino_operaciones"] = $tipif["intervino_operaciones"] == "si" ? 1 : 0;

$query_tipificacion = "INSERT INTO Tipificacion (
			id_categoria,
			id_tipocontacto,
			id_marca,
			operador,
			nombre_cliente,
			fecha,
			dominio,
			detalle,
			ip_servidor, 
			nueva_subcategoria, 
			plan_cliente, 
			problema_solucionado,
			intervino_operaciones
		) 
		VALUES (
			'{$tipif["id_categoria"]}',
			'{$tipif["id_tipocontacto"]}',
			'{$tipif["id_marca"]}',
			'{$tipif["operador"]}',
			'{$tipif["nombre_cliente"]}',
			'$fecha_actual',
			'{$tipif["dominio"]}',
			'{$tipif["detalle"]}',
			'{$tipif["ip_servidor"]}',
			'{$tipif["nueva_subcategoria"]}',
			'{$tipif["nombre_plan"]}',
			'{$tipif["problema_solucionado"]}',"
			. $tipif["intervino_operaciones"] . "
		)";


if($SACManager->execQuery($query_tipificacion)){
	exit(json_encode(array("success" => true, "message"=> "Tipificación agregada exitosamente")));
}
else{
	exit(json_encode(array("success" => false, "message"=> "Error al tipificar:<br>$query_tipificacion")));
}

?>
