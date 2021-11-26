<html style="border-radius: 50px;border:3px solid gray;">

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

<title>Medidor de Transferencias</title>

<body>
<h1>Medidor de Transferencias</h1><hr><br>
<div id="wrapper">
<?php

set_time_limit(0);
include ("include/funciones.php");

// LLAMADAS
$con_asterisk = mysqli_connect("190.153.249.226","root","hosting.,12","ASTDB");

if (mysqli_connect_errno())
{
	echo "Failed to connect to MySQL: " . mysqli_connect_error();
	exit;
}
		
$query = "SELECT * FROM cdr WHERE 
		disposition = 'ANSWERED' AND 
		DATE(end) >= DATE(NOW()) AND 
		dcontext NOT LIKE '%encuesta%' AND 
		dcontext != '' AND 
		dcontext != 'from-pstn' AND 
		dcontext != 'interno' AND 
		lastapp = 'Queue' AND 
		duration >= 40
			ORDER BY start DESC";
$result = mysqli_query($con_asterisk,$query);

$llamadas = array();
$error_message="";
while ($row = mysqli_fetch_assoc($result))
{
	$id = substr($row['dstchannel'],strpos($row['dstchannel'],"/")+1,3);
	if($id == "i1/")
		$id = $row['src'];
	//obtengo el nombre asociado a la extension
	$query = "SELECT CONCAT(nombre,' ',apellido) as nombre_completo FROM Operadores WHERE id_fop = $id AND mostrar_tipificaciones = 1";
	$result_nombre = mysqli_query($con_asterisk,$query);
	if(!$result_nombre)
	{
		$error_message .= "Error con query: $query<br>";
		//print_r($row);
		//echo "<br>";
		continue;
	}
	$operador = "";
	$num_rows = mysqli_num_rows($result_nombre);
	if($num_rows == 1)
		$operador = mysqli_fetch_assoc($result_nombre)["nombre_completo"];
	//$operador = NombreOperador($id);
	if(!is_numeric($operador) && $operador != "")
		array_push($llamadas,$operador);
}

$llamadas = array_count_values($llamadas);
ksort($llamadas);
//print_r($llamadas);


// TRANSFERENCIAS
$con_asterisk_transferencias = mysqli_connect("190.153.249.226","root","hosting.,12","encuesta");
if (mysqli_connect_errno())
{
	echo "Failed to connect to MySQL: " . mysqli_connect_error();
	exit;
}

$query = "SELECT operador,fecha FROM transferencias WHERE DATE(fecha) >= DATE(NOW()) AND origen != 'interno' ORDER BY fecha DESC";
$result = mysqli_query($con_asterisk_transferencias,$query);

$transferencias = array();

while ($row = mysqli_fetch_assoc($result))
{
	$ext = $row['operador'];
	if(!is_numeric($ext))
		continue;
	//obtengo el nombre asociado a la extension
	$query = "SELECT CONCAT(nombre,' ',apellido) as nombre_completo FROM Operadores WHERE id_fop = $ext AND mostrar_tipificaciones = 1";
	$result_nombre = mysqli_query($con_asterisk,$query);
	$operador = "";
	$num_rows = mysqli_num_rows($result_nombre);
	if($num_rows == 1)
		$operador = mysqli_fetch_assoc($result_nombre)["nombre_completo"];
	//$operador = NombreOperador($ext);
	if(!is_null($operador) && !is_numeric($operador) && isset($llamadas[$operador]))
		array_push($transferencias,$operador);
}

mysqli_close($con_asterisk);

//Contar cuantas transferencias tiene cada uno
$transferencias = array_count_values($transferencias);

//Agregar a los que tienen 0 transferencias
foreach($llamadas as $key => $value)
		if(!isset($transferencias[$key]))
			$transferencias[$key] = 0;
		
ksort($transferencias);
// print_r($transferencias);
mysqli_close($con_asterisk_transferencias);

//Crear un canvas para cada operador
for ($i = 0; $i<count($llamadas);$i++)
	echo "<canvas id='cvs$i' width='450' height='350'>[No canvas support]</canvas>";
echo "<br>$error_message";
	
//Crear la versión lineal de cada array para pasarlo a Javascript
$total_array = "";
$operadores_array = "";
$transferencias_array = "";
foreach($llamadas as $key => $value)
{
	$total_array .= "'$value',";
	$operadores_array .= "'$key',";
}
foreach($transferencias as $value)
	$transferencias_array .= "'$value',";

$total_array = rtrim($total_array, ',');
$operadores_array = rtrim($operadores_array, ',');
$transferencias_array = rtrim($transferencias_array, ',');

?>
</div>
<script>
$(document).ready(function ()
{
	var red = 0.80; // En qué porcentaje termina el rojo
	var green = 0.90; // En qué porcentaje empieza el verde
						// El naranjo se pone en el porcentaje que falta
	var array_total = [<?=$total_array;?>];
	var array_operadores = [<?=$operadores_array;?>];
	var array_transferencias = [<?=$transferencias_array;?>];
	
	for(var i = 0; i < <?=count($llamadas);?>; i++)
	{
		var operador = array_operadores[i];
		var total = array_total[i];
		var diff = total - operador;
		var transferencias = array_transferencias[i];
		var cvs = "cvs" + i;

		var green_end = total * red; 
		var red_start = total * green;
		
		var bgcolor = "white";
		
		//Definir el color de fondo
		var sobran = '';
		if(parseInt(total * red) >= parseInt(transferencias)) //rojo
		{
			bgcolor = "#FF6666";
			bgcolor = "#FF8383";
		}
		else if(parseInt(total * red) < parseInt(transferencias) && parseInt(total * green) >= parseInt(transferencias)) //amarillo
		{
			bgcolor = "#FFCC00";
			bgcolor = "#F8E487";
		}	

		else if(parseInt(total) + 1 == parseInt(transferencias)) //blanco
		{
			bgcolor = "#A2EDCD";
			sobran = "Sobran: "+(transferencias-total);
		}
		else if(parseInt(total) + 1 < parseInt(transferencias)) //blanco
		{
			bgcolor = "white";
			sobran = "Sobran: "+(transferencias-total);
		}
		else				
		{			//verde
			bgcolor	= "#A2EDCD";
		}
		
		
		//Crear el medidor
		var gauge_transferencias = new RGraph.Gauge(cvs, 0, total, transferencias)
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