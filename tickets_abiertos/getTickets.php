<?php

//Poner true para que se actualicen las p�ginas que ya estan abiertas
$reload_clients = false;
//$reload_clients = true;

$debug = isset($_GET['debug']);

function secondsToTime($seconds) {
    $dtF = new DateTime("@0");
    $dtT = new DateTime("@$seconds");
	
	return $dtF->diff($dtT)->format('%ad %hh %im');
}

function cmp($a, $b){
	$a = $a['time_passed'];
    $b = $b['time_passed'];
	
	if ($a == $b) {
        return 0;
    }
    return ($a < $b) ? -1 : 1;
}

if($reload_clients)
	exit(json_encode(array("reload" => true)));

$response = array();
$brands = array("Hosting","PlanetaHosting","HostingCenter","PlanetaPeru","InkaHosting","NinjaHosting");

$conn_strings["Hosting"] = array("190.96.85.228","hostingc_fabian","hosting.,","hostingc_whmcsnew");
$conn_strings["PlanetaHosting"] = array("201.148.105.50","igor_userph","whm*2008*.,","igor_whmph");
$conn_strings["HostingCenter"]=array("190.96.85.3","hcenter_admin","Planeta*1910Center","hcenter_whmcs");
$conn_strings["PlanetaPeru"] = array("201.148.107.40","planetco_user","admin7440","planetco_whmcs");
$conn_strings["InkaHosting"]= array("201.148.107.40","inkahost_user","inka_.,2013","inkahost_whmcs");
$conn_strings["NinjaHosting"]= array("panel.ninjahosting.cl","panelnin_ting14","]^k5[OVvr!PZ","panelnin_whmcs");
$conn_strings["NinjaHosting"]= array("panel.ninjahosting.cl","panelnin_ting14","]^k5[OVvr!PZ","panelnin_whmcs");

foreach($brands as $brand){
	$link = $conn_strings[$brand];
	$link = mysqli_connect($link[0],$link[1],$link[2],$link[3]);

	$query = "SELECT  T.id,
			 D.name AS departamento,
			 IFNULL(CONCAT(A.firstname,' ',A.lastname),'-') AS admin_asociado,
			 T.title AS asunto,
			 IF(T.name = '' ,CONCAT(C.firstname,' ',C.lastname),T.name) AS enviado_por,
			 T.status AS status,
			 IFNULL(R2.date,IFNULL(R.date,T.date)) AS tiempo_de_espera,
			 T.date AS inicio_ticket,
			 IFNULL(R3.admin,'') AS respondido_por			 
				FROM tbltickets T 
				LEFT JOIN tblticketreplies R 
				ON R.id =                        
						 ( SELECT RR.id              
						 FROM tblticketreplies RR
						 WHERE T.id = RR.tid
						 AND RR.admin != ''
						 ORDER BY RR.date DESC
						 LIMIT 1
						 )
				LEFT JOIN tblticketreplies R2
				ON R2.id =                        
						 ( SELECT RR.id              
						 FROM tblticketreplies RR
						 WHERE T.id = RR.tid
						 AND RR.admin = ''
						 AND RR.date > (SELECT date FROM tblticketreplies WHERE tid = T.id AND admin != '' ORDER BY date DESC LIMIT 1)
						 ORDER BY RR.date ASC
						 LIMIT 1
						 )
				LEFT JOIN tblticketdepartments D
				ON T.did = D.id
				LEFT JOIN tblclients C
				ON T.userid = C.id
				LEFT JOIN tbladmins A
				ON T.flag = A.id
				LEFT JOIN tblticketreplies R3
				ON R3.id =                        
						 ( SELECT RR.id              
						 FROM tblticketreplies RR
						 WHERE T.id = RR.tid
						 AND RR.admin != ''
						 ORDER BY RR.date DESC
						 LIMIT 1
						 )
				WHERE T.status IN (SELECT title FROM tblticketstatuses WHERE showawaiting = 1)
				ORDER BY T.date ASC";
	$result = mysqli_query($link,$query);
	
	while($row = mysqli_fetch_assoc($result)){
	
		//Corregir UTF-8 en caso de ser necesario
		$enviado_por = preg_match('!!u', $row['enviado_por']) ? $row['enviado_por'] : utf8_encode($row['enviado_por']);
		$asunto = preg_match('!!u', $row['asunto']) ? $row['asunto'] : utf8_encode($row['asunto']);
		$respondido_por = preg_match('!!u', $row['respondido_por']) ? $row['respondido_por'] : utf8_encode($row['respondido_por']);
			
		//Segundos desde la fecha
		$tiempo_de_espera = time() - strtotime($row['tiempo_de_espera']);
		
		//Restar 2 horas si es de Peru o NinjaHosting
		if($brand == 'PlanetaPeru' || $brand == 'InkaHosting' || $brand == 'NinjaHosting')
			$tiempo_de_espera = $tiempo_de_espera == '-' ? $tiempo_de_espera : $tiempo_de_espera - 2*60*60;
			
		//Color de fondo 		
		if($tiempo_de_espera < 1*60*60) // 1- hrs gris
			$flag = 'active';
		else if($tiempo_de_espera >= 1*60*60 && $tiempo_de_espera < 2*60*60) // 1 - 2 hrs azul
			$flag = 'info';
		else if($tiempo_de_espera >= 2*60*60 && $tiempo_de_espera < 4*60*60) // 2 - 4 hrs amarillo
			$flag = 'warning';
		else if($tiempo_de_espera >= 4*60*60) // 4+ hrs rojo
			$flag = 'danger';
			
		//Marcar en caso de responder urgente si no se ha respondido en los plazos
		$warning = '';
		if (!$respondido_por && $tiempo_de_espera >= 23*60*60)
			$warning = 'warning_24_hrs';
		else if (!$respondido_por && $tiempo_de_espera >= 3*60*60)
			$warning = 'warning_4_hrs';
		else if ($tiempo_de_espera >= 23*60*60)
			$warning = 'warning_24_hrs_respondido';
			
		//Marcar en caso de que se haya transferido a operaciones y no se haya respondido en los plazos
		if($row['departamento'] == 'Operaciones'){
			$query_operaciones = "SELECT IFNULL(TL.date,T.date) AS transferencia_operaciones,
										 IFNULL(IF(R.date>IFNULL(TL.date,T.date),R.date,''),'') AS primera_respuesta
									FROM tbltickets T
									LEFT JOIN tblticketlog TL
									ON TL.id = (SELECT L.id
															FROM tblticketlog L 
															WHERE L.tid = T.id 
															AND LOWER(L.action) LIKE '%changed%operaciones%' 
															ORDER BY L.date DESC
															LIMIT 1)
									LEFT JOIN tblticketreplies R 
									ON R.id =                        
											 ( SELECT RR.id              
											 FROM tblticketreplies RR
											 WHERE T.id = RR.tid
											 AND RR.admin != ''
											 ORDER BY RR.date DESC
											 LIMIT 1
											 )
									WHERE T.status IN (SELECT title FROM tblticketstatuses WHERE showawaiting = 1)
									AND T.id = {$row['id']}";
			$result_operaciones = mysqli_query($link,$query_operaciones);
			$row_operaciones = mysqli_fetch_assoc($result_operaciones);
			
			if(!$row_operaciones['primera_respuesta']){
				$tiempo_de_espera_operaciones = time() - strtotime($row_operaciones['transferencia_operaciones']);
				
				if ($tiempo_de_espera_operaciones >= 23*60*60)
					$warning = 'warning_24_hrs_operaciones';
				else if ($tiempo_de_espera_operaciones >= 3*60*60)
					$warning = 'warning_4_hrs_operaciones';
			}
		}

		//Dejar el tiempo en el formato correcto
		$time_passed = $tiempo_de_espera;
		$tiempo_de_espera = secondsToTime($tiempo_de_espera);		
		
		$response[] = array('brand' => $brand,
							'departamento' => $row['departamento'],
							'admin_asociado' => utf8_encode($row['admin_asociado']),
							'asunto' => $asunto,
							'enviado_por' => $enviado_por,
							'status' => $row['status'],
							'tiempo_de_espera' => $tiempo_de_espera,
							'respondido_por' => $respondido_por,
							'flag' => $flag,
							'warning' => $warning,
							'time_passed' => $time_passed
						);
	}
	
	mysqli_close($link);
}

//Ordenar por �ltima respuesta
usort($response,"cmp");

if($debug){
	echo "<pre>";print_r($response);echo "</pre>";
	exit;
}
exit(json_encode($response));

?>