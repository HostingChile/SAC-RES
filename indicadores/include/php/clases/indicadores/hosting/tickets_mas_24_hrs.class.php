<?php

class tickets_mas_24_hrs extends Indicador {

	private $margen_error = 60; //Minutos de margen para Errores

    protected function calcularIndicador($operador,$mes,$año) {
        $errores = $this->calcularErrores($operador,$mes,$año);	
		$aciertos = $this->calcularAciertos($operador,$mes,$año);
		return array("aciertos" => $aciertos, "errores" => $errores);
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
    	return ($errores_totales / ($errores_totales + $aciertos_totales))*100;
    }

    //Se obtiene acierto cuando un ticket es respondido en menos de 24 hrs Y a la hora de la respuesta estaba en turno
	private function calcularAciertos($operador,$mes,$año){
		$aciertos = 0;
	
		$WHMCSManager = WHMCSManager::singleton();
		$query = "SELECT T.date as fecha_pregunta,IFNULL(R.date,'Sin Respuesta') as fecha_respuesta, HOUR(IF(ISNULL(R.message),TIMEDIFF(NOW(),T.date),TIMEDIFF(R.date,T.date))) as hrs_respuesta
					 FROM tbltickets T 
						LEFT JOIN tblticketreplies R 
						ON R.id =                        
						   ( SELECT RR.id              
							 FROM tblticketreplies RR
							 WHERE T.id = RR.tid
							 ORDER BY RR.date ASC
							 LIMIT 1
						   )
						WHERE MONTH(T.date) = $mes AND YEAR(T.date) = $año AND T.status != 'SPAM' AND T.status != 'Nulo'
						AND T.id NOT IN (SELECT TT.id FROM tbltickets TT WHERE MONTH(TT.date) = $mes AND YEAR(TT.date) = $año AND TT.status != 'SPAM' AND TT.status != 'Nulo' 
										AND TT.did = (SELECT D.id FROM tblticketdepartments D WHERE D.name = 'Operaciones')
										AND id NOT IN (SELECT tid FROM tblticketlog WHERE action LIKE 'Department Changed to Operaciones%' AND MONTH(date) = $mes AND YEAR(date) = $año))		
					  AND HOUR(TIMEDIFF(NOW(), T.date)) >= 4
					  HAVING hrs_respuesta < 24
					  ORDER BY T.date ASC";
					  
		$result = $WHMCSManager->execQueryByBrand($query);

		//revisar que en los tickets cumpla las condiciones de turno
		foreach($result as $brand => $brand_results){
			$marca = $operador->getNombre();
			if($brand == $marca || $marca == "Todas")
			{
				foreach($brand_results as $row)
					$aciertos++;
			}
		}
		
		return $aciertos;
	}
	
	//Se obtiene error cuando un ticket es respondido en más de 24 hrs (o aun no se responde) y se ha estado de turno durante más de $margen_error minutos mientras se pudo responder el ticket
	private function calcularErrores($operador,$mes,$año){
		$errores = 0;
	
		$WHMCSManager = WHMCSManager::singleton();
		$query = "SELECT T.tid as ticketid, T.date as fecha_pregunta,IFNULL(R.date,NOW()) as fecha_respuesta, HOUR(IF(ISNULL(R.message),TIMEDIFF(NOW(),T.date),TIMEDIFF(R.date,T.date))) as hrs_respuesta
				 FROM tbltickets T 
					LEFT JOIN tblticketreplies R 
					ON R.id =                        
					   ( SELECT RR.id              
						 FROM tblticketreplies RR
						 WHERE T.id = RR.tid
						 ORDER BY RR.date ASC
						 LIMIT 1
					   )
					WHERE MONTH(T.date) = $mes AND YEAR(T.date) = $año AND T.status != 'SPAM' AND T.status != 'Nulo'
					AND T.id NOT IN (SELECT TT.id FROM tbltickets TT WHERE MONTH(TT.date) = $mes AND YEAR(TT.date) = $año AND TT.status != 'SPAM' AND TT.status != 'Nulo'
									AND TT.did = (SELECT D.id FROM tblticketdepartments D WHERE D.name = 'Operaciones')
									AND id NOT IN (SELECT tid FROM tblticketlog WHERE action LIKE 'Department Changed to Operaciones%' AND MONTH(date) = $mes AND YEAR(date) = $año))		
				  HAVING hrs_respuesta >= 24
					ORDER BY T.date ASC";
		$result = $WHMCSManager->execQueryByBrand($query);
		
		//revisar que en los tickets cumpla las condiciones de turno
		foreach($result as $brand => $brand_results){
			$marca = $operador->getNombre();
			if($brand == $marca || $marca == "Todas")
			{
				foreach($brand_results as $row)
				{
					debug("$brand - {$row["ticketid"]}");
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
					AND (T.marca = '$brand' OR T.marca = 'Todas')
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
		
		$query = "SELECT SUM(TIME_TO_SEC(TIMEDIFF(LEAST('$fecha_llegada_ticket' + INTERVAL 24 HOUR,fin) , GREATEST('$fecha_llegada_ticket',inicio))) / 60) as minutos_en_turno
					FROM turnos WHERE 
					id_operador = $id_operador 
					AND medio = {$this->medio} 
					AND (marca = '$brand' OR marca = 'Todas')
					AND '$fecha_llegada_ticket' < fin
					AND '$fecha_llegada_ticket' + INTERVAL 24 HOUR > inicio";
		$result_turnos = $database->execQuery($query);
		
		$minutos_en_turno = floor(mysqli_fetch_array($result_turnos, MYSQLI_ASSOC)['minutos_en_turno']);
		$minutos_en_turno = !$minutos_en_turno ? 0 : $minutos_en_turno;
		
		// if($minutos_en_turno >= $this->margen_error)
			// debug("$fecha_llegada_ticket: $minutos_en_turno minutos en turno");
			
		return $minutos_en_turno >= $this->margen_error;
	}
}

?>