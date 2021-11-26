<?php

class nota_chat extends Indicador {

	protected function calcularIndicador($operador,$mes,$año) {
		$nota_amabilidad = 0;
		$nota_eficiencia = 0;
		$cantidad_encuestas_recibidas = 0;
		$cantidad_chats_contestados = 0;
	
		$mailManager = MailManager::singleton();
		
		$año_final = $mes == 12 ? $año + 1 : $año;
		$mes_final = $mes == 12 ? 1 : $mes + 1;
		$mails = $mailManager->obtenerCorreosPorFecha("$año-$mes-01","$año_final-$mes_final-01");
		if(!$mails){
			$this -> last_error = $mailManager->ultimoError();
			return false;
		}
		
		foreach($mails as $mail){
		
			$subject = iconv_mime_decode($mail['header'][0]->subject);
			
			if($this->chatContestado($subject)){
				
				$operador_chat_original = $this->obtenerOperador($subject);
								
				//Revisar si es un operador v?ido o un alias
				$database = DBManager::singleton();			
				$query = "SELECT CONCAT(O.nombre,' ',O.apellido) AS nombre_completo FROM operadores O LEFT JOIN alias A ON O.id = A.id_operador 
							WHERE CONCAT(O.nombre,' ',O.apellido) = '$operador_chat_original' OR A.alias = '$operador_chat_original'";
				$result_alias = $database->execQuery($query);
		
				$operador_chat = mysqli_fetch_array($result_alias, MYSQLI_ASSOC)['nombre_completo'];
				
				if(is_null($operador_chat)){
					$operador_chat = $operador_chat_original;
					//Revisar si es un operador excluido
					$query = "SELECT * FROM operadores_excluidos WHERE nombre = '$operador_chat'";
					$result_excluidos = $database->execQuery($query);
					if(!mysqli_num_rows($result_excluidos))
					{
						debug("No existe el operador o alias: <b>$operador_chat</b>");
						//return $this->setError("No existe el operador o alias: <b>$operador_chat</b>");
					}
				}
				
				if($operador->nombreCompleto() == $operador_chat || $operador->isJefeDeArea() && $operador->nombreCompleto() != $operador_chat ){
					//Revisar si el correo no se recibio en un d? inv?ido
					$fecha_chat = $this->obtenerFechaHora($subject);
					$brand = $this->nombreMarca(explode(' ',$mail['header'][0]->to)[0]);
					if(!$this->diaValido($fecha_chat,$brand,$operador))
						continue;
				
					$cantidad_chats_contestados++;
					
					if($this->contieneEncuesta($mail['body'])) {
						
						$encuesta = $this->extraerEncuesta($mail['body']);
						$amabilidad = $this->obtenerAmabilidad($encuesta);
						$eficiencia = $this->obtenerEficiencia($encuesta);

						// debug("$operador_chat - $fecha_chat ($brand) A:$amabilidad E:$eficiencia");
						
						$nota_amabilidad += $amabilidad;
						$nota_eficiencia += $eficiencia;
						$cantidad_encuestas_recibidas++;
					}
				}
			}	
						
		}
		
		//Si es que notas obtenidas < 10% chats contestadss && notas obtenidas < 10, o no se reciben encuestas, este indicador no cuenta y se reparte entre los otros
		if(($cantidad_encuestas_recibidas < 10 && ($cantidad_encuestas_recibidas < 0.1*$cantidad_chats_contestados)) || !$cantidad_chats_contestados){
            $message = "[[No Considerar]] El operador {$operador->nombreCompleto()} tiene $cantidad_chats_contestados llamadas y solamente $cantidad_encuestas_recibidas encuestas";
			$this->setError($message);
            throw new Exception($message);
        }
		
		if(!$cantidad_encuestas_recibidas)
			return $this->setError("No se recibieron encuestas.");
			
		$nota_amabilidad = $nota_amabilidad / $cantidad_encuestas_recibidas;
		$nota_eficiencia = $nota_eficiencia / $cantidad_encuestas_recibidas;
		
		$nota_encuesta = 0.3 * $nota_amabilidad + 0.7 * $nota_eficiencia;

		return array('nota_amabilidad' => $nota_amabilidad, 'nota_eficiencia' => $nota_eficiencia, 'encuestas_recibidas' => $cantidad_encuestas_recibidas, 'cantidad_contestados' => $cantidad_chats_contestados);
    }
	
	//array([0] => array('nota_amabilidad' => X, 'nota_eficiencia' => Y, 'encuestas_recibidas' => Z), [1] => array (...)
	protected function evaluarIndicador($data) {
		$total_encuestas_recibidas = 0;
		$total_amabilidad = 0;
		$total_eficiencia = 0;
		
		foreach($data as $mes){
			$total_encuestas_recibidas += $mes['encuestas_recibidas'];
			$total_amabilidad += $mes['encuestas_recibidas'] * $mes['nota_amabilidad'];
			$total_eficiencia += $mes['encuestas_recibidas'] * $mes['nota_eficiencia'];
		}
		
		if(!$total_encuestas_recibidas)
			return $this->setError('No hay encuestas de chats recibidas');
		
		return (0.3*($total_amabilidad / $total_encuestas_recibidas) + 0.7*($total_eficiencia / $total_encuestas_recibidas));
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
	
	//Devuelve s?o la encuesta del mail de chat
	private function extraerEncuesta($texto) {
		$inicio = strpos($texto,'Post Chat Survey');
		$fin = strpos($texto,'This transcript email message was automatically generated by',$inicio);
		$largo = $fin - $inicio;
		
		return substr($texto,$inicio,$largo);
	}

	//Obtiene el operador a partir del asunto del correo
	private function obtenerOperador($texto) {
		if(strpos($texto,'Operators:') !== false) {
			$inicio = strpos($texto,'Operators:') + 11;
			$fin = strpos($texto,',',$inicio);
			$largo = $fin - $inicio;
			
			$operador = substr($texto,$inicio,$largo);
		}
		else if(strpos($texto,'Operator:') !== false) {
			$inicio = strpos($texto,'Operator:') + 10;
			$fin = strpos($texto,'.',$inicio);
			$largo = $fin - $inicio;
			
			$operador = substr($texto,$inicio,$largo);
		}
		else
			$operador = "~ No Contestado";

		//caso especial por transcripciones de chat que estan con problemas en la ñ
		if(strpos($operador, "Lina Qui") === 0){
			$operador = "Lina Quiñones";
		}
		if(strpos($operador, "Natalia Mu") === 0){
			$operador = "Natalia Muñoz";
		}
		if(strpos($operador, "Gerardo Mu") === 0){
			$operador = "Gerardo Muñoz";
		}
		if(strpos($operador, "Jhonathan Qui") === 0){
			$operador = "Jhonathan Quiñones";
		}

		return $operador;
	}

	//Obtiene el valor de amabilidad del operador de la encuesta de chat
	private function obtenerAmabilidad($texto){
		$inicio = strpos($texto,'Politeness:');
		$inicio = strpos($texto,'value-td',$inicio) + 10;
		
		if($inicio == 10)
			$inicio = strpos($texto,'Politeness:') + 12;
		
		return substr($texto,$inicio,1);	
	}

	//Obtiene el valor de eficiencia del operador de la encuesta de chat
	private function obtenerEficiencia($texto){
		$inicio = strpos($texto,'Proficiency:');
		$inicio = strpos($texto,'value-td',$inicio) + 10;
		
		if($inicio == 10)
			$inicio = strpos($texto,'Proficiency:') + 13;
			
		return substr($texto,$inicio,1);
	}

	//Comprueba si un mail de chat contiene o no encuesta
	function contieneEncuesta($texto) {
		if(strpos($texto,'Post Chat Survey') !== false)
			return true;
		return false;
	}
}

?>