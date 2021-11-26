<?php

class monitoreo extends Indicador{

	// Estos son los minutos que tiene el operador al comenzar su turno, y antes de terminarlo, de gracia donde no se consideran los chats perdidos
	private $margen_horario = 10;
    
	protected function calcularIndicador($operador,$mes,$año) {
	
		ini_set('memory_limit', '256M');

	 	$ruta_json = "https://interno.operaciones.hosting.cl/api/monitoreo/bonus/stats/$año/$mes";
        $json = file_get_contents($ruta_json);
        $bonus_result = json_decode($json, true);

        if($bonus_result["success"] === false){
        	throw new Exception("No se pudo cargar la info del monitoreo");
        }
        $bonus_stats = $bonus_result["bonus_stats"];

        $ok = 0;
        $error = 0;
        $error_log = "";
        foreach ($bonus_stats as $bonus_stat) {
        	$fecha = $bonus_stat["date"];

        	$date = date_create_from_format("d/m/Y", $fecha);
        	$weekday = $date->format('w');

        	//se omite la revision de los lunes de herramientas que se revisan diariamente, porque el domingo nadie trabaja en operaciones.
        	if($weekday == 1 && $bonus_stat["tool_period"] == "daily"){
        		continue;
        	}

        	if($bonus_stat["status"] == "ok"){
        		$ok++;
        	}
        	elseif($bonus_stat["status"] == "error"){
        		$error++;
        		$mensaje = $bonus_stat["message"];
				$mensaje = str_replace("\n", '', $mensaje);
				$mensaje = str_replace('"', '\\"', $mensaje);
				$mensaje = str_replace('<br />', '', $mensaje);

				$error_log .= "<br>$fecha - {$bonus_stat["server_ip"]} ({$bonus_stat["tool_name"]}) $mensaje";
        	}
        }
        
        return array('mediciones_error' => $error, 'mediciones_totales' => ($error + $ok), 'log' => $error_log);

    }
	
	protected function evaluarIndicador($data) {
		$total_errores = 0;
		$total_mediciones = 0;
		
		foreach($data as $mes){
			$total_errores += $mes['mediciones_error'];
			$total_mediciones += $mes['mediciones_totales'];
		}
		
		return ($total_errores / $total_mediciones) * 100;
	}
}

?>