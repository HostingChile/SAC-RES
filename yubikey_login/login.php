<?php 
session_start();
$bom = chr(239) . chr(187) . chr(191);
echo $bom;

require_once __DIR__ . '/include/config.php';

// Seguridad de PHP Sessions

ini_set('session.use_cookies', 1);
ini_set('session.use_only_cookies', 1);

session_set_cookie_params(0,ini_get('session.cookie_path'),ini_get('session.cookie_domain'),isset($_SERVER['HTTPS']),true);

//Regeneración de id
if(!isset($_SESSION['ag13sh58g:daj'])){
    @session_regenerate_id();
    $_SESSION['ag13sh58g:daj'] = true;
}

//Comprobación de user agent
if(isset($_SESSION['HTTP_USER_AGENT'])){
    if($_SESSION['HTTP_USER_AGENT'] != md5($_SERVER['HTTP_USER_AGENT']))
        $_SESSION['user_name'] = '';
}
else{
    $_SESSION['HTTP_USER_AGENT'] = md5($_SERVER['HTTP_USER_AGENT']);
	$_SESSION['user_name'] = '';
}

//Comprobación de IP
if(isset($_SESSION['REMOTE_ADDR'])){
    if($_SESSION['REMOTE_ADDR'] != md5($_SERVER['REMOTE_ADDR']))
        $_SESSION['user_name'] = '';
}
else{
    $_SESSION['REMOTE_ADDR'] = md5($_SERVER['REMOTE_ADDR']);
	$_SESSION['user_name'] = '';
}

//Mostrar el login si no está logeado
if(!isset($_SESSION['user_name']) || $_SESSION['user_name'] == '' || !isset($_SESSION[md5($system_name)]) || !$_SESSION[md5($system_name)]){
	$include_path =  '/SAC/yubikey_login';
?>
<html>
<head>
<title><?=$text_title?></title>
<link rel="stylesheet" type="text/css" href="<?=$include_path?>/include/style/style.css" />
<link rel="icon" type="image/png" href="<?=$include_path?>/include/img/fav_icon.png">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/1.11.0/jquery.min.js" integrity="sha512-h9kKZlwV1xrIcr2LwAPZhjlkx+x62mNwuQK5PAu9d3D+JXMNlGx8akZbqpXvp0vA54rz+DrqYVrzUGDMhwKmwQ==" crossorigin="anonymous"></script>

<script type="text/javascript">
	$(document).ready(function(){
	
		//Focus Out para que funcione en Firefox
		$('#input_otp').focusout(function() {
			setTimeout(function() {
				$("#input_otp").focus();
				$("#input_otp").val("");
			}, 0);
		});
	
		//Enviar el formulario via AJAX
		$("form").on("submit",function(e){
			$("#spinner").show();
			e.preventDefault();
			$("#login_message").hide();
			
			var OTP = $("#input_otp").val();
			OTP = OTP.substr(OTP.length - 44); // <-- Esto es para corregir el error cuando se envian los OTP's antiguos
			
			$.post("<?=$include_path?>/include/checkOTP.php", {OTP: OTP}, function(data) {
				var response = $.parseJSON(data);
				$("#spinner").hide();
				if(!response.auth){ //No autorizado

					$("#login_message").removeClass("success_msg");
					$("#login_message").addClass("error_msg");
					$("#login_message").html("<span>Error</span><hr>Acceso no autorizado").show();
					$("#input_otp").focus();
					$("#input_otp").val("");
				}
				else{
					$("#login_message").removeClass("error_msg");
					$("#login_message").addClass("success_msg");
					$("#login_message").html("<span>Éxito</span><hr>Ingresando al sistema...").show();

					setTimeout(function(){
						<?
							$PROTOCOL = (!empty($_SERVER['HTTPS'])) ? 'https' : 'http';
							$DOC_ROOT = $PROTOCOL.'://'.$_SERVER['SERVER_NAME'];
							$path = $DOC_ROOT.$_SERVER['REQUEST_URI'];
						?>
						window.open('<?=$path?>','_self',false);

					},1000);
				}			
			});
		});
	});
</script>

<style type="text/css">
	
</style>

</head>
	<body style="background: url('<?="$include_path/include/img/backgrounds/$background_img"?>.jpg') no-repeat center center fixed;
				-webkit-background-size: cover;
				-moz-background-size: cover;
				-o-background-size: cover;
				background-size: cover;">
		<div id="container">
			<div id="inputblock">
				<div id="leftinputblock">
					<img id="logo_img" src="<?=$include_path?>/include/img/login_pic.png">
				</div>
				<div id="rightinputblock">
					<div id="title"><?=$text_title?></div>
					<form id= "input_form" method="POST">
						<input id="input_otp" type="password" name="OTP" autofocus autocomplete="off" placeholder="Presione su yubikey">
						<input id="submit_btn" type="submit" style="" value="Login">
					</form>
					<div id='login_message'></div>
					<div id='spinner'>
						<img id="img-spinner" src="<?=$include_path?>/include/img/ajax-loader.gif" alt="Loading"/>
					</div>
				</div>
			</div>	
		</div>
	</body>
</html>

<? exit;} ?>
