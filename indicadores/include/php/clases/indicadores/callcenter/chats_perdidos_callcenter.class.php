<?php

class chats_perdidos_callcenter extends Indicador{

	// Estos son los minutos que tiene el operador al comenzar su turno, y antes de terminarlo, de gracia donde no se consideran los chats perdidos
	private $margen_horario = 10;
    
	protected function calcularIndicador($operador,$mes,$año) {
		$cantidad_chats_perdidos_operador = 0;
		$cantidad_chats_contestados_operador = 0;

		$this->error_log ="";
	
		$mailManager = MailManager::singleton();
		
		$año_final = $mes == 12 ? $año + 1 : $año;
		$mes_final = $mes == 12 ? 1 : $mes + 1;
		$mails = $mailManager->obtenerCorreosPorFecha("$año-$mes-01","$año_final-$mes_final-01");
		if(!$mails)
			return $this->setError("MailManager Error: ".$mailManager->ultimoError());
		
		foreach($mails as $mail){
		
			$subject = iconv_mime_decode($mail['header'][0]->subject);
			$fecha_chat = $this->obtenerFechaHora($subject);
			$brand = $this->nombreMarca(explode(' ',$mail['header'][0]->to)[0]);

/*
			if($brand == "NinjaHosting" && $operador->nombreCompleto() == "Jhonathan Qui?nes")
				continue;
			if($brand == "NinjaHosting" && $operador->nombreCompleto() == "Carlos Navarro")
				continue;
*/
			
			//Revisar si el correo se recibio en turno y no se recibio en un d? inv?ido
			if($this->enTurno($fecha_chat,$brand,$operador) && $this->diaValido($fecha_chat,$brand,$operador)){
				//Revisar si el correo no se recibio durante la colacion
				if($this->enColacion($fecha_chat,$operador)){
					continue;
				}
				
				if($this->chatContestado($subject)){
					// debug("{$operador->nombreCompleto()} - {$fecha_chat} ({$brand})");
					$cantidad_chats_contestados_operador++;
				}
				else{
					$this->error_log .= "<br>$fecha_chat ($brand)";// debug("{$operador->nombreCompleto()} - {$fecha_chat} ({$brand}) ???SIN RESPUESTA!!!");
					// debug("{$operador->nombreCompleto()} - {$fecha_chat} ({$brand}) ???SIN RESPUESTA!!!");
					$cantidad_chats_perdidos_operador++;
				}			
			}							
		}
		
		$cantidad_chats_totales_operador = $cantidad_chats_perdidos_operador + $cantidad_chats_contestados_operador;
		

		if($cantidad_chats_totales_operador == 0)
			return $this->setError("No hay chats recibidos");
		
		$porcentaje_chats_perdidos = ($cantidad_chats_perdidos_operador/$cantidad_chats_totales_operador)*100;
		
		return array('chats_perdidos' => $cantidad_chats_perdidos_operador, 'chats_totales' => $cantidad_chats_totales_operador, 'log' => $this->error_log);
    }
	
	//array([0] => array('chats_perdidos' => X, 'chats_totales' => Y), [1] => array (...)
	protected function evaluarIndicador($data) {
		$total_chats_perdidos = 0;
		$total_chats = 0;
		
		foreach($data as $mes){
			$total_chats_perdidos += $mes['chats_perdidos'];
			$total_chats += $mes['chats_totales'];
		}
		
		if(!$total_chats)
			return $this->setError('No hay chats recibidos en el turno');
		
		return ($total_chats_perdidos / $total_chats) * 100;
	}
	
	private function chatContestado($subject) {
		return strpos($subject,'Operators:') !== false || strpos($subject,'Operator:') !== false;
	}
	
	private function obtenerFechaHora($subject) {
		$start = strpos($subject,'of') + 3;
		$end = strpos($subject,'. Visitor');
		$length = $end - $start;
		
		return substr($subject,$start,$length);
	}
	
	private function nombreMarca($brand) {
		switch($brand){
			case '"Hosting.cl"':
					return 'Hosting';
			case 'planetahosting':
					return 'PlanetaHosting';
			case 'hostingcenter':
					return 'HostingCenter';
			case 'ninjahosting':
					return 'NinjaHosting';
			case 'planetaperu':
					return 'PlanetaPeru';			
		}
	}
	
	private function enTurno($datetime,$brand,$operador) {
		$datetime = strtotime($datetime);
		$fecha_chat_perdido = date('Y-m-d H:i:s', $datetime);
		
		$database = DBManager::singleton();
		$id_operador = $operador->getId();
		//El margen se le debe SUMAR a la hora de incio del turno y RESTAR a la hora de t?mino
		$query = "SELECT COUNT(T.id) AS enTurno FROM turnos T LEFT JOIN medios M ON T.medio = M.id WHERE 
					T.id_operador = $id_operador 
					AND M.nombre = '{$this->medio_string}'
					AND (T.marca = '$brand' OR T.marca = 'Todas')
					AND '$fecha_chat_perdido'
						BETWEEN T.inicio + INTERVAL {$this->margen_horario} MINUTE
						AND T.fin - INTERVAL {$this->margen_horario} MINUTE";
		$result_turnos = $database->execQuery($query);
		
		return mysqli_fetch_array($result_turnos, MYSQLI_ASSOC)['enTurno'];
	}

}

?>