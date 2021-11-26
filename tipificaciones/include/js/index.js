
	//dominio seleccionado
	function getDominioSeleccionado(){ 
		return $("#dominio_cliente").html();
	}

	function getWhmcsUsername(){ 
		return $("#username_cliente").html();
	}

	//marca seleccionada
	function getMarcaSeleccionada(){
		return $("#marca_cliente").html();
	}

	function getSelectedOption(select_id, data_name){
		return $("#"+select_id+" .selected_option").data(data_name);
	}
	function loadCategories(){
		$.post( rutaAjaxLocal+'getAllCategories.php', function(data) {
			if(data.success){
				list = data.categories;
				var dd = $('#drill-down').DrillDown({
					list: list,
					unselectOnNavigate: true,
					onSelect: function(event, data){
						$('#selection .data').text(data.sel.text());
						$('#btn_tipificar').show();
					},
					onUnselect: function(event, data){
						$('#selection .data').text('Ninguna');
						$('#btn_tipificar').hide();
					}
				});
			}
			else{
				alert('No se pudo cargar las categorias de tipificacion: '+data.message);
			}
		},"json").fail(function(XMLHttpRequest, textStatus, errorThrown) {
			console.log("error: "+errorThrown);
			alert( "error\n"+textStatus+"\n"+errorThrown );
		});
	}

	//Loader
	function showLoader(){
		$("#loading-cliente").show();
	}
	function hideLoader(){
		$("#loading-cliente").hide();
	}

	function addAvisoOperaciones(title,description,trello_url, match, creation_date, trello_comments_number){
		var alert_type = "info";
		var alert_message ="Más información en ";
		if(trello_comments_number > 0){
			alert_type = "warning";
			if(trello_comments_number == 1){
				alert_message = trello_comments_number + " comentario en ";
			}
			else{
				alert_message = trello_comments_number + " comentarios en "; //lul
			}
		}
		$('#avisos_operaciones').append(`  <div class="panel panel-default trello_info">
		<div class="panel-heading">
			<h4 class="panel-title">
				<b>${title}</b> <i>${creation_date}</i> <i>${match}</i>
			</h4>
		 </div>
		   <div class="panel-body" style="white-space: pre-line">
			 ${description}
			 <div class="alert alert-${alert_type}" role="alert">
			 	<strong>${alert_message} </strong> <a target="_blank" rel="noopener noreferrer" href="${trello_url}" class="alert-link">Trello</a>
			</div>
		   </div>
		 </div>
	 </div>`);
	}

//Funciones de WHMCS

	//Buscar info del cliente en WHMCS
	function getClientInfo(){
		hideMultiplesResultados();

		var dominio = $.trim($("#inputDominio").val());
		var marca = getSelectedOption("select_brand","nombre");

		//reviso los datos antes de consultar al whmcs por el cliente
		if( $("#inputNoEsCliente").is(':checked') || typeof(marca) === 'undefined' || dominio.length < 2 ){
			showAlert('Especifique dominio y marca para buscar información desde el WHMCS', 'alert-danger', 1500);
			return;
		}
		showLoader();
		console.log("calling getdomainfinfo");
		console.log({ domain: dominio, brand: marca });
		$.post( rutaAjaxLocal+"getDomainInfo.php", { domain: dominio, brand: marca }, function( data ) {
			console.log("finished getdomain info");
			processClientPostData(data);
			hideLoader();
		}, "json")
		.fail(function(XMLHttpRequest, textStatus, errorThrown) {
			hideLoader();
			console.log(XMLHttpRequest.responseText);
			console.log("error: "+errorThrown);
			alert( "error\n"+textStatus+"\n"+errorThrown );
		});
	}

	//Procesa la info obtenida desde el WHMCS
	function processClientPostData(data){

		//data.result -> success, nohits, multiplehits o error

		//si es que hubo solo un resultado, muestro la info de ese cliente
		if(data.result === "success"){
			console.log("Informacion del cliente:");
			console.log(data);
			$("#inputDominio").val(data.whmcs_info.general.domain);
			$("#detalle_tipificacion").show();
			$("#dominio_cliente").html(data.whmcs_info.general.domain);
			$("#username_cliente").html(data.whmcs_info.general.username);
			$("#marca_cliente").html(getSelectedOption("select_brand","id"));
			showClientInfo(data);
		}
		else
		{
			$("#detalle_tipificacion").hide();

			//si es que no hubo resultados, pongo rojo el input del dominio
			if(data.result == "nohits"){
				showAlert("No se encontraron clientes con ese dominio en el WHMCS", 'alert-danger', 1500);
			}
			//si hubo multiples resultados, muestro las opciones para que el usuario elija
			else if(data.result == "multiplehits"){
				var append = "";
				$.each(data.hits, function(i, item) {
				    append += "<div class='resultado' id='"+item["id"]+"'>"+item["domain"]+" ("+item["domainstatus"]+")</div>";
				});

				$("#select_multiples_resultados").html(append);
				$("#select_multiples_resultados").show();
			}
			//si hubo error alerto
			else if(data.result == "error"){
				alert("Error: "+ data.message);
			}
			else{
				alert("Excepcion desconocida: "+data);
			}
		}
	}

	//Funciones Formulario de tipificacion
	function resetearFormularioTipificacion(){
		$('.form-group').removeClass('has-error');
		$('.custom_select').find('.selected_option').removeClass('selected_option');
		$("#pending_call_id").val('');
		$("#marca_cliente").html('');
		$('#inputNombreCliente').val('');
		$('#inputNoEsCliente').attr('checked',false);
		$('#inputDominio').val('');
		$("#dominio_cliente").html('');
		$("#username_cliente").html('');
		$('#inputDetalle').val('');
		$('#drill-down').find('.item').removeClass('selected');
		$('#selection').find('.data').html('Ninguna');
		$('#detalle_tipificacion').hide();
		$('#btn_buscar_dominio').show();
	}

	//Funciones Formulario de tipificacion
	function resetearBreadcrumbs(){
		//Reset breadcrumbs
		let breadcrumbs = document.getElementsByClassName('breadcrumb-item');
		while(breadcrumbs.length > 1){
			breadcrumbs[breadcrumbs.length-1].remove();
		}
		loadCategories();
	}

	function showAlert(message, type, hideAfter)
	{
		$('#alertas_tipificacion').removeClass('alert-success');
		$('#alertas_tipificacion').removeClass('alert-danger');

		$('#alertas_tipificacion').html(message);
		$('#alertas_tipificacion').addClass(type);
		$('#alertas_tipificacion').alert();
		if(hideAfter !== false){
			$('#alertas_tipificacion').fadeTo(hideAfter, 500).slideUp(500, function(){
        	});
		}
		else{
			$('#alertas_tipificacion').fadeTo(hideAfter, 500);
		}
	}

	function hideMultiplesResultados(){
		$('#select_multiples_resultados').css('display','none');
	}

//Funciones para mostrar info del cliente
	function getBrandWhmcsUrl(brand){
		var brands_urls = {
			'Hosting': 'https://panel.hosting.cl/admin', 
			'PlanetaHosting': 'https://www.planetahosting.cl/whmcs/admin',
			'NinjaHosting': 'https://www.ninjahosting.cl/whmcs/admin', 
			'Hostingcenter': 'https://www.hostingcenter.cl/whmcs/admin',
			'iHost': 'https://www.ihost.cl/whmcs/admin',
			'PlanetaPeru': 'https://www.panel.planetahosting.pe/admin', 
			'InkaHosting': 'https://www.panel.inkahosting.com.pe/admin',
			'1Hosting': 'https://www.1hosting.com.pe/whmcs/admin',
			'PlanetaColombia': 'https://panel.planetahosting.com.co/admin', 
			'HostingcenterColombia': 'https://panel.hostingcenter.com.co/admin', 
			'HostingPro': 'https://panel.hostingpro.com.co/admin', 
		};
		return brands_urls[brand]; 
	}

	//Retorna una url con informacion acerca de un dominio basado en si es .cl o no
	function domainServiceToQuery(domain_name){
		if(domain_name.endsWith('.cl')){
			return 'http://www.nic.cl/registry/Whois.do?d='+domain_name;
		}
		return 'https://www.whois.com/whois/' + domain_name;
	}

	//Retorna una url a un logo, dependiendo del tipo de servicio que representa (nic.cl,whois,etc)
	function domainServiceLogo(domain_name){
		if(domain_name.endsWith('.cl')){
			return 'include/img/logos/nic.png';
		}
		return 'include/img/logos/whois_logo.png';
	}

	//Mostrar la informacion del cliente al apretar "Buscar info"
	function showClientInfo(data){
		
		//alertas que se muestran en la parte superior
		var alerts = {danger: [], warning: [], info: []};

		//WHMCS
		var whmcs_info = data.whmcs_info.general;
		var client_info = data.whmcs_info.client;
		var ip_whmcs_cliente = whmcs_info.ipaddress;
		
		//Trello
		var trello_info = data.trello;

		//datos del servicio
			$('#contenido_cliente').find('#plan_servicio').html(whmcs_info.plan);
			if(whmcs_info.dedicatedip == '' && whmcs_info.ipaddress != null){
				$('#contenido_cliente').find('#ip_servidor').html(whmcs_info.ipaddress);
				setearLinkGaspar(whmcs_info.ipaddress);
				if(whmcs_info.es_asp){
					$('.opcion_compartidos').prop('disabled', true);
				}
			}
			else{
				var ip_whmcs_cliente = whmcs_info.dedicatedip;
				if(ip_whmcs_cliente != ""){
					$('#contenido_cliente').find('#ip_servidor').html(whmcs_info.dedicatedip+' (IP dedicada)');
				}
				else{
					$('#contenido_cliente').find('#ip_servidor').html('Sin Servidor');
				}
				
				$('#contenido_cliente').find('#link_whm').hide();
				$('.opcion_compartidos').prop('disabled', true);
			}

			//link al whmcs
			
			$('#contenido_cliente').find('#link_whmcs').attr('href',getBrandWhmcsUrl(data.brand)+'/clientsservices.php?userid='+whmcs_info.userid+'&id='+whmcs_info.id);
			
			$('#contenido_cliente').find('#link_intodns').attr('href','http://www.intodns.com/'+whmcs_info.domain);

			$('#contenido_cliente').find('#link_nic').attr('href',domainServiceToQuery(whmcs_info.domain));
			$('#contenido_cliente').find('#logo_domain_lookup_service').attr('src',domainServiceLogo(whmcs_info.domain));


			var monto = whmcs_info.amount;
			var billing_cycles = {'Annually': 'Anual', 'Biennially': 'Bianual', 'Free Account': 'Hosting Gratis', 'Monthly': 'Mensual', 'One Time': 'Una vez', 'Quarterly': 'Cuatrimestral', 'Semi-Annually': 'Semi-Anual'};
			$('#contenido_cliente').find('#monto_plan_servicio').html(formatAmount(whmcs_info.amount,billing_cycles[whmcs_info.billingcycle]));
			$('#contenido_cliente').find('#fecha_vencimiento_servicio').html(whmcs_info.nextduedate);
			var domain_status_name = {Active: 'Activo', Terminated: 'Terminado', Suspended: 'Suspendido', Cancelled: 'Cancelado'};
			$('#contenido_cliente').find('#status_servicio').html(domain_status_name[whmcs_info.domainstatus]);
			var domain_status_class = {Active: 'success', Terminated: 'danger', Suspended: 'warning', Cancelled: 'danger'};
			$('#contenido_cliente').find('#status_servicio').closest('tr').addClass(domain_status_class[whmcs_info.domainstatus]);

			var statusClass = whmcs_info.domainstatus == 'Terminated' ? 'danger' : '';
			statusClass = whmcs_info.domainstatus == 'Suspended' ? 'warning' : statusClass;
			$('#contenido_cliente').find('#status_servicio').closest('tr').addClass(statusClass);

		//datos del cliente
			$('#contenido_cliente').find('#contacto_administrativo_cliente').html(client_info.contacto_administrativo);
			$('#contenido_cliente').find('#contactos_tecnicos_cliente').html(client_info.contactos_tecnicos);
			$('#contenido_cliente').find('#fecha_registro_cliente').html(client_info.fecha_registro);
			$('#contenido_cliente').find('#servicios_activos_cliente').html(client_info.servicios_activos);
			$('#contenido_cliente').find('#monto_total_cliente').html(formatAmount(client_info.monto_total+"", "Anual"));	
		
		//trello info
		if(trello_info.length>0){
			alerts.danger[alerts.danger.length] = '<a href="#avisos_operaciones">Existen alertas desde operaciones.</a>';
			$('.trello_info').remove();
			$('#avisos_operaciones').show();	
			for(card in trello_info){
				addAvisoOperaciones(trello_info[card].name,trello_info[card].description,trello_info[card].url,trello_info[card].match, trello_info[card].creation_date, trello_info[card].number_of_comments);
			}
		}
		else{
			$('#avisos_operaciones').hide();
		}


		//alerta por uso de disco
		var diskUsage = whmcs_info.diskusage;
		var diskCapacity =whmcs_info.disklimit;
		var limitDanger = 0.95;
		var limitWarning = 0.9;
		var currentPercentage = diskUsage/diskCapacity;
		if(currentPercentage>=limitDanger){
			alerts.danger[alerts.danger.length] = '<strong>Uso de disco en '+currentPercentage*100+'%: </strong>'+diskUsage+'/'+diskCapacity+' MB<br />';
		}
		else if(currentPercentage>=limitWarning){
			alerts.warning[alerts.warning.length] = '<strong>Uso de disco en '+currentPercentage*100+'%: </strong>'+diskUsage+'/'+diskCapacity+' MB<br />';
		}


		//notas del servicio WHMCS
		if(whmcs_info.notes != ''){
			//remplazo 6 digitos por un link al ticket
			// whmcs_info.notes = whmcs_info.notes.replace(/([0-9][0-9][0-9][0-9][0-9][0-9]) /g, '<a target="_blank" href="'+getBrandUrl(data.brand)+'/whmcs/admin/supporttickets.php?action=view&id='+'$1'+'">$1 </a>');
			alerts.info[alerts.info.length] = '<strong>WHMCS - Notas del servicio: </strong><br>'+whmcs_info.notes.replace(/(?:\r\n|\r|\n)/g, '<br />');
		}
		// notas del cliente WHMCS
		if(client_info.notes){
			alerts.info[alerts.info.length] = '<strong>WHMCS - Notas del cliente: </strong><br>'+client_info.notes.replace(/(?:\r\n|\r|\n)/g, '<br />');
		}

		//BADGES
		var badges = data.badges;
		$.each(badges, function(i, badge){
			var id_badge = badge['nombre'].toLowerCase()+'_cliente';
			var dom_badge = $('#'+id_badge);
			dom_badge.attr('title',badge.motivo);
			if(badge['tipo'] === 'binary'){
				if(badge['valor'] === 'true'){
					dom_badge.find('img').removeClass('not_selected');
				}
				else if(badge['valor'] === 'false'){
					dom_badge.find('img').addClass('not_selected');
				}
				else{
					alert('Error: valor de badge es nulo');
				}
			}
			else if(badge['tipo'] === 'star'){
				if(badge['valor'] != null){
					var glyph = dom_badge.find('.categoria_cliente[data-id="'+badge["valor"]+'"]');
					var newCategory = glyph.attr('title');
					glyph.prevAll().addBack().addClass('selected_category');
					glyph.nextAll().removeClass('selected_category');
					glyph.closest('.star_rating').closest('.distintivo').find('.selected_star_msg').html(newCategory);
				}
				else{
					dom_badge.find('.categoria_cliente').removeClass('selected_category');
					dom_badge.find('.selected_star_msg').html('');
					alert('Error: valor de badge es nulo');
				}
			}		
		});

		//ATENCIONES
		$('.no_attentions').remove();
		var atentions = data.atentions.detail;
		if(data.atentions.detail.length >0){
			$('#historial_atenciones').show();
			$.each(atentions, function(i, at){
				addAtention($("#contenido_cliente").find("#historial_atenciones"), at['nombre_cliente'], at['categoria'], at['detalle'], at['operador'], at['fecha'], at['new_atention'], at['problema_solucionado'], at['img_compania'], at['img_tipocontacto']);
			});
	
			var new_atentions = data.atentions.new_atentions;
			if(new_atentions>3){
				alerts.danger[alerts.danger.length] = 'Este dominio registra <strong>'+new_atentions+' contactos recientes</strong>. Por favor revisar estos para dar una correcta atención y solucionar el problema definitivamente.';
			}
			else if (new_atentions>1){
				alerts.warning[alerts.warning.length] = 'Este dominio registra <strong>'+new_atentions+' contactos recientes</strong>. Por favor revisar estos para dar una correcta atención y solucionar el problema definitivamente.';
			}
		}
		else{
			$('#historial_atenciones').append('<p class="no_attentions">No existen atenciones asociadas a este cliente.</p>');
		}


		//PING Y MX
		var ping_info = data.ping;
		$('#contenido_cliente').find('#ping_servicio').html(ping_info.ip_ping);

		var alternative_ip = "";
		let ip_errors = {ping: false, www:false, mail:false}; //Errors to display warning
		if(ip_whmcs_cliente != ping_info.ip_ping && !whmcs_info.es_asp){
			$('#contenido_cliente').find('#ping_servicio').closest('tr').addClass('danger');
			if(ping_info.ip_ping != "No responde"){
				alternative_ip = ping_info.ip_ping;
				ip_errors.ping = true;
			}
		}
		$('#contenido_cliente').find('#ping_www_servicio').html(ping_info.ip_ping_www);
		if(ip_whmcs_cliente != ping_info.ip_ping_www && !whmcs_info.es_asp && !ping_info.cloudflare){
			ip_errors.www = true;
			$('#contenido_cliente').find('#ping_www_servicio').closest('tr').addClass('danger');
		}
		$('#contenido_cliente').find('#ping_mail_servicio').html(ping_info.ip_ping_mail);
		if(ip_whmcs_cliente != ping_info.ip_ping_mail && whmcs_info.es_asp){
			$('#contenido_cliente').find('#ping_mail_servicio').closest('tr').addClass('danger');
			if(ping_info.ip_ping_mail != "No responde"){ 
				alternative_ip = ping_info.ip_ping_mail;
				ip_errors.mail = true;
			}
		}
		var mxs = "<ul style='list-style: none;padding-left: 0;'>";
		$.each(ping_info.mxs, function(i, mx){
			mxs+="<li>"+mx+"</li>";
		});
		mxs+="</ul>";
		$('#contenido_cliente').find('#mx_servicio').html(mxs);

		//Alerto en caso de que su IP este en cloudflare
		if(ping_info.cloudflare){
			$('#contenido_cliente').find('#ping_www_servicio').closest('tr').addClass('warning');
			alerts.warning[alerts.warning.length] = 'Este dominio hace ping a un servidor de <a href="http://desarrollo.hosting.cl/trac/wiki/CloudFlare">CloudFlare</a>';
		}

		//MOSTRAR EL PANEL
		$('#area_informacion_cliente').show();
		hideLoader();

		if(ip_errors.ping === true || ip_errors.www === true || ip_errors.mail === true){
			let warning_message_to_display = "Se encontraron los siguientes errores: <br/>";
			for (let key in ip_errors){
				if(ip_errors[key]){
					warning_message_to_display += 'IP de ' + key + " distinto al almacenado en el WHMCS. <br/>";
				}
			}
			bootbox.alert(warning_message_to_display);
		}


		//OBTENGO INFORMACION DEL SERVIDOR DESDE RACKHOST (si es que es un servidor compartido) 
		/*bootbox.confirm("<strong>La IP del WHMCS no coincide con la IP del ping</strong><br>¿Desea cambiar la IP del WHMCS de '"+ip_whmcs_cliente+"' a '"+alternative_ip+"'?", function(result) {
			if(result){
				$.post( rutaAjaxLocal+"update_ip_whmcs.php", { domain: whmcs_info.domain, domain_id: whmcs_info.id, hosting: data.brand, esASP: whmcs_info.es_asp, web_ip: ping_info.ip_ping, mail_ip: ping_info.ip_ping_mail }, function( data ) {
					if(data.success){
						$('#contenido_cliente').find('#ip_servidor').html(alternative_ip);
						setearLinkGaspar(alternative_ip);
						$('#contenido_cliente').find('#ping_servicio').closest("tr").removeClass('danger');
						$('#contenido_cliente').find('#ping_www_servicio').closest("tr").removeClass('danger');
						$('#contenido_cliente').find('#ping_mail_servicio').closest("tr").removeClass('danger');
					}
					else{
						bootbox.alert("Error: "+data.message);
					}
				}, "json")
				.fail(function(XMLHttpRequest, textStatus, errorThrown) {
					console.log("error: "+errorThrown);
					alert( "error\n"+textStatus+"\n"+errorThrown );
				});
			}
		});
	}*/
		//-> elimine la condicion de si era servidor compartido. Ahora los problemas de servidores pueden ser para cualquier IP
		console.log("dedicado: "+whmcs_info.dedicatedip);
		// if(whmcs_info.dedicatedip == ''){
			$.post( rutaAjaxOperaciones+"/operaciones/getserverinfo", { domain: whmcs_info.domain, ping_server: ping_info.ip_ping, whmcs_server: ip_whmcs_cliente }, function( data ) {
				console.log("Info de rackhost");
				console.log(data);
				if(data.success)
				{
					//alertas de rackhost
					var rackhostAlert = "";
					$.each(data.alerts,function(i, alerta){
						var mensaje = alerta.mensaje.replace(/<br\s*[\/]?>/gi, "\n");
						rackhostAlert += "<li><strong>"+alerta.herramienta+": </strong> "+mensaje+"</li>"; 
					});
					if(rackhostAlert.length > 0){
						alerts.warning[alerts.warning.length] = "<strong>Alertas del servidor: </strong><ul>"+rackhostAlert+"</ul>";
					}
					//problemas de servidor
					var serverProblems = "";
					$.each(data.problemas_servidor,function(i, problema_servidor){
						serverProblems += "<li>"+problema_servidor.respuesta_clientes+"</li>"; 
					});
					if(serverProblems.length > 0){
						alerts.danger[alerts.danger.length] = "<strong>El servidor está con problemas: </strong><br>Se le está dando esta respuesta a los clientes<ul>"+serverProblems+"</ul>";
						$('.opcion_compartidos').prop('disabled', true);
					}
					//llego a tope de bloqueos de IP
					if(data.desbloqueos_ip > 2){
						alerts.danger[alerts.danger.length] = "<strong>Desbloqueo de IP: </strong>El dominio ya tiene "+data.desbloqueos_ip+" desbloqueos de IP en el sistema automático";
					}
				}
				else{
					alerts.danger[alerts.danger.length] = data.message;
				}
				showAlerts(alerts);
			},"json")
			.fail(function(XMLHttpRequest, textStatus, errorThrown) {
				console.log("error an ajax de rackhost: "+errorThrown);
				showAlerts(alerts);
			});
		// }
		// else{
		// 	showAlerts(alerts);
		// }

	}

	function decode_utf8(s) {
	  	return decodeURIComponent(escape(s));
	}

	function showAlerts(alerts){
		for(var alert_type in alerts){
			for(var alerts_key in alerts[alert_type]){
				addAlert(alert_type,alerts[alert_type][alerts_key]);
			}
		}
	}

	function formatAmount(amount, billingcycle){
		//si tiene un ".", lo elimino junto con todos los numeros a continuacion
		var monto = amount.indexOf('.') >= 0 ? amount.substring(0, amount.indexOf('.')) : amount;
		//pongo un "." cada 3 numeros (separador de miles)
		monto = monto.replace(/(\d)(?=(\d\d\d)+(?!\d))/g, function($1) { return $1 + "." });
		if(monto == "0"){
			return "No Paga";
		}
		else{
			return "$ "+monto+" + IVA "+billingcycle;
		}
	}

	function addAlert(type, message){
		$("#contenido_cliente").find("#alertas").append('<div class="alert alert-'+type+' alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+message+'</div>');
	}

	

	function clearClientInfo(){
		//borro las alertas
		$('#contenido_cliente').find('.alert').remove();

		//desseteo los badges
		$('.distintivo').find('img').addClass('not_selected');
		$('.distintivo').find('.categoria_cliente').removeClass('selected_category');
		$('.distintivo').find('.selected_star_msg').html('');


		//borro los datos ocultos
		clearHtml($('#dominio_cliente'));
		clearHtml($('#username_cliente'));
		clearHtml($('#marca_cliente'));

		//borro info de la tabla (servicio)
		clearHtml($('#contenido_cliente').find('#plan_servicio'));
		clearHtml($('#contenido_cliente').find('#ip_servidor'));
		$('#contenido_cliente').find('#link_whm').attr('href','').show();
		clearHtml($('#contenido_cliente').find('#monto_plan_servicio'));
		clearHtml($('#contenido_cliente').find('#fecha_vencimiento_servicio'));
		clearHtml($('#contenido_cliente').find('#status_servicio'));
		clearHtml($('#contenido_cliente').find('#ping_servicio'));
		clearHtml($('#contenido_cliente').find('#ping_www_servicio'));
		clearHtml($('#contenido_cliente').find('#ping_mail_servicio'));
		clearHtml($('#contenido_cliente').find('#mx_servicio'));
		clearHtml($('#contenido_cliente').find('#encargado_servicio'));

		//borro la info de la tabla (cliente)
		clearHtml($('#contenido_cliente').find('#contacto_administrativo_cliente'));
		clearHtml($('#contenido_cliente').find('#contactos_tecnicos_cliente'));
		clearHtml($('#contenido_cliente').find('#fecha_registro_cliente'));
		clearHtml($('#contenido_cliente').find('#servicios_activos_cliente'));
		clearHtml($('#contenido_cliente').find('#monto_total_cliente'));

		//elimino las clases contextuales
		$('#contenido_cliente').find('tr').removeClass('danger');
		$('.opcion_compartidos').prop("disabled",false);

		//borro el hisotrial de atenciones
		$('#historial_atenciones').find('.atencion').remove();

		// $("#area_informacion_cliente").hide();
	}

	function clearHtml(dom_element){
		dom_element.html("");
	}

	function setearLinkGaspar(ip){
		$('#contenido_cliente').find('#link_whm').attr('href','https://operaciones.hosting.cl/server_info/'+ip+'/login').show();
	}

	function showSpammers(spammers){
		var message = "No hay cuentas bloqueadas en el servidor";
		if(spammers.length > 0){
			message = "<table class='table table-bordered'><tr><th>Cuenta bloqueada</th><th>Fecha Bloqueo</th><th>Motivo</th><th>Acciones</th></tr>"
			$.each(spammers,function(i,spammer){
				var tr_class = spammer.email.indexOf("@"+getDominioSeleccionado()) !== -1 ? 'success' : '';
				message+="<tr class='"+tr_class+"'>"+
							"<td>"+spammer.email+"</td>"+
							"<td>"+spammer.date+"</td>"+
							"<td class='cell-breakWord'>"+spammer.reason+"</td>"+
							"<td><button type='button' class='btn btn_desbloquear_cuenta' data-loading-text=\"<i class='fa fa-circle-o-notch fa-spin'></i> Espere\" data-email='"+spammer.email+"'>Desbloquear</button>"+
						"</tr>";
			});
			message+="</table>";
		}
		$('#blocked_accounts_content').html(message);
		$('#blocked_accounts_modal').modal('show');
	}


//document ready
$(function() {

	$("#area_informacion_cliente").hide();
	hideLoader();


//Funciones de "custom select" (select con imagenes para tipo de contacto y marca

	$(".custom_select_option").on("click",function(){
		$(this).addClass("selected_option");
		$(this).siblings().removeClass("selected_option");
	});

	$("#select_brand").on("click",".custom_select_option",function(){
		$("#div_dominio").show();
	});

	$(".client_call_option").on("click",function(){
		// var list = [];
	 //    $.post( rutaAjaxLocal+'getPendingCalls.php', function(data) {
	 //        //data = jQuery.parseJSON(data);
	 //        console.log("Llamadas Pendientes");
	 //        console.log(data);
	 //        if(data.success){
	 //        	select_pending_calls = "<table class='table'><tr><th></th><th>Fecha</th><th>Nombre</th><th>Teléfono</th><th>Compañia</th></tr>";
	 //            $.each(data.llamadas_pendientes,function(index, value){
	 //            	select_pending_calls += '<tr><td><input type="radio" name="pending_calls" value="'+value.id+'"></td><td>'+value.fecha_solicitud+"</td><td>"+value.nombre+'</td><td>'+value.telefono+'</td><td>'+value.compañia+'</td></tr>';
	 //            });
	 //            select_pending_calls += '<tr><td><input type="radio" name="pending_calls" value="-1"></td><td colspan="8">Otra llamada</td></tr></table>';
	 //            bootbox.dialog({
	 //                title: "Seleccione llamada pendiente",
	 //                message: select_pending_calls,
	 //                buttons: {
	 //                    success: {
	 //                        label: "Asignar",
	 //                        className: "btn-success",
	 //                        callback: function () {
	 //                        	var sel_id = $('input[name=pending_calls]:checked').val();
	 //                        	sel_id = sel_id == undefined ? "" : sel_id;
	 //                        	$("#pending_call_id").val(sel_id);
	 //                        }
	 //                    }
	 //                }
	 //            });

	 //        }
	 //        else{
	 //            // bootbox.alert("No hay llamadas pendientes a clientes.<br>");
	 //            var sel_id = -1;
	 //            $("#pending_call_id").val(sel_id);
	 //        }
	 //        $("#pending_call_id").val(sel_id);
	 //    },"json").fail(function(XMLHttpRequest, textStatus, errorThrown) {
		// 	console.log("error: "+errorThrown);
		// 	alert( "error\n"+textStatus+"\n"+errorThrown );
		// });
	});

//Drilldown
	var list = [];
	loadCategories();
	
    
//Formulario de tipificacion

	//Esconder la ventana que muestra los multiples dominios del WHMCS

	$('#btn_buscar_dominio').on('click',function(){
		clearClientInfo();
		getClientInfo();
	});

	$('#inputDominio').focus(function(){
		$('#inputDominio').removeClass('error_input');
	});

	$('#select_multiples_resultados').on('click','.resultado',function(){
		hideMultiplesResultados();
		var id_producto = $(this).attr('id');
		var marca = getSelectedOption('select_brand','nombre');
		showLoader();
		$.post( rutaAjaxLocal+'getDomainInfo.php', { id_product: id_producto, brand: marca }, function( data ) {
			processClientPostData(data);
			hideLoader();
		}, 'json')
		.fail(function(XMLHttpRequest, textStatus, errorThrown){
			hideLoader();
			console.log("error: "+errorThrown);
			alert( "error\n"+textStatus+"\n"+errorThrown );
		});
	});

	$('#inputNoEsCliente').bind('change', function(){
		if($(this).is(':checked')){
			$('#detalle_tipificacion').show();
			$('#btn_buscar_dominio').hide();
		}else{
			$('#btn_buscar_dominio').show();
		}
	});

	$('form input').keydown(function(event){
		if(event.keyCode == 13) {
			event.preventDefault();
			return;
		}
	});

	//Agregar tipificacion (validando los inputs)
	$('#btn_tipificar').on('click',function(event){
		event.preventDefault();
		//elimino todos los errores
		$('.form-group').removeClass('has-error');

		//obtengo los valores del formulario
		var detalle_tipificacion = {};
		detalle_tipificacion['operador'] = $('#logged_user').val();
		detalle_tipificacion['id_tipocontacto'] = getSelectedOption('select_contact_type','id');
		// detalle_tipificacion['id_llamada_cliente'] = $("#pending_call_id").val();
		detalle_tipificacion['nombre_cliente'] = $('#inputNombreCliente').val();
		detalle_tipificacion['no_cliente'] = $('#inputNoEsCliente').is(':checked');
		//si es que es cliente, debe haber apretado el boton "buscar_info" para que sea valido
		if($('#inputNoEsCliente').is(':checked')){
			detalle_tipificacion['id_marca'] = getSelectedOption("select_brand","id");
			detalle_tipificacion['dominio'] = $("#inputDominio").val();
			detalle_tipificacion['ip_servidor'] = "";
			detalle_tipificacion['nombre_plan'] = "";
		}
		else{
			detalle_tipificacion['id_marca'] = getMarcaSeleccionada();
			detalle_tipificacion['dominio'] = getDominioSeleccionado();
			detalle_tipificacion['ip_servidor'] = $('#contenido_cliente #ip_servidor').html();
			detalle_tipificacion['nombre_plan'] = $('#contenido_cliente #plan_servicio').html();
		}
		detalle_tipificacion['problema_solucionado'] = getSelectedOption('select_problem_solved','value');
		detalle_tipificacion['intervino_operaciones'] = getSelectedOption('select_intervino_operaciones','value');
		detalle_tipificacion['detalle'] = $('#inputDetalle').val();
		detalle_tipificacion['id_categoria'] = $('#drill-down .selected').data('id');
		
		

		detalle_tipificacion['nueva_subcategoria'] = "";
		//valido los inputs del usuario
		errors = [];
		if(typeof detalle_tipificacion['id_tipocontacto'] === 'undefined'){
			$('#select_contact_type').closest('.form-group').addClass('has-error');
			errors.push('Seleccione un medio de contacto');
		}
		// if(detalle_tipificacion['id_tipocontacto'] == 3 && detalle_tipificacion['id_llamada_cliente'] == "" ){
		// 	errors.push('No asoció la tipificación a una llamada pendiente');
		// } 
		if(detalle_tipificacion['nombre_cliente'].trim().length === 0){
			$('#inputNombreCliente').closest('.form-group').addClass('has-error');
			errors.push('Ingrese el nombre del cliente');
		}
		if(typeof detalle_tipificacion['id_marca'] === 'undefined' || detalle_tipificacion['id_marca'] === ''){
			$('#select_brand').closest('.form-group').addClass('has-error');
			errors.push('Ingrese la marca de hosting del cliente');
		}
		if(detalle_tipificacion['dominio'].trim().length === 0 && detalle_tipificacion['no_cliente'] === false){
			$('#inputDominio').closest('.form-group').addClass('has-error');
			errors.push('Ingrese el dominio del cliente');
		}
		if(typeof detalle_tipificacion['problema_solucionado'] === 'undefined'){
			$('#select_problem_solved').closest('.form-group').addClass('has-error');
			errors.push('Seleccione si el problema fue solucionado');
		}
		if(detalle_tipificacion['detalle'].trim().length === 0){
			$('#inputDetalle').closest('.form-group').addClass('has-error');
			errors.push('Ingrese el detalle de la atención');
		}
		if(typeof detalle_tipificacion['id_categoria'] === 'undefined'){
			$('#inputCategoria').closest('.form-group').addClass('has-error');
			errors.push('Seleccione una categoría');
		}

		if(errors.length > 0)
		{
			error_msg = '<ul>';
			for ( var i = 0; i < errors.length; i++ ) {
			    error_msg += '<li>' + errors[i] + '</li>';
			}
			error_msg += '</ul>';
			showAlert(error_msg, 'alert-danger', false);
			return;
		}

		if(detalle_tipificacion['id_categoria'] == -1){
			bootbox.prompt("Ingrese la subcategoría a la que corresponda la atención entregada", function(result){ 
				if( result == null || result.trim() == ""){
					bootbox.alert("Debe ingresar una subcategoría");
					return;
				}
				detalle_tipificacion["nueva_subcategoria"] = result;
				//envio la info por ajax para tipificar
				sendAjaxTipification(detalle_tipificacion);
			});
		}
		else{
			//envio la info por ajax para tipificar
			sendAjaxTipification(detalle_tipificacion);
		}
		
	});

	function sendAjaxTipification(detalle_tipificacion){
		showLoader();
		console.log("detalle tipificacionx", detalle_tipificacion);
		$.post(rutaAjaxLocal+'tipificar.php', {'detalle_tipificacion': detalle_tipificacion},function(data){
			hideLoader();
			if(data.success){
				resetearFormularioTipificacion();
				clearClientInfo();
				$("#area_informacion_cliente").hide();
				showAlert('Tipificación agregada exitosamente', 'alert-success', 2500);
			}
			else{
				showAlert('Error<br>'+data.message, 'alert-danger', false);
			}
		}, 'json')
		.fail(function(XMLHttpRequest, textStatus, errorThrown){
			hideLoader();
			console.log("error: "+errorThrown);
			alert( "error\n"+textStatus+"\n"+errorThrown );
		});
	}




//Datos del cliente
	$('#btn_clear_data').on('click',function(){
		clearClientInfo();
	});

	//backups locales
	// $("#btn_backup_locales").on("click",function(){
	// 	var dominio =  getDominioSeleccionado();
	// 	var marca = getMarcaSeleccionada();
	// 	var ping_servidor = $('#contenido_cliente').find('#ping_servicio').html();
	// 	var ip_whmcs_cliente = $('#contenido_cliente').find('#ip_servidor').html();
	// 	showLoader();
	// 	$.post( rutaAjaxOperaciones+"getLocalBackups.php", { domain: dominio, ping_server: ping_servidor , whmcs_server: ip_whmcs_cliente }, function( data ) {
	// 		hideLoader();
	// 		console.log("Info de backups locales");
	// 		console.log(data);
	// 		if(data.success){
	// 			// var display_msg = "<b>Backups locales disponibles para "+dominio+":</b><br><ul>";
	// 			// $.each(data.backups, function(i, item) {
	// 			//     display_msg += "<li>"+item["fecha"]+"     <i>"+item["ruta"]+"</i></li>";
	// 			// });
	// 			//bootbox.alert(display_msg+"</ul>");

	// 			var display_msg = "<b>Backups locales disponibles para "+dominio+":</b><br>";
	// 			display_msg+=data.message;
	// 			bootbox.alert(display_msg);
				
				
	// 		}
	// 		else{
	// 			alert("Error en ajax");
	// 		}
	// 	}, "json")
	// 	.fail(function(XMLHttpRequest, textStatus, errorThrown) {
	// 		hideLoader();
	// 		console.log("error: "+errorThrown);
	// 		alert( "error\n"+textStatus+"\n"+errorThrown );
	// 	});
	// });

	//backups locales y externos
	$("#btn_backups_disponibles").on("click",function(){
		var nombre_cuenta = getWhmcsUsername();
		var marca = getMarcaSeleccionada();
		var ping_servidor = $('#contenido_cliente').find('#ping_servicio').html();
		var ip_whmcs_cliente = $('#contenido_cliente').find('#ip_servidor').html();
		showLoader();
		$.post( rutaAjaxOperaciones+"/operaciones/getlocalbackups", { account: nombre_cuenta, ping_server: ping_servidor , whmcs_server: ip_whmcs_cliente }, function( data ) {
			hideLoader();
			console.log("Info de backups (locales y externos)");
			console.log(data);
			if(data.success){
				//informacion de los backups internos
				var display_msg = "<b>Backups locales disponibles:</b><br><br>";
				$.each(data.local_backups, function(i, item) {
				    display_msg += '<button type="button" class="btn btn-success btn-backup" data-toggle="tooltip" data-placement="bottom" title="'+item.ruta+'">'+item.fecha+'</button>';
				});
				//informacion de los backups externos
				display_msg += "<hr><b>Backups externos disponibles:</b><br><br>Debe consultar a personal de operaciones para ver los backups disponibles de R1Soft";

				// if(data.external_backup_info.success){
				// 	display_msg += "<hr><b>Backups externos cpRemote disponibles para "+dominio+":</b><br><br>";
				// 	$.each(data.external_backup_info.external_backups, function(i, item) {
				// 	    display_msg += '<button class="btn btn-success btn-backup" data-toggle="tooltip" data-placement="bottom" title="'+item.ruta+' (Servidor '+item.servidor+')">'+item.fecha+'</button>';
				// 	});
				// }
				// else{
				// 	display_msg += "<b>No hay backups externos cpRemote disponibles para "+dominio+"</b><br>Motivo: "+data.external_backup_info.message;
				// }

				bootbox.alert(display_msg);
				$('[data-toggle="tooltip"]').tooltip();
			}
			else{
				bootbox.alert("<b>Error al obtener los backups locales de la cuenta</b><br>Motivo: "+data.message);
			}
		}, "json")
		.fail(function(XMLHttpRequest, textStatus, errorThrown) {
			hideLoader();
			console.log("error: "+errorThrown);
			alert( "error\n"+textStatus+"\n"+errorThrown );
		});
	});

	//correo desde cuenta de cliente
	$("#enviar_correo_desde_cliente").on("click",function(){

		bootbox.dialog({
                title: "Prueba de correo saliente",
                message: '<div class="row">  ' +
                    '<div class="col-md-12"> ' +
	                    '<form class="form-horizontal"> ' +
		                    '<div class="form-group"> ' +
			                    '<label class="col-md-2 control-label" for="correo_de">De:</label> ' +
			                    '<div class="col-md-8"> ' +
			                    	'<input value="cuentadelcliente@'+getDominioSeleccionado()+'" id="correo_de" name="correo_de" type="text" placeholder="Cuenta de correo saliente" class="form-control input-md"> ' +
			                    '</div> ' +
		                    '</div> ' +
		                    '<div class="form-group"> ' +
			                    '<label class="col-md-2 control-label" for="correo_de_pass">Contraseña:</label> ' +
			                    '<div class="col-md-8"> ' +
			                    	'<input value="" id="correo_de_pass" name="correo_de_pass" type="text" placeholder="" class="form-control input-md"> ' +
			                    '</div> ' +
		                    '</div> ' +
		                    '<div class="form-group"> ' +
			                    '<label class="col-md-2 control-label" for="puerto">Puerto:</label> ' +
			                    '<div class="col-md-8"> ' +
			                    	'<input value="587" id="puerto" name="puerto" type="text" placeholder="587" class="form-control input-md"> ' +
			                    '</div> ' +
		                    '</div> ' +
		                    '<div class="form-group"> ' +
			                    '<label class="col-md-2 control-label" for="seguridad">Seguridad:</label> ' +
			                    '<div class="col-md-8"> ' +
			                    	'<select id="seguridad" name="seguridad" class="form-control input-md"> ' +
			                    		'<option value="tls">tls</option>' +
			                    		'<option value="ssl">ssl</option>' +
			                    		'<option value="ninguna">ninguna</option>' +
			                    	'</select> ' +
			                    '</div> ' +
		                    '</div> ' +
		                    '<div class="form-group"> ' +
			                    '<label class="col-md-2 control-label" for="correo_de">Para:</label> ' +
			                    '<div class="col-md-8"> ' +
			                    	'<input value="tucuenta@hosting.cl" id="correo_para" name="correo_para" type="text" placeholder="Cuenta de correo destino" class="form-control input-md"> ' +
			                    '</div> ' +
		                    '</div> ' +
	                    '</form> '+
	               	'</div>  </div>',
                buttons: {
                    success: {
                        label: "Enviar correo",
                        className: "btn-success",
                        callback: function () {
                            var correo_de = $('#correo_de').val();
                            var correo_para = $('#correo_para').val();
                            var pass = $('#correo_de_pass').val();
                            var dominio = getDominioSeleccionado();
                            var puerto = $("#puerto").val();
                            var seguridad = $("#seguridad").val();

							showLoader();
							$.post(rutaAjaxOperaciones+"/operaciones/sendmailfromclientaccount", { from: correo_de, to: correo_para , pass: pass, dominio: dominio, puerto: puerto, seguridad: seguridad }, function( data ) {
								hideLoader();
								console.log("Correo desde cuenta de cliente enviado");
								console.log(data);
								if(data.success){
									bootbox.alert("<b>Exito!</b><br>"+data.message);
								}
								else{
									bootbox.alert("<b>Error!</b><br>"+data.message);
								}
							}, "json")
							.fail(function(XMLHttpRequest, textStatus, errorThrown) {
								hideLoader();
								console.log("error: "+errorThrown);
								alert( "Error en ajax\n"+textStatus+"\n"+errorThrown );
							});
                        }
                    }
                }
            }
        );
	});

	$("#enviar_correo_hacia_cliente").on("click",function(){
		bootbox.dialog({
            title: "Prueba de correo entrante",
            message: '<div class="row">  ' +
                '<div class="col-md-12"> ' +
                    '<form class="form-horizontal"> ' +
	                    '<div class="form-group"> ' +
		                    '<label class="col-md-2 control-label" for="correo_para">Para:</label> ' +
		                    '<div class="col-md-8"> ' +
		                    	'<input value="cuentadelcliente@'+getDominioSeleccionado()+'" id="correo_para" name="correo_para" type="text" placeholder="Cuenta de correo saliente" class="form-control input-md"> ' +
		                    '</div> ' +
	                    '</div>' +    
                    '</form> '+
               	'</div>  </div>',
            buttons: {
                success: {
                    label: "Enviar correo",
                    className: "btn-success",
                    callback: function () {
                        var correo_para = $('#correo_para').val();
						showLoader();
						$.post( rutaAjaxOperaciones+"/operaciones/sendmailtoclientaccount", { email_client: correo_para }, function( data ) {
							hideLoader();
							if(data.success){
								bootbox.alert("<b>Exito!</b><br>"+data.message);
							}
							else{
								bootbox.alert("<b>Error!</b><br>Motivo: "+data.message);
							}
						}, "json")
						.fail(function(XMLHttpRequest, textStatus, errorThrown) {
							hideLoader();
							console.log("error: "+errorThrown);
							alert( "Error en ajax\n"+textStatus+"\n"+errorThrown );
						});
                    }
                }
            }
        });
	});

	$("#btn_estado_servidor").on("click",function(){
		var ping_servidor = $('#contenido_cliente').find('#ping_servicio').html();
		var ip_whmcs_cliente = $('#contenido_cliente').find('#ip_servidor').html();
		showLoader();
		$.post( rutaAjaxOperaciones+"/operaciones/getserverstatus", { ping_server: ping_servidor , whmcs_server: ip_whmcs_cliente }, function( data ) {
			hideLoader();
			if(data.success){
				//info de los servicios
				var message="<div id='mensaje_estado_servidor'><h4>Servicios:</h4><ul>";
				if(data.services_info.success){
					$.each(data.services_info.services,function(i,service){
						var status_glyphicon = service.status == "0" ? "glyphicon-ok" : "glyphicon glyphicon-remove";
						var class_glyphicon = service.status == "0" ? "ok_glyphicon" : "error_glyphicon";
						message += "<li><span class='glyphicon "+status_glyphicon+" "+class_glyphicon+"'></span> "+service.name+"</li>";
					});
				}else{
					message += "<li>Error al obtener los servicios</li>";
				}

				//info de la carga
				message += "</ul><hr><h4>Carga del servidor ("+data.uptime_info.procs+" núcleos):</h4><ul>";
				if(data.uptime_info.success){
					var uptime_titles = ["Último minuto", "Últimos 5 minutos", "Últimos 15 minutos"];
					$.each(data.uptime_info.uptime.split("-"),function(i,uptime){
						var load_glyphicon = uptime >= data.uptime_info.procs * 1.5 ? "<span class='glyphicon glyphicon-warning-sign error_glyphicon' title='Carga muy alta. Revisar con personal de operaciones'></span>" : "";
						message += "<li>"+load_glyphicon+"  "+uptime_titles[i]+": <b>"+uptime+"</b></li>";
					});
				}else{
					message += "<li>Error al obtener el uptime</li>";
				}
				message += "</ul></div>";
				bootbox.alert(message);
				console.log("Estado del servidor");
				console.log(data);
			}
			else{
				bootbox.alert("<b>Error!</b><br>Motivo: "+data.message);
			}
		}, "json")
		.fail(function(XMLHttpRequest, textStatus, errorThrown) {
			hideLoader();
			console.log("error: "+errorThrown);
			alert( "Error en ajax\n"+textStatus+"\n"+errorThrown );
		});
	});

	//este boton borra el archivo del usuario en /var/cpanel/tempquota
	$("#btn_arreglar_cuota_temporal").on("click",function(){

		//tomo en cuenta lo que diga el ping de mail (para el caso de los windows que tienen los correos en linux)
		var ping_servidor = $('#contenido_cliente').find('#ping_mail_servicio').html();
		var ip_whmcs_cliente = $('#contenido_cliente').find('#ip_servidor').html();

		showLoader();
		$.post( rutaAjaxOperaciones+"/operaciones/fixtemporalquota", { ping_server: ping_servidor , whmcs_server: ip_whmcs_cliente, domain: getDominioSeleccionado() }, function( data ) {
			hideLoader();
			if(data.success){
				bootbox.alert("<b>Exito!</b><br>"+data.message);
			}
			else{
				bootbox.alert("<b>Error!</b><br>"+data.message);
			}
		}, "json")
		.fail(function(XMLHttpRequest, textStatus, errorThrown) {
			hideLoader();
			console.log("error: "+errorThrown);
			alert( "Error en ajax\n"+textStatus+"\n"+errorThrown );
		});
	});

	//este boton ve las cuentas de correo que estan bloqueadas en el servidor
	$("#btn_ver_cuentas_bloqueadas").on("click",function(){

		//tomo en cuenta lo que diga el ping de mail (para el caso de los windows que tienen los correos en linux)
		var ping_servidor = $('#contenido_cliente').find('#ping_mail_servicio').html();

		showLoader();
		$.post( rutaAjaxOperaciones+"/outlook_warning/spammers", { server_ip: ping_servidor }, function( data ) {
			hideLoader();
			if(data.success){
				showSpammers(data.spammers);
			}
			else{
				bootbox.alert("<b>Error!</b><br>"+data.message);
			}
		}, "json")
		.fail(function(XMLHttpRequest, textStatus, errorThrown) {
			hideLoader();
			console.log("error: "+errorThrown);
			alert( "Error en ajax\n"+textStatus+"\n"+errorThrown );
		});
	});

	//este boton ve las cuentas de correo que estan bloqueadas en el servidor
	$(document).on("click", ".btn_desbloquear_cuenta", function(){
		var $this = $(this);
  		$this.button('loading');
		//tomo en cuenta lo que diga el ping de mail (para el caso de los windows que tienen los correos en linux)
		var ping_servidor = $('#contenido_cliente').find('#ping_mail_servicio').html();
		var cuenta = $(this).data("email");
		$.post( rutaAjaxOperaciones+"/outlook_warning/spammer/delete", { server_ip: ping_servidor, account: cuenta }, function( data ) {
			$this.button('reset');
			if(data.success){
				showSpammers(data.spammers);
			}
			else{
				bootbox.alert("<b>Error!</b><br>"+data.message);
			}
		}, "json")
		.fail(function(XMLHttpRequest, textStatus, errorThrown) {
			$this.button('reset');
			console.log("error: "+errorThrown);
			alert( "Error en ajax\n"+textStatus+"\n"+errorThrown );
		});
	});
});
