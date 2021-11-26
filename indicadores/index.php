<?php 
	$upOne = realpath(__DIR__ . '/..');
	require $upOne."/dashboard_header.php";
?>
	<link rel="stylesheet" type="text/css" href="include/css/index.css">
	<script>
		$(function () {
			//popover
		  	$('[data-toggle="popover"]').popover({
		        html: true,
		        trigger: 'manual'
		    })
		    .click(function(e) {
		    	$(".popover").popover("hide");
		        $(this).popover('show');
		        e.preventDefault();
		    }).css('cursor','pointer');

		    $(".panel-heading").click(function(e) {
		    	$(".popover").popover("hide");
		    });
		    //seleccionar la fila (borde azul) al hacer click
			$("tr:nth-child(n+3)").click(function(){
			    var highlight = "2px solid rgb(0, 119, 255)";
			    var initial = ""
			    var paint=false;
			    //if($(this).css("border") != highlight)
			        var paint = true;
			    $("tr").css("border-bottom","1px solid black");
			    $("tr").css("border-top","1px solid black");
			    $("tr").css("border-left","2px solid black");
			    $("tr").css("border-right","2px solid black");
			    if(paint)
			        $(this).css("border",highlight);
			});  
		});  
	</script>

<?php 
require("navbar.php");

error_reporting(0);
global $periodos_bono_al_ano;
global $nombre_periodo_bono;
global $periodo_bono;

?>

<?php
$mostrar_resumen_periodo = true;
$database = DBManager::singleton();
//-----TITULO CON EL AREA, TRIMESTRE---------//
//junto con links al peridoo siguiente y anterior
$condicion_jefe = !in_array(3, $areas_aceptadas) ? " AND jefe_de_area != 1 ":"";
$titulo = "$nombre_periodo_bono $periodo-$ano";

$ano_previo = $periodo==1 ? $ano -1 : $ano;
$periodo_previo = $periodo==1 ? $periodos_bono_al_ano : $periodo - 1;

$ano_siguiente = $periodo == $periodos_bono_al_ano ? $ano + 1: $ano;
$periodo_siguiente = $periodo == $periodos_bono_al_ano ? 1 : $periodo + 1;

echo "<div id='info_trimestre'>
		".getLink($periodo_previo,$ano_previo,$id_area,true)."
		<span style='font-size:25px;font-weight:bold;'>$titulo</span>
		".getLink($periodo_siguiente,$ano_siguiente,$id_area,false)."
	</div>";

function getLink($trim, $year, $areaa,$prev)
{
	global $base_url;
	global $nombre_periodo_bono;
	$glyphicon = $prev ? "glyphicon-menu-left": "glyphicon-menu-right";
	return "<a 
		href='$base_url/index.php?periodo=$trim&ano=$year&area=$areaa' 
		title='$nombre_periodo_bono $trim-$year'>
		<span class='glyphicon $glyphicon' aria-hidden='true'></span>
	</a>";
}
//--------ACCORDION---------
echo '<div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true" style="margin-top:30px;">';

//------HEADER COMUN TABLAS MENSUALES---------
//PRIMERA FILA
//le pongo los medios a la tabla
$query = "SELECT COUNT(*) AS indicadores,I.medio,M.nombre FROM indicadores I LEFT JOIN medios M ON I.medio = M.id WHERE I.id_area = $id_area AND activo = 1  GROUP BY medio ORDER BY I.medio ASC";
	$result_medios = $database->execQuery($query);
	$cant_medios = mysqli_num_rows($result_medios);
	$nombre_medios = array();
	//if($cant_medios > 1)
	{
		$table_header = "<tr class='fila_medios'><td></td>";
	while (list($indicadores,$medio,$nombre) = mysqli_fetch_array($result_medios, MYSQLI_NUM))
	{
		$nombre_medios[$medio] = ucfirst($nombre);
		$table_header.="<td colspan='$indicadores'>".ucfirst($nombre)."</td>";
	}
	$table_header.="<td colspan='100%'>Final</td></tr>";
}

//SEGUNDA FILA
$table_header.="<tr class='fila_indicadores'><td></td>";
//le pongo los indicadores por medio a la tabla
$indicadores = array();
$query = "SELECT id,nombre,ponderacion,explicacion,excepcion FROM indicadores WHERE id_area = $id_area AND activo = 1  ORDER BY medio ASC,id";
	$result_indicadores = $database->execQuery($query);
while (list($id,$nombre,$ponderacion,$explicacion,$excepcion) = mysqli_fetch_array($result_indicadores, MYSQLI_NUM))
{
	$indicadores[] = $id;
	//$explicacion = strlen($explicacion)>0?"$explicacion<br>":"";
	$excepcion = strlen($excepcion)>0?"<h5>$excepcion</h5><hr>":"";
	$data_popover = "$excepcion<h5><b>Rangos</b></h5>";
	//hago una tabla con los rango de notas
	$data_popover.="<table class=indicator_range_table><tr><th>Limite Inferior</th><th>Limite Superior</th><th>Nota</th></tr>";
	$query = "SELECT R.limite_inferior,R.limite_superior,ROUND(R.nota*I.ponderacion/100,1) AS nota_final 
				FROM rangos R LEFT JOIN indicadores I ON R.id_indicador = I.id 
				WHERE id_indicador = $id";
		$result_rangos = $database->execQuery($query);
	while (list($limite_inferior,$limite_superior,$nota_final) = mysqli_fetch_array($result_rangos, MYSQLI_NUM))
	{
		$nota_final = floor($nota_final)!=$nota_final?$nota_final:round($nota_final,0);
		$data_popover.="<tr><td>$limite_inferior</td><td>$limite_superior</td><td>$nota_final</td></tr>";
	}
	$data_popover.="</table>";
	$glyphicon = "<span class='glyphicon glyphicon-info-sign' aria-hidden='true' 
						style='color:rgb(255, 107, 0);' data-toggle='popover' 
						data-placement='bottom' data-container='body' 
						title='<h4>$explicacion</h4>' data-content='$data_popover'></span>";
	$table_header.="<td>$nombre<br>($ponderacion ptos) $glyphicon</td>";
}
//si hay mas de un medio, pongo cada uno al final (para poner las ponderaciones de cada usuario)
//if($cant_medios>1)
{
	$border="style='border-left: 2px solid black;'";
	foreach($nombre_medios as $id=>$nombre_medio)
	{
		$table_header.="<td $border>$nombre_medio</td>";
		$border = "";
	}
}
//pongo una columna para la nota final
$table_header.="<td style='border-left: 2px solid black;'>Nota Final</td></tr>";
//------FIN HEADER COMUN---------

//----- TABLAS MENSUALES---------
$tables = array();
//por cada mes, pongo la informacion de cada usuario
$mes_inicial = ($periodo - 1)*$periodo_bono+1;
$nombre_meses = array("Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");
$meses = array();
for($i=$mes_inicial;$i<$mes_inicial+$periodo_bono;$i++)
{
	$meses[] = $i;
	$month_table="<table id='month_table'><tr><td colspan='100%'>{$nombre_meses[$i-1]} - $ano</td></tr>$table_header";
	$month_table="<table id='month_table'>$table_header";
	//para cada operador activp del area, pongo la informacion de sus indicadores para el mes
	$query = "SELECT id,nombre,apellido,jefe_de_area FROM operadores WHERE id_area = $id_area AND activo = 1 $condicion_jefe ORDER BY jefe_de_area DESC,apellido,nombre";
	$result_operadores = $database->execQuery($query);
	while (list($id_operador,$nombre,$apellido,$jefe_de_area) = mysqli_fetch_array($result_operadores, MYSQLI_NUM))
	{
		$dist = $jefe_de_area == 1 ? "(*)":"";
		$month_table.= "<tr class='fila_info_usuario'><td>$nombre $apellido$dist</td>";
		foreach ($indicadores as $indicador)
		{
			$query = "SELECT ROUND(nota*ponderacion/100,1) AS nota_ponderada,datos 
			FROM indicadores I  
			LEFT JOIN historial_indicadores HI ON HI.id_indicador = I.id 
			WHERE I.id = $indicador AND HI.id_operador = $id_operador 
			AND HI.mes = $i AND HI.año = $ano AND I.activo = 1
			ORDER BY I.medio ASC,I.id";

			$result_historial = $database->execQuery($query);
			if(mysqli_num_rows($result_historial)!=1)
				$month_table .= "<td>--</td>";
			else
			{
				list($nota_ponderada,$datos) = mysqli_fetch_array($result_historial, MYSQLI_NUM);
				$datos = $datos;
				$datos = json_decode($datos);
				// if(! json_last_error() == JSON_ERROR_NONE){
				// 	echo "Error en json:<br>$datos<br>$query";
				// }
				
				$tmp = "";
				foreach ($datos as $key => $value)
				{
					if(is_numeric($value) && strpos($value, '.'))
						$value = round($value,2);
					$tmp .= "$key : $value<br>";
				}
				$nota_ponderada = floor($nota_ponderada) != $nota_ponderada?$nota_ponderada:round($nota_ponderada,0);
				$month_table.="<td data-toggle='popover' data-container='body' data-placement='bottom'
								title='Detalle' data-content='$tmp'>$nota_ponderada ptos</td>";
			}
		}
		//agrego la info de los ponderadores de cada medio
		$border = "style='border-left: 2px solid black'";
		foreach ($nombre_medios as $id => $nombre_medio) {
			$query = "SELECT * FROM historial_ponderadores WHERE id_operador = $id_operador 
			AND medio = '".strtolower($nombre_medio)."' AND mes = $i AND año = $ano";
			$result_pond = $database->execQuery($query);
			$ponderador = mysqli_num_rows($result_pond)>0?mysqli_fetch_array($result_pond,MYSQLI_ASSOC)["ponderador"]:"";
			$ponderador=round($ponderador*100,1);
			$month_table.="<td $border>$ponderador%</td>";
			$border = "";
		}
		//agrego la info de la nota final
		$query = "SELECT * FROM notas_finales WHERE id_operador = $id_operador AND mes = $i AND año = $ano";
		$result_nota = $database->execQuery($query);
		$nota_final = mysqli_num_rows($result_nota)>0?mysqli_fetch_array($result_nota,MYSQLI_ASSOC)["nota_final"]:"";
		$month_table.="<td style='border-left:2px solid black'>$nota_final ptos</td>";
		$month_table.="</tr>";
	}
	$month_table.="</table>";
	$tables[$nombre_meses[$i-1]] = $month_table;


}
if($id_area == 2){
	?><img id="negan_img" src="include/img/negan.jpg"><?php
}

foreach ($tables as $mes => $table) 
{
	$mes_id = array_search($mes, $nombre_meses) + 1;
	$class_open_accordion = "$mes_id" == "$mes_actual" && $ano == $ano_actual ? "in" : "";
	?>
	<div class="panel panel-success panel-mes">
	    <div class="panel-heading" role="tab" id="heading<?=$mes?>">
	      <h4 class="panel-title">
	        <a class="collapsed" data-toggle="collapse" data-parent="#accordion" href="#collapse<?=$mes?>" aria-expanded="false" aria-controls="collapse<?=$mes?>">
	          <?="$mes - $ano";?>
	        </a>
	      </h4>
	    </div>
	    <div id="collapse<?=$mes?>" class="panel-collapse collapse <?=$class_open_accordion?>" role="tabpanel" aria-labelledby="heading<?=$mes?>">
	      <div class="panel-body">
	      	<?="$table"?> 
	      </div>
	    </div>
	</div>
	<?php
}

//----- FIN TABLAS MENSUALES--------


//---------TABLA TRIMESTRAL------
if($mostrar_resumen_periodo)// && in_array(3, $areas_aceptadas))
{
	//pongo la informacion trimestral por cada usuario
	$trimestral_table="<table id='trimestral_table'>
							<tr><td></td><td>Nota Final</td><td>Bono</td></tr>";
	//para cada operador activp del area, pongo la informacion de sus indicadores para el mes
	$query = "SELECT id,nombre,apellido FROM operadores WHERE id_area = $id_area AND activo = 1 $condicion_jefe ORDER BY jefe_de_area DESC,apellido,nombre ";
	$result_operadores = $database->execQuery($query);
	while (list($id_operador,$nombre,$apellido) = mysqli_fetch_array($result_operadores, MYSQLI_NUM))
	{
		$trimestral_table.= "<tr><td>$nombre $apellido</td>";
		//OBTENGO LA NOTA TRIMESTRAL
		$query = "SELECT ROUND(AVG(nota_final),0) as nota_periodo FROM notas_finales 
			WHERE id_operador = $id_operador AND año = $ano 
			AND mes >= {$meses[0]} AND mes <= {$meses[sizeof($meses)-1]}";
			// echo "$query<br>";
		$result_nota = $database->execQuery($query);
		$nota_periodo = mysqli_fetch_array($result_nota, MYSQLI_ASSOC)["nota_periodo"];

		//OBTENGO EL BONO QUE CORREPSONDE SEGUN LA NOTA DEL PERIODO
		$query = "SELECT bono FROM rangos_bono WHERE id_area = $id_area AND año = '$ano' AND periodo = '$periodo'
				AND $nota_periodo >= nota_inferior AND $nota_periodo <= nota_superior";
		$result_bono = $database->execQuery($query);
		$bono = $result_bono? mysqli_fetch_array($result_bono, MYSQLI_ASSOC)["bono"]:"";
		
		$trimestral_table.="<td>$nota_periodo ptos</td><td>$bono</td></tr>";
	}
	$trimestral_table.="</table>";

	$class_open_accordion =  "in";
	if(($mes_actual <= $meses[2] && $ano_actual == $ano) || $ano_actual < $ano)
		$class_open_accordion = "";
	?>
	<div class="panel panel-info">
	    <div class="panel-heading" role="tab" id="headingTrimestre">
	      <h4 class="panel-title">
	        <a class="collapsed" data-toggle="collapse" data-parent="#accordion" href="#collapseTrimestre" aria-expanded="false" aria-controls="collapseTrimestre">
	          <?="Resumen $nombre_periodo_bono";?>
	        </a>
	      </h4>
	    </div>
	    <div id="collapseTrimestre" class="panel-collapse collapse <?=$class_open_accordion?>" role="tabpanel" aria-labelledby="headingTrimestre">
	      <div class="panel-body">
	      	<?="$trimestral_table"?>  
	      </div>
	    </div>
	</div>
	<?php
}
?>		

<?php require $upOne."/dashboard_footer.html";?>