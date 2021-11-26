<?php
$upOne = realpath(__DIR__ . '/..');

require_once $upOne."/DB/SingleWHMCSManager.class.php";
require_once $upOne."/DB/SACManager.class.php";

date_default_timezone_set('America/Santiago');

//retorno -> json (result -> "error", "nohits", "multiplehits" o "success")

if(!isset($_POST["brand"])){
	exit(json_encode(array("result" => "error", "message" => "No se especifico la marca")));
}

$brand = $_POST["brand"];

if(isset($_POST["id_product"]))
{
	$id_product = $_POST["id_product"];
	displayClientInfo($id_product,$brand);
}
else if(isset($_POST["domain"]))
{
	$domain = $_POST["domain"];
	$domain = addslashes($domain);

	$special_characters = array('ü');
	foreach ($special_characters as $special_character) {
		$domain = str_replace("$special_character", '%', $domain);
	}
	// $domain = mysqli_real_escape_string($domain);

	$WHMCSManager = SingleWHMCSManager::singleton($brand);
	$query = "SELECT id, domain, domainstatus FROM tblhosting WHERE domain LIKE '$domain%' ORDER BY domainstatus";

	$result = $WHMCSManager->execQuery($query);
	$total_resultados = sizeof($result);
	if($total_resultados === 1){
		displayClientInfo($result[0]["id"], $brand);
		exit();
	}
	else if($total_resultados === 0){
		echo json_encode(array("result" => "nohits"));
		exit();
	}
	else{
		$return = array();
		$return["result"] = "multiplehits";
		$return["hits"] = $result;
		echo json_encode($return);
		exit();
	}
}
else
{
	$return = array("result" => "error", "message" => "No se especifico un dominio");
	echo json_encode($return);
	exit();
}


function displayClientInfo($id_product, $brand)
{
	$return = array();
	$return["brand"] = $brand;

	//obtengo la info del whmcs
	$WHMCSManager = SingleWHMCSManager::singleton($brand);
	$query = "SELECT H.*, S.ipaddress, P.name as plan
			FROM tblhosting H 
			LEFT JOIN tblproducts P ON H.packageid = P.id
			LEFT JOIN tblservers S ON H.server = S.id
			WHERE H.id = '$id_product'";
	$result = $WHMCSManager->execQuery($query);

	$return["whmcs_info"]["general"] = $result[0];

	$client_info = array();
	$client_id =  $result[0]['userid'];
	$domain = $result[0]["domain"];
	$plan = $result[0]["plan"];
	$es_asp = strpos($plan, "_ASP_") !== false || strpos($plan, "_ASP") !== false || strpos($plan, "ASP_") !== false ||
		strpos($plan, "_Asp_") !== false || strpos($plan, "_Asp") !== false || strpos($plan, "Asp_") !== false;
	$return["whmcs_info"]["general"]["es_asp"] = $es_asp;


	//obtengo, desde rackhost, la informacion del servidor del dominio

	//obtengo la info de los contactos tecnicos
	$query = "SELECT GROUP_CONCAT(cont.email SEPARATOR ', ') as contactos_tecnicos FROM tblclients cli 
		LEFT JOIN tblcontacts cont ON cli.id = cont.userid WHERE cli.id = '$client_id' GROUP BY cli.id";
	$result_contacts = $WHMCSManager->execQuery($query);
	$client_info["contactos_tecnicos"] = $result_contacts[0]["contactos_tecnicos"];

	//obtengo la info de las notas
	$query = "SELECT GROUP_CONCAT(N.note SEPARATOR '\n ') as notes FROM tblclients C LEFT JOIN tblnotes N ON C.id = N.userid WHERE C.id = '$client_id' GROUP BY C.id";
	$result_notes = $WHMCSManager->execQuery($query);
	$client_info["notes"] = $result_notes[0]["notes"];
	
	//obtengo la info de los otros servicios del cliente
	$query = "SELECT C.firstname, C.lastname, C.email, C.datecreated, H.billingcycle, H.amount, H.domainstatus
		FROM tblclients C 
		LEFT JOIN tblhosting H ON C.id = H.userid 
		WHERE C.id = '$client_id'";
	$result_client = $WHMCSManager->execQuery($query);
	
	$client_info["detalle_servicios_activos"] = $result_client;
	$client_info["contacto_administrativo"] = $result_client[0]["firstname"]." ".$result_client[0]["lastname"]." (".$result_client[0]["email"].")";
	$client_info["fecha_registro"] = $result_client[0]["datecreated"];
	//obtengo el monto que paga anualmente el cliente por sus servicios activos
	$multiplicadores = array('Free Account' => 0, 'One Time' => 0,  'Monthly' => 12, 'Quarterly' => 4, 'Semi-Annually' => 2, 'Annually' => 1, 'Biennially' => 0.5);
	$monto_total = 0;
	$servicios_activos;
	foreach ($result_client as $client_service) {
		if($client_service["domainstatus"] === "Active"){
			$servicios_activos++;
			$monto_total += $client_service["amount"] * $multiplicadores[$client_service["billingcycle"]];
		}
	}
	$client_info["monto_total"] = $monto_total;
	$client_info["servicios_activos"] = $servicios_activos;

	$return["whmcs_info"]["client"] = $client_info;

	//obtengo la info de los badges del cliente
	$SACManager = SACManager::singleton();
	$query = "SELECT * FROM Distintivo d LEFT JOIN DistintivoCliente dc ON d.id = dc.id_distintivo AND dc.dominio = '$domain'";
	$return_badges = array();
	foreach ($SACManager->execQuery($query) as $db_badge) {
		//si es que el badge no tiene valor, lo calculo a mano
		if(is_null($db_badge["valor"])){
			$calculator = new $db_badge["nombre"]();
			$calculo = $calculator->calcular($return["whmcs_info"]);
			$db_badge["valor"] = $calculo[0];
			$db_badge["motivo"] = $calculo[1];
		}
		else{
			$db_badge["motivo"] = $db_badge["operador"]." lo asignó manualmente el dia ".$db_badge["fecha"];
		}
		$return_badges[] = $db_badge;
	}
	$return["badges"] = $return_badges;
	
	//obtengo el historial de atenciones del cliente
	$days_consider_new_atention = 7;
	$new_atention_count=0;
	$current_date = date('Y/m/d H:i:s');
	$query = "SELECT t.operador, t.nombre_cliente, t.fecha, t.detalle, t.dominio, t.problema_solucionado, ct.nombre as categoria, tc.img AS img_tipocontacto, c.image AS img_compania
			FROM Tipificacion t 
				LEFT JOIN CategoriaTipificacion ct ON ct.id = t.id_categoria 
				LEFT JOIN TipoContacto tc ON tc.id = t.id_tipocontacto
				LEFT JOIN Compania c ON c.id = t.id_marca
			WHERE t.dominio = '$domain' AND c.nombre = '$brand' ORDER BY t.fecha DESC";
	$atentions_detail = array();
	foreach ($SACManager->execQuery($query) as $db_atention) {
		//veo si la atencion es reciente o no
		$str = strtotime($current_date) - (strtotime($db_atention["fecha"]));
		$diff = floor($str/3600); //diferencia en horas
		$new_atention = $diff > $days_consider_new_atention * 24 ? false : true;
		$db_atention["new_atention"] = $new_atention;
		if($new_atention){
			$new_atention_count++;
		}
		$atentions_detail[] = $db_atention;
	}
	$return['atentions'] = array();
	$return['atentions']['detail'] = $atentions_detail;
	$return['atentions']['new_atentions'] = $new_atention_count;

	//obtengo los datos del ping
	$ping_info = getPingInfo($domain);
	$return["ping"] = $ping_info;

	//se obtienen los datos desde la api de trello para verificar anuncios en el board de operaciones
	$trello_info = getTrelloInfo($ping_info,$whmcs_info,$domain,$brand);
	$return['trello'] = $trello_info;

	//retorno toda la informacion
	$return["result"] = "success";

	//$return = utf8ize($return);
	echo json_encode($return);
}

function getPingInfo($domain){
	$ping_info = array();

	// if($domain == "innovacom.cl" || $domain == "damacomunicaciones.cl"){
	// 	return array("ip_ping" => "No responde", "ip_ping_www" => "No responde", "ip_ping_mail" => "No responde", "cloudflare" => "No responde", "mxs" => array());
	// }

	$pings = array("ip_ping" => "$domain", "ip_ping_www" => "www.$domain", "ip_ping_mail" => "mail.$domain");
	foreach ($pings as $ping_variable => $ping_address) {
		$ping_ret = exec("host $ping_address | grep 'has address' | rev | cut -d' ' -f1 | rev");
		$ping_info["$ping_variable"] = $ping_ret ? : 'No responde';
	}

	$ping_info["cloudflare"] = $ping_info["ip_ping_www"] == "No responde" ? false : checkIfCloudflare($ping_info["ip_ping_www"]);
	exec("/usr/bin/host $domain | grep mail | rev | cut -d' ' -f-2 | rev", $mxs);
	
	$ping_info["mxs"] = $mxs ? : array();

	return $ping_info;
}

#obtiene información del tablero de trello de operaciones. La idea es que agreguen cosas referentes a ciertas ips y se muestren en el sac
#recibe objeto ping, con lista de ips que se revisarán contra los anuncios en trello
function getTrelloInfo($ping_info,$whmcs_info,$domain,$brand){
	$request = 'https://api.trello.com/1/lists/5c9a3b012291f5364fffd051/cards?key=efc72cd37b0560eac3a0b2a00c110f5b&token=ff429787711c473db7e2bcab0cf068cb679709757c84004a8e20aec2c7edaca6';
	$trello_response = json_decode(file_get_contents($request),true); #true para que retorne como arreglo
	$announces = [];
	foreach($trello_response as $card){
		$matched_label = checkIfCardRefersDomain($card,$ping_info, $whmcs_info,$domain,$brand);
		if($matched_label != FALSE){
			$announce = [];
			$announce['description'] = $card['desc'];
			$announce['name'] = $card['name'];
			$announce['url']=$card['shortUrl'];
			$announce['match'] = $matched_label;
			$announce['creation_date'] = getTrelloPublishedTimeFromId($card['id']);
			$announce['number_of_comments'] = getNumberOfCommentsInTrelloCard($card['id']);
			$announces[] = $announce; #append card to list of announces */
		}
	}
	return $announces;
}

function getNumberOfCommentsInTrelloCard($card_id){
	$request = 'https://api.trello.com/1/cards/' . strval($card_id) . '/actions?key=efc72cd37b0560eac3a0b2a00c110f5b&token=ff429787711c473db7e2bcab0cf068cb679709757c84004a8e20aec2c7edaca6';
	$trello_response = json_decode(file_get_contents($request),true); #true para que retorne como arreglo
	return sizeof($trello_response);
}

#Uso función mágica para obtener fecha de publicación usando id (fuente: https://help.trello.com/article/759-getting-the-time-a-card-or-board-was-created)
function getTrelloPublishedTimeFromId($card_id){
	$created_timestamp = hexdec( substr( $card_id , 0, 8 ) );
	$created_date = date('d/m/Y  H:i:s', $created_timestamp); 
	return $created_date;
}

#funcion auxiliar (usada por getTrelloInfo) que verifica si una cierta carta en trello hace referencia a alguna de las direcciones Domain mencionadas en el objeto ping_info
function checkIfCardRefersDomain($card,$ping_info, $whmcs_info,$domain,$brand){
	foreach($card['labels'] as $label){
		$label_name = $label['name'];
		if(strtolower($label_name) == 'todos' or $label_name==$ping_info['ip_ping'] or $label_name==$ping_info['ip_ping_email'] or $label_name==$ping_info['ip_ping_www'] or $label_name==$whmcs_info['general']['ipaddress'] or strtolower($label_name)==strtolower($domain) or checkIfLabelNameReferencesBrand($label_name,$brand)){
			return $label_name;
		}
	}
	return FALSE;
}

#Verifca si $label_name hace referencia a una marca.
function checkIfLabelNameReferencesBrand($label_name,$brand){
	$processed_label = strtolower($label_name);
	$processed_brand = strtolower($brand);
	return $processed_label == $processed_brand;
}

function checkIfCloudflare($ip){
	$ranges = array(
		"103.21.244.0/22",
		"103.22.200.0/22",
		"103.31.4.0/22",
		"104.16.0.0/12",
		"108.162.192.0/18",
		"141.101.64.0/18",
		"162.158.0.0/15",
		"172.64.0.0/13",
		"173.245.48.0/20",
		"188.114.96.0/20",
		"190.93.240.0/20",
		"197.234.240.0/22",
		"198.41.128.0/17",
		"199.27.128.0/21"
	);

	foreach ($ranges as $range) {
		if(cidr_match($ip, $range)){
			return true;
		}
	}
	return false;
}

function cidr_match($ip, $range)
{
    list ($subnet, $bits) = explode('/', $range);
    $ip = ip2long($ip);
    $subnet = ip2long($subnet);
    $mask = -1 << (32 - $bits);
    $subnet &= $mask; # nb: in case the supplied subnet wasn't correctly aligned
    return ($ip & $mask) == $subnet;
}

function utf8ize($d) {
    if (is_array($d)) {
        foreach ($d as $k => $v) {
            $d[$k] = utf8ize($v);
        }
    } else if (is_string ($d)) {
        return utf8_encode($d);
    }
    return $d;
}

abstract class Distintivo
{
	abstract protected function calcular($whmcs_info);
}

class Categoria extends Distintivo 
{
	public function calcular($whmcs_info){
		$monto_total = $whmcs_info["client"]["monto_total"];
		if($monto_total>180000){//aprox 10% superior{
			return array("4", "Cliente Platinum: dentro del 10% de los mejores clientes");
		}else if($monto_total>80000){//aprox 40% superior
			return array("3", "Cliente Premium: dentro del 40% de los mejores clientes ");
		}else if($monto_total>45000){//aprox 80% superior
			return array("2", "Cliente Estandar: dentro del promedio de los clientes");
		}else{
			return array("1", "Cliente Basico");
		}
		return array("0", "No entró en los rangos");
	}
}

class Antiguedad extends Distintivo
{
	public function calcular($whmcs_info){
		$days_consisder_antique = 365*3;
		//calculo la diferencia en dias entre la fecha actual y la fecha de ingreso
		date_default_timezone_set('America/Santiago');
		$current_date = date('Y/m/d H:i:s');
		$str = strtotime($current_date) - (strtotime($whmcs_info["client"]["fecha_registro"]));
		$diff = floor($str/3600/24); //diferencia en dias

		$return = $diff > $days_consisder_antique ? array("true","Es cliente hace más de ".floor($diff/365)." años") : array("false", "Es cliente hace menos de ".ceil($diff/365)." año(s)");
		return $return;
	}
}

class Conflictivo extends Distintivo
{
	public function calcular($whmcs_info){
		return array("false", "Cliente no ha sido asignado manualmente como conflictivo");
	}
}

?>
