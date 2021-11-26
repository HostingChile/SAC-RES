<?php

$debug = isset($_GET['debug']);

$status = array();
$brands = array('Hosting.cl' => 'hostingcl', 'PlanetaHosting' => 'planetahosting', 'HostingCenter' => 'hostingcenter', 'PlanetaPeru' => 'planetaperu', 'NinjaHosting' => 'ninjahosting');
$usuarios = array(  'negrocasa' => 'Cristian Casamayor',
					'gerardom' => 'Gerardo Muñoz',
					'waldof' => 'Waldo Flores',
					'fernanda' => 'Fernanda Lopez',
					'mauriciob' => 'Mauricio Bravo',
					'hernan' => 'Hernan Ortega',
					'davidh' => 'David Herrera',
					'carlosn' => 'Carlos Navarro',
					'juano' => 'Juan Ortega',
					'luisa' => 'Luis Acebo',
					'silviav' => 'Silvia Vivanco',
					'roni' => 'Roni De Souza',
					'alonsoc' => 'Operaciones',
					'jhonathanq' => 'Jhonathan Quiñones',
					'danielr' => 'Daniel Roman',
					'smarchant' => 'Sebastian Marchant',
					'nicolasg' => 'Nicolas Gutierrez',
					'alvarof' => 'Alvaro Flaño');

$horarios = array('Hosting.cl' => array('inicio' => '00:00', 'fin' => '23:59'),
					'PlanetaHosting' => array('inicio' => '09:00', 'fin' => '18:00'),
					'HostingCenter' => array('inicio' => '09:00', 'fin' => '18:00'),
					'PlanetaPeru' => array('inicio' => '00:00', 'fin' => '23:59'),
					'NinjaHosting' => array('inicio' => '09:00', 'fin' => '18:00'));
					
$chat_operators = array();
$chat_departments = array();
$hora_actual_leible = date('H:i');
$hora_actual = strtotime($hora_actual_leible);
					
foreach($brands as $brand_name => $brand_url){
	$somebody = false;
	
	$en_turno = strtotime($horarios[$brand_name]['inicio']) <= $hora_actual && strtotime($horarios[$brand_name]['fin']) >= $hora_actual;
	$status[$brand_name]['en_turno'] = $en_turno;
	
	$url = "http://image.providesupport.com/online-presense/$brand_url";
	
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$status_page = curl_exec($ch);
	curl_close($ch);

	$status_page = nl2br($status_page);
	$status_page = strtolower($status_page);

	$status_brand = explode('<br />',$status_page);
	for($i = 1; $i < count($status_brand); $i++) {
		$type = trim(explode(' ',$status_brand[$i])[0]);
		if($type == 'operator'){
			$username = trim(explode(' ',$status_brand[$i])[1]);
			$name = array_key_exists($username,$usuarios) ? utf8_encode($usuarios[$username]) : $username;
			if(!in_array($name, $chat_operators) && $name != '')
				$chat_operators[] = $name;
		}
		else{
			$name = ucfirst(utf8_encode(trim(trim(explode(' ',$status_brand[$i])[1]),'"')));
			if(!in_array($name, $chat_departments) && $name != '')
				$chat_departments[] = $name;
		}
		
		$online_offline = trim(explode(' ',$status_brand[$i])[3]);
		
		if($online_offline == 'online')
			$somebody = true;
		
		if($type)
			$status[$brand_name][$type][$name] = $online_offline;
	}
	
	$status[$brand_name]['warning_login'] = $en_turno && !$somebody; //Avisar si se tienen que conectar
	$status[$brand_name]['warning_logout'] = !$en_turno && $somebody; //Avisar si se tienen que desconectar
}

//Agregar los operadores y departamentos que faltan
foreach($status as $brand => $brand_status){
	foreach($chat_operators as $chat_operator){
		if(!array_key_exists($chat_operator,$brand_status['operator']))
			$status[$brand]['operator'][$chat_operator] = 'no_atiende'; 
	}
	foreach($chat_departments as $chat_department){
		if(!array_key_exists($chat_department,$brand_status['department']))
			$status[$brand]['department'][$chat_department] = 'no_existe'; 
	}
}

//Ordenar los operadores y departamentos
foreach($status as $brand => &$brand_status){
	ksort($brand_status['operator']);
	ksort($brand_status['department']);
}

if($debug){
	echo "<pre>";
	print_r($status);
	echo "</pre>";
	exit;
}
exit(json_encode($status));

?> 