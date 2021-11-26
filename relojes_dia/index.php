<?php 
	$upOne = realpath(__DIR__ . '/..');
	require $upOne."/dashboard_header.php";

	exit("Sistema obsoleto. Ir a soporte.hosting.cl para la &uacute;ltima versi&oacute;n");
?>

<style type="text/css">
	/*#contenedor_iframes
	{
		background-color:rgb(39, 90, 121);
	}*/
	iframe
	{
		border:none;
		width: 40%;
		margin-left: 6%;
		margin-top: 50px;
		float:left;
		height: 2000px;
		-webkit-border-radius: 50px;
		-moz-border-radius: 50px;
		border-radius: 50px;
	}
</style>
<div id="contenedor_iframes">
	<iframe src="tipificaciones/index.php"></iframe>
	<iframe src="transferencias/index.php"></iframe>
</body>

<?php require $upOne."/dashboard_footer.html";?>



