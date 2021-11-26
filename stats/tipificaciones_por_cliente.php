<?php

	$upOne = realpath(__DIR__ . '/..');
	require $upOne."/dashboard_header.php";

	$titulo_pagina_actual = "Contactos por Servidor"; 
	include "navbar.php";
	include "generaldata.php";
	include "db/SACManager.class.php";

	$dominios_a_mostrar = 30;
?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootbox.js/4.4.0/bootbox.min.js"></script>

<script>
$(function () {
	$('[data-toggle="popover"]').popover({'placement': 'left', 'width': '500px'})

	$('.btn_tipificaciones_revisadas').on("click", function() {
		var clicked_btn = this;
		console.log($(clicked_btn).data("typifications")) ;
		var selected_typifications = String($(clicked_btn).data("typifications")).split(",");
    	var text = selected_typifications.length == 1 ? 'esta tipificación como revisada' : 'estas '+selected_typifications.length+' tipificaciones como revisadas';
    	bootbox.prompt({
			title: "<h4><b>¿Esta seguro que quiere marcar "+text+"?</b></h4>"+
        				"<h4>De ser así, ingrese un detalle de la revisión: </h4>",
			inputType: 'textarea',
			callback: function(result){ 
				if(result == ""){
					clicked_btn.checked = false;
					bootbox.alert("Debe ingresar el detalle de la revisión");
				}else if(result == null){
        	    	clicked_btn.checked = false;
        		}else{
        			var logged_user = $("#logged_user").html();
        			$.post( "ajax/checktypifications.php", {'typification_ids': selected_typifications, 'operator': logged_user, 'detail' : result }, function( data ) {
        				console.log("data",data)
  						if(data.success){
  							location.reload();
  						}else{
  							bootbox.alert(data.message);
  						}
					},"json");
        		}
       		}
		});
	});
})
</script>

<style>
	.detail_table tr td, .detail_table tr th{
		text-align: center;
	}
	.popover{
		max-width: none;
		width: 	500px;
	}
	#logged_user{
		display: none;
	}
</style>

<?php
//obtengo el dato de los contactos de chat,ticket y telefono entre las fechas seleccionadas

$formated_info = array();
// foreach ($medios as $medio) 
// 	foreach ($hosting_names as $hosting_name)
// 		$formated_info[$medio][$hosting_name] = 0; 
//$hostings = array('Hosting.cl', 'PlanetaHosting', 'NinjaHosting', 'InkaHosting', 'Planeta Peru', 'HostingCenter')

//obtengo datos de chat y telefono desde el SAC
$SACManager = SACManager::singleton();
$query = "SELECT T.dominio, 
				C.nombre as compania, 
				T.plan_cliente, 
				COUNT(*) as total_contactos, 
				SUM(T.id_revision IS NULL) as contactos_no_revisados, 
				GROUP_CONCAT(DISTINCT(T.id_revision)) as revisiones_asociadas,
				GROUP_CONCAT(CASE WHEN T.id_revision IS NULL THEN T.id ELSE NULL END) as tipificaciones_asociadas
			FROM Tipificacion T LEFT JOIN Compania C ON T.id_marca = C.id
			WHERE fecha >= '$start_date' AND fecha <= '$end_date' 
			GROUP BY dominio 
			ORDER BY COUNT(*) DESC 
			LIMIT $dominios_a_mostrar";
$result_contactos = $SACManager->execQuery($query);
$contact_info = array();
$alert_message = "";
while ($contacto = mysqli_fetch_array($result_contactos,MYSQLI_ASSOC)) {
	$dominio = $contacto["dominio"];
	if($dominio == ""){
		// $alert_message = "Hay ".$contacto["total_contactos"]." tipificaciones que no están asociadas a un dominio dentro del período seleccionado";
	}else{
		$domain_info = array();
		$domain_info["compania"] = $contacto["compania"];
		$domain_info["total_contactos"] = $contacto["total_contactos"];
		$domain_info["contactos_no_revisados"] = $contacto["contactos_no_revisados"];
		$domain_info["revisiones_asociadas"] = $contacto["revisiones_asociadas"];
		$domain_info["tipificaciones_asociadas"] = $contacto["tipificaciones_asociadas"];
		$domain_info["plan_cliente"] = $contacto["plan_cliente"];
		$domain_info["detalle_contactos"] = array();
		$query = "SELECT CT.nombre, COUNT(*) as total_categoria, SUM(T.problema_solucionado) as solucionados 
				FROM Tipificacion T 
				LEFT JOIN CategoriaTipificacion CT ON T.id_categoria = CT.id 
				WHERE T.fecha >= '$start_date' AND T.fecha <= '$end_date' AND T.dominio = '$dominio' 
				GROUP BY T.id_categoria
				ORDER BY COUNT(*) DESC";
		$result_detalles = $SACManager->execQuery($query);
		while ($detalle = mysqli_fetch_array($result_detalles, MYSQLI_ASSOC)) {
			$domain_info["detalle_contactos"][] = array("total" => $detalle["total_categoria"], 
														"categoria" => $detalle["nombre"], 
														"solucionados" => $detalle["solucionados"]);
		}

		$contact_info[$dominio] = $domain_info;
	}
	
}

?>

<div id="main_frame">
	<div class="alert alert-info alert-dismissible" role="alert">
		 <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		 Se muestran los <?=$dominios_a_mostrar?> dominios con mas tipificaciones del período.
	</div>
	<?php if($alert_message != ""){ ?>
		<div class="alert alert-warning alert-dismissible" role="alert">
			 <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<?= $alert_message; ?>
		</div>
	<?php } ?>

	<span id="logged_user"><?=utf8_decode($_SESSION["user_name"])?></span>
	<table class="table">
		<tr>
			<th>Compañia</th>
			<th>Dominio</th>
			<th>Plan</th>
			<th>Numero de Contactos</th>
			<th>Revisados</th>
			<th></th>
			<th>Detalle</th>
			<th></th>
		</tr>
		<?php foreach ($contact_info as $dominio => $domain_info) { ?>
			<tr>
				<td><?=$domain_info["compania"];?></td>
				<td><?=$dominio;?></td>
				<td><?=$domain_info["plan_cliente"];?></td>
				<td>
					<?=$domain_info["total_contactos"]?>
				</td>
				<td><?=$domain_info["total_contactos"] - $domain_info["contactos_no_revisados"]?></td>
				<td>
					<?php 
					if($domain_info["contactos_no_revisados"] > 0){
						$s = $domain_info["contactos_no_revisados"] > 1 ? 's' : '';
						?>
						<button type="button" class="btn btn-success btn-small btn_tipificaciones_revisadas" 
							data-typifications="<?=$domain_info['tipificaciones_asociadas']?>">
							Marcar <?=$domain_info["contactos_no_revisados"]?> como revisado<?=$s?>
						</button>
						<?php
					}
					?>
				</td>
				<td>
					<?php 
					$full_table = "<table class='table table-condensed detail_table'>
										<tr>
											<th>Tipo de Contacto</th>
											<th>Total</th>
											<th>Solucionados</th>
										</tr>";
					$part_table = $full_table;
					$current_row = 0;
					$rows_part_table = 4;
					foreach ($domain_info["detalle_contactos"] as $detalle_contacto) {
						$current_row++;
						$new_row = "<tr>
										<td>".$detalle_contacto["categoria"]."</td>
										<td>".$detalle_contacto["total"]."</td>
										<td>".$detalle_contacto["solucionados"]."</td>
									</tr>";
						$full_table .= $new_row;
						if($current_row <= $rows_part_table){
							$part_table .= $new_row;
						}
					} 
					$full_table .= "</table>";
					$part_table .= "</table>";
					// echo "$part_table";

					// if($current_row > $rows_part_table){
						echo '<span class="glyphicon glyphicon-info-sign" 
										data-toggle="popover" 
										data-html="true" 
										data-trigger="hover" 
										title="Detalle de atencion" 
										data-content="'.$full_table.'">
							</span>';
					// }
					?>

				</td>
				<td>
					<a href="http://sistemas.hosting.cl/SAC/tipificaciones/buscar.php?servidor=&rango_fecha=<?=$cookie_daterange;?>&dominio=<?=$dominio?>&operador=-1" target="detalle_contactos">
						<span class="glyphicon glyphicon-new-window" aria-hidden="true" title="Ver tipificaciones"></span>
					</a>
				</td>
			</tr>
		<?php } ?>
		
	</table>
</body>

<?php require $upOne."/dashboard_footer.html";?>
	
