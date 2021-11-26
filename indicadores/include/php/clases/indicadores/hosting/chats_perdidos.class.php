<?php

class chats_perdidos extends Indicador{

    
	protected function calcularIndicador($operador,$mes,$año) {
		$marca = $operador->getNombre();
		$cantidad_chats_perdidos_marca = 0;
		$cantidad_chats_contestados_marca = 0;

		
	
		$mailManager = MailManager::singleton();
		
		$año_final = $mes == 12 ? $año + 1 : $año;
		$mes_final = $mes == 12 ? 1 : $mes + 1;
		$mails = $mailManager->obtenerCorreosPorFecha("$año-$mes-01","$año_final-$mes_final-01");
		if(!$mails)
			return $this->setError("MailManager Error: ".$mailManager->ultimoError());
		
		foreach($mails as $mail){
		
			$subject = iconv_mime_decode($mail['header'][0]->subject);
			$brand = $this->nombreMarca(explode(' ',$mail['header'][0]->to)[0]);
			
			if($marca == $brand || $marca == "Todas"){
				if($this->chatContestado($subject)){
					// debug("{$operador->nombreCompleto()} - {$fecha_chat} ({$brand})");
					$cantidad_chats_contestados_marca++;
				}
				else{
					$cantidad_chats_perdidos_marca++;
				}				
			}							
		}
		
		$cantidad_chats_totales_marca = $cantidad_chats_perdidos_marca + $cantidad_chats_contestados_marca;
		

		if($cantidad_chats_totales_marca == 0)
			return $this->setError("No hay chats recibidos");
		
		$porcentaje_chats_perdidos = ($cantidad_chats_perdidos_marca/$cantidad_chats_totales_marca)*100;
		
		return array('chats_perdidos' => $cantidad_chats_perdidos_marca, 'chats_totales' => $cantidad_chats_totales_marca);
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

}

?>