<?php
//unset($_COOKIE['startdate']);
?>
<!--
<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>

<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap-theme.min.css">
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
-->
<!-- <script src="include/bootstrap-datepicker-1.4.0-dist/js/bootstrap-datepicker.js"></script>
<script src="include/bootstrap-datepicker-1.4.0-dist/js/bootstrap-datepicker.min.js"></script>
<script src="include/bootstrap-datepicker-1.4.0-dist/locales/bootstrap-datepicker.es.min.js"></script> -->

<!-- Include Required Prerequisites -->
<script type="text/javascript" src="//cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>

<!-- Include Date Range Picker -->
<script type="text/javascript" src="//cdn.jsdelivr.net/bootstrap.daterangepicker/2/daterangepicker.js"></script>
<link rel="stylesheet" type="text/css" href="//cdn.jsdelivr.net/bootstrap.daterangepicker/2/daterangepicker.css" />


<!-- charts -->
<link href="include/c3-0.4.10/c3.css" rel="stylesheet" type="text/css">
<script src="http://d3js.org/d3.v3.min.js" charset="utf-8"></script>
<script src="include/c3-0.4.10/c3.min.js"></script>

<!-- cookies -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-cookie/1.4.1/jquery.cookie.js"></script>

<!-- <link rel="stylesheet" href="include/bootstrap-datepicker-1.4.0-dist/css/bootstrap-datepicker3.css"> -->

<?php
if(isset($_COOKIE['daterange'])){
	$cookie_daterange = $_COOKIE['daterange'];
	// echo "<h2>$cookie_daterange</h2>";
	$ranges = explode(" - ", $cookie_daterange);

	$start_date = date("Y-m-d H:i:s", strtotime(str_replace("/", "-", $ranges[0])." 00:00:00"));
	$end_date = date("Y-m-d H:i:s", strtotime(str_replace("/", "-", $ranges[1])." 23:59:59"));

	// echo "<h1>$start_date - $end_date</h1>";


	// $date=str_replace("/", "-", $cookie_initdate);
	// $start_date = date("Y-m-d",strtotime($date));

	// $cookie_enddate = $_COOKIE['enddate'];
	// $date=str_replace("/", "-", $cookie_enddate);
	// $end_date = date("Y-m-d",strtotime($date));
}
else {
	// $start_month = date("m")-5;
	// $start_year = date("Y");
	// if($start_month<1)
	// {
	// $start_month = 12-$start_month;
	// $start_year = $start_year -1;
	// }
	// if(strlen($start_month) == 1)
	// $start_month = "0$start_month";

	
	// $cookie_initdate = "01/$start_month/$start_year";
	// $cookie_enddate = date("d/m/Y", time()+86400);//mañana, para que incluya los datos de hoy
}

?>

<script type="text/javascript">

$(function () {
    $('#daterange').daterangepicker({
		autoApply: true,
		opens: "left",
	    ranges: {
	        "Hoy": [ moment(), moment() ],
	        "Ayer": [ moment().subtract(1, 'days'), moment().subtract(1, 'days') ],
	        "Últimos 7 días": [ moment().subtract(6, 'days'), moment() ],
	        "Mes actual": [ moment().startOf('month'), moment().endOf('month') ],
	        "Mes pasado": [ moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month') ],
	        "Ultimos 6 meses": [ moment().subtract(5, 'month').startOf('month'), moment().endOf('month') ],
	        "Año Actual": [ moment().startOf('year'), moment().endOf('year') ],
         	"Año Pasado": [ moment().subtract(1, 'year').startOf('year'), moment().subtract(1, 'year').endOf('year') ],
	    },
	    locale: {
	        "format": "DD/MM/YYYY",
	        "separator": " - ",
	        "applyLabel": "Aplicar",
	        "cancelLabel": "Borrar",
	        "fromLabel": "Desde",
	        "toLabel": "Hasta",
	        "customRangeLabel": "Personalizado",
	        "daysOfWeek": ["Do","Lu","Ma","Mi","Ju","Vi","Sa"],
	        "monthNames": ["Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre"],
	        "firstDay": 1
	    }
	});

	$("#btncalcular").on("click",function(e){
		var date = new Date();
		var minutes = 60;
		date.setTime(date.getTime() + (minutes * 60 * 1000));
		$.cookie("daterange", $("#daterange").val(), { expires : date });
		location.reload();
	});
});

</script>

<style type="text/css">
	#daterange{
		height: 34px;
		padding: 6px 12px;
		font-size: 14px;
		line-height: 1.42857143;
		color: #555;
		background-color: #fff;
		border: 1px solid #ccc;
		border-radius: 4px;
		margin-top: 7px;
	}
	#btncalcular{
	    margin-left: 10px;
	    margin-right: 20px;
	    margin-top: 7px;
	}
</style>

<nav class="navbar navbar-default">
  <div class="container-fluid">
    <div class="navbar-header">
      <a class="navbar-brand" href="#">
        <img width="50" height="40" style="position:relative;top:-10px;" alt="Brand" src="img/stats.png">
      </a>
    </div>

    <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
      <ul class="nav navbar-nav">
        <?php
        $paginas = array(
                          "% Clientes Contactan" => "porc_clientes_contactan.php",
                          "Temas Contacto" => "temas_contacto.php",
                          "Promedio Contactos" => "promedio_contactos.php",
                          "Tipificaciones por Servidor" => "tipificaciones_por_servidor.php",
						  "Tipificaciones por Cliente" => "tipificaciones_por_cliente.php",
					      "Intervencion Operaciones" => "intervencion_operaciones.php"
						);
        foreach ($paginas as $titulo => $pagina)
        {
          $class_active = $titulo_pagina_actual == $titulo ? "active":"";
          echo "<li class='$class_active'><a href='$pagina'>$titulo</a></li>";
        }
        ?>
      </ul>
      <div class="nav navbar-nav navbar-right">
        <div class="input-group">
            <button type="submit" class="btn btn-primary" id="btncalcular">Calcular</button>
        </div>
      </div>
      <div class="nav navbar-nav navbar-right">
        <div class="input-daterange input-group" id="datepicker">
            <input type="text" id="daterange" value="<?=$cookie_daterange;?>" />
        </div>
      </div>


    </div>
  </div>
</nav>

<?php

if(!isset($_COOKIE['daterange']))
{
  echo "<style>
          #daterange{background-color:#FFB0B0;}
        </style>";
  echo "<div style='margin-left:20px;'>Calcule para un rango de fechas</div>";
  exit();
}

?>


