<?php

	$upOne = realpath(__DIR__ . '/..');
	require $upOne."/dashboard_header.php";

	$titulo_pagina_actual = "Temas Contacto"; 
	include "navbar.php";
	include "generaldata.php";
	include "db/SACManager.class.php";
?>

<?php

$formated_info = array();

$cantidad_temas = 10;
$SACManager = SACManager::singleton();
//obtengo los 10 temas de contacto mas frecuente entre las fechas seleccionadas
$query = "SELECT COUNT(*) AS contactos, C.nombre AS tipo FROM Tipificacion T 
		LEFT JOIN CategoriaTipificacion C ON T.id_categoria = C.id 
		WHERE T.fecha >= '$start_date' AND T.fecha <= '$end_date' 
		GROUP BY C.nombre ORDER BY COUNT(*) DESC LIMIT $cantidad_temas";

$result_contactos = $SACManager->execQuery($query);
$tipos_contacto = array();
while ($contacto = mysqli_fetch_array($result_contactos,MYSQLI_ASSOC)) 
{
	$tipo_contacto = $contacto["tipo"];
	if(!in_array($tipo_contacto, $tipos_contacto)){
		$tipos_contacto[] = $tipo_contacto;
	}
}
$tipo_contacto = "Responsabilidad de la Empresa";
if(!in_array($tipo_contacto, $tipos_contacto))
		$tipos_contacto[] = $tipo_contacto;
$tipos_contacto_csv = "";
foreach ($tipos_contacto as $contacto) {
	$tipos_contacto_csv.="'$contacto',";
}
$tipos_contacto_csv = substr($tipos_contacto_csv, 0,-1);

//obtengo los temas de contacto desde el SAC
$query = "SELECT DATE_FORMAT(T.fecha,'%Y-%m') AS fecha, C.nombre AS tipo, COUNT(*) AS cantidad 
		FROM Tipificacion T LEFT JOIN CategoriaTipificacion C ON T.id_categoria = C.id 
		WHERE C.nombre in ($tipos_contacto_csv) AND T.fecha >= '$start_date' AND T.fecha <= '$end_date'
		GROUP BY DATE_FORMAT(T.fecha,'%Y-%m'), C.nombre";
// echo "$query<br>";
$result_contactos = $SACManager->execQuery($query);
while ($contacto = mysqli_fetch_array($result_contactos,MYSQLI_ASSOC)) 
{
	$mes_contacto = $contacto["fecha"];
	$tipo_contacto = $contacto["tipo"];
	// echo "$mes_contacto - $tipo_contacto - ". $contacto["cantidad"]."<br>";
	$formated_info[$mes_contacto][$tipo_contacto] = $contacto["cantidad"];
}

//obtengo el total de tipificaciones por mes, para calcular el porcentaje de cada tema
$formated_info_percentage = array();
$query = "SELECT DATE_FORMAT(T.fecha,'%Y-%m') AS fecha, COUNT(*) AS total_mes 
		FROM Tipificacion T WHERE T.fecha >= '$start_date' AND T.fecha <= '$end_date'
		GROUP BY DATE_FORMAT(T.fecha,'%Y-%m')";
// echo "$query<br>";
$result_contactos = $SACManager->execQuery($query);
while ($contacto = mysqli_fetch_array($result_contactos,MYSQLI_ASSOC)) 
{
	$mes_contacto = $contacto["fecha"];
	foreach ($formated_info[$mes_contacto] as $tipo_contacto => $cantidad) {
		// if($tipo_contacto == "Datacenter: Bloqueo de IP")
		// 	echo "$cantidad / {$contacto["total_mes"]} ($mes_contacto - $tipo_contacto)<br>";
		$formated_info_percentage[$mes_contacto][$tipo_contacto] = round($cantidad/$contacto["total_mes"],3);
	}
}

//hago el row_data del grafico de cantidad
$row_data = "['x',";
foreach ($months as $mes) {
	$row_data .= "'$mes',";
}
$row_data = substr($row_data, 0, -1);
$row_data.="],";

//agrego el detalle del medio correspondiente
foreach ($tipos_contacto as $tipo_contacto) {
	$row_data.="['$tipo_contacto',";
	foreach ($months as $mes) {
		$row_data.="'".$formated_info[$mes][$tipo_contacto]."',";
	}
	$row_data = substr($row_data, 0, -1);
	$row_data.="],";
}
$row_data = substr($row_data, 0, -1);

//hago el row_data del grafico de porcentajes
$row_data_perc = "['x',";
foreach ($months as $mes) {
	$row_data_perc .= "'$mes',";
}
$row_data_perc = substr($row_data_perc, 0, -1);
$row_data_perc.="],";

//agrego el detalle del medio correspondiente
foreach ($tipos_contacto as $tipo_contacto) {
	$row_data_perc.="['$tipo_contacto',";
	foreach ($months as $mes) {
		$row_data_perc.="'".$formated_info_percentage[$mes][$tipo_contacto]."',";
	}
	$row_data_perc = substr($row_data_perc, 0, -1);
	$row_data_perc.="],";
}
$row_data_perc = substr($row_data_perc, 0, -1);

//echo $row_data_perc;

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
	   				type: 'pie',
	   				colors: {
			            <?=$color_string?>
			        },
	   				order: 'asc',
			        columns: [<?=$row_data?>]
			    },
		    axis: {
		        x: {
		            type: 'category' // this needed to load string x value
		        }
		    },
		    tooltip: {
		        grouped: true // Default true
		    }
		});
	}

	function generateGraphicTotal()
	{
		//alert(chat_data);
		var initdate = '<?=$start_date?>';
        var enddate = '<?=$end_date?>';

        chart_total = c3.generate({
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
	        bindto: '#total_chart',
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
		        }
		    },
		    tooltip: {
		        grouped: true // Default true
		    }
		});
	}

	function generateGraphicPercentage()
	{
		//alert(chat_data);
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
			        columns: [<?=$row_data_perc?>]
			    },
		    axis: {
		        x: {
		            type: 'category' // this needed to load string x value
		        },
		        y : {
		            tick: {
		                format: d3.format("%")
		            }
		        }
		    },
		    tooltip: {
		        grouped: true // Default true
		    }
		});
	}

	$(function() {
		generateGraphicTotal();
		generateGraphicPercentage();
		generateGraphicSummary();
	});
	// setTimeout(function () {
	//     chart_total.transform('pie');
	// }, 3000);

</script>


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
		/*float: left;*/
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
	<div class="alert alert-info alert-dismissible" role="alert">
		 <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		Se muestran los <?=$cantidad_temas?> tipos de contacto mas recurrentes del periodo, ademas de aquellos por Responsabilidad de la empresa. Los datos son obtenidos desde el sistema de tipificaciones
	</div>
	<div id="summary_chart_div">
		<div class='chart'>
				<h2 class='graph_title'>Resumen</h2>
				<div id='summary_chart' class='chart'></div>
		</div>
	</div>
	<div id="detail_charts_div">
		<h2 class='graph_title'>Detalle mensual</h2>
		<ul class="nav nav-tabs" role="tablist" id="graphs_nav">
			<li role="presentation" class="active"><a href="#grafico_porcentaje" aria-controls="grafico_porcentaje" role="tab" data-toggle="tab">Porcentaje</a></li>
			<li role="presentation"><a href="#grafico_cantidad" aria-controls="grafico_cantidad" role="tab" data-toggle="tab">Cantidad</a></li>
		</ul>
		<div class="tab-content">
			<div role="tabpanel" class="tab-pane active" id="grafico_porcentaje">
				<div class='chart'>
					<!--<h2 class='graph_title'>Temas de contacto (Porcentaje)</h2>-->
					<div id='percentage_chart' class='chart'></div>
				</div>
			</div>
			<div role="tabpanel" class="tab-pane" id="grafico_cantidad">
				<div class='chart'>
					<!--<h2 class='graph_title'>Temas de contacto (Total)</h2>-->
					<div id='total_chart' class='chart'></div>
				</div>
			</div>
		</div>
	</div>

	
	
</body>

<?php require $upOne."/dashboard_footer.html";?>