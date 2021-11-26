<?php

date_default_timezone_set('America/Santiago');

$system_name = 'SAC'; //  <---- COMPLETAR: nombre del sistema, tal como aparece en el Sistema de Permisos
$text_title = 'SAC'; //  <---- COMPLETAR: título que aprecerá en el login
$background_img = 'bluewave'; // <---- ELEGIR ENTRE: rainforest,skydiving,sunset,greenlife,lava,bubbles,bluewave,sahara,goldbricks,sunlight

$db_ip = '190.96.85.3';
$db_user = 'sistemas_alvaro';
$db_password = 'alv15963';
$db_name = 'sistemas_yubikey';

$text_invalid_otp = 'No se ha utilizado una llave reconocible';
$text_key_unregistered = 'Llave no registrada';
$text_user_unauthorized = 'Usuario no autorizado para entrar';
$text_key_repeated = 'El OTP ingresado ya fue utilizado';

$log_invalid_otp = 'Se ha intentado ingresar con una llave irreconocible';
$log_key_unregistered = 'Se ha intentado ingresar con una llave no registrada';
$log_user_unauthorized = 'Se ha intentado ingresar un usuario no autorizado';
$log_key_repeated = 'Se ha intentado reutilizar un OTP';
$log_user_logedin = 'Ha ingresado el usuario';

$yubikey_client_id = '17874';
$yubikey_secret_key = 'D9DmCrtyZR4XqUVXp/1GeryLWb8=';

?>