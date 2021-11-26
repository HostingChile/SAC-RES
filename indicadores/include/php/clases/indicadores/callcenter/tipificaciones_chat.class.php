<?php

class tipificaciones_chat extends Indicador
{
	//Las tipificaciones son TIPO 3 en el SAC

    protected function calcularIndicador($operador,$mes,$año) {
        $chats_a_tipificar = 0;
		$chats_tipificados = 0;
		
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
				
				//Revisar si es un operador válido o un alias/
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
					if(!mysqli_num_rows($result_excluidos)){
						debug("No existe el operador o alias: <b>$operador_chat</b>");
						//return $this->setError("No existe el operador o alias: <b>$operador_chat</b>");
					}
				}
				
				if($operador->nombreCompleto() == $operador_chat || ($operador->isJefeDeArea() && $operador->nombreCompleto() != $operador_chat) ){
					//Revisar si el correo no se recibio en un día inválido
					$fecha_chat = $this->obtenerFechaHora($subject);
					$brand = $this->nombreMarca(explode(' ',$mail['header'][0]->to)[0]);
					if(!$this->diaValido($fecha_chat,$brand,$operador))
						continue;
				
					$chats_a_tipificar++;
				}
			}						
		}
		//Las tipificaciones de chat son TIPO 1 en el SAC
		
		$mes_tipificacion = $mes < 10 ? "0$mes" : $mes;
		
		$jefe_de_area = $operador->isJefeDeArea()?1:0;
		$sacManager = SACManager::singleton();
		$query = "SELECT COUNT(*) as TOTAL FROM Tipificacion
		WHERE (operador = '{$operador->nombreCompleto()}' OR $jefe_de_area = 1 AND operador != '{$operador->nombreCompleto()}')
		AND id_tipocontacto = 1 AND fecha LIKE '$año-$mes_tipificacion-%'";
		debug($query);
		$result_tipificaciones = $sacManager->execQuery($query);
		
		$chats_tipificados = mysqli_fetch_array($result_tipificaciones,MYSQLI_ASSOC)['TOTAL'];
		
		$porcentaje_tipificacion = ( $chats_tipificados / $chats_a_tipificar ) * 100; 
		
		return array('chats_a_tipificar' => $chats_a_tipificar, 'chats_tipificados' => $chats_tipificados);
    }
	
	//array([0] => array('chats_a_tipificar' => X, 'chats_tipificados' => Y), [1] => array (...)
	protected function evaluarIndicador($data) {
		$total_chats_a_tipificar = 0;
		$total_chats_tipificados = 0;
		
		foreach($data as $mes){
			$total_chats_a_tipificar += $mes['chats_a_tipificar'];
			$total_chats_tipificados += $mes['chats_tipificados'];
		}
		
		if(!$total_chats_a_tipificar)
			return $this->setError('No hay chats recibidos');
		
		return ($total_chats_tipificados / $total_chats_a_tipificar) * 100;	
	}
	
	//Obtiene el operador a partir del asunto del correo
	private function obtenerOperador($texto) {
		if(strpos($texto,'Operators:') !== false) {
			// $inicio = strpos($texto,'Operators:') + 11;
			// $fin = strpos($texto,',',$inicio);
			$inicio = strrpos($texto, ",")+2;
			$fin = strrpos($texto,'.',$inicio);
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

	private function chatContestado($subject) {
		return strpos($subject,'Operators:') !== false || strpos($subject,'Operator:') !== false;
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
	
	private function obtenerFechaHora($subject) {
		$start = strpos($subject,'of') + 3;
		$end = strpos($subject,'. Visitor');
		$length = $end - $start;
		
		return substr($subject,$start,$length);
	}

}

?>