<?php

	$upOne = realpath(__DIR__ . '/..');
	require $upOne."/dashboard_header.php";

	$titulo_pagina_actual = "Contactos por Servidor"; 
	include "navbar.php";
	include "generaldata.php";
	include "db/SACManager.class.php";

	$servidores_a_mostrar = 30;
?>

<script>
$(function () {
	$('[data-toggle="popover"]').popover()
})
</script>

<?php
//obtengo el dato de los contactos de chat,ticket y telefono entre las fechas seleccionadas

$formated_info = array();
// foreach ($medios as $medio) 
// 	foreach ($hosting_names as $hosting_name)
// 		$formated_info[$medio][$hosting_name] = 0; 
//$hostings = array('Hosting.cl', 'PlanetaHosting', 'NinjaHosting', 'InkaHosting', 'Planeta Peru', 'HostingCenter')

//obtengo datos de chat y telefono desde el SAC
$SACManager = SACManager::singleton();
$query = "SELECT ip_servidor, COUNT(*) as total_contactos FROM Tipificacion WHERE fecha >= '$start_date' AND fecha <= '$end_date' GROUP BY ip_servidor ORDER BY COUNT(*) DESC LIMIT $servidores_a_mostrar";
$result_contactos = $SACManager->execQuery($query);
$contact_info = array();
$alert_message = "";
while ($contacto = mysqli_fetch_array($result_contactos,MYSQLI_ASSOC)) {
	$ip_servidor = $contacto["ip_servidor"];
	if($ip_servidor == ""){
		$alert_message = "Hay ".$contacto["total_contactos"]." tipificaciones que no están asociadas a un servidor dentro del período seleccionado";
	}else{
		$server_info = array();
		$server_info["total_contactos"] = $contacto["total_contactos"];
		$server_info["detalle_contactos"] = array();
		$query = "SELECT CT.nombre, COUNT(*) as total_categoria FROM Tipificacion T 
				LEFT JOIN CategoriaTipificacion CT ON T.id_categoria = CT.id 
				WHERE T.fecha >= '$start_date' AND T.fecha <= '$end_date' AND T.ip_servidor = '$ip_servidor' 
				GROUP BY T.id_categoria
				ORDER BY COUNT(*) DESC";
		$result_detalles = $SACManager->execQuery($query);
		while ($detalle = mysqli_fetch_array($result_detalles, MYSQLI_ASSOC)) {
			$server_info["detalle_contactos"][] = array("total" => $detalle["total_categoria"], "categoria" => $detalle["nombre"]);
		}

		$contact_info[$ip_servidor] = $server_info;
	}
	
}

?>

<div id="main_frame">
	<div class="alert alert-info alert-dismissible" role="alert">
		 <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		 Se muestran los <?=$servidores_a_mostrar?> servidores con mas tipificaciones del período.
	</div>
	<?php if($alert_message != ""){ ?>
		<div class="alert alert-warning alert-dismissible" role="alert">
			 <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<?= $alert_message; ?>
		</div>
	<?php } ?>



	<table class="table">
		<tr><th>Servidor</th><th>Numero de Contactos</th><th>Detalle</th><th></th></tr>
		<?php foreach ($contact_info as $ip_servidor => $server_info) { ?>
			<tr>
				<td><?=$ip_servidor;?></td>
				<td><?=$server_info["total_contactos"]?></td>
				<td>
					<?php 
					$full_table = "<table class='table table-condensed'>";
					$part_table = "<table class='table table-condensed'>";
					$current_row = 0;
					$rows_part_table = 4;
					foreach ($server_info["detalle_contactos"] as $detalle_contacto) {
						$current_row++;
						$new_row = "<tr><td>".$detalle_contacto["categoria"]."</td><td>".$detalle_contacto["total"]."</td></tr>";
						$full_table .= $new_row;
						if($current_row <= $rows_part_table){
							$part_table .= $new_row;
						}
					} 
					$full_table .= "</table>";
					$part_table .= "</table>";
					// echo "$part_table";

					// if($current_row > $rows_part_table){
						echo '<span class="glyphicon glyphicon-info-sign" data-toggle="popover" data-html="true" data-trigger="hover" title="Detalle de atencion" data-content="'.$full_table.'"></span>';
					// }
					?>

				</td>
				<td>
					<a href="http://sistemas.hosting.cl/SAC/tipificaciones/buscar.php?dominio=&rango_fecha=<?=$cookie_daterange;?>&servidor=<?=$ip_servidor?>&operador=-1" target="detalle_contactos">
						<span class="glyphicon glyphicon-new-window" aria-hidden="true" title="Ver tipificaciones"></span>
					</a>
				</td>
			</tr>
		<?php } ?>
		
	</table>
</body>

<?php require $upOne."/dashboard_footer.html";?>
	
