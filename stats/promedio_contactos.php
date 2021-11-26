<?php

	$upOne = realpath(__DIR__ . '/..');
	require $upOne."/dashboard_header.php";

	$titulo_pagina_actual = "Promedio Contactos"; 
	include "navbar.php";
	include "generaldata.php";
	include "db/SACManager.class.php";
	include "db/WHMCSManager.class.php";
?>
<style type="text/css">
	#main_frame{
		margin:40px;
	}
	#summary_chart_div{
		width: 20%;
		height: 900px;
		border-top: 1px solid #CACACA;
		border-right: 1px solid #CACACA;
		background-color: #F1F1F1;
		float: left;
	}
	#detail_charts_div{
		border-top: 1px solid #CACACA;
		width: 70%;
		height: 900px;
		float: left;
	}
	.chart{
		width: 90%;
		height: 70%;
		padding-left: 15px;
		display: inline-block;
	}
	.graph_title{
		padding-left: 30%;
	}
	#graphs_nav{
		margin-left: 20px;
	}
	#summary_chart{
		margin-top: 60px;
	}

</style>
<div id="main_frame">
	<div id="progress_message"></div>
	<div class="progress">
	  <div class="progress-bar progress-bar-striped active" role="progressbar"
	  aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width:0%">
	  </div>
	</div>

<?php



$contacts_per_month = array(); //arreglo asociativo con todos los user ids -> #contactos del WHMCS (unicos), por mes y por marca que se contactaron $fomrated_info[mes][marca] -> arreglo user_id
foreach ($months as $month)
	foreach ($hosting_names as $hosting_name)
		$contacts_per_month[$month][$hosting_name] = array();

$all_contact_userids = array(); //arreglo con todos los user_id -> #contactos del WHMCS (unicos) que se contactaron en el periodo
foreach ($hosting_names as $hosting_name)
		$all_contact_userids[$hosting_name] = array();


//////CONTACTOS CHAT Y TELEFONO

$SACManager = SACManager::singleton();
//obtengo los user_id del WHMCS por mes, por marca de los clientes que se contactaron durante el periodo para chat y telefono
$query = "SELECT id_cliente, fecha as fecha_completa, DATE_FORMAT(fecha,'%Y-%m') as fecha,hosting,dominio 
		FROM cliente C WHERE fecha >= '$start_date' and fecha <= '$end_date' AND dominio != 'nulldb'";

//BUSCO SOLO LOS DOMINIOS QUE TENGAN UN "." (para evitar los nn, cliente, etc)
$query = "SELECT T.id, T.fecha as fecha_completa, DATE_FORMAT(T.fecha,'%Y-%m') as fecha, C.nombre as hosting, T.dominio 
		FROM Tipificacion T LEFT JOIN Compania C ON T.id_marca = C.id 
		WHERE T.fecha >= '$start_date' and T.fecha <= '$end_date' AND T.dominio != 'nulldb' AND T.dominio LIKE '%.%'";
$result_dominios = $SACManager->execQuery($query);
$total = mysqli_num_rows($result_dominios);
$actual=0;
$factor_avance = 0.9;
cambiarMensajeProgreso("Revisando $total tipificaciones de chat y telefono entre $start_date y $end_date ...");
while ($contacto = mysqli_fetch_array($result_dominios,MYSQLI_ASSOC)) 
{
	$actual++;
	$porcentaje_actual = round($actual/$total,2)*100;
	if($porcentaje_actual != round(($actual-1)/$total,2)*100)
		cambiarPorcentajeAvance($porcentaje_actual*$factor_avance);
	$mes_contacto = $contacto["fecha"];
	//reviso casos en que el hosting de la tipificacion sea invalido
	if(trim($contacto["hosting"]) == "" || $contacto["hosting"] == "planetabrasil")
		continue;
	$hosting = $contacto["hosting"];
	if($hosting == "" || !is_array($contacts_per_month[$mes_contacto][$hosting]))
		continue;
	//busco el user id del WHMCS para el dominio
	$fecha_contacto_completa = $contacto["fecha_completa"];
	$dominio = strtolower(trim($contacto["dominio"]));
	$user_ids = buscarUseridPorDominio($dominio,$hosting,$fecha_contacto_completa);
	if($user_ids === false)
	{
		//echo "No se encontro user id para dominio <b>$dominio</b> en hosting <b>$hosting</b><br>";
		continue;
	}
	foreach ($user_ids as $user_id) 
	{
		$contacts_per_month[$mes_contacto][$hosting][] = $user_id;
		$all_contact_userids[$hosting][] = $user_id;
	}
}

//////CONTACTOS TICKETS
cambiarMensajeProgreso("Revisando tickets entre $start_date y $end_date ...");
$WHMCSManager = WHMCSManager::singleton();
//obtengo los user_id sin repetir por mes, por marca de los clientes que se contactaron durante el periodo para ticket
$query = "SELECT T.userid, DATE_FORMAT(T.date,'%Y-%m') as fecha FROM 
			(
				SELECT userid, date FROM tbltickets
					WHERE date >= '$start_date' AND date <= '$end_date' 
					AND admin = '' AND userid != 0  AND status != 'NULO' AND status != 'SPAM'
				UNION
				SELECT userid, date FROM tblticketreplies
					WHERE date >= '$start_date' AND date <= '$end_date' AND admin = '' AND userid != 0  
			) AS T
			LEFT JOIN tblhosting H ON T.userid = H.userid
			LEFT JOIN tblproducts P ON H.packageid = P.id
			WHERE T.date >= H.regdate AND (T.date <= DATE_ADD(H.nextduedate, INTERVAL 30 DAY) 
				OR ISNULL(H.nextduedate) OR H.nextduedate = '0000-00-00') 
				AND P.type != 'other'
			GROUP BY T.userid, DATE_FORMAT(T.date,'%Y-%m')";

$result_dominios_by_brand = $WHMCSManager->execQueryByBrand($query);
foreach ($result_dominios_by_brand as $hosting => $result_dominios) {
	foreach ($result_dominios as $contacto) 
	{
	 	$mes_contacto = $contacto["fecha"];
		$whmcs_userid = $contacto["userid"];
		$contacts_per_month[$mes_contacto][$hosting][] = $whmcs_userid;
		$all_contact_userids[$hosting][] = $whmcs_userid;
	}
}


foreach ($months as $month)
	foreach ($hosting_names as $hosting_name)
	{
		$contacts_per_month[$month][$hosting_name] = array_count_values($contacts_per_month[$month][$hosting_name]);
		$total_contacts = array_sum($contacts_per_month[$month][$hosting_name]);
		$clients_contact = sizeof($contacts_per_month[$month][$hosting_name]);
		$contacts_per_month[$month][$hosting_name] = round($total_contacts/$clients_contact,2);
	}

foreach ($hosting_names as $hosting_name)
{
	$all_contact_userids[$hosting_name] = array_count_values($all_contact_userids[$hosting_name]);
	$total_contacts = array_sum($all_contact_userids[$hosting_name]);
	$clients_contact = sizeof($all_contact_userids[$hosting_name]);
	$all_contact_userids[$hosting_name] = $total_contacts/$clients_contact;
}

///// RENDER DE LOS GRAFICOS
cambiarPorcentajeAvance(100) ;
cambiarMensajeProgreso("Dibujando los graficos ...");
//hago el row_data del grafico de detalle mensual
$row_data = "['x',";
foreach ($months as $mes) {
	$row_data .= "'$mes',";
}
$row_data = substr($row_data, 0, -1);
$row_data.="],";

foreach ($hosting_names as $hosting_name) {
	$row_data.="['$hosting_name',";
	foreach ($months as $mes) 
	{
		$percentage = round($contacts_per_month[$mes][$hosting_name],2);
		$row_data.="'".$percentage."',";
	}
	$row_data = substr($row_data, 0, -1);
	$row_data.="],";
}
$row_data = substr($row_data, 0, -1);

//hago el row_data del grafico de resumen
$row_data_resumen = "['x',";
foreach ($hosting_names as $hosting_name) {
	$row_data_resumen .= "'$hosting_name',";
}
$row_data_resumen = substr($row_data_resumen, 0, -1);
$row_data_resumen.="],";

$row_data_resumen.="['$start_date hasta $end_date',";
foreach ($all_contact_userids as $hosting_name => $promedio_contactos) {
	$row_data_resumen.="'$promedio_contactos',";
}
$row_data_resumen = substr($row_data_resumen, 0, -1);
$row_data_resumen .= "]";

removerElementosProgreso();

function buscarUseridPorDominio($dominio,$hosting,$fecha_contacto)
{
	$WHMCSManager = WHMCSManager::singleton();
	$dominio_utf8 = utf8_decode($dominio);
	//obtengo el usuario asociado al dominio en el WHMCS respectivo
	$query = "SELECT domain,userid FROM tblhosting H LEFT JOIN tblproducts P ON H.packageid = P.id
			WHERE (H.domain = '$dominio' OR H.domain = '$dominio_utf8') 
			AND '$fecha_contacto' >= H.regdate 
			AND ('$fecha_contacto' <= DATE_ADD(H.nextduedate, INTERVAL 30 DAY) 
				OR ISNULL(H.nextduedate) OR H.nextduedate = '0000-00-00')
			AND P.type != 'other' GROUP BY userid";
	$result_user_id = $WHMCSManager->execQueryInBrand($query,$hosting);
	if(sizeof($result_user_id) < 1)
	{
		//veo si en las notas esta el dominio (por caso de cambio de dominio)
		$query = "SELECT domain,userid FROM tblhosting H LEFT JOIN tblproducts P ON H.packageid = P.id
			WHERE H.notes LIKE '%$dominio%' AND ('$fecha_contacto' <= DATE_ADD(H.nextduedate, INTERVAL 30 DAY)
			OR ISNULL(H.nextduedate) OR H.nextduedate = '0000-00-00') 
			AND P.type != 'other' GROUP BY userid";
		$result_user_id = $WHMCSManager->execQueryInBrand($query,$hosting);
		if(sizeof($result_user_id) < 1)
			return false;
		if(sizeof($result_user_id) > 1)
		{
			// echo "Se encontro mas de una nota para <b>$dominio</b> de <b>$hosting</b> $dominio<br>".$query."<br>";
		}
		else
		{
			//echo "Se encontraron notas para el dominio <b>$dominio</b> de <b>$hosting</b><br>";
		}
	}
	$ret = array();
	foreach ($result_user_id as $row)
		$ret[] = $row["userid"];
	return $ret;
}

function cambiarMensajeProgreso($message)
{	
	echo "<script>$('#progress_message').html('$message')</script>";
}
function cambiarPorcentajeAvance($porcentaje_actual)
{
	echo "<script>$('.progress-bar').css('width', $porcentaje_actual+'%').attr('aria-valuenow', $porcentaje_actual);</script>";
}

function removerElementosProgreso()
{
	echo "<script>$('.progress').remove(); $('#progress_message').remove();</script>";
}
?>

<script type="text/javascript">
	var chart_total;
	var chart_percentage;

	function generateGraphicSummary()
	{
		//alert(chat_data);
		var initdate = '<?=$start_date?>';
        var enddate = '<?=$end_date?>';

        chart_total = c3.generate({
	        bindto: '#summary_chart',
	   		data: {
	   				x : 'x',
	   				type: 'bar',
	   				colors: {
			            <?=$color_string?>
			        },
	   				order: 'asc',
			        rows: [<?=$row_data_resumen?>]
			    },
		    axis: {
		        x: {
		            type: 'category' // this needed to load string x value
		        }
		    },
		    tooltip: {
		        grouped: false // Default true
		    },
		    legend: {
		        show: false
		    }
		});
	}

	function generateGraphicPercentage()
	{
		var initdate = '<?=$start_date?>';
        var enddate = '<?=$end_date?>';

        chart_percentage = c3.generate({
        	padding: {
		        top: 40,
		        right: 100,
		        bottom: 40,
		        left: 100,
		    },
		    size: {
		        height: 600,
		        width: 1200
		    },
	        bindto: '#percentage_chart',
	   		data: {
	   				x : 'x',
	   				type: 'spline',
	   				colors: {
			            <?=$color_string?>
			        },
	   				order: 'asc',
			        columns: [<?=$row_data?>]
			    },
		    axis: {
		        x: {
		            type: 'category' // this needed to load string x value
		        },
		        y: {
           			min: 1,
		        }
		    },
		    tooltip: {
		        grouped: true // Default true
		    }
		});
	}

	$(function() {
		generateGraphicPercentage();
		generateGraphicSummary();
	});

</script>

	<div class="alert alert-info alert-dismissible" role="alert">
		 <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		Esta página muestra el numero de contactos promedio de aquellos clientes que se contactaron alguna vez en el periodo seleccionado.
		Los datos son obtenidos desde el sistema de tipificaciones (para chat y telefono) y desde los WHMCS para los tickets.<br>
		Consideraciones para el cálculo:<br>
			&nbsp;&nbsp;&bull;&nbsp;<b>Servicio de hosting activo:</b> Un servicio de hosting se considera activo entre la fecha de contratacion y 30 días despues de la fecha de término.<br>
			&nbsp;&nbsp;&bull;&nbsp;<b>Cliente activo:</b> Cliente que tenía algun servicio de hosting activo en la fecha de contacto.<br>
			&nbsp;&nbsp;&bull;&nbsp;<b>Contacto válido:</b> Contacto realizado por un cliente activo, ya sea por chat, ticket o teléfono.<br>
	</div>
	<div id="summary_chart_div">
		<div class='chart'>
				<h2 class='graph_title'>Resumen</h2>
				<div id='summary_chart' class='chart'></div>
		</div>
	</div>
	<div id="detail_charts_div">
		<h2 class='graph_title'>Detalle mensual</h2>
		<div class='chart'>
			<!--<h2 class='graph_title'>Temas de contacto (Porcentaje)</h2>-->
			<div id='percentage_chart' class='chart'></div>
		</div>
	</div>

<div style='float:left; width:40%;'>
	<hr>CONTACTOS POR CLIENTE (MES Y TOTAL)<br>
	<pre style='background-color: yellow;'><?=print_r($contacts_per_month)?></pre>
	<pre><?=print_r($all_contact_userids)?></pre>
</div>
</body>

<?php require $upOne."/dashboard_footer.html";?>