<?php

set_time_limit(0);
// ignore_user_abort(true);
ini_set('max_execution_time', 0);

error_reporting(E_ALL & ~E_NOTICE);

require 'include/php/helpers.php';
require 'include/php/clases/MailManager.class.php';
require 'include/php/clases/DBManager.class.php';
require 'include/php/clases/SACManager.class.php';
require 'include/php/clases/WHMCSManager.class.php';
require 'include/php/clases/AsteriskManager.class.php';
require 'include/php/clases/RackhostManager.class.php';
require 'include/php/clases/ISCOLManager.class.php';
require 'include/php/clases/Operador.class.php';
require 'include/php/clases/Calculador.class.php';
require 'include/php/clases/Indicador.class.php';

if (ob_get_level() == 0) ob_start();

debug("<h1 class='debug_mode'>Debug Mode ON</h1>",false);

$mes = isset($_GET["mes"]) ? $_GET["mes"] : 4;
$año = isset($_GET["ano"]) ? $_GET["ano"] : 2015;
$id_area = isset($_GET["area"]) ? $_GET["area"] : 1;
$operador_calculado = isset($_GET["operador"]) ? $_GET["operador"] : "";

echo "<h1>$mes - $año</h1>";

//mensaje para mostrar el progreso
echo "<div id='progress_msg'></div>";
echo "<div id='progress_sub_msg'></div>";

//Variables a utilizar
$operadores = array();
$indicadores = array();

//Crear Mail Manager y DB con los datos de conexion desde config.php
$mailManager = MailManager::singleton();
$database = DBManager::singleton();

$operator_condition = strlen($operador_calculado)>0?" AND id_operador = $operador_calculado":"";
$operator_condition_2 = strlen($operador_calculado)>0?" AND id = $operador_calculado":"";

//borro el historial de indicadores para ese mes,año y area
$query = "SELECT * FROM indicadores WHERE id_area = $id_area AND activo = 1";
$result = $database->execQuery($query);
while ($indic = mysqli_fetch_array($result, MYSQLI_ASSOC))
{
	$id_indicador = $indic["id"];
	$query  = "DELETE FROM historial_indicadores WHERE id_indicador = $id_indicador AND mes = $mes AND año = $año $operator_condition"; 
	echo $query."<br>";
	$database->execQuery($query);
	echo "Fueron eliminados ".$database->getLastAffectedRows()." registros<br>";
}

//borro el historial de ponderadores para ese mes,año y medios del area
$query = "SELECT * FROM medios WHERE id_area = $id_area";
$result = $database->execQuery($query);
while ($medio = mysqli_fetch_array($result, MYSQLI_ASSOC))
{
	$id_medio = $medio["id"];
	$nombre_medio = $medio["nombre"];
	$query  = "DELETE FROM historial_ponderadores WHERE medio = '$nombre_medio' AND mes = $mes AND año = $año $operator_condition"; 
	echo $query."<br>";
	$database->execQuery($query);
}

//borro el historial de notas finales para ese mes y año
$query = "SELECT * FROM operadores WHERE id_area = $id_area $operator_condition_2";
$result = $database->execQuery($query);
while ($operador = mysqli_fetch_array($result, MYSQLI_ASSOC))
{
	$id_operador = $operador["id"];
	$nombre_medio = $medio["nombre"];
	$query  = "DELETE FROM notas_finales WHERE id_operador = $id_operador AND mes = $mes AND año = $año"; 
	echo "$query<br>";
	$database->execQuery($query);
}


debug("<hr>Area con id($id_area)<hr>",false);
$operadores = array();
$indicadores = array();

/* Obtener los operadores del area llenar el arreglo de operadores*/
$query = "SELECT id,id_area,nombre,apellido,hora_trabajo,pais,jefe_de_area FROM operadores WHERE activo = 1 AND id_area = $id_area $operator_condition_2 ORDER BY nombre";
echo $query;
$result = $database->execQuery($query);
while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC))
	$operadores[] = new Operador($row);
mysqli_free_result($result);

/* Obtener los indicadores del area. Incluir el archivo class.php de cada uno y llenar el arreglo de indicadores*/
$query = "SELECT I.*, A.nombre as nombre_area FROM indicadores I
LEFT JOIN areas A ON I.id_area = A.id
WHERE activo = 1 AND id_area = $id_area ORDER BY medio DESC";
$result = $database->execQuery($query);
while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)){
	$nombre = str_replace(' ', '_', strtolower($row["nombre"]));
	$nombre_area = $row["nombre_area"];
	$include_name = "include/php/clases/indicadores/$nombre_area/$nombre.class.php";
	if(file_exists($include_name)){
		require "$include_name";
		if(class_exists($nombre))
			$indicadores[] = new $nombre($row);
		else
			exit("No existe la clase <b>$nombre</b><br>");
	}
	else
		exit("No existe el archivo <b>$include_name</b><br>");
}

mysqli_free_result($result);
	
/*Calcular los indicadores para los meses que correspondan, y mostrar los ultimos meses en pantalla.*/
$medios = array('ticket','telefono','chat');
$tabla_resultados = "<table style='text-align:center'><tr><th>Operador</th>";
foreach ($indicadores as $indicador)
	$tabla_resultados .= "<th>".$indicador->getInfo()["nombre"]."</th>";
foreach ($medios as $medio)
	$tabla_resultados.="<th>$medio</th>";
$tabla_resultados.="<th>Final</th></tr>";
foreach ($operadores as $operador) 
{
	$calculador = new Calculador($operador, $indicadores);
	
	debug("<hr><h3>{$operador->nombreCompleto()} ({$operador->getId()})</h3>",false);
	//El calculador guarda los datos en la BBDD
	$nota_final = $calculador->calcularCalificacionBono($mes,$año);
	$resultados = $calculador->getResults();
	$resultados_indicadores = $resultados["indicadores"];
	$resultados_medios = $resultados["medios"];
	$resultado_final = round($resultados["final"],1);
	$ponderadores = $calculador->getPonderadores();

	$tabla_resultados.="<tr><td>".$operador->nombreCompleto()."</td>";
	foreach ($indicadores as $indicador) {
		$info = $indicador->getInfo();
		$id_indicador = $info["id"];
		if(strlen($resultados_indicadores["$id_indicador"])>0)
			$tabla_resultados.="<td title='".$indicador->getValorIndicador()."'>
				{$resultados_indicadores["$id_indicador"]}</td>";
		else
			$tabla_resultados.="<td title='".$indicador->ultimoError()."'>---</td>";
	}
	echo "<pre>";
	print_r($resultados_medios);
	echo "</pre>";
	echo "<pre>";
	print_r($ponderadores);
	echo "</pre>";
	foreach ($medios as $medio)
		$tabla_resultados.="<td>{$resultados_medios["$medio"]} (".round($ponderadores["$medio"]*100,1)."%)</td>";
	$tabla_resultados.="<td>$resultado_final</td></tr>";
}
$tabla_resultados .= "</table>";
echo $tabla_resultados;
/*Dejo de mostrar el mensaje de progreso*/
showMessage("");
ob_end_flush();



?>