<?php
require_once __DIR__ . '/config.php';

function checkUser($yubikey){
    $curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, 'https://administracion.hosting.cl/api/permissions/yubikey_login/SAC/' . $yubikey);
	curl_setopt($curl,CURLOPT_RETURNTRANSFER ,1);
	$result = json_decode(curl_exec($curl),true);
	if(!$result){
		return ['user'=>'','auth'=>false, 'message'=>'mensaje 1'];
	}
	$success = $result['success'];

	if($success == false){
		$error = $result['error'];	
		return ['user'=>'','auth'=>false,'message'=>$error];
	}
	$user = $result['username'];
	$variables = $result['variables'];
	$groups = $result['groups'];
	return ["user"=>$user, 'auth'=>true, 'groups'=>$groups, 'variables'=>$variables];
}

?>
