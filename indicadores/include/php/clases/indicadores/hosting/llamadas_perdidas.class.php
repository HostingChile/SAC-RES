<?php

class llamadas_perdidas extends Indicador
{
	//Las tipificaciones de teléfono son TIPO 2 en el SAC
 
    protected function calcularIndicador($operador,$mes,$año) 
    {
    	$asteriskManager = AsteriskManager::singleton();
        $brand = $operador->getNombre();
        $marcas = array("Hosting" => "hosting", 
                        "PlanetaHosting" => "planeta", 
                        "HostingCenter" => "hcenter", 
                        "NinjaHosting" => "ninja",
                        "PlanetaPeru" => "peru",
                        "Todas" => "Todas");
        $marca = $marcas[$brand];
        $aditional_condition = "";
        if($marca != "Todas")
            $aditional_condition = " (dcontext = '$marca' OR dcontext = '$marca' OR dcontext LIKE '{$marca}_%') AND ";

        //obtengo las llamadas perdidas
        $query = "SELECT COUNT(*) AS llamadas_perdidas FROM cdr WHERE 
                YEAR(end) = '$año' AND 
                MONTH(end) = '$mes' AND 
                dcontext NOT LIKE '%encuesta%' AND 
                $aditional_condition
                dcontext != '' AND 
                dcontext != 'from-pstn' AND 
                dcontext != 'interno' AND 
                lastapp = 'Queue' AND 
                disposition != 'ANSWERED' AND
                duration >= 35";
        debug($query);
        $result = $asteriskManager->execQueryASTDB($query);
        $llamadas_perdidas = mysqli_fetch_array($result,MYSQL_ASSOC)["llamadas_perdidas"];

        //obtengo el total de llamados recibidas
    	$query = "SELECT COUNT(*) AS llamadas_totales FROM cdr WHERE 
                YEAR(end) = '$año' AND 
                MONTH(end) = '$mes' AND 
                dcontext NOT LIKE '%encuesta%' AND 
                $aditional_condition
                dcontext != '' AND 
                dcontext != 'from-pstn' AND 
                dcontext != 'interno' AND 
                lastapp = 'Queue' AND 
                duration >= 35";;
        debug($query);
        $result = $asteriskManager->execQueryASTDB($query);
        $llamadas_totales = mysqli_fetch_array($result,MYSQL_ASSOC)["llamadas_totales"];
		
		return array("perdidas" => $llamadas_perdidas, "totales" => $llamadas_totales);
    }

	//array([0] => array('tipificaciones' => X, 'contestadas' => Y), [1] => array (...)
    protected function evaluarIndicador($data)
    {
		$total_llamados_perdidos = 0;
		$total_llamados = 0;
		
		foreach($data as $mes){
			$total_llamados_perdidos += $mes['perdidas'];
			$total_llamados += $mes['totales'];
		}
		
		return ($total_llamados_perdidos / $total_llamados ) * 100;	
    }

   
}

?>