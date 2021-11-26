<?php require 'include/php/clases/DBManager.class.php'; ?>

<?php

$departamento = $_SESSION["Departamento"];
$areas_aceptadas=array();
if(in_array("Gerencia", $departamento) || in_array("Jefe de Area", $departamento) || in_array("Desarrollo", $departamento))
	$areas_aceptadas = array(1,2,3);
if(in_array("Operaciones", $departamento))
	$areas_aceptadas[] = 2;
if(in_array("Soporte", $departamento))
	$areas_aceptadas[] = 1; 

//print_r($areas_aceptadas);
$base_url = "http://sistemas.hosting.cl/SAC/indicadores";
// $base_url = "http://localhost/desarrollo/SAC/trunk/indicadores";

$nombre_periodo_bono = "Trimestre";
$periodo_bono = 3;//meses
$periodos_bono_al_ano = 4;


date_default_timezone_set("America/Santiago");
$mes_actual = date("m");
$ano_actual = date("Y");

if($mes_actual == 1){
	$mes_actual = 12;
	$ano_actual = $ano_actual -1;
}
else{
	$mes_actual = $mes_actual -1;
}

$errors = array();
$id_area = isset($_GET["area"]) ? $_GET["area"] : $areas_aceptadas[0];
$ano = isset($_GET["ano"]) ? $_GET["ano"] : $ano_actual;
$periodo = isset($_GET["periodo"]) ? $_GET["periodo"]  :ceil($mes_actual/$periodo_bono);
if($periodo < 1 || $periodo > $periodos_bono_al_ano)
	$errors[] = "$nombre_periodo_bono inválido";

if(sizeof($errors)>0){
	echo "<h2>Error:</h2>";
	foreach ($errors as $error) {
		echo $error."<br>";
	}
	exit(0);
}

if(sizeof($areas_aceptadas) == 0)
	exit("Error: El usuario no tiene permiso para ver ningún departamento");
if(!in_array($id_area, $areas_aceptadas))
	exit("No esta autorizado para ingresar a esta area");

//si es que tiene mas de un area aceptada, muestro los tabs
if(sizeof($areas_aceptadas)>1){
	echo '<ul id="btns_areas_empresa" class="nav nav-pills">';
	$database = DBManager::singleton();
	$query = "SELECT * FROM areas";
	$result_areas = $database->execQuery($query);
	while (list($id,$area) = mysqli_fetch_array($result_areas, MYSQLI_NUM)){
		if(in_array($id, $areas_aceptadas)){
			$active_str = $id == $id_area?'class="active"':'';
			echo "<li role='presentation' $active_str>
					<a href='$base_url/index.php?periodo=$periodo&ano=$ano&area=$id'>".ucfirst($area)."</a></li>";
		}
	}
	echo "</ul>";
}

?>

