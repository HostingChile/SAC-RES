<?php 
	$noborder = true;
	$upOne = realpath(__DIR__ . '/..');
	require $upOne."/dashboard_header.php";
?>

<!DOCTYPE html>
<html lang="es">

<head>

    <title>Tickets Abiertos</title>
	<link rel="icon" type="image/png" href="images/favicon.png" /> 
	
    
	<link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
	<link href="css/style.css" rel="stylesheet">

	
	<script src="js/jquery_cookie.js"></script>
	
	
	<script>
	$(function(){
		var reloadTime = $("#refresh input[type=number]").val();
		var reload = true;
		var timeout;
		var selected_brands;
		var selected_departments;
		
		//Cargar datos de cookie
		function loadCookieInfo(){
			//Filtros de marcas
			selected_brands = $.cookie("selected_brands");
			if(typeof selected_brands !== "undefined" &&  selected_brands !== "undefined"){
				selected_brands = selected_brands.split(',');
				$.each(selected_brands, function(i,brand){
					$(".sidebar_brand[data-brand='"+brand+"']").addClass("sidebar_brand_selected");
				});
			}
			else
				$(".sidebar_brand").addClass("sidebar_brand_selected");
				
			//Filtros de departamentos
			selected_departments = $.cookie("selected_departments");
			if(typeof selected_departments !== "undefined" &&  selected_brands !== "undefined"){
				selected_departments = selected_departments.split(',');
				$.each(selected_departments, function(i,department){
					$("#"+department).prop('checked', true);
				});
			}
			else
				$("#sidebar_departments input").prop('checked', true);
				
			$("#apply_filter").click();
			
			//Reload time
			if(typeof $.cookie("reloadTime") !== "undefined" &&  selected_brands !== "undefined"){
				reloadTime = $.cookie("reloadTime");
				$("#refresh input[type=number]").val(reloadTime);
			}
			
			//Mostrar sidebar
			var show_sidebar = $.cookie("show_sidebar");
			if(typeof show_sidebar !== "undefined" &&  selected_brands !== "undefined" && show_sidebar == "false"){
				hideSidebar();
			}
			
				
		}
		loadCookieInfo();
		
		//Toggle sidebar
		$("#sidebar_toggle").click(function(){
			//Show sidebar
			if($("#sidebar").css("width") == "20px"){
				showSidebar();
			}
			//Hide Sidebar
			else{
				hideSidebar();
			}
		});
		
		//Show sidebar
		function showSidebar(){
			$("#sidebar_content").show();
			$("#sidebar").animate({width: "20%"});	
			$("#content").animate({width: "81%"});
			$.cookie("show_sidebar",true, { expires: 365 });
		}
		
		//Hide sidebar
		function hideSidebar(){
			$("#sidebar").animate({width: "20px"},function(){
					$("#sidebar_content").hide();
				});
				$("#content").animate({width: "100%"});
				$.cookie("show_sidebar",false, { expires: 365 });
		}
		
		//Toggle selected brands
		$(".sidebar_brand").click(function(){
			$(this).toggleClass("sidebar_brand_selected");
			
			selected_brands = '';
			$(".sidebar_brand_selected").each(function(){
				selected_brands += $(this).data("brand") + ',';
			});
			selected_brands = selected_brands.replace(/,$/g,'');
		});
		
		//Cargar tickets
		function reloadTickets(){
			$("#content h1 span").text('?');
			$("#table_tickets").hide();
			$("#loading_circles").show();
			$("#table_tickets tbody").html('');
			$.get("getTickets.php", function(data) {
				if(data.reload){
					setTimeout(function(){ location.reload(); }, 5000);
					return;
				}
					
				$.each(data, function(num,ticket) {
					var fila_ticket = $('<tr class="'+ticket.flag+'"></tr>');
					
					//Agregar el destacado de advertencia
					fila_ticket.addClass(ticket.warning);
					 
					fila_ticket.append('<td><img src="images/'+ticket.brand+'.png"></img></td>');
					fila_ticket.append('<td>'+ticket.departamento+'</td>');
					fila_ticket.append('<td>'+ticket.admin_asociado+'</td>');
					fila_ticket.append('<td>'+ticket.asunto+'</td>');
					fila_ticket.append('<td>'+ticket.enviado_por+'</td>');
					fila_ticket.append('<td>'+ticket.status+'</td>');
					if(ticket.respondido_por)
						fila_ticket.append('<td>'+ticket.respondido_por+'</td>');
					else
						fila_ticket.append('<td class="sin_respuesta">Sin Respuesta</td>');
					fila_ticket.append('<td>'+ticket.tiempo_de_espera+'</td>');
					
					$("#table_tickets tbody").prepend(fila_ticket);
				});
				$("#apply_filter").click();
				$("#loading_circles").hide();
				$("#table_tickets").show();
				
				countTickets();
				
				
			},"json");
			if(reload)
				timeout = setTimeout(reloadTickets,reloadTime * 1000);
		}
		reloadTickets();
		
		//Contar tickets abiertos
		function countTickets(){
			var tickets_visibles = $('#table_tickets tr:visible').length - 1;
			var tickets_totales = $('#table_tickets tr').length - 1;
			var tickets_no_visibles = tickets_totales - tickets_visibles;
			
			if(tickets_no_visibles){
				$("#content h1 span").text(tickets_visibles);
				$("#content h4").text("Tickets Filtrados ("+tickets_no_visibles+")");
			}
			else{
				$("#content h1 span").text(tickets_totales);
				$("#content h4").text('');
			}
			
		}
		
		//Filtrar
		$("#apply_filter").click(function(){
			//Guardar Cookie
			$.cookie("selected_brands",selected_brands, { expires: 365 });
			selected_departments = '';
			$("#sidebar_departments input:checked").each(function(){
				selected_departments += $(this).attr("id") + ',';
			});
			selected_departments = selected_departments.replace(/,$/g,'');
			$.cookie("selected_departments",selected_departments, { expires: 365 });
			
			var show_rows = $("#table_tickets tr:not(:first)");
			var hide_rows;
			
			//FILTRO DE MARCAS
			var hide = $(".sidebar_brand").not(".sidebar_brand_selected");
			$.each(hide,function(i,elem){
				var img_src = $(elem).attr("src");
				var row = $("#table_tickets").find("img[src='"+img_src+"']").parents("tr");
				
				show_rows = show_rows.not(row);
			});
			
			//FILTRO DE DEPARTAMENTOS
			var hide = $("#sidebar_departments input:not(:checked)");
			$.each(hide,function(i,elem){
				var departments = $(elem).val();
				departments = departments.split(',');
				
				$.each(departments,function(i,department){
					var row = $("#table_tickets").find('*').filter(function(){
						return $(this).text() === department;
					}).parents("tr");
					show_rows = show_rows.not(row);
				});		
			});
			
			hide_rows = $("#table_tickets tr:not(:first)").not(show_rows);
			
			show_rows.show();
			hide_rows.hide();
			countTickets();
			
		});
	
		//Cambiar tiempo de actualización
		$("#refresh input[type=number]").change(function(){
			clearTimeout(timeout);
			var min = parseInt($(this).attr('min'));
			
			if($(this).val() < min){
				reloadTime = min;
				$(this).val(min);
			}
			else
				reloadTime = $(this).val();
				
			//Guardar cookie
			$.cookie("reloadTime",reloadTime, { expires: 365 });
				
			if(reload)
				timeout = setTimeout(reloadTickets,reloadTime * 1000);
		});
		
		//Activar o desactivar actualizacion
		$("#refresh input[type=checkbox]").change(function(){
			clearTimeout(timeout);
			reload = $(this).prop('checked');
			if(reload)
				timeout = setTimeout(reloadTickets,reloadTime * 1000);
		});
	});
	</script>
	
</head>

<body>
	<div id="wrapper">
		<? include 'sidebar.php'; ?>
		<? include 'content.php'; ?>
	</div>
</body>

</html>

<?php require $upOne."/dashboard_footer.html";?>
