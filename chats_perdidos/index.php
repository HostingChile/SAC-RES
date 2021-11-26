<?php 
	$upOne = realpath(__DIR__ . '/..');
	require $upOne."/dashboard_header.php";

	$month = isset($_GET['month']) ? $_GET['month'] : date('n');
	$year = isset($_GET['year']) ? $_GET['year'] : date('Y');
	$tab_actual = isset($_GET['tab_actual']) ? $_GET['tab_actual'] : "perdidos";
?>

<title>Chats Perdidos / Recibidos</title>
<head>
	<script src="http://code.jquery.com/jquery-1.10.2.js"></script>
	<script src="http://code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
	
	<link rel="stylesheet" href="http://code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
	<link rel="stylesheet" href="include/style.css">

	<script src="include/datepicker-es.js"></script>
	
	<script>

	function load_page(month, year)
	{
		//obtengo el tab actual que esta viendo el usuario (perdidas, recibidas o porcentaje)
		var id_tab = $(".active_tab").attr("id");
		var tab_actual = id_tab.substr(id_tab.indexOf("_") + 1);

    	//recargo la pagina, y envio por POST la info de las categorias
    	$('body')
    		.append($('<form/>')
			  .attr({'action': 'index.php', 'method': 'get', 'id': 'replacer'})
			  .append($('<input/>').attr({'type': 'hidden', 'name': 'tab_actual', 'value': tab_actual}))
			  .append($('<input/>').attr({'type': 'hidden', 'name': 'month', 'value': month}))
			  .append($('<input/>').attr({'type': 'hidden', 'name': 'year', 'value': year}))
			).find('#replacer').submit();
	}

  $(function() {
	$('.loading').remove();
	$('#progress_msg').remove();
	$('#ver').show();
  
    var tabs_perdidos = $( "#tabs_perdidos" ).tabs();
    tabs_perdidos.find( ".ui-tabs-nav" ).sortable({
      axis: "x",
      stop: function() {
        tabs_perdidos.tabs( "refresh" );
      }
    });
	
	 var tabs_recibidos = $( "#tabs_recibidos" ).tabs();
    tabs_recibidos.find( ".ui-tabs-nav" ).sortable({
      axis: "x",
      stop: function() {
        tabs_recibidos.tabs( "refresh" );
      }
    });
	
	 //muestro el tab correspondiente
	$("span[id^='chats_']").hide();
	$("#chats_<?=$tab_actual?>").show();
	$(".ver_tab").removeClass("active_tab");
	$("#ver_<?=$tab_actual?>").addClass("active_tab");
	
	//click para ver un tab diferente
	$(".ver_tab").click(function(){
		$(".ver_tab").removeClass("active_tab");
		$(this).addClass("active_tab");
		var type = $(this).attr('id').split('_')[1];
		$("span[id^='chats_']").hide();
		$("#chats_"+type).show();
	});
//OLD
	$("#ver").click(function(){
		var actual = $(this).find('span').text();
		
		if(actual == 'Recibidos'){
			$("#chats_perdidos").hide();
			$("#chats_recibidos").show();
			$(this).find('span').text('Perdidos');
		}
		else if(actual == 'Perdidos'){
			$("#chats_perdidos").show();
			$("#chats_recibidos").hide();
			$(this).find('span').text('Recibidos');
		}
	});
	
	$('.date-picker').datepicker( {
		showOn: "button",
      	buttonImage: "images/calendar.png",
     	buttonImageOnly: true,
        changeMonth: true,
        changeYear: true,
        showButtonPanel: true,
        dateFormat: 'MM yy',
        onClose: function(dateText, inst) { 
            var month = $("#ui-datepicker-div .ui-datepicker-month :selected").val();
            month = parseInt(month)+1;
            var year = $("#ui-datepicker-div .ui-datepicker-year :selected").val();
            //$(this).datepicker('setDate', new Date(year, month, 1));
            if(month != <?=$month?> || year != <?=$year?>){
            	load_page(month, year);
            }
        }
    });

    $('.date-picker').datepicker( $.datepicker.regional[ "es" ] );
    $('.date-picker').datepicker('setDate', new Date(<?=$year;?>, <?=$month;?>, 1));
    

});


  </script>
  
</head>
<body>

<?php 
	$nombre_meses = array("Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre");
	$current_month_name = $nombre_meses[$month-1];
?>

<div id='progress_msg' style='background-color:rgb(18, 221, 237);width:0%;'></div>
<div id="ver">
	<div id='ver_perdidos' class='ver_tab'>Perdidos</div>
	<div id='ver_recibidos' class='ver_tab'>Recibidos</div>
</div>
<div id='panel_configuracion'>
	<span id="mes_actual"><?="$current_month_name - $year"?></span><input name="startDate" id="startDate" class="date-picker" style="display:none;" />
</div>
<div style="clear:both;margin-top:60px;"></div>
<?php

function loading($pctg){
	echo "<script class='loading'>$('#progress_msg').text('$pctg%').css('width','$pctg%')</script>";
	
	echo str_pad('',4096)."\n";    
    ob_flush();
    flush();
}

//Muestra la tabla con el detalle por dia y horario
function createTable($listado_inicios){
	// echo "<pre>";print_r($listado_inicios);echo "</pre>";

	//Perdidos por rango horario y dia
	$max = $listado_inicios[0][1];
	$min = $listado_inicios[0][1];
	for($i = 0; $i < 24; $i++)
		for($j = 1;$j <= 7; $j++){
			$max = $listado_inicios[$i][$j] > $max ? $listado_inicios[$i][$j] : $max; 
			$min = $listado_inicios[$i][$j] < $min ? $listado_inicios[$i][$j] : $min; 
		}
	$range = $max - $min;
	if($range == 0)
		return "<h2>No hay registros</h2>";
	$steps = ceil(510 / $range);
	$middle = $min + floor(($max - $min) / 2);
	
	ksort($listado_inicios);
	$table = "<table><tr><th>Hora/Dia</th><th>Lunes</th><th>Martes</th><th>Miercoles</th><th>Jueves</th><th>Viernes</th><th>Sabado</th><th>Domingo</th><th>Total</th></tr>";
	$sumadia = array();
	for($i = 0; $i < 24; $i++){
		$time = $i < 10 ? "0$i" : $i;
		$table .= "<tr><td>$time:00 - $time:59</td>";
		$suma_hora = 0;
		for($j = 1;$j <= 7;$j++){
			$color = $listado_inicios[$i][$j] <= $middle ? "rgba(255,255,".(255-($listado_inicios[$i][$j]-$min)*$steps)."),0.7" : "rgba(255,".(255-($listado_inicios[$i][$j]-$middle)*$steps).",0,0.7)";
			$sumadia[$j] += $listado_inicios[$i][$j];
			$suma_hora += $listado_inicios[$i][$j];
			$table.= "<td style='background-color:$color'>{$listado_inicios[$i][$j]}</td>";
		}
		$table.="<td style=''>$suma_hora</td></tr>";
	}
	$table.="<tr><th>Total</th>";
	$suma_total = 0;
	for($j = 1;$j <= 7;$j++){
		$table.= "<th>{$sumadia[$j]}</th>";
		$suma_total += $sumadia[$j];
	}
	$table.="<th>$suma_total</th></tr>";
	$table .= "</table></center>";
	
	return $table;
}

//Devuelve el nombre del mes en ingles, en 3 letras
function nombreMesIngles($num){
	switch($num){
		case 0:return "Dec";break;
		case 1:return "Jan";break;
		case 2:return "Feb";break;
		case 3:return "Mar";break;
		case 4:return "Apr";break;
		case 5:return "May";break;
		case 6:return "Jun";break;
		case 7:return "Jul";break;
		case 8:return "Aug";break;
		case 9:return "Sep";break;
		case 10:return "Oct";break;
		case 11:return "Nov";break;
		case 12:return "Dec";break;
	}
}

//Obtiene el operador a partir del asunto del correo
function esContestado($texto){
	return (strpos($texto,'Operators:') === false && strpos($texto,'Operator:') === false) ? false : true;
}

//Obtiene le fecha y hora de inicio y término de la conversación
function obtenerInicioTermino($message){
	
	$remove_chars = array("\r", "\n" , "=");
	
	$start = strpos($message,'"value-td"',strpos($message,'>Started:<')) + 11;
	$finish = strpos($message,'</td>',$start);
	$inicio = str_replace($remove_chars,'',substr($message,$start, $finish - $start));
	$inicio = date_create_from_format('j-M-Y g:i:s A',strtolower($inicio));
	
	$start = strpos($message,'"value-td"',strpos($message,'>Finished:<')) + 11;
	$finish = strpos($message,'</td>',$start);
	$termino = str_replace($remove_chars,'',substr($message,$start, $finish - $start));
	$termino = date_create_from_format('j-M-Y g:i:s A',strtolower($termino));

	if(!$inicio || !$termino){
		// echo "Error al obtener hora de chat -> ".substr($message,$start, $finish - $start)."<br>";
		return false;
	}

	$duracion = $termino->diff($inicio);

	$seconds = ($duracion->s)
         + ($duracion->i * 60)
         + ($duracion->h * 60 * 60)
         + ($duracion->d * 60 * 60 * 24)
         + ($duracion->m * 60 * 60 * 24 * 30)
         + ($duracion->y * 60 * 60 * 24 * 365);

	
	return array('inicio' => $inicio, 'termino' => $termino, 'duracion' => $seconds);
}

//Calcula las calificaciones leyendo los correos de chat@hosting.cl
function obtenerChatsPerdidos($mes,$año){
	$año_inicio = $año;
	$año_fin = $mes == 12 ? $año + 1 : $año;

	$mes_inicio = $mes;
	$mes_fin = $mes == 12 ? 1 : $mes + 1;
	
	$mes_inicio_ingles = nombreMesIngles($mes);
	$mes_fin_ingles = nombreMesIngles($mes_fin);

	$hostname = "{mail.hosting.cl/notls}";
	$username = "chat@hosting.cl";
	$password = "ignacia.,14";

	$evaluaciones_chat = array();

	$inbox = imap_open($hostname,$username,$password) or die('Ha fallado la conexión con el Mail: ' . imap_last_error());
	$emails = imap_search($inbox, 'SINCE "1 '.$mes_inicio_ingles.' '.$año_inicio.'" BEFORE "1 '.$mes_fin_ingles.' '.$año_fin.'"'); //Para Mayo-14 usar: 'SINCE "1 May 2014" BEFORE "1 Jun 2014"'
	
	$listado_esperas = array();
	$listado_inicios = array();
	$marcas = array('TOTAL','Hosting.cl','planetahosting','hostingcenter','ninjahosting','planetaperu', 'planetacolombia');
	
	foreach($marcas as $marca)
		for($i = 0; $i < 24; $i++)
			for($j = 1;$j <= 7; $j++){
				$listado_inicios[$marca]['perdidos'][$i][$j] = 0;
				$listado_inicios[$marca]['recibidos'][$i][$j] = 0;
			}
	
	if($emails !== false){
		$current = 0;
		$duracion_chats = array(); //duracion en segundos de todos los chats recibidos
		foreach($emails as $email_number){
			$current++;
			loading(round($current / count($emails) * 100, 0));
			$message = imap_body($inbox,$email_number);
			$overview = imap_fetch_overview($inbox,$email_number);		
			$subject = iconv_mime_decode($overview[0]->subject);	
			
			$inicio_termino = obtenerInicioTermino($message);
			
			if(!$inicio_termino){
				continue;
			}

			

			$inicio = $inicio_termino['inicio'];
			$termino = $inicio_termino['termino'];
			$duracion = $inicio_termino['duracion'];
	
			$start = strpos($message,'>',strpos($message,'value-td',strpos($message,'Company:'))) + 1;
			$finish = strpos($message,'</td>',$start);
			$company = substr($message,$start,$finish-$start);
			
			if(strpos($company,'Started'))
				$company = substr($company,0,strpos($company,'<'));
							
			$dia_semana = $inicio_termino['inicio']->format('N');
			// echo "Inicio: {$inicio_termino['inicio']->format('Y-m-d H:i:s')} - Termino: {$inicio_termino['termino']->format('Y-m-d H:i:s')} - Duracion: {$inicio_termino["duracion"]}<br>";
			
			$hour = $inicio_termino['inicio']->format('G');
			$listado_inicios[$company]['recibidos'][$hour][$dia_semana]++;
			$listado_inicios['TOTAL']['recibidos'][$hour][$dia_semana]++;	
			
			if(!esContestado($subject)){
				$listado_inicios[$company]['perdidos'][$hour][$dia_semana]++;
				$listado_inicios['TOTAL']['perdidos'][$hour][$dia_semana]++;
			}else{
				$duracion_chats[] = $duracion;
			}

		}
	}
	if(count($duracion_chats)){
		sort($duracion_chats);
		$duracion_promedio = round(array_sum($duracion_chats)/count($duracion_chats)/60);
		$mediana = round($duracion_chats[round(count($duracion_chats)/2)]/60);

		// echo "<h2>Promedio: $duracion_promedio minutos</h2>";
		// echo "<h2>Mediana: $mediana minutos</h2>";

		// for($i=0; $i<count($duracion_chats); $i++) {
		// 	echo ($i+1)."/".count($duracion_chats)." - ".round($duracion_chats[$i]/60)." minutos<br>";
		// }
	}
	
	
		
	//Mostrar los perdidos
	echo "<span id='chats_perdidos'>";
	
	//Tabs
	echo "<div id='tabs_perdidos'>";
	echo "<ul>";
	foreach($listado_inicios as $marca => $detalles)
		echo "<li><a href='#tabs_perdidos-$marca'>$marca</a></li>";
	echo "</ul>";
	
	foreach($listado_inicios as $marca => $detalles){
		echo "<div id='tabs_perdidos-$marca'>";
		echo createTable($detalles['perdidos']);
		echo "</div>";
	}
	echo "</div></span>";
	
	
	//Mostrar los recibidos
	echo "<span id='chats_recibidos'>";
	
	//Tabs
	echo "<div id='tabs_recibidos'>";
	echo "<ul>";
	foreach($listado_inicios as $marca => $detalles)
		echo "<li><a href='#tabs_recibidos-$marca'>$marca</a></li>";
	echo "</ul>";
	
	foreach($listado_inicios as $marca => $detalles){
		echo "<div id='tabs_recibidos-$marca'>";
		echo createTable($detalles['recibidos']);
		echo "</div>";
	}
	echo "</div></span>";	
	
	imap_close($inbox);

}

//echo "Obtener chats del $month-$year<br>";

if (ob_get_level() == 0) ob_start();
obtenerChatsPerdidos($month,$year);

ob_end_flush();

?>
</body>

<?php require $upOne."/dashboard_footer.html";?>