<?php
if(!isset($_POST["OTP"]))
	exit;

session_start();
	
require_once __DIR__ . '/Auth/Yubico.php';
require_once __DIR__ . '/Auth/Modhex.php';
require_once __DIR__ . '/checkUser.php';

function removeAccents($str){
	$unwanted_array = array(    'Š'=>'S', 'š'=>'s', 'Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
                            'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U',
                            'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss', 'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c',
                            'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o',
                            'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y' );
	$str = strtr( $str, $unwanted_array );
	return $str;
}

$logfile = __DIR__ . "/log.txt";
$date = date("d/m/Y H:i:s");

$otp = strtolower($_POST["OTP"]);

$srctext = ((strlen($otp) % 2) == 1) ? 	$srctext = 'c' . $otp : $otp;		
$yubikey = trim(hexdec(b64ToHex(modhexToB64(substr($srctext,2,10)))));	// No se consideran los 2 primeros caracteres por si se ha modificado la Yubikey
$user = checkUser($yubikey);
$response = array();
if(!$user['auth']){ // Usuario no autorizado por BBDD de permisos

	$response['auth'] = false;
	$response['play_sound'] = $play_sound;
	if($yubikey == 0)
		$message = $text_invalid_otp;
	else if($user['user'] == '')
		$message = "$yubikey: $text_key_unregistered";
	else
		$message = "{$user['user']}: $text_user_unauthorized";
	
	$response['message'] = $message;
	
	//Escribir en el Log
	if($yubikey == 0)
		$log = "$date - $log_invalid_otp \n";
	else if($user['user'] == '')
		$log = "$date - $log_key_unregistered: $yubikey \n";
	else
		$log = "$date - $log_user_unauthorized: {$user['user']} \n";		
	file_put_contents($logfile,$log,FILE_APPEND);
	
	//JSON Encode
	$response = json_encode($response,JSON_UNESCAPED_UNICODE);
}
else{ // Usuario autorizado por BBDD de permisos
		
	# Generate a new id+key from https://upgrade.yubico.com/getapikey/
	$yubi = new Auth_Yubico($yubikey_client_id, $yubikey_secret_key, 1, 1);
	$auth = $yubi->verify($otp);
	
	if (@PEAR::isError($auth)) {
	
		$response['auth'] = false;
		$response['play_sound'] = $play_sound;
		if($yubikey == 0)
			$response['message'] = $text_invalid_otp;
		else
			$response['message'] = $text_key_repeated;
		
		//Escribir en el Log
		if($yubikey == 0)
			$log = "$date - $log_invalid_otp \n";
		else
			$log = "$date - $log_key_repeated: $yubikey \n";
		file_put_contents($logfile,$log,FILE_APPEND);
		
		//JSON Encode
		$response = json_encode($response,JSON_UNESCAPED_UNICODE);
		
	} 
	else { // ------------------------------------------------------------------------------ USUARIO AUTORIZADO ------------------------------------------------------------------------------ //
	
		
		$user_name = removeAccents($user['user']); //STRING: Nombre completo del usuario
		$user_groups = $user['groups']; //ARRAY: Nombres de los grupos autorizados a entrar al sistema a los que pertenece el usuario
		$user_variables = $user['variables'];//ARRAY: Cada elemento es un arreglo-> key:nombre variable, value:arreglo con todos los valores de esa variable
		$_SESSION["user_name"] = $user_name;
		$_SESSION["groups"] = $user_groups;
		$_SESSION[md5($system_name)] = true;
		$_SESSION['REMOTE_ADDR'] = md5($_SERVER['REMOTE_ADDR']);	
		//Set user variables that come in object with json format {name: value}
		foreach ($user_variables as $variable) {
		 	$_SESSION[$variable['name']] = $variable['value'];
		} 
	
		$response['auth'] = true;
		$response['play_sound'] = false;
		//Escribir en el Log				
		$log = "$date - $log_user_logedin: {$user['user']} \n";
		file_put_contents($logfile,$log,FILE_APPEND);
		//JSON Encode
		$response = json_encode($response,JSON_UNESCAPED_UNICODE);
	}
}

echo $response;
exit;

?>
