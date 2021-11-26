<?php

class uptime_individual extends Indicador {
	
    protected function calcularIndicador($operador,$mes,$año)  {

        $this->error_log = "";
        $jefe_de_area = $operador->isJefeDeArea() || $operador->getNombre() == "Roni"?1:0;
        //obtengo el json con el uptime de los servidores para el mes y año
        $ruta_json = "http://apihosting.centinelaweb.cl/api/Estadistica/ReporteMensual/$año/$mes";
        $json = file_get_contents($ruta_json);
        $uptimes = json_decode($json, true);

        //obtengo los servidores del encargado
        $rackManager = RackhostManager::singleton();
        $operador = $operador->getNombre();

        if($jefe_de_area == 1)
        {
            $uptime_cpanel = 0;
            $uptime_pop = 0;
            $numero_servidores = 0;
            foreach ($uptimes["servidores"] as $serverinfo) {
                //veo si la ip corresponde a un servidor valido
                $ip = $serverinfo["ip"];
                if($serverinfo["cpanel"] != 0 && $serverinfo["pop3"] != 0)
                {
                    $uptime_cpanel+=$serverinfo["cpanel"];
                    $uptime_pop+=$serverinfo["pop3"];
                    $numero_servidores++;
                }      
            }

            if($mes == 10 && $año == 2015)
            {
                $uptime_cpanel = 99.93*$numero_servidores;
                $uptime_pop = 99.93*$numero_servidores;
                $uptime_promedio = ($uptime_cpanel+$uptime_pop)/2;
            }


        }
        else
        {
            $query = "SELECT ip FROM Servidores WHERE (encargado = '$operador' OR  $jefe_de_area = 1) AND plan <> 'dns / sin clientes'";
            $result = $rackManager->execQuery($query);
            $servidores = array();
            while($data = mysqli_fetch_array($result,MYSQL_ASSOC))
                $servidores[$data["ip"]] = -1;

            $uptime_cpanel = 0;$uptime_pop = 0;$numero_servidores = 0;
            foreach ($uptimes["servidores"] as $serverinfo) {
                //veo si la ip corresponde al encargado
                $ip = $serverinfo["ip"];

                if(isset($servidores[$ip]))
                {
                    unset($servidores["$ip"]);
                    if($serverinfo["cpanel"]!=0 && $serverinfo["pop3"]!=0)
                    {
                        $uptime_cpanel+=$serverinfo["cpanel"];
                        $uptime_pop+=$serverinfo["pop3"];
                        $this->error_log .= "<br>$ip (pop:{$serverinfo["pop3"]}, cpanel:{$serverinfo["cpanel"]})";
                        $numero_servidores++;
                    }      
                }
            }

            foreach ($servidores as $ip => $uptime) {
                    debug("$ip no estaba en datos de centinela ($uptime)");
            }
        }
        
        $uptime_cpanel = $uptime_cpanel/$numero_servidores;
        $uptime_pop = $uptime_pop/$numero_servidores;
        $uptime_promedio = ($uptime_cpanel+$uptime_pop)/2;

		return array("numero_servidores" => $numero_servidores, "uptime_promedio" => $uptime_promedio, "log" =>$this->error_log);
		
    }

    protected function evaluarIndicador($data) {

    	$total_servidores = 0;
    	$nota_final = 0;
    	foreach($data as $mes)
    	{
    		$total_servidores += $mes["numero_servidores"];
    		$nota_final +=  $mes["uptime_promedio"] * $mes["numero_servidores"];
    	}
    	if($total_servidores == 0)
    		return false;
    	return $nota_final/$total_servidores;
    }
	
}

?>