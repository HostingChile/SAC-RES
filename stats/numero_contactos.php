<?php

	$upOne = realpath(__DIR__ . '/..');
	require $upOne."/dashboard_header.php";

	$titulo_pagina_actual = "Numero de Contactos"; 
	include "navbar.php";
	include "generaldata.php";
	include "db/SACManager.class.php";
	include "db/WHMCSManager.class.php";
	include "db/AsteriskManager.class.php";
	include "db/ISCOLManager.class.php";
?>

<?php
//obtengo el dato de los contactos de chat,ticket y telefono entre las fechas seleccionadas

$formated_info = array();

//obtengo datos de chat y telefono desde el SAC
$SACManager = SACManager::singleton();

$query = "SELECT DATE_FORMAT(T.fecha,'%Y-%m') as fecha,C.nombre AS hosting,TC.nombre as medio_contacto,COUNT(*) as cantidad 
		FROM Tipificacion T LEFT JOIN Compania C ON T.id_marca = C.id
		LEFT JOIN TipoContacto TC ON T.id_tipocontacto = TC.id 
		WHERE T.fecha >= '$start_date' and T.fecha <= '$end_date'
		GROUP BY DATE_FORMAT(T.fecha,'%Y-%m'),T.id_tipocontacto, T.id_marca";


$result_contactos = $SACManager->execQuery($query);
while ($contacto = mysqli_fetch_array($result_contactos,MYSQLI_ASSOC)) {
	$mes_contacto = $contacto["fecha"];
	$medio_contacto = $contacto["medio_contacto"];
	$hosting = $contacto["hosting"];
	$formated_info[$mes_contacto][$medio_contacto][$hosting] = $contacto["cantidad"];
}

//obtengo los datos del total de llamadas desde asterisk

$query = "
SELECT q.fecha, COUNT(*) as cantidad FROM 
(
	SELECT DATE_FORMAT(calldate,'%Y-%m') as fecha FROM cdr
	WHERE 
	calldate >= '$start_date' AND 
	calldate <= '$end_date' AND
	recordingfile != '' AND 
	(dst LIKE '5__' OR cnum LIKE '5__') AND 
	disposition = 'ANSWERED'
	GROUP BY recordingfile
) as q 
GROUP BY q.fecha";

$AsteriskManager = AsteriskManager::singleton();

$result_llamadas = $AsteriskManager->execQueryASTDB($query);
while ($llamadas = mysqli_fetch_array($result_llamadas,MYSQLI_ASSOC)) {
	$formated_info[$llamadas["fecha"]]["Telefono"]["Total"] = $llamadas["cantidad"];
}

//obtengo el total de chats desde el iscol
$mapeo_meses = array('01' => 'Enero','02' => 'Febrero','03' => 'Marzo','04' => 'Abril', 
			'05' => 'Mayo','06' => 'Junio','07' => 'Julio','08' => 'Agosto', 
			'09' => 'Septiembre','10' => 'Octubre','11' => 'Noviembre','12' => 'Diciembre');
foreach ($months as $month) {
	$month_data = explode("-",$month);
	$fecha_iscol = $mapeo_meses[$month_data[1]]."-".$month_data[0];
	//echo "FECHA ISCOL: ".$fecha_iscol."<br>";
	$ISCOLManager = ISCOLManager::singleton();
	$query = "SELECT cantidad_chat FROM calificaciones 
			WHERE operador = 'Total recibidos' and fecha = '$fecha_iscol'";
	$result_chats = $ISCOLManager->execQuery($query);
	$chats = mysqli_fetch_array($result_chats,MYSQLI_ASSOC);
	$formated_info[$month]["Chat"]["Total"] = $chats["cantidad_chat"];
}

//obtengo datos de tickets desde los WHMCS de cada marca
$WHMCSManager = WHMCSManager::singleton();
$query = "SELECT DATE_FORMAT(date,'%Y-%m') AS fecha, COUNT(*) as total FROM tbltickets 
			WHERE date >= '$start_date' AND date <= '$end_date' 
			AND status != 'Nulo' AND status != 'SPAM' 
			GROUP BY DATE_FORMAT(date,'%Y-%m')";
$whmcs_results = $WHMCSManager->execQueryByBrand($query);
foreach ($whmcs_results as $brand => $whmcs_result) {
	foreach ($whmcs_result as $final_whmcs_result) 
	{
		$formated_info[$final_whmcs_result["fecha"]]["Ticket"][$brand] = $final_whmcs_result["total"];
		$formated_info[$final_whmcs_result["fecha"]]["Ticket"]["Total"] += $final_whmcs_result["total"];

	}
}

//genero el string para poblar el grafico a partir de los datos formateados
$medios_data=array(); //row_data["chat"] -> datos que generan el grafico de chat

foreach ($medios as $medio) 
{
	//agrego los meses para que aparezcan en el eje x
	$row_data = "['x',";
	foreach ($months as $mes) {
		$row_data .= "'$mes',";
	}
	$row_data = substr($row_data, 0, -1);
	$row_data.="],";

	//agrego el total de los telefonos
	if($medio == "Telefono" || $medio == "Chat" || $medio == "Ticket")
	{
		$row_data.="['Total',";
		foreach ($months as $mes)
		{
			if($medio == "Chat" && $mes == date("Y-m"))
				continue;
			$row_data.="'".$formated_info[$mes][$medio]["Total"]."',";
		}
		$row_data = substr($row_data, 0, -1);
		$row_data.="],";
	}

	//agrego el detalle del medio correspondiente
	foreach ($hostings_sac as $hosting_sac) {
		$row_data.="['$hosting_sac',";
		foreach ($months as $mes) {
			//echo "formated_info[$mes][$medio][$hosting_sac]<br>";
			$row_data.="'".$formated_info[$mes][$medio][$hosting_sac]."',";
		}
		$row_data = substr($row_data, 0, -1);
		$row_data.="],";
	}


	$row_data = substr($row_data, 0, -1);
	$medios_data[$medio] = $row_data;
}
// echo "<pre>";
// print_r($formated_info);
// echo "</pre>";
 // echo "<pre>";
 // print_r($medios_data);
 // echo "</pre>";
// $chat_data = $medios_data["Chat"];
// echo $chat_data;
?>

<script type="text/javascript">
	<?php
	foreach($medios as $medio)
	{
	?>
		function generate<?=$medio?>Graphic()
		{
			//alert(chat_data);
			var initdate = '<?=$start_date?>';
	        var enddate = '<?=$end_date?>';

	        var chart = c3.generate({
		        bindto: '#<?=$medio?>_chart',
		   		data: {
		   				x : 'x',
		   				type: 'bar',
		   				types: {
				            Total: 'spline',
				        },
		   				colors: {<?=$color_string?>},
		   				order: 'asc',
		   				groups: [['Hosting', 'PlanetaHosting','HostingCenter','iHost','NinjaHosting','PlanetaPeru','InkaHosting','1Hosting','NinjaPeru','PlanetaColombia','HostingcenterColombia','NinjaColombia']],
				        columns: [<?=$medios_data[$medio]?>]
				    },
			    axis: {
			        x: {
			            type: 'category' // this needed to load string x value
			        }
			    }
			});
		}
	<?php
	}
	?>

	$(function() {
		<?php
		foreach($medios as $medio)
		{
			echo "generate".$medio."Graphic();";
		}
		?>
	});

</script>

<style type="text/css">
	#main_frame{
		margin:40px;
	}
	.chart{
		width: 400px;
		/*float: left;*/
		display: inline-block;
		margin-right: 80px;
	}
	.graph_title{
		padding-left: 45%;
	}
</style>

<?php
	//stacked bar chart
	//     -------
	//	   planeta
	//	   -------
	//
	//     hosting 
	//	   -------
	//      CHAT                     TICKET                   TELEFONO
?>
<div id="main_frame">
	<div class="alert alert-info alert-dismissible" role="alert">
		 <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		Los datos de Chat y Telefono por marca son obtenidos desde el sistema de tipificaciones.<br>
		El total de chats es obtenido desde el ISCOL, y el de telefonos directamente desde Asterisk
	</div>
	<?php
	foreach ($medios as $medio) {
		echo "<div class='chart'>
					<h2 class='graph_title'>$medio</h2>
					<div id='".$medio."_chart' class='chart'></div>
			</div>";
	}
	?>
</body>

<?php require $upOne."/dashboard_footer.html";?>
	
