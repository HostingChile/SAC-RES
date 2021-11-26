<?php
	$upOne = realpath(__DIR__ . '/..');
	require $upOne."/dashboard_header.php";

	require __DIR__.'/DB/SACManager.class.php';
	$SACManager = SACManager::singleton();

	$yubikey_username = utf8_decode($_SESSION["user_name"]);
?>

<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

<!-- Cargo js y css locales-->
<link rel="stylesheet" type="text/css" href="include/css/index.css">
<link rel="stylesheet" type="text/css" href="include/css/attention_history.css">
<link rel="stylesheet" type="text/css" href="include/css/drilldown.css">
<link rel="stylesheet" type="text/css" href="include/css/trello_card.css">

<script type="text/javascript" src="include/js/index.js?v=2.3" charset="UTF-8"></script>

<?php

$departamento = $_SESSION["Departamento"];
if(isset($_SESSION['Departamento']) && (("Gerencia" == $departamento) || ("Jefe de Area" == $departamento) || ("Desarrollo" == $departamento))){
	?>
		<script type="text/javascript" src="include/js/distintivos.js" charset="UTF-8"></script>
	<?php
}
?>

<script type="text/javascript" src="include/js/attention_history.js"></script>
<script type="text/javascript" src="include/js/drilldown.js"></script>
<script type="text/javascript" src="include/js/bootbox.min.js"></script>


<div class="container-fluid">

<div class="row" style="margin-top: 20px;">
	<div id="loading-cliente">
	 	<img src="include/img/loader-gray.gif" alt="Loading..." />
	</div>

	<div id="area_tipificacion"  class="col-lg-4">
		<div id="alertas_tipificacion" class="alert"></div>
		<form class="form-horizontal">
			<input type="hidden" id="logged_user" value="<?=$yubikey_username?>">
			<input type="hidden" id="pending_call_id" value="">
			<div class="form-group">
				<label for="inputMedio" class="col-sm-2 control-label">Medio</label>
				<div class="col-sm-10">
					<div id="select_contact_type" class="custom_select">
					<?php
						$query = "SELECT * FROM TipoContacto";
						$result = $SACManager->execQuery($query);
						foreach ($result as $tipocontacto) {
							$aditionalClass = $tipocontacto["id"] == 3 ? "client_call_option" : "";
							echo "<img class='custom_select_option $aditionalClass' 
										data-id='{$tipocontacto["id"]}' 
										src='include/img/medios/{$tipocontacto["img"]}'
										data-nombre='{$tipocontacto["nombre"]}'>";
						}
					?>
					</div>
				</div>
			</div>

			<div class="form-group">
				<label for="inputNombreCliente" class="col-sm-2 control-label">Cliente</label>
				<div class="col-sm-6">
					<input type="text" class="form-control" id="inputNombreCliente" placeholder="Ingrese nombre del cliente">
				</div>
			</div>
			
			<div class="form-group">
				<label for="inputEsCliente" class="col-sm-2 control-label"></label>
				<div class="col-sm-6">
					<input type="checkbox" id="inputNoEsCliente"> No es cliente
				</div>
			</div>

			<div class="form-group">
				<label for="inputMarca" class="col-sm-2 control-label">Marca</label>
				<div class="col-sm-9">
					<div id="select_brand" class="custom_select">
					<?php
						$query = "SELECT * FROM Compania ORDER BY `order` ASC";
						$result = $SACManager->execQuery($query);
						// $class = "selected_option";
						$class = "";
						foreach ($result as $compañia) {
							echo "<img class='custom_select_option $class' 
										data-id='{$compañia["id"]}' 
										src='include/img/marcas/{$compañia["image"]}'
										data-nombre='{$compañia["nombre"]}'>";
							$class = "";
						}
					?>
					</div>
				</div>
			</div>

			<div class="form-group" id="div_dominio">
				<label for="inputDominio" class="col-sm-2 control-label">Dominio</label>
				<div class="col-sm-6" style="postion:relative">
					<input type="text" class="form-control" id="inputDominio" placeholder="Ingrese dominio del cliente">
					<div id="select_multiples_resultados"></div>
				</div>
				<div class="col-sm-4">
					<button type='button' class="btn btn-default" id="btn_buscar_dominio">Buscar Info</button>
				</div>
			</div>

			<div id="detalle_tipificacion">
				<hr>

				<div class="form-group">
					<label for="inputCategoria" class="col-sm-2 control-label">Tipo de Contacto</label>
					<div class="col-sm-9">
						<div id="inputCategoria">
							<div id="drill-down"></div>
						</div>
						<div id="selection">
							<span class="title">Selecci&oacute;n actual:</span>
							<span class="data">Ninguna</span>
						</div>

					</div>
				</div>

				<div class="form-group">
					<label for="inputMedio" class="col-sm-2 control-label">Problema Solucionado</label>
					<div class="col-sm-10">
						<div id="select_problem_solved" class="custom_select">
							<img class='custom_select_option' data-value='si' src='include/img/problema_solucionado/yes.png'>
							<img class='custom_select_option' data-value='no' src='include/img/problema_solucionado/no.png'>
						</div>
					</div>
				</div>
				
				<div class="form-group">
					<label for="inputMedio" class="col-sm-2 control-label">¿Intervino Operaciones?</label>
					<div class="col-sm-10">

						<div id="select_intervino_operaciones" class="custom_select">
							<img class='custom_select_option' data-value='si' src='include/img/problema_solucionado/yes.png'>
							<img class='custom_select_option' data-value='no' src='include/img/problema_solucionado/no.png'>
						</div>
					</div>
				</div>

				<div class="form-group">
					<label for="inputDetalle" class="col-sm-2 control-label">Detalle</label>
					<div class="col-sm-9">
						<textarea class="form-control" rows="5" id="inputDetalle" placeholder="Ingrese detalle de la atenci&oacute;n..."></textarea>
					</div>
				</div>
				<div class="form-group">
					<div class="col-sm-offset-2 col-sm-9">
						<button class="btn btn-primary" id="btn_tipificar">Tipificar <span id="btn_tipificar_span"></span></button>
					</div>
				</div>

			</div>
		</form>
	</div>
	<div id="area_informacion_cliente"  class="col-lg-8">

		<div id="contenido_cliente" class="container-fluid">
			<span id="info_cliente">
				<span id="dominio_cliente"></span>
				<span id="marca_cliente"></span>
				<span id="username_cliente"></span>
				<span id="id_cliente_whmcs"></span>
				<span id="id_servicio_whmcs"></span>
				<span id="fecha_registro_servicio"></span>
			</span>
			<div class="row">
				<div id="alertas" class="col-lg-12"></div>
			</div>
			<div class="row">
				<div id="distintivos_clientes" class="col-lg-2">
					<div class="client_detail_title">Distintivos</div>
					<hr>
					<div id="categoria_cliente" class="distintivo" data-id="1">
						<div class="star_rating">
						<?php
							$query = "SELECT * FROM OpcionDistintivo WHERE id_distintivo = '1'";
							$result = $SACManager->execQuery($query);
							foreach ($result as $opcion_distintivo) {
								echo '<span class="glyphicon glyphicon-star categoria_cliente selected_category" aria-hidden="true" data-toggle="tooltip" title="'.$opcion_distintivo["nombre"].'" data-id="'.$opcion_distintivo["id"].'" ></span>';
							}
						?>
						</div>
						<div class="selected_star_msg">Premium</div>
					</div>
					<hr>
					<div id="conflictivo_cliente" class="distintivo distintivo_binario" title="Cliente conflictivo" data-name="Cliente conflictivo" data-id="2"><img class="not_selected" src="include/img/distintivos/conflictivo.png"></div>
					<hr>
					<div id="antiguedad_cliente" class="distintivo distintivo_binario" title="Cliente antiguo" data-name="Cliente antiguo" data-id="3"><img src="include/img/distintivos/antiguedad.png"></div>
				</div>

				<div class="col-lg-8">
					<div class="client_detail_title">Informaci&oacute;n General</div>
					<table class="table table-bordered table-hover table-condensed" id="tab">
						
						<tr class="info"><th colspan="4" class="header_informacion_general">Servicio</th></tr>
							<tr><th>Plan</th><td colspan="3" id="plan_servicio">Basico</td></tr>
							<tr><th>Status</th><td id="status_servicio" colspan="3">Activo</td></tr>
							
							<tr><th rowspan="5">Servidor Alojado</th>
								<td rowspan="5" id="servidor_alojado">
									<span id="ip_servidor">201.148.104.20</span>
									<div>
										<a id="link_whm" href="https://192.168.111.10/cgi-bin/1044.pl" target="_blank">
											<img border="0" alt="Servidor Alojado" src="include/img/logos/whm.png" width="40" height="15">
										</a>
										<a id="link_whmcs" href="" target="_blank">
											<img border="0" alt="Servidor Alojado" src="include/img/logos/whmcs.png" width="60" height="15">
										</a>
									</div>
									
									<div>
										<a id="link_intodns" href="" target="_blank">
											<img border="0" alt="" src="include/img/logos/intodns.png" width="60" height="15">
										</a>
										<a id="link_nic" href="" target="_blank">
											<img id="logo_domain_lookup_service" border="0" alt="" src="include/img/logos/nic.png" width="60" height="15">
										</a>
									</div>

								</td>
								<tr class="danger"><th>Ping</th><td id="ping_servicio">190.96.85.3</td></tr>
								<tr><th>www</th><td id="ping_www_servicio">201.148.104.21</td></tr>
								<tr><th>Mail</th><td id="ping_mail_servicio">201.148.104.20</td></tr>
								<tr><th>MX</th><td id="mx_servicio"></td></tr>
							</tr>
							<tr><th>Monto Pago</th><td id="monto_plan_servicio" colspan="3">$59.990 + IVA anual</td></tr>
							<tr><th>Fecha Vencimiento</th><td id="fecha_vencimiento_servicio" colspan="3">30/05/2015</td></tr>
						
						<tr class="info"><th colspan="4" class="header_informacion_general">Cliente</th></tr>
						
							<tr><th>Contacto Administrativo</th><td id="contacto_administrativo_cliente" colspan="3"></td></tr>
							<tr><th>Contactos T&eacute;cnicos</th><td id="contactos_tecnicos_cliente" colspan="3"></td></tr>
							<tr><th>Cliente desde</th><td id="fecha_registro_cliente" colspan="3">30/05/2015</td></tr>
							<tr><th>Servicios Activos</th><td id="servicios_activos_cliente" colspan="3">6</td></tr>
							<tr><th>Monto total</th><td id="monto_total_cliente" colspan="3">$ 199.900 + IVA Mensual</td></tr>
					</table>
				</div>
				<div class="col-lg-2">
					<div class="client_detail_title">Correo</div>
					<div class="btn-group-vertical " role="group">
					  	<button class="btn btn-default" id="enviar_correo_desde_cliente">Prueba Saliente</button>
					  	<button class="btn btn-default" id="enviar_correo_hacia_cliente">Prueba Entrante</button>
					  	<button class="btn btn-default" id="btn_ver_cuentas_bloqueadas">Ver Cuentas Bloqueadas</button>
					</div>
					<hr>
					<div class="client_detail_title">Extra</div>
					<div class="btn-group-vertical " role="group">
						<button class="btn btn-default opcion_compartidos" id="btn_backups_disponibles">Backups disponibles</button>
						<button class="btn btn-default opcion_compartidos" id="btn_estado_servidor">Estado Servidor</button>
						<button class="btn btn-default opcion_compartidos" id="btn_arreglar_cuota_temporal">Arreglar Cuota Temporal</button>
						
					</div>
				</div>
			</div>
			<div id="avisos_operaciones" style="display:none">
				<div class="avisos_operaciones_title">Avisos de operaciones</div>
			</div>
			<div class="row">
				<div id="historial_atenciones" class="col-lg-12">
					<div class="client_detail_title">Historial de Atenciones</div>
					<div class="atencion">
						<img class="avatar" src="include/img/avatarDefault.png">
					    <div class="message recent_message">
					    	<p class="client_detail">Juan Carrasco (Bloqueo de IP)</p>
					      	<p class="message_content">Se desbloquea la IP</p>
					      	<p class="message_info">Gerardo Mu&ntilde;oz - 25/04/2015 11:49</p>
					    </div>
					    <div class="end_message"></div>
					</div>
				</div>
			</div>

			<div class="modal fade" tabindex="-1" role="dialog" id="blocked_accounts_modal">
				<div class="modal-dialog modal-lg" role="document">
					<div class="modal-content">
					  	<div class="modal-header">
						    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
						    <h4 class="modal-title">Cuentas Bloqueadas</h4>
					  	</div>
					  	<div class="modal-body" id="blocked_accounts_content">
					    	<table>
					    		<tr>
					    			<th>Col 1</th>
					    			<th>Col 2</th>
					    		</tr>
					    		<tr>
					    			<td>Val 1</td>
					    			<td>Val 2</td>
					    		</tr>
					    	</table>
					 	</div>
					</div><!-- /.modal-content -->
				</div><!-- /.modal-dialog -->
			</div><!-- /.modal -->

		</div> 
	</div>
</div>

<?php
require "../dashboard_footer.html";
?>
