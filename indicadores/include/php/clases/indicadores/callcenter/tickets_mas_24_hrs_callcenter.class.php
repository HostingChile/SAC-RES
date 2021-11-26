<?php

class tickets_mas_24_hrs_callcenter extends Indicador {

	private $margen_error = 60; //Minutos de margen para Errores

    protected function calcularIndicador($operador,$mes,$año) {
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
    	{
    		$message = "[[No Considerar]] No tiene tickets para contabilizar";
			$this->setError($message);
            throw new Exception($message);
    	}
    	return ($errores_totales / ($errores_totales + $aciertos_totales))*100;
    }

    //Se obtiene acierto cuando un ticket es respondido en menos de 24 hrs Y a la hora de la respuesta estaba en turno
	private function calcularAciertos($operador,$mes,$año){
		$aciertos = 0;
	
		$WHMCSManager = WHMCSManager::singleton();
		$query_old = "SELECT T.date as fecha_pregunta,IFNULL(R.date,'Sin Respuesta') as fecha_respuesta, HOUR(IF(ISNULL(R.message),TIMEDIFF(NOW(),T.date),TIMEDIFF(R.date,T.date))) as hrs_respuesta
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
					  AND HOUR(TIMEDIFF(NOW(), T.date)) >= 24
					  HAVING hrs_respuesta < 24
					  ORDER BY T.date ASC";

	  $query_new = "SELECT T.date as fecha_pregunta,IFNULL(R.date,'Sin Respuesta') as fecha_respuesta, HOUR(IF(ISNULL(R.message),TIMEDIFF(NOW(),T.date),TIMEDIFF(R.date,T.date))) as hrs_respuesta
					 FROM tbltickets T 
						LEFT JOIN tblticketreplies R 
						ON R.id =                        
						   ( SELECT RR.id              
							 FROM tblticketreplies RR
							 WHERE T.id = RR.tid
							 ORDER BY RR.date ASC
							 LIMIT 1
						   )
						WHERE MONTH(T.date) = $mes AND YEAR(T.date) = $año AND T.status != 'SPAM' AND T.status != 'Nulo' AND T.merged_ticket_id = 0
						AND T.id NOT IN (SELECT TT.id FROM tbltickets TT WHERE MONTH(TT.date) = $mes AND YEAR(TT.date) = $año AND TT.status != 'SPAM' AND TT.status != 'Nulo' 
										AND TT.did = (SELECT D.id FROM tblticketdepartments D WHERE D.name = 'Operaciones')
										AND id NOT IN (SELECT tid FROM tblticketlog WHERE action LIKE 'Department Changed to Operaciones%' AND MONTH(date) = $mes AND YEAR(date) = $año))		
					  AND HOUR(TIMEDIFF(NOW(), T.date)) >= 24
					  HAVING hrs_respuesta < 24
					  ORDER BY T.date ASC";

	  	$result = array();

	  	$result["Hosting"] = $WHMCSManager->execQueryInBrand($query_old, "Hosting");
	  	$result["PlanetaHosting"] = $WHMCSManager->execQueryInBrand($query_old, "PlanetaHosting");
	  	$result["HostingCenter"] = $WHMCSManager->execQueryInBrand($query_old, "HostingCenter");
	  	$result["InkaHosting"] = $WHMCSManager->execQueryInBrand($query_new, "InkaHosting");
	  	$result["PlanetaPeru"] = $WHMCSManager->execQueryInBrand($query_new, "PlanetaPeru");

		//revisar que en los tickets cumpla las condiciones de turno
		foreach($result as $brand => $brand_results){
			foreach($brand_results as $row){
				$fecha_respuesta = $row['fecha_respuesta'];
				
				if($this->diaValido($fecha_respuesta,$brand,$operador) && $this->enTurnoAciertos($fecha_respuesta,$brand,$operador))
					$aciertos++;
			}
		}
		
		return $aciertos;
	}
	
	//Se obtiene error cuando un ticket es respondido en más de 24 hrs (o aun no se responde) y se ha estado de turno durante más de $margen_error minutos mientras se pudo responder el ticket
	private function calcularErrores($operador,$mes,$año){
		$errores = 0;
	
		$WHMCSManager = WHMCSManager::singleton();
		$query_old = "SELECT T.id, T.tid, TL.action, T.date as fecha_pregunta,IFNULL(R.date,NOW()) as fecha_respuesta, HOUR(IF(ISNULL(R.message),TIMEDIFF(NOW(),T.date),TIMEDIFF(R.date,T.date))) as hrs_respuesta
				 FROM tbltickets T 
				 	LEFT JOIN tblticketlog TL ON TL.tid = T.id AND TL.action LIKE 'Status changed to Closed (%'
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

		$query_new = "SELECT T.id, T.tid, TL.action, T.date as fecha_pregunta,IFNULL(R.date,NOW()) as fecha_respuesta, HOUR(IF(ISNULL(R.message),TIMEDIFF(NOW(),T.date),TIMEDIFF(R.date,T.date))) as hrs_respuesta
				 FROM tbltickets T 
				 	LEFT JOIN tblticketlog TL ON TL.tid = T.id AND TL.action LIKE 'Status changed to Closed (%'
					LEFT JOIN tblticketreplies R 
					ON R.id =                        
					   ( SELECT RR.id              
						 FROM tblticketreplies RR
						 WHERE T.id = RR.tid
						 ORDER BY RR.date ASC
						 LIMIT 1
					   )
					WHERE MONTH(T.date) = $mes AND YEAR(T.date) = $año AND T.status != 'SPAM' AND T.status != 'Nulo' AND T.merged_ticket_id = 0
					AND T.id NOT IN (SELECT TT.id FROM tbltickets TT WHERE MONTH(TT.date) = $mes AND YEAR(TT.date) = $año AND TT.status != 'SPAM' AND TT.status != 'Nulo'
									AND TT.did = (SELECT D.id FROM tblticketdepartments D WHERE D.name = 'Operaciones')
									AND id NOT IN (SELECT tid FROM tblticketlog WHERE action LIKE 'Department Changed to Operaciones%' AND MONTH(date) = $mes AND YEAR(date) = $año))		
				  HAVING hrs_respuesta >= 24
					ORDER BY T.date ASC";

		$result = array();

	  	$result["Hosting"] = $WHMCSManager->execQueryInBrand($query_old, "Hosting");
	  	$result["PlanetaHosting"] = $WHMCSManager->execQueryInBrand($query_old, "PlanetaHosting");
	  	$result["HostingCenter"] = $WHMCSManager->execQueryInBrand($query_old, "HostingCenter");
	  	$result["InkaHosting"] = $WHMCSManager->execQueryInBrand($query_new, "InkaHosting");
	  	$result["PlanetaPeru"] = $WHMCSManager->execQueryInBrand($query_new, "PlanetaPeru");
		
		$brand_links = array("Hosting" => "https://panel.hosting.cl/admin","PlanetaHosting" => "https://www.planetahosting.cl/whmcs/admin",
							"PlanetaPeru" => "http://panel.planetahosting.pe/admin", "NinjaHosting" => "https://www.ninjahosting.cl/whmcs/admin",
							"HostingCenter" => "https://www.hostingcenter.cl/whmcs/admin", "InkaHosting" => "http://panel.inkahosting.com.pe/admin",
							"HostingPro" => "http://panel.hosting.com.co/admin");
		//revisar que en los tickets cumpla las condiciones de turno
		foreach($result as $brand => $brand_results){
			foreach($brand_results as $row){
				$fecha_pregunta = $row['fecha_pregunta'];
				if($this->diaValido($fecha_pregunta,$brand,$operador) && $this->enTurnoErrores($fecha_pregunta,$brand,$operador))
				{
					$link = $brand_links[$brand]."/supporttickets.php?action=view&id=".$row['id'];
					$link =utf8_encode('<a href=\\"'.$link.'\\" target=\\"_blank\\" >Ver Ticket</a>');
					$action = strlen($row['action'])>0?"[".utf8_encode($row['action'])."]":"";
					$this->error_log.="<br>$link ($brand #{$row["tid"]}) $action";
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
