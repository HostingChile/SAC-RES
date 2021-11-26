<?php 
	$upOne = realpath(__DIR__ . '/..');
	require $upOne."/dashboard_header.php";
?>
<html>
<head>
	

	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">

	<title>Informe de Satisfacci? del Cliente - ISCOL</title>
    <script src='https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.18/jquery-ui.min.js'></script>
	<script type="text/JavaScript" src="js/funciones.js"></script> 
	<script type="text/JavaScript" src="js/jquery.mtz.monthpicker.js"></script> 
	<link rel="stylesheet" type="text/css" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.18/themes/base/jquery-ui.css">
	<link rel="stylesheet" type="text/css" href="css/style.css">

</head>
<body>

<!--
<div id="header">
	<h1>Informe de Satisfacci? del Cliente - ISCOL</h1>	
</div>
-->
<div class="monthpicker">
	<form method="GET" action="index.php">
		Selecci&oacute;n de Mes <input type="text" id="monthpicker" name="fecha"/> 
		<input type="submit" id="actualizar" value="Actualizar" disabled>
	</form>	
</div>

<?php
if(isset($_GET["fecha"]))
{
?>

<div id='container'>

<a href="index.php?modo=nota_final&fecha=<?=$_GET["fecha"]?>"><button class="detail_button" <?php echo (!isset($_GET["fecha"]) || !isset($_GET["modo"]) || $_GET["modo"] == 'nota_final') ? 'disabled' : '';?>>Notas Finales</button></a>
<a href="index.php?modo=nota_detalle&fecha=<?=$_GET["fecha"]?>"><button class="detail_button" <?php echo (!isset($_GET["fecha"]) || (isset($_GET["modo"]) && $_GET["modo"] == 'nota_detalle')) ? 'disabled' : '';?>>Notas Detalladas</button></a>


<?php
$modo = isset($_GET["modo"]) ? $_GET["modo"] : "nota_final";

include("include/funciones.php");
set_time_limit(0);

$mes = split("-",$_GET["fecha"]);
$mes = $mes[0];
$año = split("-",$_GET["fecha"]);
$año = $año[1];

if(strtotime("1-$mes-$año +1 month") - strtotime("now") >= 0)
	exit;

$mes_español = nombreMesEspañol($mes);

echo "<h1>Reporte para $mes_español $año</h1>";
echo "<br><table class='tabla_principal'><tr><th class='titulo'>Chat</th><th class='titulo'>Ticket</th><th class='titulo'>Tel&eacute;fono</th><th class='titulo'>Total</th></tr><tr>";

/* -----------	Obtener informaci? de la base de datos de calificaciones	-----------	*/
$link = conectarBDCalificaciones();
$query = "SELECT * FROM calificaciones WHERE fecha = '$mes_español-$año'";
$result = mysqli_query($link,$query);


/* ------------ Si no hay datos, calcularlos --------------*/
if(mysqli_num_rows($result) == 0) {
	
	/* -----------	Obtener las calificaciones del chat	-----------	*/

	$evaluaciones_chat = obtenerCalificacionesChat($mes,$año);
	$evaluaciones_chat = enviarTotalesAlFinal($evaluaciones_chat);
	
	/* ----------- Obtener las calificaciones de tickets ----------- */

	$evaluaciones_ticket = obtenerCalificacionesTicket($mes,$año);
	$evaluaciones_ticket = enviarTotalesAlFinal($evaluaciones_ticket);
	
	/* ----------- Obtener las calificaciones de telefono ----------- */

	$evaluaciones_telefono = obtenerCalificacionesTelefono($mes,$año);
	$evaluaciones_telefono = enviarTotalesAlFinal($evaluaciones_telefono);
	
	/* ----------- Obtener las calificaciones totales ----------- */

	$evaluaciones_total = obtenerCalificacionesTotal($evaluaciones_chat,$evaluaciones_ticket,$evaluaciones_telefono);
	$evaluaciones_total = enviarTotalesAlFinal($evaluaciones_total);
	
	/* ----------- Guardar las calificaciones ----------- */
	
	guardarCalificaciones($evaluaciones_chat,$evaluaciones_ticket,$evaluaciones_telefono,$evaluaciones_total,$mes_español,$año);
}

$query = "SELECT * FROM calificaciones WHERE fecha = '$mes_español-$año'";
$result = mysqli_query($link,$query);

$evaluaciones_chat = array();
$evaluaciones_ticket = array();
$evaluaciones_telefono = array();
$evaluaciones_total = array();

while($row = mysqli_fetch_array($result, MYSQLI_ASSOC))
{
	$operador = $row['operador'];

	if($operador == "Porcentaje de Respuesta" || $operador == "Total recibidos" )
	{
		$evaluaciones_chat[$operador]=(double)$row['cantidad_chat'];
		$evaluaciones_ticket[$operador]=(double)$row['cantidad_ticket'];
		$evaluaciones_telefono[$operador]=(double)$row['cantidad_telefono'];
		$evaluaciones_total[$operador]=(double)$row['cantidad_total'];
	}
	else
	{
		$evaluaciones_chat[$operador] = array('amabilidad' => (double)$row['amabilidad_chat'],'eficiencia' => (double)$row['eficiencia_chat'],'cantidad' => $row['cantidad_chat'],'cantidad_contestados' => $row['cantidad_contestados_chat']);
		$evaluaciones_ticket[$operador] = array('amabilidad' => (double)$row['amabilidad_ticket'],'eficiencia' => (double)$row['eficiencia_ticket'],'cantidad' => $row['cantidad_ticket'],'cantidad_contestados' => $row['cantidad_contestados_ticket']);
		$evaluaciones_telefono[$operador] = array('amabilidad' => (double)$row['amabilidad_telefono'],'eficiencia' => (double)$row['eficiencia_telefono'],'cantidad' => $row['cantidad_telefono'],'cantidad_contestados' => $row['cantidad_contestados_telefono']);
		$evaluaciones_total[$operador] = array('amabilidad' => (double)$row['amabilidad_total'],'eficiencia' => (double)$row['eficiencia_total'],'cantidad' => $row['cantidad_total'],'cantidad_contestados' => $row['cantidad_contestados_total']);
	}
}

ksort($evaluaciones_chat);
ksort($evaluaciones_ticket);
ksort($evaluaciones_telefono);
ksort($evaluaciones_total);

$evaluaciones_chat = enviarTotalesAlFinal($evaluaciones_chat);
$evaluaciones_ticket = enviarTotalesAlFinal($evaluaciones_ticket);
$evaluaciones_telefono = enviarTotalesAlFinal($evaluaciones_telefono);
$evaluaciones_total = enviarTotalesAlFinal($evaluaciones_total);

crearTabla($evaluaciones_chat,$modo);
crearTabla($evaluaciones_ticket,$modo);
crearTabla($evaluaciones_telefono,$modo);
crearTabla($evaluaciones_total,$modo);

mysqli_close($link);
?>

</tr></table>

</div>

<?php
}
?>

</body>
</html>

<?php require $upOne."/dashboard_footer.html";?>