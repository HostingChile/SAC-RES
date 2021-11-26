<?php

class nota_ticket extends Indicador {
    protected function calcularIndicador($operador,$mes,$año) {
		
		$WHMCSManager = WHMCSManager::singleton();
				
		$sum_amabilidad = 0;
		$sum_eficiencia = 0;
		$cantidad_encuestas = 0;
	
		$nombre_operador = $operador->nombreCompleto();
		$nombre_operador_utf8 = utf8_encode($nombre_operador);
		
		$mes_query = $mes < 10 ? "0$mes" : $mes;

		//tickets invalidos
		$tickets_inavlidos = "";
		if($mes >= 1 && $mes <=3 && $año == 2017)
			$tickets_inavlidos = "AND T.tid NOT IN (979466,908763,205699,528216,297694,205699,979466)";

		$jefe_de_area = $operador->isJefeDeArea()?1:0;

		//Calcular las notas
		$query = "SELECT CONCAT(B.firstname,' ',B.lastname) as nombre, AVG(FLOOR(A.rating/10)) as amabilidad, AVG(A.rating%10) as eficiencia, COUNT(CONCAT(B.firstname,' ',B.lastname)) as cantidad 
					FROM tblticketfeedback as A 
					LEFT JOIN tbladmins as B ON A.adminid = B.id
					LEFT JOIN tbltickets as T ON A.ticketid = T.id
					WHERE A.datetime LIKE '$año-$mes_query-%'
					$tickets_inavlidos
					AND ( 
					CONCAT(B.firstname,' ',B.lastname) = '$nombre_operador' OR 
					CONCAT(B.firstname,' ',B.lastname) = '$nombre_operador_utf8' OR 
					$jefe_de_area = 1 AND CONCAT(B.firstname,' ',B.lastname) != '$nombre_operador_utf8')
					ORDER BY datetime ASC";

					echo $query;
		
		$result = $WHMCSManager->execQuery($query);

		echo "<pre>";
		print_r($result);
		echo "</pre>";
		foreach ($result as $dataWHMCS){
			$sum_amabilidad += $dataWHMCS['amabilidad'] * $dataWHMCS['cantidad'];
			$sum_eficiencia += $dataWHMCS['eficiencia'] * $dataWHMCS['cantidad'];
			$cantidad_encuestas += $dataWHMCS['cantidad'];
		}
		
		//Calcular la cantidad de tickets en los que participo
		$query = "SELECT A.admin,COUNT(DISTINCT A.tid) AS totaltickets 
					FROM tblticketreplies as A, tbltickets as T WHERE 
					A.date LIKE '$año-$mes_query-%'
					$tickets_inavlidos
					AND (A.admin = '$nombre_operador' OR A.admin = '$nombre_operador_utf8' OR $jefe_de_area = 1 AND A.admin != '$nombre_operador_utf8')
					AND A.tid = T.id
					AND T.status = 'Closed'
					";
		$cantidad_tickets_contestados = $WHMCSManager->execSumQuery($query,'totaltickets');
		
		//Si es que notas obtenidas < 10% chats contestadss && notas obtenidas < 10, o no se reciben encuestas, este indicador no cuenta y se reparte entre los otros
		if(($cantidad_encuestas < 10 && ($cantidad_encuestas < 0.1*$cantidad_tickets_contestados)) || !$cantidad_tickets_contestados){
            $message = "[[No Considerar]] El operador {$operador->nombreCompleto()} tiene $cantidad_tickets_contestados tickets y solamente $cantidad_encuestas encuestas";
			$this->setError($message);
            throw new Exception($message);
        }		

        //Pongo ene log las encuestas con nota baja
        $query = "SELECT T.tid, T.id,
					  FLOOR(TF.rating / 10)as amabilidad,
					  (TF.rating % 10) as eficiencia,
					  CONCAT(A.firstname,' ',A.lastname) as nombre
					FROM tblticketfeedback TF
					LEFT JOIN tbltickets T
					ON TF.ticketid = T.id
					LEFT JOIN tbladmins A
					ON A.id = TF.adminid
					WHERE TF.datetime LIKE '$año-$mes_query-%'
					$tickets_inavlidos
					AND TF.adminid != 0
					HAVING amabilidad < 4 AND eficiencia < 4 AND (amabilidad*0.3 + eficiencia*0.7) <2
					AND (nombre = '$nombre_operador' OR  nombre = '$nombre_operador_utf8' OR $jefe_de_area = 1 AND nombre != '$nombre_operador_utf8')";

		$result = $WHMCSManager->execQueryByBrand($query);
		$log = "";
				$brand_links = array("Hosting" => "https://www.hosting.cl","PlanetaHosting" => "https://www.planetahosting.cl",
							"PlanetaPeru" => "https://www.planetahosting.pe", "NinjaHosting" => "https://www.ninjahosting.cl",
							"HostingCenter" => "https://www.hostingcenter.cl", "InkaHosting" => "http://www.inkahosting.com.pe");	

		foreach($result as $brand => $brand_results){
			foreach($brand_results as $row){
				$link = $brand_links[$brand]."/whmcs/admin/supporttickets.php?action=view&id=".$row['id'];
				$link =utf8_encode('<a href=\\"'.$link.'\\" target=\\"_blank\\" >Ver Ticket</a>');
				$log.="<br>$link ($brand #{$row["tid"]}) -> amabilidad:{$row["amabilidad"]}, eficiencia:{$row["eficiencia"]}";
			}
		}
		
		$amabilidad = $sum_amabilidad / $cantidad_encuestas;
		$eficiencia = $sum_eficiencia / $cantidad_encuestas;
		
		return array('nota_amabilidad' => $amabilidad, 'nota_eficiencia' => $eficiencia, 'encuestas_recibidas' => $cantidad_encuestas, 'encuestas_nota_baja' => $log);
		
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
			return $this->setError('No hay encuestas de tickets recibidas');
		
		return (0.3*($total_amabilidad / $total_encuestas_recibidas) + 0.7*($total_eficiencia / $total_encuestas_recibidas));
	}
}

?>