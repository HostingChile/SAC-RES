<?php 
	$upOne = realpath(__DIR__ . '/..');
	require $upOne."/dashboard_header.php";

	exit("Sistema obsoleto. Ir a soporte.hosting.cl para la &uacute;ltima versi&oacute;n");
?>

<html>

<head>
<title>Chat Operator Panel</title>
<link rel="stylesheet" type="text/css" href="style.css">
<link rel="stylesheet" type="text/css" href="sweet_alert/sweetalert.css">

<script src="//code.jquery.com/jquery-1.11.2.min.js"></script>
<script src="sweet_alert/sweetalert.min.js"></script>
<script>
$(function() {
	var reloadTime = 3;
	var reloadTime_after_error = 5;
	
	// Cargar la informacion al inicio
	$.get('status.php',function(data){
		
		var separador = $('<div class="separador"></div>');
		
		$.each(data, function(brand, brand_info) {
			//Mostrar advertencias
			if(brand_info.warning_logout)
				swal({ title: "DESCONECTAR", text: "Se deben desconectar de "+brand, imageUrl: "images/"+brand+"_logout.png", showConfirmButton: false});
			if(brand_info.warning_login)
				swal({ title: "CONECTAR", text: "Se deben conectar a "+brand, imageUrl: "images/"+brand+".png", showConfirmButton: false});
		
			//Crear la fila para cada marca
			var row = $('<div class="brand"></div>');
			
			//Marcarla si esta offline
			if(!brand_info.en_turno)
				row.addClass('brand_offline');
			
			//Nombre de la marca
			row.append('<div class="name">'+brand+'</div>');
			
			//Crear y agregar los departamentos
			var departments = $('<div class="departments"></div>');
			$.each(brand_info.department, function(department, status) {
				var new_department = $('<div class="department">'+department+'</div>');
				new_department.addClass(status);
				
				departments.append(new_department);			
			});
			row.append(departments);
			
			//Crear y agregar los operadores
			var operators = $('<div class="operators"></div>');
			$.each(brand_info.operator, function(operator, status) {
				var new_operator = $('<div class="operator">'+operator+'</div>');
				new_operator.addClass(status);
				
				operators.append(new_operator);			
			});
			row.append(operators);
			
			//Agregar el separador
			row.append(separador);
			
			//Agregar la fila
			$("#content").append(row);
		});
		
		//Esconder el GIF de cargando
		$('#content img').remove();
		
	},'json');

	// Consultar periodicamente el status
	var reload = setInterval(function(){ 
		swal.close();	
		$.get('status.php',function(data){
			//Actualizo los estados
			$.each(data, function(brand, brand_info) {
				//Marcar marca si esta offline o no
				if(!brand_info.en_turno)
					$(".brand .name:contains('"+brand+"')").parent().addClass('brand_offline');
				else
					$(".brand .name:contains('"+brand+"')").parent().removeClass('brand_offline');
			
				//Actualizar estado departamentos
				$.each(brand_info.department, function(department, status) {
					var target = $(".brand .name:contains('"+brand+"')").siblings(".departments").find(".department:contains('"+department+"')");
					target.removeClass('online').removeClass('offline');
					target.addClass(status);					
				});
				
				//Actualizar estado operadores
				$.each(brand_info.operator, function(operator, status) {
					var target = $(".brand .name:contains('"+brand+"')").siblings(".operators").find(".operator:contains('"+operator+"')");
					target.removeClass('online').removeClass('offline');
					target.addClass(status);					
				});
				
				//Mostrar advertencias
				if(brand_info.warning_logout)
					swal({ title: "DESCONECTAR", text: "Se deben desconectar de "+brand, imageUrl: "images/"+brand+"_logout.png", showConfirmButton: false});
				if(brand_info.warning_login)
					swal({ title: "CONECTAR", text: "Se deben conectar a "+brand, imageUrl: "images/"+brand+".png", showConfirmButton: false});
			});
		},'json').fail(function() {
			swal.close();
			clearInterval(reload);
			$('#content').html("<div id='error' style='color:white'>Se ha producido un error al cargar la información. Se va a actualizar la página en 5 segundos...</div>");
			setTimeout(function(){ location.reload(); }, reloadTime_after_error * 1000);
			
		});
	
	}, reloadTime * 1000);
	
	
});
</script>
</head>

<body>

<h1 id="title">Chat Operator Panel</h1>
<div id="content">
	<img src="loader.gif"/>
</div>

</body>

</html>

<?php require $upOne."/dashboard_footer.html";?>