<?php

class uptime_grupal extends Indicador {
	
    protected function calcularIndicador($operador,$mes,$año)  {
        
    	//obtengo el json con el uptime de los servidores para el mes y año
        $ruta_json = "http://apihosting.centinelaweb.cl/api/Estadistica/ReporteMensual/$año/$mes";
        $json = file_get_contents($ruta_json);
        $uptimes = json_decode($json, true);
        
        $uptime_con_servidor_excluido = $uptimes["uptimeservidorexcluido"];
        $servidor_excluido = $uptimes["servidorexcluido"];

        return array("uptime_con_servidor_excluido" => $uptime_con_servidor_excluido, "servidor_excluido" => $servidor_excluido);
		
    }

    protected function evaluarIndicador($data) {
    	$total_uptime = 0;
    	foreach($data as $mes){
    		$total_uptime += $mes["uptime_con_servidor_excluido"];
    	}
    	return $total_uptime/sizeof($data);
    }
	
}

?>