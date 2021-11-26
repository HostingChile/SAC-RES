<html style="border-radius: 50px; border:3px solid gray;">

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
<script src="RGraph/libraries/RGraph.common.core.js" ></script>
<script src="RGraph/libraries/RGraph.gauge.js" ></script>
<link rel="shortcut icon" type="image/x-icon" href="include/favicon.ico">
<link href="include/style.css" rel="stylesheet" type="text/css">
<meta http-equiv="refresh" content="60">
<style type="text/css">
@-moz-document url-prefix() {
    #wrapper {
        -moz-transform-origin: 0 0;
		-moz-transform: scale(0.5);
		width:200%;
    }
	h1{
		font-size:35px;
	}
}
body{
	zoom:50%;
}

</style>
</head>

<title>Medidor de Tipificaciones</title>

<body>
<h1>Medidor de Tipificaciones</h1><hr><br>
<div id="wrapper">
<?php

set_time_limit(0);
include ("include/funciones.php");

// CHATS
$hostname = "{mail.hosting.cl/notls}";
$username = "chat@hosting.cl";
$password = "ignacia.,14";

$evaluaciones = array();

$fecha = date("j M Y");

$inbox = imap_open($hostname,$username,$password) or die('Ha fallado la conexión con el Mail: ' . imap_last_error());
$emails  = imap_search($inbox, 'SINCE "'.$fecha.'"');

$chats = array();

foreach($emails as $email_number) {
	$message = imap_body($inbox,$email_number);
	
	$overview = imap_fetch_overview($inbox,$email_number);
	
	$subject = iconv_mime_decode($overview[0]->subject);	
	// $fecha = $overview[0]->date;
	
	$operador = obtenerOperador($subject);
	
	//Cambios de nombre de operador
	$operador = nombreRealChat($operador);
		
	if($operador != "" && $operador != "soporte2")
		array_push($chats,$operador);
}

$chats = array_count_values($chats);
ksort($chats);
// print_r($chats);
imap_close($inbox);

// LLAMADAS
$con_asterisk = mysqli_connect("190.153.249.226","root","hosting.,12","ASTDB");

if (mysqli_connect_errno())
{
	echo "Failed to connect to MySQL: " . mysqli_connect_error();
	exit;
}

$query = "SELECT CONCAT(O.nombre,' ',O.apellido) as operador FROM cdr as C LEFT JOIN Operadores O ON SUBSTR(C.dstchannel,5,3) = O.id_fop
			WHERE C.disposition = 'ANSWERED' AND C.dstchannel != '' 
			AND C.dcontext != 'interno' AND C.dcontext != 'from-pstn' 
			AND C.lastapp = 'Queue'
			AND DATE(C.end) >= DATE(NOW())
			AND O.mostrar_tipificaciones = 1
			ORDER BY start DESC";
$result = mysqli_query($con_asterisk,$query);

$llamadas = array();

while ($row = mysqli_fetch_assoc($result))
	array_push($llamadas, stripAccents($row['operador']));
	
$llamadas = array_count_values($llamadas);
ksort($llamadas);
// print_r($llamadas);

$total = array_merge_recursive($llamadas,$chats);
$total = eliminarRecursividad($total);

ksort($total);
// print_r($total);
mysqli_close($con_asterisk);


// TIPIFICACIONES
$con_sac = mysqli_connect("190.96.85.3","sistemas_callc","sys1830","sistemas_sac");
if (mysqli_connect_errno())
{
	echo "Failed to connect to MySQL: " . mysqli_connect_error();
	exit;
}

$query = "SELECT operador FROM Tipificacion
			WHERE DATE(fecha) >= DATE(NOW())
			ORDER BY fecha DESC";
$result = mysqli_query($con_sac,$query);

$tipificaciones = array();

while ($row = mysqli_fetch_assoc($result)){
	$operador = stripAccents($row['operador']);
	if(!is_numeric($operador) && isset($total[$operador])){
		array_push($tipificaciones,$operador);
	}
}

//Contar cuantas tipificaciones tiene cada uno
$tipificaciones = array_count_values($tipificaciones);

//Agregar a los que tienen 0 tipificaciones
foreach($total as $key => $value)
	if(!isset($tipificaciones[$key]))
		$tipificaciones[$key] = 0;
		
ksort($tipificaciones);
// print_r($tipificaciones);
mysqli_close($con_sac);

//Crear un canvas para cada operador
for ($i = 0; $i<count($total);$i++)
	echo "<canvas id='cvs$i' width='450' height='350'>[No canvas support]</canvas>";
	
//Crear la versión lineal de cada array para pasarlo a Javascript
$total_array = "";
$operadores_array = "";
$tipificaciones_array = "";
foreach($total as $key => $value)
{
	$total_array .= "'$value',";
	$operadores_array .= "'$key',";
}
foreach($tipificaciones as $value)
	$tipificaciones_array .= "'$value',";

$total_array = rtrim($total_array, ',');
$operadores_array = rtrim($operadores_array, ',');
$tipificaciones_array = rtrim($tipificaciones_array, ',');


?>
</div>
<script>
$(document).ready(function ()
{
	var red = 0.60; // En qué porcentaje termina el rojo
	var green = 0.80; // En qué porcentaje empieza el verde
						// El naranjo se pone en el porcentaje que falta
	var array_total = [<?=$total_array;?>];
	var array_operadores = [<?=$operadores_array;?>];
	var array_tipifcicaciones = [<?=$tipificaciones_array;?>];
	
	for(var i = 0; i < <?=count($total);?>; i++)
	{
		var operador = array_operadores[i];
		var total = array_total[i];
		var diff = total - operador;
		var tipificaciones = array_tipifcicaciones[i];
		var cvs = "cvs" + i;

		var green_end = total * red; 
		var red_start = total * green;
		
		var bgcolor = "white";
		
		//Definir el color de fondo
		var sobran = '';
		if(parseInt(total * red) >= parseInt(tipificaciones)) //rojo
		{
			bgcolor = "#FF6666";
			bgcolor = "#FF8383";
		}
		else if(parseInt(total * red) < parseInt(tipificaciones) && parseInt(total * green) >= parseInt(tipificaciones)) //amarillo
		{
			bgcolor = "#FFCC00";
			bgcolor = "#F8E487";
		}
		else if(parseInt(total) + 1 == parseInt(tipificaciones)) //blanco
		{
			bgcolor = "#A2EDCD";
			sobran = "Sobran: "+(tipificaciones-total);
		}
		else if(parseInt(total) + 1 < parseInt(tipificaciones)) //blanco
		{
			bgcolor = "white";
			sobran = "Sobran: "+(tipificaciones-total);
		}
		else							//verde
		{
			bgcolor	= "#A2EDCD";
		}
		
		
		//Crear el medidor
		var gauge = new RGraph.Gauge(cvs, 0, total, tipificaciones)
			.set('background.color', bgcolor)
			.set('green.color','red')
			.set('red.color','green')
			.set('green.end',green_end)
			.set('red.start',red_start)
			.set('tickmarks.small', 50)
			.set('tickmarks.big',5)
			.set('title.top', operador)
			.set('title.top.size', 24)
			.set('title.top.pos', 0.15)
			.set('title.bottom.pos', 0.7)
			.set('title.top.color', 'black')
			.set('title.bottom', sobran)
			.set('title.bottom.color', 'black')
			.set('border.outer', 'Gradient(white:white:white:white:white:white:white:white:white:white:#aaa)')
			.set('scale.decimals', 0)
			.set('text.color','black')
			.set('text.size',15)
			.draw();
	}
})
</script>


</body>
</html>