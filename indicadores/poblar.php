<?php
require 'include/php/clases/DBManager.class.php';
error_reporting(E_ALL & ~E_NOTICE);

poblarTurnos();

function poblarTurnos()
{
	// poblarTurnosSoporte();
	poblarTurnosOperaciones(); 
}

function poblarTurnosSoporte(){
	$db = DBManager::singleton();
	//borro los registros de turnos
	$query = "DELETE FROM turnos WHERE medio <= 3";
	$db->execQuery($query);
	
	//borro los registros de colaciones
	$query = "DELETE FROM colaciones WHERE 1";
	$db->execQuery($query);

	//borro los dias de licencia/vacaciones
	$query = "DELETE FROM dias_invalidos WHERE id_area = 1";
	$db->execQuery($query);

	$query = "INSERT INTO turnos ( `id_operador`, `medio`, `marca`, `inicio`,`fin`, `info_adicional`) VALUES ";
	$turnos_semana = "";

	$query_colaciones = "INSERT INTO colaciones (`id_operador`, `inicio`, `fin`) VALUES ";
	$turnos_colaciones = "";

	date_default_timezone_set('America/Santiago');

	$date = '2017-01-01';
	$end_date = '2017-03-31';

	//turnos de semana normales
	$turnos_fechatope = array();

	$turnos_comunes = "
		( '2', '1', 'Todas', '##FECHAINICIO## 9:00', '##FECHAFIN0## 18:00',''),

		( '2', '3', 'Todas', '##FECHAINICIO## 9:00', '##FECHAFIN0## 18:00',''),
		( '2', '2', 'Todas', '##FECHAINICIO## 9:00', '##FECHAFIN0## 18:00',''),
		( '16', '1', 'Todas', '##FECHAINICIO## 8:30', '##FECHAFIN0## 17:30',''),

		( '16', '3', 'Todas', '##FECHAINICIO## 8:30', '##FECHAFIN0## 17:30',''),
		( '16', '2', 'Todas', '##FECHAINICIO## 8:30', '##FECHAFIN0## 17:30',''),

		( '33', '1', 'Todas', '##FECHAINICIO## 9:00', '##FECHAFIN0## 18:00',''),

		( '15', '2', 'Todas', '##FECHAINICIO## 9:00', '##FECHAFIN0## 17:00',''),
		( '11', '1', 'Hosting', '##FECHAINICIO## 18:00', '##FECHAFIN0## 21:00',''),
		( '11', '1', 'PlanetaPeru', '##FECHAINICIO## 18:00', '##FECHAFIN0## 21:00',''),
		( '11', '2', 'Todas', '##FECHAINICIO## 18:00', '##FECHAFIN0## 21:00',''),
		( '11', '3', 'Hosting', '##FECHAINICIO## 18:00', '##FECHAFIN0## 21:00',''),
		( '11', '3', 'PlanetaPeru', '##FECHAINICIO## 18:00', '##FECHAFIN0## 21:00',''),
		( '11', '3', 'Todas', '##FECHAINICIO## 9:00', '##FECHAFIN0## 18:00',''),
		( '41', '1', 'Todas', '##FECHAINICIO## 8:30', '##FECHAFIN0## 17:30',''),

		( '41', '3', 'Todas', '##FECHAINICIO## 8:30', '##FECHAFIN0## 17:30',''),
		( '41', '2', 'Todas', '##FECHAINICIO## 8:30', '##FECHAFIN0## 17:30',''),
		( '42', '3', 'Todas', '##FECHAINICIO## 9:00', '##FECHAFIN0## 18:00',''),
		( '46', '3', 'Todas', '##FECHAINICIO## 9:00', '##FECHAFIN0## 18:00',''),

		( '35', '2', 'Todas', '##FECHAINICIO## 9:00', '##FECHAFIN0## 18:00',''),";

	$turnos_fechatope["2017-03-31"] = "$turnos_comunes";

	$colaciones_fechatope = array();
	$colaciones_comunes = "
		( '2', '##FECHAINICIO## 15:00', '##FECHAFIN0## 16:00'),
		( '16', '##FECHAINICIO## 14:00', '##FECHAFIN0## 15:00'),
		( '33', '##FECHAINICIO## 14:00', '##FECHAFIN0## 15:00'),
		( '15', '##FECHAINICIO## 15:00', '##FECHAFIN0## 16:00'),
		( '41', '##FECHAINICIO## 14:00', '##FECHAFIN0## 15:00'),
		( '42', '##FECHAINICIO## 14:00', '##FECHAFIN0## 15:00'),
		( '46', '##FECHAINICIO## 14:00', '##FECHAFIN0## 15:00'),
		( '35', '##FECHAINICIO## 15:00', '##FECHAFIN0## 16:00'),";
	$colaciones_fechatope["2017-03-31"] = "$colaciones_comunes";

	//turnos mineros
	$minero_mj_1 = "
			( '37', '1', 'Todas', '##FECHAINICIO## 8:00', '##FECHAFIN0## 9:00',''),
			( '37', '3', 'Todas', '##FECHAINICIO## 8:00', '##FECHAFIN0## 18:00',''),
			";

	$minero_mj_2 = "
			( '37', '1', 'Todas', '##FECHAINICIO## 11:00', '##FECHAFIN0## 21:00',''),
			( '37', '3', 'Todas', '##FECHAINICIO## 11:00', '##FECHAFIN0## 21:00',''),
			";

	$colacion_mj_1 = "( '37', '##FECHAINICIO## 14:00', '##FECHAFIN0## 15:00'),
	";
	$colacion_mj_2 = "( '37', '##FECHAINICIO## 14:00', '##FECHAFIN0## 15:00'),
	";


	$minero_fechatope = array();
	$minero_colacionn = array();
	
	$minero_fechatope["2017-01-30"] = "";
	$minero_colacionn["2017-01-30"] = "";
	$minero_fechatope["2017-01-31"] = "$minero_mj_1";
	$minero_colacionn["2017-01-31"] = "$colacion_mj_1";
	$minero_fechatope["2017-02-03"] = "";
	$minero_colacionn["2017-02-03"] = "";
	$minero_fechatope["2017-02-04"] = "$minero_mj_1";
	$minero_colacionn["2017-02-04"] = "$colacion_mj_1";
	$minero_fechatope["2017-02-05"] = "#minero_mj_2";
	$minero_colacionn["2017-02-05"] = "$colacion_mj_2";
	$minero_fechatope["2017-02-09"] = "$minero_mj_1";
	$minero_colacionn["2017-02-09"] = "$colacion_mj_1";
	$minero_fechatope["2017-02-10"] = "";
	$minero_colacionn["2017-02-10"] = "";
	$minero_fechatope["2017-02-11"] = "$minero_mj_1";
	$minero_colacionn["2017-02-11"] = "$colacion_mj_1";
	$minero_fechatope["2017-02-12"] = "$minero_mj_2";
	$minero_colacionn["2017-02-12"] = "$colacion_mj_2";
	$minero_fechatope["2017-02-14"] = "$minero_mj_1";
	$minero_colacionn["2017-02-14"] = "$colacion_mj_1";
	$minero_fechatope["2017-03-05"] = "";
	$minero_colacionn["2017-03-05"] = "";
	$minero_fechatope["2017-03-08"] = "$minero_mj_1";
	$minero_colacionn["2017-03-08"] = "$colacion_mj_1";
	$minero_fechatope["2017-03-10"] = "";
	$minero_colacionn["2017-03-10"] = "";
	$minero_fechatope["2017-03-11"] = "$minero_mj_1";
	$minero_colacionn["2017-03-11"] = "$colacion_mj_1";
	$minero_fechatope["2017-03-12"] = "$minero_mj_2";
	$minero_colacionn["2017-03-12"] = "$colacion_mj_2";
	$minero_fechatope["2017-03-14"] = "$minero_mj_1";
	$minero_colacionn["2017-03-14"] = "$colacion_mj_1";
	$minero_fechatope["2017-03-17"] = "";
	$minero_colacionn["2017-03-17"] = "";
	$minero_fechatope["2017-03-18"] = "$minero_mj_1";
	$minero_colacionn["2017-03-18"] = "$colacion_mj_1";
	$minero_fechatope["2017-03-19"] = "$minero_mj_2";
	$minero_colacionn["2017-03-19"] = "$colacion_mj_2";
	$minero_fechatope["2017-03-20"] = "$minero_mj_1";
	$minero_colacionn["2017-03-20"] = "$colacion_mj_1";
	$minero_fechatope["2017-03-21"] = "";
	$minero_colacionn["2017-03-21"] = "";
	$minero_fechatope["2017-03-22"] = "$minero_mj_1";
	$minero_colacionn["2017-03-22"] = "$colacion_mj_1";
	$minero_fechatope["2017-03-26"] = "";
	$minero_colacionn["2017-03-26"] = "";
	$minero_fechatope["2017-03-29"] = "$minero_mj_1";
	$minero_colacionn["2017-03-29"] = "$colacion_mj_1";
	$minero_fechatope["2017-03-31"] = "";
	$minero_colacionn["2017-03-31"] = "";


	while (strtotime($date) <= strtotime($end_date)) 
	{
		//hago los turnos normales de semana
		//segun la fecha veo que query correpsonde
		foreach ($turnos_fechatope as $fecha_tope => $turnos) {
			if(strtotime($date) <= strtotime($fecha_tope))
			{
				echo "$date - Fecha tope: $fecha_tope<br>";
				$turnos_semana = $turnos;
				$turnos_colaciones = $colaciones_fechatope[$fecha_tope];
				break;
			}
		}

		$nextday = strtotime("+1 day", strtotime($date));
		$nextday = date ("Y-m-d", $nextday);

		$dia = date('N',strtotime($date));
		if($dia != 6 && $dia != 7)
		{
			//hago los reemplazon en la query de los turnos
			$tmp = str_replace("##FECHAINICIO##", $date, $turnos_semana);
			$tmp = str_replace("##FECHAFIN0##", $date, $tmp);	
			$tmp = str_replace("##FECHAFIN1##", $nextday, $tmp);
			$query.= $tmp;

			//hago los reemplazos en la query de las colaciones
			$tmp = str_replace("##FECHAINICIO##", $date, $turnos_colaciones);
			$tmp = str_replace("##FECHAFIN0##", $date, $tmp);	
			$tmp = str_replace("##FECHAFIN1##", $nextday, $tmp);
			$query_colaciones.= $tmp;
		}

		//hago los turnos mineros
		foreach ($minero_fechatope as $fecha_tope => $turnos) {
			if(strtotime($date) <= strtotime($fecha_tope))
			{
				echo "MINERO: $date - Fecha tope: $fecha_tope<br>";
				$turnos_semana = $turnos;
				$turnos_colaciones = $minero_colacionn[$fecha_tope];
				break;
			}
		}
		//hago los reemplazon en la query de los turnos
		$tmp = str_replace("##FECHAINICIO##", $date, $turnos_semana);
		$tmp = str_replace("##FECHAFIN0##", $date, $tmp);	
		$tmp = str_replace("##FECHAFIN1##", $nextday, $tmp);
		$query.= $tmp;

		//hago los reemplazos en la query de las colaciones
		$tmp = str_replace("##FECHAINICIO##", $date, $turnos_colaciones);
		$tmp = str_replace("##FECHAFIN0##", $date, $tmp);	
		$tmp = str_replace("##FECHAFIN1##", $nextday, $tmp);
		$query_colaciones.= $tmp;
		
		if($dia != 6 && $dia != 7){
			//hago los reemplazon en la query de los turnos
			$tmp = str_replace("##FECHAINICIO##", $date, $turnos_semana);
			$tmp = str_replace("##FECHAFIN0##", $date, $tmp);	
			$tmp = str_replace("##FECHAFIN1##", $nextday, $tmp);
			$query.= $tmp;
		}

		$date = $nextday;
	}

	$query = substr($query, 0,-1);
	echo "<br>TURNOS SEMANA<br>$query<br>";
	if(!$db->execQuery($query)){
		echo "<br><b>ERROR CON QUERY</b><br>";
	}

	$query_colaciones = substr($query_colaciones, 0,-1);
	echo "<hr><br>COLACIONES<br>$query_colaciones<br>";
	if(!$db->execQuery($query_colaciones)){
		echo "<br><b>ERROR CON QUERY</b><br><hr>";
	}

	//borro los turnos de feriados por pais
	// echo "<br>BORRAR TURNOS DIAS FERIADOS<br>";
	// $delete_query = "DELETE FROM turnos WHERE inicio LIKE '2015-08-06 %' AND id_operador IN
	// 					 (SELECT id FROM operadores WHERE pais = 'Bolivia')";
	// echo $delete_query."<br>";
	// $db->execQuery($delete_query);

	$delete_query = "DELETE FROM turnos WHERE 
			(inicio LIKE '2017-01-01 %' OR inicio LIKE '2017-03-25 %')
			AND id_operador IN (SELECT id FROM operadores WHERE pais = 'Chile')";
	echo "<hr><br>DELETE TURNOS FERIADOS<br>$delete_query<br>";
	if(!$db->execQuery($delete_query)){
		echo "<br><b>ERROR CON QUERY</b><br><hr>";
	}

	//agrego los turnos de gente que se quedo en los feriados
	// echo "<br>TURNOS EXTRA DIAS FERIADOS<br>";
	// $feriados_query = "
	// 	INSERT INTO turnos ( `id_operador`, `medio`, `marca`, `inicio`, `fin`, `info_adicional`) VALUES
	// 			( '12', '1', 'Todas', ' 2015-10-12 10:00', '2015-10-12 18:00',''),
	// 			( '12', '3', 'Todas', ' 2015-10-12 10:00', '2015-10-12 18:00',''),
	// 			( '12', '2', 'Todas', ' 2015-10-12 10:00', '2015-10-12 18:00',''),
	// 			( '12', '50', '', ' 2015-10-12 15:00', '2015-10-12 16:00',''),
	// 			( '12', '1', 'Todas', ' 2015-12-08 10:00', '2015-12-08 18:00',''),
	// 			( '12', '3', 'Todas', ' 2015-12-08 10:00', '2015-12-08 18:00',''),
	// 			( '12', '2', 'Todas', ' 2015-12-08 10:00', '2015-12-08 18:00',''),
	// 			( '12', '50', '', ' 2015-12-08 15:00', '2015-12-08 16:00','');
	// 	";
	// echo $feriados_query."<br>";
	// $db->execQuery($feriados_query);

	//agrego los turnos extra DE NOCHE
	// $query = "
	// 	INSERT INTO turnos ( `id_operador`, `medio`, `marca`, `inicio`, `fin`, `info_adicional`) VALUES";
	// echo "<br>TURNOS EXTRA NOCHE<br>$query<br>";
	// $db->execQuery($query);

	//agrego los turnos extra de FDS
	$query = "
				INSERT INTO turnos ( `id_operador`, `medio`, `marca`, `inicio`, `fin`, `info_adicional`) VALUES
				( '100', '3', 'Todas', ' 2017-03-04 10:00', '2017-03-04 18:00','');";

	echo "<hr><br>TURNOS FIN DE SEMANA<br>$query<br>";
	if(!$db->execQuery($query)){
		echo "<br><b>ERROR CON QUERY</b><br>";
	}

	//agrego los dias de licencia/vacaciones etc
	//EJ: CAIDA DE SERVIDORES: ('2016-03-01 00:00:00', '2016-03-01 23:59:59', '-1', '1', '-1', 'Todas', 'Dia omitido por caida masiva de servidores') 
	$query="
		INSERT INTO dias_invalidos (`inicio`, `fin`, `id_operador`, `id_area`, `id_medio`, `marca`, `comentario`) VALUES 
		('2017-03-28 00:00:00', '2017-04-13 23:59:59', '35', '1', '-1', 'Todas', 'Vacaciones'), 
		('2017-01-01 00:00:00', '2017-01-31 23:59:59', '33', '1', '-1', 'Todas', 'Vacaciones'), 
		('2017-03-21 00:00:00', '2017-03-21 23:59:59', '15', '1', '-1', 'Todas', 'Administrativo'), 
		('2017-03-15 00:00:00', '2017-03-15 23:59:59', '15', '1', '-1', 'Todas', 'Administrativo'), 
		('2017-03-09 00:00:00', '2017-03-09 23:59:59', '2', '1', '-1', 'Todas', 'Ausencia'), 
		('2017-03-03 00:00:00', '2017-02-22 23:59:59', '16', '1', '-1', 'Todas', 'Administrativo'), 
		('2017-02-17 00:00:00', '2017-02-17 23:59:59', '41', '1', '-1', 'Todas', 'Administrativo'), 
		('2017-03-31 00:00:00', '2017-03-31 23:59:59', '41', '1', '-1', 'Todas', 'Administrativo'), 
		('2017-03-01 00:00:00', '2017-03-03 23:59:59', '41', '1', '-1', 'Todas', 'Vacaciones'), 
		('2017-02-06 00:00:00', '2017-02-17 23:59:59', '2', '1', '-1', 'Todas', 'Vacaciones'), 
		('2017-02-20 00:00:00', '2017-03-03 23:59:59', '37', '1', '-1', 'Todas', 'Vacaciones'), 
		('2017-02-06 00:00:00', '2017-02-10 23:59:59', '42', '1', '-1', 'Todas', 'Vacaciones'), 

		('2017-02-18 00:00:00', '2017-02-18 23:59:59', '-1', '1', '-1', 'Todas', 'Dia omitido por Gerardo'), 
		('2017-02-19 00:00:00', '2017-02-19 23:59:59', '-1', '1', '-1', 'Todas', 'Dia omitido por Gerardo'), 
		('2017-02-25 00:00:00', '2017-02-25 23:59:59', '-1', '1', '-1', 'Todas', 'Dia omitido por Gerardo'), 
		('2017-02-26 00:00:00', '2017-02-26 23:59:59', '-1', '1', '-1', 'Todas', 'Dia omitido por Gerardo'), 
		('2017-03-04 00:00:00', '2017-03-04 23:59:59', '-1', '1', '-1', 'Todas', 'Dia omitido por Gerardo'), 
		('2017-03-05 00:00:00', '2017-03-05 23:59:59', '-1', '1', '-1', 'Todas', 'Dia omitido por Gerardo'), 
		('2017-03-25 00:00:00', '2017-03-25 23:59:59', '-1', '1', '-1', 'Todas', 'Dia omitido por Gerardo'), 
		('2017-03-26 00:00:00', '2017-03-26 23:59:59', '-1', '1', '-1', 'Todas', 'Dia omitido por Gerardo'), 
		('2017-03-22 00:00:00', '2017-03-22 23:59:59', '-1', '1', '-1', 'Todas', 'Dia omitido por Gerardo'), 
		('2017-03-27 00:00:00', '2017-03-27 23:59:59', '-1', '1', '-1', 'Todas', 'Dia omitido por Gerardo');";

	echo "<hr><br>DIAS INVALIDOS<br>$query<br>";
	if(!$db->execQuery($query)){
		echo "<br><b>ERROR CON QUERY</b><br>";
	}
}

function poblarTurnosOperaciones(){
	$db = DBManager::singleton();
	//borro los registros de turnos
	$query = "DELETE FROM turnos WHERE medio = 4";
	$db->execQuery($query);

	//borro los registros de dias_invalidos
	$query = "DELETE FROM dias_invalidos WHERE id_area = 2";
	$db->execQuery($query);

	$query = "INSERT INTO turnos ( `id_operador`, `medio`, `marca`, `inicio`,`fin`, `info_adicional`) VALUES ";
	$turnos_semana = " ( '45', '4', 'Todas', '##FECHAINICIO## 08:00','##FECHAFIN0## 19:30',''),";
	$turnos_sabado  = " ( '45', '4', 'Todas', '##FECHAINICIO## 10:00','##FECHAFIN0## 18:00',''),";

	date_default_timezone_set('America/Santiago');

	$date = '2018-01-01';
	$end_date = '2019-12-31';

	while (strtotime($date) <= strtotime($end_date)) 
	{
		$nextday = strtotime("+1 day", strtotime($date));
		$nextday = date ("Y-m-d", $nextday);

		$dia = date('N',strtotime($date));
		if($dia != 6 && $dia != 7)
		{
			$tmp = str_replace("##FECHAINICIO##", $date, $turnos_semana);
			$tmp = str_replace("##FECHAFIN0##", $date, $tmp);
			$tmp = str_replace("##FECHAFIN1##", $nextday, $tmp);
			$query.= $tmp;
		}elseif ($dia == 6) {
			$tmp = str_replace("##FECHAINICIO##", $date, $turnos_sabado);
			$tmp = str_replace("##FECHAFIN0##", $date, $tmp);
			$tmp = str_replace("##FECHAFIN1##", $nextday, $tmp);
			$query.= $tmp;
		}

		$date = $nextday;
	}
	$query = substr($query, 0, -1);

	echo $query."<br><br>";
	$db->execQuery($query);
}
?>