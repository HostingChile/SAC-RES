<?php

class tickets_menos_4_hrs_operaciones extends Indicador {
	//Recordar que los tickets que son SPAM deben ser marcados como SPAM o eliminarse

	private $margen_error = 60; //Minutos de margen para Errores
	//2-2015 private $where = "AND T.tid NOT IN (658678, 552698, 312761, 381410, 491232, 986439, 703331, 327796, 747750, 455943, 905483, 696022, 716740, 804040, 494313, 726994, 489242, 728164, 897495, 200829, 291304, 948172, 948653, 327796, 762998, 763616, 800461, 731210, 240549, 763616, 499490)";
	private $where = "AND T.tid NOT IN (515233,793891,733820)";

    protected function calcularIndicador($operador,$mes,$año)  {

    	$this->error_log = "";
        $errores = $this->calcularErrores($operador,$mes,$año);
		$aciertos = $this->calcularAciertos($operador,$mes,$año);

		return array("aciertos" => $aciertos, "errores" => $errores, "log" => $this->error_log);
		
    }

    protected function evaluarIndicador($data) {
    	$aciertos_totales = 0;
    	$errores_totales = 0;
    	foreach($data as $mes)
    	{
    		$aciertos_totales += $mes["aciertos"];
    		$errores_totales += $mes["errores"];
    	}
    	if($errores_totales + $aciertos_totales == 0)
    		return false;
    	return ($aciertos_totales / ($errores_totales + $aciertos_totales)*100);
    }
	
	//Se obtiene acierto cuando un ticket es respondido en menos de 4 hrs Y a la hora de la respuesta estaba en turno
	private function calcularAciertos($operador,$mes,$año){
		$aciertos = 0;
	
		$WHMCSManager = WHMCSManager::singleton();

		$query = "SELECT TL.date AS fecha_pregunta,
				                 R.date AS fecha_respuesta,
				                 HOUR(TIMEDIFF(R.date,TL.date)) AS hrs_respuesta
				    FROM tbltickets T
				    LEFT JOIN tblticketlog TL
				    ON T.id = TL.tid
				    LEFT JOIN tblticketreplies R
				    ON R.id = ( SELECT RR.id
				                            FROM tblticketreplies RR
				                            WHERE RR.tid = T.id
				                            AND RR.date > TL.date
				                            AND RR.admin != ''
				                            ORDER BY RR.date ASC
				                            LIMIT 1
				                        )
				    WHERE YEAR(T.date) = $año AND MONTH(T.date) = $mes
				    AND TL.action LIKE '%changed%operaciones%'
				    AND T.status != 'Nulo'
				    AND T.status != 'SPAM'
				    AND HOUR(TIMEDIFF(NOW(),T.date)) >= 4
				    HAVING hrs_respuesta < 4
				
				UNION DISTINCT
				
				    SELECT T.date AS fecha_pregunta,
				                 R.date AS fecha_respuesta,
				                 HOUR(TIMEDIFF(R.date,T.date)) AS hrs_respuesta
				    FROM tbltickets T
				    LEFT JOIN tblticketdepartments D
				    ON T.did = D.id
				    LEFT JOIN tblticketreplies R
				    ON R.id = ( SELECT RR.id
				                            FROM tblticketreplies RR
				                            WHERE RR.tid = T.id
				                            AND RR.date > T.date
				                            AND RR.admin != ''
				                            ORDER BY RR.date ASC
				                            LIMIT 1
				                        )
				    WHERE YEAR(T.date) = $año AND MONTH(T.date) = $mes
				    AND D.name = 'Operaciones'
				    AND T.id NOT IN (SELECT TL.tid FROM tblticketlog TL WHERE TL.action LIKE '%changed%operaciones%')
				    AND T.status != 'Nulo'
				    AND T.status != 'SPAM'
				    AND HOUR(TIMEDIFF(NOW(),T.date)) >= 4
				    HAVING hrs_respuesta < 4
				
				ORDER BY fecha_pregunta ";
		//debug($query);		  
		$result = $WHMCSManager->execQueryByBrand($query);

		//revisar que en los tickets cumpla las condiciones de turno
		foreach($result as $brand => $brand_results){
			foreach($brand_results as $row){
				$fecha_respuesta = $row['fecha_respuesta'];
				
				if($this->enTurnoAciertos($fecha_respuesta,$brand,$operador))
					$aciertos++;
			}
		}
		
		return $aciertos;
	}
	
	//Se obtiene error cuando un ticket es respondido en más de 4 hrs (o aun no se responde) y se ha estado de turno durante más de $margen_error minutos mientras se pudo responder el ticket
	private function calcularErrores($operador,$mes,$año){
		$errores = 0;
	
		$WHMCSManager = WHMCSManager::singleton();

		$query = "SELECT T.tid,T.id,TL.date AS fecha_pregunta,
				                 R.date AS fecha_respuesta,
				                 HOUR(TIMEDIFF(R.date,TL.date)) AS hrs_respuesta
				    FROM tbltickets T
				    LEFT JOIN tblticketlog TL
				    ON T.id = TL.tid
				    LEFT JOIN tblticketreplies R
				    ON R.id = ( SELECT RR.id
	                            FROM tblticketreplies RR
	                            WHERE RR.tid = T.id
	                            AND RR.date > TL.date
	                            AND RR.admin != ''
	                            ORDER BY RR.date ASC
	                            LIMIT 1
	                        )
				    WHERE YEAR(T.date) = $año AND MONTH(T.date) = $mes
				    {$this->where}
				    AND LOWER(TL.action) LIKE '%changed%operaciones%'
				    AND T.status != 'Nulo'
				    AND T.status != 'SPAM'
				    AND HOUR(TIMEDIFF(NOW(),T.date)) >= 4
				    HAVING hrs_respuesta >= 4
				
				UNION DISTINCT
				
				    SELECT T.tid,T.id,T.date AS fecha_pregunta,
				                 R.date AS fecha_respuesta,
				                 HOUR(TIMEDIFF(R.date,T.date)) AS hrs_respuesta
				    FROM tbltickets T
				    LEFT JOIN tblticketdepartments D
				    ON T.did = D.id
				    LEFT JOIN tblticketreplies R
				    ON R.id = ( SELECT RR.id
				                            FROM tblticketreplies RR
				                            WHERE RR.tid = T.id
				                            AND RR.date > T.date
				                            AND RR.admin != ''
				                            ORDER BY RR.date ASC
				                            LIMIT 1
				                        )
				    WHERE YEAR(T.date) = $año AND MONTH(T.date) = $mes
				    {$this->where}
				    AND D.name = 'Operaciones'
				    AND T.id NOT IN (SELECT TL.tid FROM tblticketlog TL WHERE LOWER(TL.action) LIKE '%changed%operaciones%')
				    AND T.status != 'Nulo'
				    AND T.status != 'SPAM'
				    AND HOUR(TIMEDIFF(NOW(),T.date)) >= 4
				    HAVING hrs_respuesta >= 4
				
				ORDER BY fecha_pregunta ";
		
		debug($query);
		$result = $WHMCSManager->execQueryByBrand($query);

		$brand_links = array("Hosting" => "https://www.hosting.cl","PlanetaHosting" => "https://www.planetahosting.cl",
							"PlanetaPeru" => "https://www.planetahosting.pe", "NinjaHosting" => "https://www.ninjahosting.cl",
							"HostingCenter" => "http://www.hostingcenter.cl");			
		//revisar que en los tickets cumpla las condiciones de turno
		foreach($result as $brand => $brand_results){
			foreach($brand_results as $row){
				$fecha_pregunta = $row['fecha_pregunta'];
				//debug(" - Revisando ticket {$row['tid']}");
				if($this->enTurnoErrores($fecha_pregunta,$brand,$operador))
				{
					$link = $brand_links[$brand]."/whmcs/admin/supporttickets.php?action=view&id=".$row['id'];
					$link =utf8_encode('<a href=\\"'.$link.'\\" target=\\"_blank\\" >Ver Ticket</a>');
					$this->error_log.="<br>$link ($brand #{$row["tid"]})";
					$errores++;
				}
			}
		}
		
		return $errores;
	}
	
	//Acierto solo si esta de turno a la hora de ser contestado el ticket
	private function enTurnoAciertos($datetime_respuesta,$brand,$operador){
		$datetime = strtotime($datetime_respuesta);
		$fecha_ticket_contestado = date('Y-m-d H:i:s', $datetime);
		
		$database = DBManager::singleton();
		$id_operador = $operador->getId();
		
		$query = "SELECT COUNT(T.id) AS enTurno FROM turnos T LEFT JOIN medios M ON T.medio = M.id WHERE 
					T.id_operador = $id_operador 
					AND M.nombre = '{$this->medio_string}'
					AND (marca = '$brand' OR marca = 'Todas')
					AND '$fecha_ticket_contestado'
						BETWEEN T.inicio AND T.fin";
		$result_turnos = $database->execQuery($query);
		
		return mysqli_fetch_array($result_turnos, MYSQLI_ASSOC)['enTurno'];
	}
	
	//Error si estuvo $margen_error minutos de turno, entre la llegada del ticket y 4 horas despues
	private function enTurnoErrores($datetime_llegada,$brand,$operador){
		$datetime = strtotime($datetime_llegada);
		$fecha_llegada_ticket = date('Y-m-d H:i:s', $datetime);
		
		$database = DBManager::singleton();
		$id_operador = $operador->getId();
		
		$query = "SELECT SUM(TIME_TO_SEC(TIMEDIFF(LEAST('$fecha_llegada_ticket' + INTERVAL 4 HOUR,fin) , GREATEST('$fecha_llegada_ticket',inicio))) / 60) as minutos_en_turno
					FROM turnos WHERE 
					id_operador = $id_operador 
					AND medio = {$this->medio} 
					AND (marca = '$brand' OR marca = 'Todas')
					AND '$fecha_llegada_ticket' < fin
					AND '$fecha_llegada_ticket' + INTERVAL 4 HOUR > inicio";
		$result_turnos = $database->execQuery($query);
		
		$minutos_en_turno = floor(mysqli_fetch_array($result_turnos, MYSQLI_ASSOC)['minutos_en_turno']);
		$minutos_en_turno = !$minutos_en_turno ? 0 : $minutos_en_turno;
		
		 //if($minutos_en_turno >= $this->margen_error)
		 	//debug("$fecha_llegada_ticket: $minutos_en_turno minutos en turno");
			
		return $minutos_en_turno >= $this->margen_error;
	}
}

?>