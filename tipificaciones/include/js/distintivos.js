$(function() {

	//Star rating
	$(".star_rating").find(".glyphicon").hover(function(){
		$(this).siblings().addBack().removeClass("hovered");
		$(this).prevAll().addBack().addClass("hovered");
		$(this).nextAll(".glyphicon").addClass("other_hovered");
	});
	$(".star_rating").mouseleave(function(){
		$(this).find(".glyphicon").removeClass("hovered");
		$(this).find(".glyphicon").removeClass("other_hovered");
	});

	$(".star_rating").find(".glyphicon").click(function(){
		var glyph = $(this);
		var newCategory = glyph.attr("title");
		var newCategoryId = glyph.data("id");
		var oldCategory = $(".selected_star_msg").html();
		if(newCategory == oldCategory){
			return;
		}
		var distintivo = $(this).closest(".distintivo");
		var domain = getDominioSeleccionado();
		bootbox.confirm("¿Desea cambiar la categoría "+domain+" de '"+oldCategory+"' a '"+newCategory+"'?", function(result) {
			if(result !== false)
			{
				showLoader();
				$.post( rutaAjaxLocal+"setClientBadge.php", { domain: domain, id_badge: distintivo.data("id"), value: newCategoryId, operador: $('#logged_user').val() }, function( data ) {
					hideLoader();
					if(data.success){
						glyph.prevAll().addBack().addClass("selected_category");
						glyph.nextAll().removeClass("selected_category");
						glyph.closest(".star_rating").closest(".distintivo").find(".selected_star_msg").html(newCategory);
						distintivo.attr('title',data.motivo);
					}
					else{
						bootbox.alert("Error en ajax: <br>"+data.message);
					}
				}, "json")
				.fail(function(XMLHttpRequest, textStatus, errorThrown) {
					hideLoader();
					console.log("error: "+errorThrown);
					alert( "error\n"+textStatus+"\n"+errorThrown );
				});
				
			}
		}); 
	});

	//distintivos
	$(".distintivo_binario").dblclick(function(){
		var distintivo = $(this);
		var domain = getDominioSeleccionado();
		value = distintivo.find("img").hasClass("not_selected") ? true : false;
		action = distintivo.find("img").hasClass("not_selected") ? "agregar" : "eliminar";
		nombre_distintivo = distintivo.data("name");
		bootbox.confirm("¿Desea "+action+" el distintivo '"+nombre_distintivo+"' a "+domain+"?", function(result) {
			if(result !== false){
				showLoader();
				$.post( rutaAjaxLocal+"setClientBadge.php", { domain: domain, id_badge: distintivo.data("id"), value: value, operador: $('#logged_user').val() }, function( data ) {
					hideLoader();
					if(data.success){
						distintivo.find("img").toggleClass("not_selected");
						distintivo.attr('title',data.motivo);
					}
					else{
						bootbox.alert("Error en ajax: <br>"+data.message);
					}
				}, "json")
				.fail(function(XMLHttpRequest, textStatus, errorThrown) {
					hideLoader();
					console.log("error: "+errorThrown);
					alert( "error\n"+textStatus+"\n"+errorThrown );
				});
			}
		}); 
	});
});