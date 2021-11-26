<?php
require 'include/php/clases/DBManager.class.php';

poblarTurnosSoporte();

function poblarTurnos()
{
	poblarTurnosSoporte();
	poblarTurnosOperaciones();
}

function poblarTurnosSoporte()
{
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

	$date = '2015-10-01';
	$end_date = '2015-12-31';

	//turnos de semana normales
	$turnos_fechatope = array();
	$turnos_fechatope["2015-12-31"] = 
		"
		( '2', '1', 'Todas', '##FECHAINICIO## 9:00', '##FECHAFIN0## 18:00',''),
		( '2', '3', 'Todas', '##FECHAINICIO## 9:00', '##FECHAFIN0## 18:00',''),
		( '2', '2', 'Todas', '##FECHAINICIO## 9:00', '##FECHAFIN0## 18:00',''),
		( '12', '1', 'Hosting', '##FECHAINICIO## 8:00', '##FECHAFIN0## 9:00',''),
		( '12', '1', 'PlanetaPeru', '##FECHAINICIO## 8:00', '##FECHAFIN0## 9:00',''),
		( '12', '1', 'Hosting', '##FECHAINICIO## 18:00', '##FECHAFIN0## 20:00',''),
		( '12', '1', 'PlanetaPeru', '##FECHAINICIO## 18:00', '##FECHAFIN0## 20:00',''),
		( '12', '2', 'Todas', '##FECHAINICIO## 8:00', '##FECHAFIN0## 20:00',''),
		( '12', '3', 'Hosting', '##FECHAINICIO## 8:00', '##FECHAFIN0## 9:00',''),
		( '12', '3', 'PlanetaPeru', '##FECHAINICIO## 18:00', '##FECHAFIN0## 20:00',''),
		( '13', '1', 'Todas', '##FECHAINICIO## 9:00', '##FECHAFIN0## 18:00',''),
		( '14', '1', 'Todas', '##FECHAINICIO## 9:00', '##FECHAFIN0## 18:00',''),
		( '15', '1', 'Hosting', '##FECHAINICIO## 8:00', '##FECHAFIN0## 9:00',''),
		( '15', '1', 'PlanetaPeru', '##FECHAINICIO## 8:00', '##FECHAFIN0## 9:00',''),
		( '15', '2', 'Todas', '##FECHAINICIO## 8:00', '##FECHAFIN0## 20:00',''),
		( '16', '1', 'Todas', '##FECHAINICIO## 9:00', '##FECHAFIN0## 18:00',''),
		( '16', '2', 'Todas', '##FECHAINICIO## 9:00', '##FECHAFIN0## 18:00',''),
		( '16', '3', 'Todas', '##FECHAINICIO## 9:00', '##FECHAFIN0## 18:00',''),
		( '32', '3', 'Todas', '##FECHAINICIO## 9:00', '##FECHAFIN0## 18:00',''),
		( '33', '3', 'Todas', '##FECHAINICIO## 9:00', '##FECHAFIN0## 18:00',''),
		( '35', '3', 'Todas', '##FECHAINICIO## 9:00', '##FECHAFIN0## 18:00',''),";

	$colaciones_fechatope = array();
	$colaciones_fechatope["2015-12-31"] = 
		"
		( '2', '##FECHAINICIO## 14:00', '##FECHAFIN0## 15:00'),
		( '12', '##FECHAINICIO## 14:00', '##FECHAFIN0## 15:00'),
		( '13', '##FECHAINICIO## 14:00', '##FECHAFIN0## 15:00'),
		( '14', '##FECHAINICIO## 15:00', '##FECHAFIN0## 16:00'),
		( '15', '##FECHAINICIO## 15:00', '##FECHAFIN0## 16:00'),
		( '16', '##FECHAINICIO## 15:30', '##FECHAFIN0## 16:30'),
		( '32', '##FECHAINICIO## 15:30', '##FECHAFIN0## 16:30'),
		( '33', '##FECHAINICIO## 15:30', '##FECHAFIN0## 16:30'),
		( '35', '##FECHAINICIO## 14:00', '##FECHAFIN0## 15:00'),";

	//turnos mineros de bolivia
	/*$minero_juan = "
			( '6', '1', 'Hosting', '##FECHAINICIO## 18:00', '##FECHAFIN0## 20:00',''),				
			( '6', '3', 'Todas', '##FECHAINICIO## 9:00', '##FECHAFIN0## 18:00',''),";
	$colacion_juan = "( '6', '##FECHAINICIO## 14:00', '##FECHAFIN0## 15:00'),";
	$minero_luis = "
			( '3', '1', 'Hosting', '##FECHAINICIO## 18:00', '##FECHAFIN0## 20:00',''),
			( '3', '3', 'Todas', '##FECHAINICIO## 9:00', '##FECHAFIN0## 18:00',''),";
	$colacion_luis = "( '3', '##FECHAINICIO## 14:00', '##FECHAFIN0## 15:00'),";

	$minero_fechatope = array();
	$minero_colacion = array();
	// Desde Lunes 06-07-2015 a 12-07-2015	
	$minero_fechatope["2015-07-05"] = "";
	$minero_colacion["2015-07-05"] = "";
	$minero_fechatope["2015-07-12"] = "$minero_juan";
	$minero_colacion["2015-07-12"] = "$colacion_juan";
	// Desde Lunes 13-07-2015 a 19-07-2015						
	$minero_fechatope["2015-07-19"] = "$minero_luis";
	$minero_colacion["2015-07-19"] = "$colacion_luis";
	// Desde Lunes 20-07-2015 a 26-07-2015						
	$minero_fechatope["2015-07-26"] = "$minero_juan";
	$minero_colacion["2015-07-26"] = "$colacion_juan";
	// Desde Lunes 27-07-2015 a 02-08-2015						
	$minero_fechatope["2015-08-02"] = "$minero_luis";
	$minero_colacion["2015-08-02"] = "$colacion_luis";
	// Desde Lunes 03-08-2015 a 09-08-2015						
	$minero_fechatope["2015-08-09"] = "$minero_juan";
	$minero_colacion["2015-08-09"] = "$colacion_juan";
	// Desde Lunes 10-08-2015 a 16-08-2015						
	$minero_fechatope["2015-08-16"] = "$minero_luis";
	$minero_colacion["2015-08-16"] = "$colacion_luis";
	// Desde Lunes 17-08-2015 a 23-08-2015						
	$minero_fechatope["2015-08-23"] = "$minero_juan";
	$minero_colacion["2015-08-23"] = "$colacion_juan";
	// Desde Lunes 24-08-2015 a 30-08-2015						
	$minero_fechatope["2015-08-30"] = "$minero_luis";
	$minero_colacion["2015-08-30"] = "$colacion_luis";
	// Desde Lunes 31-08-2015 a 06-09-2015	
	$minero_fechatope["2015-09-06"] = "$minero_juan";
	$minero_colacion["2015-09-06"] = "$colacion_juan";
	// Desde Lunes 07-09-2015 a 13-09-2015						
	$minero_fechatope["2015-09-13"] = "$minero_luis";
	$minero_colacion["2015-09-13"] = "$colacion_luis";
	// Desde Lunes 14-09-2015 a 20-09-2015						
	$minero_fechatope["2015-09-20"] = "$minero_juan";
	$minero_colacion["2015-09-20"] = "$colacion_juan";	
	// Desde Lunes 28-09-2015 a 30-09-2015	
	$minero_fechatope["2015-09-27"] = "";
	$minero_colacion["2015-09-27"] = "";	
	$minero_fechatope["2015-10-01"] = "$minero_juan";
	$minero_colacion["2015-10-01"] = "$colacion_juan";*/

	//turnos del mauri
	$mauri_1 = "
		( '11', '1', 'Hosting', '##FECHAINICIO## 20:00', '##FECHAFIN1## 8:00',''),
		( '11', '1', 'PlanetaPeru', '##FECHAINICIO## 20:00', '##FECHAFIN## 8:00',''),
		( '11', '2', 'Todas', '##FECHAINICIO## 20:00', '##FECHAFIN1## 8:00',''),
		( '11', '3', 'Hosting', '##FECHAINICIO## 20:00', '##FECHAFIN## 8:00',''),
		( '11', '3', 'PlanetaPeru', '##FECHAINICIO## 20:00', '##FECHAFIN1## 8:00',''),";

	$mauri_fechatope = array();
	// Desde Lunes 06-07-2015 a 12-07-2015	
	$mauri_fechatope["2015-12-20"] = "$mauri_1";
	$mauri_fechatope["2015-12-31"] = "";			



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

		//hago los turnos mineros de bolivia
		/*foreach ($minero_fechatope as $fecha_tope => $turnos) {
			if(strtotime($date) <= strtotime($fecha_tope))
			{
				echo "MINERO: $date - Fecha tope: $fecha_tope<br>";
				$turnos_semana = $turnos;
				$turnos_colaciones = $minero_colacion[$fecha_tope];
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
		*/

		//ahgo el turno del mauri
		foreach ($mauri_fechatope as $fecha_tope => $turnos) {
			if(strtotime($date) <= strtotime($fecha_tope))
			{
				echo "MAURI: $date - Fecha tope: $fecha_tope<br>";
				$turnos_semana = $turnos;
				//$turnos_colaciones = $minero_colacion[$fecha_tope];
				break;
			}
		}
		//hago los reemplazon en la query de los turnos
		$tmp = str_replace("##FECHAINICIO##", $date, $turnos_semana);
		$tmp = str_replace("##FECHAFIN0##", $date, $tmp);	
		$tmp = str_replace("##FECHAFIN1##", $nextday, $tmp);
		$query.= $tmp;

		//hago los reemplazos en la query de las colaciones
		/*$tmp = str_replace("##FECHAINICIO##", $date, $turnos_colaciones);
		$tmp = str_replace("##FECHAFIN0##", $date, $tmp);	
		$tmp = str_replace("##FECHAFIN1##", $nextday, $tmp);
		$query_colaciones.= $tmp;*/

		$date = $nextday;
	}

	$query = substr($query, 0,-1);
	echo "<br>TURNOS SEMANA<br>$query<br>";
	$db->execQuery($query);

	$query_colaciones = substr($query_colaciones, 0,-1);
	echo "<hr><br>COLACIONES<br>$query_colaciones<br>";
	$db->execQuery($query_colaciones);

	//borro los turnos de feriados por pais
	echo "<br>BORRAR TURNOS DIAS FERIADOS<br>";
	$delete_query = "DELETE FROM turnos WHERE inicio LIKE '2015-08-06 %' AND id_operador IN
						 (SELECT id FROM operadores WHERE pais = 'Bolivia')";
	echo $delete_query."<br>";
	$db->execQuery($delete_query);

	$delete_query = "DELETE FROM turnos WHERE 
			(inicio LIKE '2015-10-12 %' OR inicio LIKE '2015-12-08 %' OR inicio LIKE '2015-12-24 %' OR inicio LIKE '2015-12-25 %' OR inicio LIKE '2015-12-31 %')
			AND id_operador IN (SELECT id FROM operadores WHERE pais = 'Chile')";
	echo $delete_query."<br>";
	$db->execQuery($delete_query);

	//agrego los turnos de gente que se quedo en los feriados
	echo "<br>TURNOS EXTRA DIAS FERIADOS<br>";
	$feriados_query = "
		INSERT INTO turnos ( `id_operador`, `medio`, `marca`, `inicio`, `fin`, `info_adicional`) VALUES
				( '12', '1', 'Todas', ' 2015-10-12 10:00', '2015-10-12 18:00',''),
				( '12', '3', 'Todas', ' 2015-10-12 10:00', '2015-10-12 18:00',''),
				( '12', '2', 'Todas', ' 2015-10-12 10:00', '2015-10-12 18:00',''),
				( '12', '50', '', ' 2015-10-12 15:00', '2015-10-12 16:00',''),
				( '12', '1', 'Todas', ' 2015-12-08 10:00', '2015-12-08 18:00',''),
				( '12', '3', 'Todas', ' 2015-12-08 10:00', '2015-12-08 18:00',''),
				( '12', '2', 'Todas', ' 2015-12-08 10:00', '2015-12-08 18:00',''),
				( '12', '50', '', ' 2015-12-08 15:00', '2015-12-08 16:00','');
		";
	echo $feriados_query."<br>";
	$db->execQuery($feriados_query);

	//agrego los turnos extra DE NOCHE
	$query = "
		INSERT INTO turnos ( `id_operador`, `medio`, `marca`, `inicio`, `fin`, `info_adicional`) VALUES
			( '16', '1', 'Hosting', ' 2015-10-02 21:00', '2015-10-03 8:00',''),
			( '16', '1', 'PlanetaPeru', ' 2015-10-02 21:00', '2015-10-03 8:00',''),
			( '16', '3', 'Hosting', ' 2015-10-02 21:00', '2015-10-03 8:00',''),
			( '16', '3', 'PlanetaPeru', ' 2015-10-02 21:00', '2015-10-03 8:00',''),
			( '16', '2', 'Todas', ' 2015-10-02 21:00', '2015-10-03 8:00',''),
			( '16', '1', 'Hosting', ' 2015-10-03 23:00', '2015-10-04 8:00',''),
			( '16', '1', 'PlanetaPeru', ' 2015-10-03 23:00', '2015-10-04 8:00',''),
			( '16', '3', 'Hosting', ' 2015-10-03 23:00', '2015-10-04 8:00',''),
			( '16', '3', 'PlanetaPeru', ' 2015-10-03 23:00', '2015-10-04 8:00',''),
			( '16', '2', 'Todas', ' 2015-10-03 23:00', '2015-10-04 8:00',''),
			( '15', '1', 'Hosting', ' 2015-10-04 21:30', '2015-10-05 8:00',''),
			( '15', '1', 'PlanetaPeru', ' 2015-10-04 21:30', '2015-10-05 8:00',''),
			( '15', '3', 'Hosting', ' 2015-10-04 21:30', '2015-10-05 8:00',''),
			( '15', '3', 'PlanetaPeru', ' 2015-10-04 21:30', '2015-10-05 8:00',''),
			( '15', '2', 'Todas', ' 2015-10-04 21:30', '2015-10-05 8:00',''),
			( '2', '1', 'Hosting', ' 2015-12-03 23:00', '2015-12-04 8:00',''),
			( '2', '1', 'PlanetaPeru', ' 2015-12-03 23:00', '2015-12-04 8:00',''),
			( '2', '3', 'Hosting', ' 2015-12-03 23:00', '2015-12-04 8:00',''),
			( '2', '3', 'PlanetaPeru', ' 2015-12-03 23:00', '2015-12-04 8:00',''),
			( '2', '2', 'Todas', ' 2015-12-03 23:00', '2015-12-04 8:00','');

		";
	echo "<br>TURNOS EXTRA NOCHE<br>$query<br>";
	$db->execQuery($query);

	//agrego los turnos extra de FDS
	$query = "
		INSERT INTO turnos ( `id_operador`, `medio`, `marca`, `inicio`, `fin`, `info_adicional`) VALUES
				( '33', '1', 'Todas', ' 2015-10-10 9:00', '2015-10-10 18:00',''),
				( '33', '3', 'Todas', ' 2015-10-10 9:00', '2015-10-10 18:00',''),
				( '33', '2', 'Todas', ' 2015-10-10 9:00', '2015-10-10 18:00',''),
				( '33', '50', '', ' 2015-10-10 16:08', '2015-10-10 16:57',''),
				( '33', '1', 'Todas', ' 2015-10-11 9:00', '2015-10-11 18:00',''),
				( '33', '3', 'Todas', ' 2015-10-11 9:00', '2015-10-11 18:00',''),
				( '33', '2', 'Todas', ' 2015-10-11 9:00', '2015-10-11 18:00',''),
				( '33', '50', '', ' 2015-10-11 14:16', '2015-10-11 14:47',''),
				( '33', '2', 'Todas', ' 2015-10-24 12:00', '2015-10-24 18:00',''),
				( '33', '1', 'Todas', ' 2015-10-24 12:00', '2015-10-24 18:00',''),
				( '33', '3', 'Todas', ' 2015-10-24 12:00', '2015-10-24 18:00',''),
				( '33', '1', 'Hosting', ' 2015-10-24 18:00', '2015-10-24 20:00',''),
				( '33', '1', 'PlanetaPeru', ' 2015-10-24 18:00', '2015-10-24 20:00',''),
				( '33', '3', 'Hosting', ' 2015-10-24 18:00', '2015-10-24 20:00',''),
				( '33', '3', 'PlanetaPeru', ' 2015-10-24 18:00', '2015-10-24 20:00',''),
				( '33', '2', 'Todas', ' 2015-10-24 18:00', '2015-10-24 20:00',''),
				( '33', '1', 'Todas', ' 2015-10-25 9:00', '2015-10-25 18:00',''),
				( '33', '3', 'Todas', ' 2015-10-25 9:00', '2015-10-25 18:00',''),
				( '33', '2', 'Todas', ' 2015-10-25 9:00', '2015-10-25 18:00',''),
				( '33', '1', 'Todas', ' 2015-11-07 9:00', '2015-11-07 18:00',''),
				( '33', '3', 'Todas', ' 2015-11-07 9:00', '2015-11-07 18:00',''),
				( '33', '2', 'Todas', ' 2015-11-07 9:00', '2015-11-07 18:00',''),

				( '33', '1', 'Todas', ' 2015-11-08 12:00', '2015-11-08 18:00',''),
				( '33', '3', 'Todas', ' 2015-11-08 12:00', '2015-11-08 18:00',''),
				( '33', '1', 'Hosting', ' 2015-11-08 18:00', '2015-11-08 20:00',''),
				( '33', '1', 'PlanetaPeru', ' 2015-11-08 18:00', '2015-11-08 20:00',''),
				( '33', '3', 'Hosting', ' 2015-11-08 18:00', '2015-11-08 20:00',''),
				( '33', '3', 'PlanetaPeru', ' 2015-11-08 18:00', '2015-11-08 20:00',''),
				( '33', '2', 'Todas', ' 2015-11-08 12:00', '2015-11-08 20:00',''),
				( '33', '1', 'Todas', ' 2015-11-22 9:00', '2015-11-22 18:00',''),
				( '33', '3', 'Todas', ' 2015-11-22 9:00', '2015-11-22 18:00',''),
				( '33', '2', 'Todas', ' 2015-11-22 9:00', '2015-11-22 18:00',''),
				( '33', '1', 'Todas', ' 2015-11-22 9:00', '2015-11-22 18:00',''),
				( '33', '3', 'Todas', ' 2015-11-22 9:00', '2015-11-22 18:00',''),
				( '33', '2', 'Todas', ' 2015-11-22 9:00', '2015-11-22 18:00',''),

				( '33', '1', 'Todas', ' 2015-11-28 9:00', '2015-11-28 18:00',''),
				( '33', '3', 'Todas', ' 2015-11-28 9:00', '2015-11-28 18:00',''),
				( '33', '2', 'Todas', ' 2015-11-28 9:00', '2015-11-28 18:00',''),

				( '33', '1', 'Todas', ' 2015-11-29 9:00', '2015-11-29 17:00',''),
				( '33', '3', 'Todas', ' 2015-11-29 9:00', '2015-11-29 17:00',''),
				( '33', '2', 'Todas', ' 2015-11-29 9:00', '2015-11-29 17:00',''),

				( '33', '1', 'Todas', ' 2015-12-12 12:00', '2015-12-12 18:00',''),
				( '33', '3', 'Todas', ' 2015-12-12 12:00', '2015-12-12 18:00',''),
				( '33', '1', 'Hosting', ' 2015-12-12 18:00', '2015-12-12 20:00',''),
				( '33', '1', 'PlanetaPeru', ' 2015-12-12 18:00', '2015-12-12 20:00',''),
				( '33', '3', 'Hosting', ' 2015-12-12 18:00', '2015-12-12 20:00',''),
				( '33', '3', 'PlanetaPeru', ' 2015-12-12 18:00', '2015-12-12 20:00',''),
				( '33', '2', 'Todas', ' 2015-12-12 12:00', '2015-12-12 20:00',''),
				( '33', '1', 'Todas', ' 2015-12-13 9:00', '2015-12-13 18:00',''),
				( '33', '3', 'Todas', ' 2015-12-13 9:00', '2015-12-13 18:00',''),
				( '33', '2', 'Todas', ' 2015-12-13 9:00', '2015-12-13 18:00',''),

				( '33', '1', 'Todas', ' 2015-12-19 11:00', '2015-12-19 18:00',''),
				( '33', '3', 'Todas', ' 2015-12-19 11:00', '2015-12-19 18:00',''),
				( '33', '1', 'Hosting', ' 2015-12-19 18:00', '2015-12-19 20:00',''),
				( '33', '1', 'PlanetaPeru', ' 2015-12-19 18:00', '2015-12-19 20:00',''),
				( '33', '3', 'Hosting', ' 2015-12-19 18:00', '2015-12-19 20:00',''),
				( '33', '3', 'PlanetaPeru', ' 2015-12-19 18:00', '2015-12-19 20:00',''),
				( '33', '2', 'Todas', ' 2015-12-19 11:00', '2015-12-19 20:00',''),

				( '33', '1', 'Todas', ' 2015-12-20 9:00', '2015-12-20 16:00',''),
				( '33', '3', 'Todas', ' 2015-12-20 9:00', '2015-12-20 16:00',''),
				( '33', '2', 'Todas', ' 2015-12-20 9:00', '2015-12-20 16:00',''),
				( '33', '1', 'Hosting', ' 2015-12-26 8:00', '2015-12-26 9:00',''),
				( '33', '1', 'PlanetaPeru', ' 2015-12-26 8:00', '2015-12-26 9:00',''),
				( '33', '3', 'Hosting', ' 2015-12-26 8:00', '2015-12-26 9:00',''),
				( '33', '3', 'PlanetaPeru', ' 2015-12-26 8:00', '2015-12-26 9:00',''),
				( '33', '2', 'Todas', ' 2015-12-26 8:00', '2015-12-26 20:00',''),
				( '33', '1', 'Todas', ' 2015-12-26 9:00', '2015-12-26 18:00',''),
				( '33', '3', 'Todas', ' 2015-12-26 9:00', '2015-12-26 18:00',''),
				( '33', '1', 'Hosting', ' 2015-12-26 18:00', '2015-12-26 20:00',''),
				( '33', '1', 'PlanetaPeru', ' 2015-12-26 18:00', '2015-12-26 20:00',''),
				( '33', '3', 'Hosting', ' 2015-12-26 18:00', '2015-12-26 20:00',''),
				( '33', '3', 'PlanetaPeru', ' 2015-12-26 18:00', '2015-12-26 20:00',''),

				( '33', '1', 'Hosting', ' 2015-12-27 8:00', '2015-12-27 9:00',''),
				( '33', '1', 'PlanetaPeru', ' 2015-12-27 8:00', '2015-12-27 9:00',''),
				( '33', '3', 'Hosting', ' 2015-12-27 8:00', '2015-12-27 9:00',''),
				( '33', '3', 'PlanetaPeru', ' 2015-12-27 8:00', '2015-12-27 9:00',''),
				( '33', '2', 'Todas', ' 2015-12-27 8:00', '2015-12-27 20:00',''),
				( '33', '1', 'Todas', ' 2015-12-27 9:00', '2015-12-27 18:00',''),
				( '33', '3', 'Todas', ' 2015-12-27 9:00', '2015-12-27 18:00',''),
				( '33', '1', 'Hosting', ' 2015-12-27 18:00', '2015-12-27 20:00',''),
				( '33', '1', 'PlanetaPeru', ' 2015-12-27 18:00', '2015-12-27 20:00',''),
				( '33', '3', 'Hosting', ' 2015-12-27 18:00', '2015-12-27 20:00',''),
				( '33', '3', 'PlanetaPeru', ' 2015-12-27 18:00', '2015-12-27 20:00','');
		";
	echo "<br>TURNOS FIN DE SEMANA<br>$query<br>";
	$db->execQuery($query);

	//agrego los dias de licencia/vacaciones etc
	$query="
		INSERT INTO dias_invalidos (`inicio`, `fin`, `id_operador`, `id_area`, `id_medio`, `marca`, `comentario`) VALUES 
			('2015-10-01 00:00:00', '2015-10-01 23:59:59', '14', '1', '-1', 'Todas', 'Administrativo'), 
			('2015-10-02 00:00:00', '2015-10-04 23:59:59', '11', '1', '-1', 'Todas', 'Inasistencia'), 
			('2015-10-12 00:00:00', '2015-10-12 23:59:59', '16', '1', '-1', 'Todas', 'Inasistencia'), 
			('2015-10-17 00:00:00', '2015-10-17 23:59:59', '32', '1', '-1', 'Todas', 'Inasistencia'), 
			('2015-10-18 00:00:00', '2015-10-18 23:59:59', '12', '1', '-1', 'Todas', 'Administrativo'), 
			('2015-10-24 00:00:00', '2015-10-24 23:59:59', '12', '1', '-1', 'Todas', 'Inasistencia'), 
			('2015-12-01 00:00:00', '2015-12-02 23:59:59', '2', '1', '-1', 'Todas', 'Vacaciones'), 
			('2015-12-07 00:00:00', '2015-12-07 23:59:59', '14', '1', '-1', 'Todas', 'Vacaciones'), 
			('2015-12-11 00:00:00', '2015-12-18 23:59:59', '16', '1', '-1', 'Todas', 'Vacaciones'), 
			('2015-12-18 00:00:00', '2015-12-21 23:59:59', '12', '1', '-1', 'Todas', 'Vacaciones'), 
			('2015-12-23 00:00:00', '2015-12-23 23:59:59', '15', '1', '-1', 'Todas', 'Administrativo'), 
			('2015-10-05 00:00:00', '2015-11-02 23:59:59', '15', '1', '-1', 'Todas', 'Vacaciones'), 
			('2015-10-29 00:00:00', '2015-10-29 23:59:59', '13', '1', '-1', 'Todas', 'Administrativo'), 
			('2015-10-29 00:00:00', '2015-10-29 23:59:59', '11', '1', '-1', 'Todas', 'Administrativo'), 
			('2015-11-04 00:00:00', '2015-11-04 23:59:59', '33', '1', '-1', 'Todas', 'Inasistencia'), 
			('2015-11-10 00:00:00', '2015-11-10 23:59:59', '16', '1', '-1', 'Todas', 'Inasistencia'), 
			('2015-11-12 00:00:00', '2015-11-12 23:59:59', '16', '1', '-1', 'Todas', 'Inasistencia'), 
			('2015-12-21 00:00:00', '2015-12-21 23:59:59', '2', '1', '-1', 'Todas', 'Inasistencia'), 
			('2015-10-14 00:00:00', '2015-10-19 23:59:59', '-1', '1', '-1', 'Todas', 'Caida masiva de servidores'), 
			('2015-12-02 00:00:00', '2015-12-02 23:59:59', '-1', '1', '-1', 'Todas', 'Caida masiva de servidores'), 
			('2015-12-09 00:00:00', '2015-12-09 23:59:59', '-1', '1', '-1', 'Todas', 'Caida masiva de servidores');


	";

	echo "<br>DIAS INVALIDOS<br>$query<br>";
	$db->execQuery($query);
}

function poblarTurnosOperaciones()
{
	$db = DBManager::singleton();
	//borro los registros de turnos
	$query = "DELETE FROM turnos WHERE medio = 4";
	$db->execQuery($query);

	//borro los registros de dias_invalidos
	$query = "DELETE FROM dias_invalidos WHERE id_area = 2";
	$db->execQuery($query);

	$query = "INSERT INTO turnos ( `id_operador`, `medio`, `marca`, `inicio`,`fin`, `info_adicional`) VALUES ";
	$turnos_semana = " 
			( '17', '4', 'Todas', '##FECHAINICIO## 8:00', '##FECHAFIN0## 17:00', ''),
			( '18', '4', 'Todas', '##FECHAINICIO## 8:00', '##FECHAFIN0## 17:00', ''),
			( '19', '4', 'Todas', '##FECHAINICIO## 17:00', '##FECHAFIN0## 23:59', ''),
			( '31', '4', 'Todas', '##FECHAINICIO## 8:00', '##FECHAFIN0## 23:59', ''),";

	date_default_timezone_set('America/Santiago');

	$date = '2015-10-01';
	$end_date = '2015-12-31';

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
		}

		$date = $nextday;
	}

	//agrego los turnos adicionales (fines de semana, noche)
	$query .= "
		( '18', '4', 'Todas', '2015-10-03 10:00','2015-10-03 18:00','Trabajo el Sabado'),
		( '18', '4', 'Todas', '2015-10-10 10:00','2015-10-10 18:00','Trabajo el Sabado'),
		( '17', '4', 'Todas', '2015-10-15 0:00','2015-10-15 8:00','Turno de Noche'),
		( '18', '4', 'Todas', '2015-10-17 10:00','2015-10-17 18:00','Trabajo el Sabado'),
		( '18', '4', 'Todas', '2015-10-24 10:00','2015-10-24 18:00','Trabajo el Sabado'),
		( '18', '4', 'Todas', '2015-10-29 0:00','2015-10-29 8:00','Turno de Noche'),
		( '18', '4', 'Todas', '2015-10-31 10:00','2015-10-31 18:00','Trabajo el Sabado'),
		( '18', '4', 'Todas', '2015-11-07 10:00','2015-11-07 18:00','Trabajo el Sabado'),
		( '17', '4', 'Todas', '2015-11-12 0:00','2015-11-12 8:00','Turno de Noche'),
		( '18', '4', 'Todas', '2015-11-19 0:00','2015-11-19 8:00','Turno de Noche'),
		( '18', '4', 'Todas', '2015-11-21 10:00','2015-11-21 18:00','Trabajo el Sabado'),
		( '18', '4', 'Todas', '2015-11-28 10:00','2015-11-28 18:00','Trabajo el Sabado'),
		( '18', '4', 'Todas', '2015-12-05 10:00','2015-12-05 18:00','Trabajo el Sabado'),
		( '18', '4', 'Todas', '2015-12-12 10:00','2015-12-12 18:00','Trabajo el Sabado'),
		( '18', '4', 'Todas', '2015-12-19 10:00','2015-12-19 18:00','Trabajo el Sabado'),
	";
	//agrego los mismos turnos para fabian
	$query .="
		( '31', '4', 'Todas', '2015-10-03 10:00','2015-10-03 18:00','Trabajo el Sabado'),
		( '31', '4', 'Todas', '2015-10-10 10:00','2015-10-10 18:00','Trabajo el Sabado'),
		( '31', '4', 'Todas', '2015-10-15 0:00','2015-10-15 8:00','Turno de Noche'),
		( '31', '4', 'Todas', '2015-10-17 10:00','2015-10-17 18:00','Trabajo el Sabado'),
		( '31', '4', 'Todas', '2015-10-24 10:00','2015-10-24 18:00','Trabajo el Sabado'),
		( '31', '4', 'Todas', '2015-10-29 0:00','2015-10-29 8:00','Turno de Noche'),
		( '31', '4', 'Todas', '2015-10-31 10:00','2015-10-31 18:00','Trabajo el Sabado'),
		( '31', '4', 'Todas', '2015-11-07 10:00','2015-11-07 18:00','Trabajo el Sabado'),
		( '31', '4', 'Todas', '2015-11-12 0:00','2015-11-12 8:00','Turno de Noche'),
		( '31', '4', 'Todas', '2015-11-19 0:00','2015-11-19 8:00','Turno de Noche'),
		( '31', '4', 'Todas', '2015-11-21 10:00','2015-11-21 18:00','Trabajo el Sabado'),
		( '31', '4', 'Todas', '2015-11-28 10:00','2015-11-28 18:00','Trabajo el Sabado'),
		( '31', '4', 'Todas', '2015-12-05 10:00','2015-12-05 18:00','Trabajo el Sabado'),
		( '31', '4', 'Todas', '2015-12-12 10:00','2015-12-12 18:00','Trabajo el Sabado'),
		( '31', '4', 'Todas', '2015-12-19 10:00','2015-12-19 18:00','Trabajo el Sabado');
			";
	echo $query."<br><br>";
	$db->execQuery($query);

	//agrego los dias de licenca
	$query_licencias = "
		INSERT INTO dias_invalidos (`inicio`, `fin`, `id_operador`, `id_area`, `id_medio`, `marca`, `comentario`) VALUES 
		('2015-11-11 00:00:00', '2015-11-11 23:59:59', '18', '2', '4', 'Todas', 'Administrativo'), 
		('2015-12-03 00:00:00', '2015-12-14 23:59:59', '19', '2', '4', 'Todas', 'Postnatal'), 
		('2015-12-24 00:00:00', '2015-12-24 23:59:59', '19', '2', '4', 'Todas', 'Dia libre operaciones');
	";

	$db->execQuery($query_licencias);
}
?>