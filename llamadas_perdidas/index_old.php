<?php 
	$upOne = realpath(__DIR__ . '/..');
	require $upOne."/dashboard_header.php";

	$month = isset($_GET['month']) ? $_GET['month'] : date('n');
	$year = isset($_GET['year']) ? $_GET['year'] : date('Y');

	$tipos_llamadas = isset($_GET['tipos_llamadas']) && strlen($_GET['tipos_llamadas']) > 0 && strpos($_GET['tipos_llamadas'], "'Todas',") !== 0 ? $_GET['tipos_llamadas'] : "TODAS";
	// echo $tipos_llamadas;
	$tipos_llamadas_array = explode(",", $tipos_llamadas);

	$tab_actual = isset($_GET['tab_actual']) ? $_GET['tab_actual'] : "perdidas";
?>

<title>Llamadas Perdidas / Recibidas</title>
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

		//obtengo las tipos de llamado seleccionados
		var categorias = "";
    	$(".checkbox_tipollamado").each(function(){
    		if($(this).is(':checked')){
    			categorias+="'"+$(this).val()+"',";
    		}
    	});

    	categorias = categorias.substring(0, categorias.length - 1);
    	//recargo la pagina, y envio por POST la info de las categorias
    	$('body')
    		.append($('<form/>')
			  .attr({'action': 'index.php', 'method': 'get', 'id': 'replacer'})
			  .append($('<input/>').attr({'type': 'hidden', 'name': 'tipos_llamadas', 'value': categorias}))
			  .append($('<input/>').attr({'type': 'hidden', 'name': 'tab_actual', 'value': tab_actual}))
			  .append($('<input/>').attr({'type': 'hidden', 'name': 'month', 'value': month}))
			  .append($('<input/>').attr({'type': 'hidden', 'name': 'year', 'value': year}))
			).find('#replacer').submit();
	}

  $(function() {
	$('.loading').remove();
	$('#progress_msg').remove();
	
    var tabs_perdidos = $( "#tabs_perdidas" ).tabs();
    tabs_perdidos.find( ".ui-tabs-nav" ).sortable({
      axis: "x",
      stop: function() {
        tabs_perdidos.tabs( "refresh" );
      }
    });
	
	 var tabs_recibidos = $( "#tabs_recibidas" ).tabs();
    tabs_recibidos.find( ".ui-tabs-nav" ).sortable({
      axis: "x",
      stop: function() {
        tabs_recibidos.tabs( "refresh" );
      }
    });
	
	var tabs_recibidos = $( "#tabs_porcentaje" ).tabs();
    tabs_recibidos.find( ".ui-tabs-nav" ).sortable({
      axis: "x",
      stop: function() {
        tabs_recibidos.tabs( "refresh" );
      }
    });
	
    //muestro el tab correspondiente
	$("span[id^='llamadas_']").hide();
	$("#llamadas_<?=$tab_actual?>").show();
	$(".ver_tab").removeClass("active_tab");
	$("#ver_<?=$tab_actual?>").addClass("active_tab");

	//click para ver un tab diferente
	$(".ver_tab").click(function(){
		$(".ver_tab").removeClass("active_tab");
		$(this).addClass("active_tab");
		var type = $(this).attr('id').split('_')[1];
		$("span[id^='llamadas_']").hide();
		$("#llamadas_"+type).show();
	});

	//configuracion del arbol de checkbox para los tipos de llamados
	$.extend($.expr[':'], {
        unchecked: function (obj) {
            return ((obj.type == 'checkbox' || obj.type == 'radio') && !$(obj).is(':checked'));
        }
    });

    $("#tree input:checkbox").on('change', function () {
        $(this).next('ul').find('input:checkbox').prop('checked', $(this).prop("checked"));

        for (var i = $('#tree').find('ul').length - 1; i >= 0; i--) {
            $('#tree').find('ul:eq(' + i + ')').prev('input:checkbox').prop('checked', function () {
                return $(this).next('ul').find('input:unchecked').length === 0 ? true : false;
            });
        }
    });

    //filtrar por tipo de llamado
    $("#btn_filtrar").click(function(){
    	load_page(<?=$month?>, <?=$year?>);
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
	<div id='ver_perdidas' class='ver_tab'>Perdidas</div>
	<div id='ver_recibidas' class='ver_tab'>Recibidas</div>
	<div id='ver_porcentaje' class='ver_tab'>Porcentaje</div>
</div>
<div id='panel_configuracion'>
	<span id="mes_actual"><?="$current_month_name - $year"?></span><input name="startDate" id="startDate" class="date-picker" style="display:none;" />
	<span id="config_btn" class="glyphicon glyphicon-filter" aria-hidden="true"  data-toggle="modal" data-target="#myModal"></span>
</div>
<div style="clear:both;margin-top:60px;"></div>
<?php
$config_menu_options = obtenerCategoriasLlamadas($month,$year);
?>

<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">Filtrar llamados</h4>
      </div>
      <div class="modal-body">
        <div id="tree">
		    <ul>
		        <li>
		        	<?php $checked_all = $tipos_llamadas == "TODAS" || in_array("'Todas'", $tipos_llamadas_array) ? "checked" : "";?>
		            <input type="checkbox" class='checkbox_tipollamado' value="Todas" <?=$checked_all?> />Seleccionar Todos
		            <ul>
		           	<?php
			        	foreach ($config_menu_options as $title => $menu_options) {
			        		$checked_category = in_array("'$title'", $tipos_llamadas_array) || strlen($checked_all)>0 ? "checked":"";
			        		echo "<li style='margin-top:10px;'> <input type='checkbox' value='$title'  class='checkbox_tipollamado' $checked_category/>$title<ul style='columns: 2; -webkit-columns: 2; -moz-columns: 2;'>";
			        		foreach ($menu_options as $menu_option) {
			        			$checked_option = in_array("'$menu_option'", $tipos_llamadas_array) || strlen($checked_category)>0 ? "checked":"";
			        			echo "<li><input type='checkbox' value='$menu_option' class='checkbox_tipollamado' $checked_option />$menu_option</li>";
			        		}
			        		echo "</ul><hr></li>";
			        	}
			        ?>
		            </ul>
		        </li>
		    </ul>
		</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
        <button id="btn_filtrar" type="button" class="btn btn-primary">Filtrar</button>
      </div>
    </div>
  </div>
</div>

<?php

function loading($pctg){
	echo "<script class='loading'>$('#progress_msg').text('$pctg%').css('width','$pctg%')</script>";
	
	echo str_pad('',4096)."\n";    
    ob_flush();
    flush();
}

//Muestra la tabla con el detalle por dia y horario
function createTable($listado_inicios,$total = true){
	// echo "<pre>";print_r($listado_inicios);echo "</pre>";

	//Perdidos por rango horario y dia
	$max = $listado_inicios[0][0];
	$min = $listado_inicios[0][0];
	for($i = 0; $i < 24; $i++)
		for($j = 0;$j < 3; $j++){
			$max = $listado_inicios[$i][$j] > $max ? $listado_inicios[$i][$j] : $max; 
			$min = $listado_inicios[$i][$j] < $min ? $listado_inicios[$i][$j] : $min; 
		}
	$range = $max - $min;
	if($range == 0)
		return "<h2>No hay registros</h2>";
	$steps = ceil(510 / $range);
	$middle = $min + floor(($max - $min) / 2);
	
	ksort($listado_inicios);
	if($total)
		$table = "<table class=''><tr><th>Hora/Día</th><th>Lunes-Viernes</th><th>Sábado</th><th>Domingo</th><th>Total</th></tr>";
	else
		$table = "<table class=''><tr><th>Hora/Día</th><th>Lunes-Viernes</th><th>Sábado</th><th>Domingo</th></tr>";
	$sumadia = array();
	for($i = 0; $i < 24; $i++){
		$time = $i < 10 ? "0$i" : $i;
		for($j = 0;$j < 3;$j++){
			$var_name = "color$j";
			$$var_name = $listado_inicios[$i][$j] <= $middle ? "rgba(255,255,".(255-($listado_inicios[$i][$j]-$min)*$steps).",0.7)" : "rgba(255,".(255-($listado_inicios[$i][$j]-$middle)*$steps).",0,0.7)";
			if($total)
				$sumadia[$j] += $listado_inicios[$i][$j];
		}
		$suma = $listado_inicios[$i][0] + $listado_inicios[$i][1] + $listado_inicios[$i][2];
		if($total)
			$table .= "<tr><td>$time:00:00 - $time:59:59</td><td style='background-color:$color0'>{$listado_inicios[$i][0]}</td>
															 <td style='background-color:$color1'>{$listado_inicios[$i][1]}</td>
															 <td style='background-color:$color2'>{$listado_inicios[$i][2]}</td>
															 <td>$suma</td></tr>";
		else
			$table .= "<tr><td>$time:00:00 - $time:59:59</td><td style='background-color:$color0'>{$listado_inicios[$i][0]}</td>
															 <td style='background-color:$color1'>{$listado_inicios[$i][1]}</td>
															 <td style='background-color:$color2'>{$listado_inicios[$i][2]}</td></tr>";
	}
	if($total){
		$sumatotal = $sumadia[0]+$sumadia[1]+$sumadia[2];
		$table.="<tr><th>Total</th><th>{$sumadia[0]}</th><th>{$sumadia[1]}</th><th>{$sumadia[2]}</th><th>$sumatotal</th></tr>";
	}
	else
		$table.="<tr><th></th><th>{$sumadia[0]}</th><th>{$sumadia[1]}</th><th>{$sumadia[2]}</th></tr>";
	$table .= "</table>";
	
	return $table;
}

function obtenerLlamadasPerdidas($mes,$año,$tipos_llamadas){
	
	$listado_inicios = array();
	for($i = 0; $i < 24; $i++)
		for($j = 0;$j < 3; $j++){
			$listado_inicios['perdidas'][$i][$j] = 0;
			$listado_inicios['recibidas'][$i][$j] = 0;
			$listado_inicios['porcentaje'][$i][$j] = 0;
		}
	
	$link = mysqli_connect("190.153.249.226","root","hosting.,12","ASTDB") or die("Error de conexión con la Base de Datos: " . mysqli_error($link));
	
	$where = $tipos_llamadas != "TODAS" ? " AND dcontext IN ($tipos_llamadas) " : "";

	$query = "SELECT dia,
					 HOUR(end) AS hora, 
					 SUM(no_contestado) AS perdidas,
					 COUNT(*) AS recibidas,
					 ROUND(SUM(no_contestado) * 100 / COUNT(*),2) AS porcentaje
				FROM
				(
				SELECT *, IF(disposition = 'ANSWERED', 0, 1) AS no_contestado, IF(WEEKDAY(end) = 5,1,IF(WEEKDAY(end) = 6,2,0)) AS dia FROM	cdr
				WHERE YEAR(end) = $año
				AND MONTH(end) = $mes
				AND dcontext != ''
				AND dcontext != 'from-pstn'
				AND dcontext != 'interno'
				AND lastapp = 'Queue'
				AND duration >= 35
				$where
				) AS tmp
				GROUP BY dia,HOUR(end);";

	// echo "<br>$query<br>";

	$result = mysqli_query($link,$query);
	
	while($row = mysqli_fetch_assoc($result)){
		$listado_inicios['perdidas'][$row['hora']][$row['dia']] = $row['perdidas'];
		$listado_inicios['recibidas'][$row['hora']][$row['dia']] = $row['recibidas'];
		$listado_inicios['porcentaje'][$row['hora']][$row['dia']] = $row['porcentaje'];
	}
	
	//Mostrar las perdidas
	echo "<span id='llamadas_perdidas'>";
	
	echo "<div id='tabs_perdidas'>";
	echo createTable($listado_inicios['perdidas']);
	echo "</div></span>";
	
	
	//Mostrar las recibidss
	echo "<span id='llamadas_recibidas'>";
	
	echo "<div id='tabs_recibidas'>";
	echo createTable($listado_inicios['recibidas']);
	echo "</div></span>";
	
	//Mostrar el porcentaje
	echo "<span id='llamadas_porcentaje'>";
	
	echo "<div id='tabs_porcentaje'>";
	echo createTable($listado_inicios['porcentaje'],false);
	echo "</div></span>";
	
	mysqli_close($link);
}

//RETORNA UN ARREGLO asociativo con categoria->array('sub1','sub2')
function obtenerCategoriasLlamadas($mes,$año){

	$retorno = array('Soporte' => array(), 'Ventas' => array(), 'Facturacion' => array(), 'Otros' => array());

	$query = "SELECT dcontext FROM cdr 
			WHERE MONTH(start) = '$mes' AND YEAR(start) = '$año' GROUP BY dcontext";

	$link = mysqli_connect("190.153.249.226","root","hosting.,12","ASTDB") or die("Error de conexión con la Base de Datos: " . mysqli_error($link));
	$result = mysqli_query($link,$query);
	
	while($row = mysqli_fetch_assoc($result)){
		$categoria = $row["dcontext"];
		if(endsWith($categoria, "_soporte")){
			$retorno["Soporte"][] = $categoria;
		}elseif(endsWith($categoria, "_ventas") || endsWith($row["dcontext"], "_ventas2")){
			$retorno["Ventas"][] = $categoria;
		}elseif(endsWith($categoria, "_facturacion")){
			$retorno["Facturacion"][] = $categoria;
		}else{
			$retorno["Otros"][] = $categoria;
		}
	}
	mysqli_close($link);
	return $retorno;
}

function endsWith($haystack, $needle) {
    // search forward starting from end minus needle length characters
    return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== FALSE);
}

if (ob_get_level() == 0) ob_start();
obtenerLlamadasPerdidas($month,$year,$tipos_llamadas);

ob_end_flush();

?>

</body>

<?php require $upOne."/dashboard_footer.html";?>