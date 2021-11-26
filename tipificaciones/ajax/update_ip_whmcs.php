<?php

if(!isset($_POST['domain']) || !isset($_POST['domain_id']) || !isset($_POST['hosting']) || !isset($_POST['esASP']) || !isset($_POST['web_ip']))
	exit;
if($_POST['domain'] == '' || $_POST['domain_id'] == '' || $_POST['hosting'] == '' || $_POST['esASP'] == '' || $_POST['web_ip'] == '')
	exit;
	
$domain = $_POST['domain'];
$domain_id = $_POST['domain_id'];
$hosting = $_POST['hosting'];
$esASP = $_POST['esASP'];
$web_ip = $_POST['web_ip'];

if($esASP && isset($_POST['mail_ip']) && $_POST['mail_ip'] != '')
	$mail_ip = $_POST['mail_ip'];

//conexion a la BD del HWMCS
$upOne = realpath(__DIR__ . '/..');
require_once $upOne."/DB/SingleWHMCSManager.class.php";
$WHMCSManager = SingleWHMCSManager::singleton($hosting);

if($esASP){
	$query = "SELECT id FROM tblservers WHERE ipaddress = '$mail_ip'";
}
else{
	$query = "SELECT id FROM tblservers WHERE ipaddress = '$web_ip'";
}
$result = $WHMCSManager->execQuery($query);
if(!$result){
	$response = array('success' => false, 'message' => "Error con la query en WHMCS de $hosting<br>$query");
	exit(json_encode($response));
}

if(sizeof($result) == 0){
	$mid_ip = $esASP ? $mail_ip : $web_ip; 
	$response = array('success' => false, 'message' => "No se encontaron servidores con la IP $mid_ip en el WHMCS de $hosting<br>$query");
	exit(json_encode($response));
}
if(sizeof($result) > 1){
	$mid_ip = $esASP ? $mail_ip : $web_ip;
	$response = array('success' => false, 'message' => "Se encontraron muchos servidores con la IP $mid_ip en el WHMCS de $hosting");
	exit(json_encode($response));
}

$server_id = $result[0]["id"];
	
$query = "SELECT * FROM tblhosting WHERE domain = '$domain' AND id = $domain_id";
$result = $WHMCSManager->execQuery($query);
if(sizeof($result) == 0){
	$response = array('success' => false, 'message' => "No se encontro el dominio: $domain");
	exit(json_encode($response));
}
if(sizeof($result) > 1){
	$response = array('success' => false, 'message' => 'Se encontraron muchos dominios');
	exit(json_encode($response));
}
	
$query = "UPDATE tblhosting SET server = $server_id WHERE domain = '$domain' AND id = $domain_id";
$result = $WHMCSManager->execQueryAlone($query);

if(!$result){
	$response = array('success' => false, 'message' => "No se pudo actualizar (1) id:$server_id<br>$query");
	exit(json_encode($response));
}

// COMENTADO POR AHORA. ESTA PARTE ACTUALIZA LA IP DEL SERVIDOR WEB PARA LOS ASP
// if($esASP){
// 	//Obtener valor exacto a colocar
// 	$query = "SELECT fieldoptions FROM tblcustomfields WHERE id = (SELECT fieldid FROM tblcustomfieldsvalues WHERE relid = $domain_id AND fieldid IN (SELECT id from tblcustomfields WHERE fieldname = 'Servidor Web'));";
// 	$result = $WHMCSManager->execQueryAlone($query);
// 	$valores = explode(',',mysqli_fetch_array($result)['fieldoptions']);
	
// 	$inserted_web_ip = '';
// 	foreach($valores as $valor)
// 		if(trim($valor) == $web_ip)
// 			$inserted_web_ip = $valor;
			
// 	if($inserted_web_ip == ''){
// 		$response = array('success' => false, 'message' => "No se pudo actualizar. La IP $web_ip no es válida.");
// 		exit(json_encode($response));
// 	}

// 	$query = "UPDATE tblcustomfieldsvalues SET value = '$inserted_web_ip' WHERE relid = $domain_id AND fieldid IN (SELECT id from tblcustomfields WHERE fieldname = 'Servidor Web')";
// 	$result = $WHMCSManager->execQueryAlone($query);

// 	if(!$result){
// 		$response = array('success' => false, 'message' => 'No se pudo actualizar (3)');
// 		exit(json_encode($response));
// 	}
// }

//Determinar encargado
// $con_encargado=conn_encargado();
// $link_encargado=Conexion($con_encargado[0],$con_encargado[1],$con_encargado[2],$con_encargado[3]);
// if($esASP)
// 	$query_encargado = "SELECT encargado FROM Servidores WHERE ip = '$mail_ip'";
// else
// 	$query_encargado = "SELECT encargado FROM Servidores WHERE ip = '$web_ip'";
// $result = mysql_query($query_encargado,$link_encargado);
// if($result)
// 	$encargado = mysql_fetch_array($result)['encargado'];
// mysql_close($link_encargado);

$response = array('success' => true);
exit(json_encode($response));

?>