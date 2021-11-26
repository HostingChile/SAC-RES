<?php
	$upOne = realpath(__DIR__ . '/..');
	require $upOne."/dashboard_header.php";

	require __DIR__.'/DB/SACManager.class.php';
	$SACManager = SACManager::singleton();

	$yubikey_username = utf8_decode($_SESSION["user_name"]);
?>


<link rel="stylesheet" type="text/css" href="<?= $root_dir_name;?>/tipificaciones/include/css/attention_history.css">
<script type="text/javascript" src="include/js/bootbox.min.js"></script>

<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

<script>
$(function() {
	$('#correct_typifications_operator').on('click',function(){
		$.post( rutaAjaxLocal+'correctTypificationsOperator.php', function( data ) {
			if(data.success){
				var message = "No hubo tipificaciones actualizadas";
				if(data.affected_records == 1){
					message = "Exito: Una tipificacion fue actualizada";
				}
				if(data.affected_records > 1){
					message = "Exito: "+data.affected_records+" tipificaciones actualizadas";
				}
				bootbox.alert(message);
			}else{
				bootbox.alert("Error: "+data.message);
			}
		}, 'json')
		.fail(function(XMLHttpRequest, textStatus, errorThrown){
			hideLoader();
			console.log("error: "+errorThrown);
			alert( "error\n"+textStatus+"\n"+errorThrown );
		});
	});	
});
</script>

<?php
		
	//http://sistemas.hosting.cl/SAC/tipificaciones/ajax/correctTypificationsOperator.php
	require __DIR__."/include/filter_tipification_bar.php";

	$total_tipificaciones = mysqli_num_rows($result_tipificaciones);
	$texto_tipificaciones = $total_tipificaciones == 1 ? "tipificación" : "tipificaciones";
	echo "<button id='correct_typifications_operator' class='btn'>Corregir mal formato en operador</button>
			<h3>Total Búsqueda: $total_tipificaciones $texto_tipificaciones</h3><br>";

	echo '<div id="historial_atenciones">';
	while($tipificacion = mysqli_fetch_array($result_tipificaciones)){
		$new_atention_class = "";
		$id = $tipificacion["id"];
		$client_name = $tipificacion["nombre_cliente"];
		$contact_reason = $tipificacion["categoria_tipificacion"];
		$contact_detail = $tipificacion["detalle"];
		$operator = $tipificacion["operador"];
		$contact_date = $tipificacion["fecha"];
		$contact_domain = $tipificacion["dominio"];
		$problem_solved = $tipificacion["problema_solucionado"];
		$problem_solved_message = $problem_solved == 1 ? "Problema solucionado" : "Problema no solucionado";
		$problem_solved_class = $problem_solved == 1 ? "problem_solved" : "problem_not_solved";
		$problem_checked_message =  is_null($tipificacion["id_revision"]) ? '' :  '';
		$problem_checked_message = "";
		$company_img = $tipificacion["img_compania"];
		$contact_img = $tipificacion["img_tipocontacto"];
		$ip_servidor = $tipificacion["ip_servidor"];

		if(!is_null($tipificacion["id_revision"])){
			$query = "SELECT * FROM RevisionTipificacion WHERE id = '{$tipificacion["id_revision"]}'";
			$revision = mysqli_fetch_array($SACManager->execQuery($query));
			$problem_checked_message = '<span class="label label-success" title="'.$revision["fecha"].' - '.$revision["operador"].'&#013;'.$revision["comentario"].'">Revisado</span>' ;
		}
		?>
		<div class="atencion row">
			<div class="col-lg-1">
				<img class="avatar" src="include/img/avatarDefault.png">
			</div>
			<div class="message col-lg-6 <?=$new_atention_class?>">
				<div class="client_detail"><i><?=$contact_domain?></i> - <?=$client_name?> (<?=$contact_reason?>) - <?=$ip_servidor?> </div>
				<div class="img_container">
					<img class="small_img" src="include/img/marcas/<?=$company_img?>" >
					<img class="small_img" src="include/img/medios/<?=$contact_img?>" >
					#<?=$id?>
				</div>
				<p class="problem_checked_message"><?=$problem_checked_message;?></p>
				<p class="message_content"><?=$contact_detail?></p>
				<p class="problem_solved_message <?=$problem_solved_class?>"><?=$problem_solved_message?></p>
				<p class="message_info"><?=$operator?> - <?=$contact_date?></p>
			</div>
			<div class="end_message"></div>
		</div>
		<?php
	}

	echo '</div>';

?>


<?php
require $upOne."/dashboard_footer.html";
?>
