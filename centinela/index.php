<?php 
$upOne = realpath(__DIR__ . '/..');
require $upOne."/dashboard_header.php";
?>

<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

	<!-- para manejo de fechas -->
	<script type="text/javascript" src="//cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>

	<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.1.1/jquery.js"></script>
	<script src="http://ajax.googleapis.com/ajax/libs/angularjs/1.4.8/angular.min.js"></script>
	<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>

	<!-- daterange picker -->
	<script type="text/javascript" src="//cdn.jsdelivr.net/bootstrap.daterangepicker/2/daterangepicker.js"></script>
	<link rel="stylesheet" type="text/css" href="//cdn.jsdelivr.net/bootstrap.daterangepicker/2/daterangepicker.css" />

	<style type="text/css">
		body{
			-webkit-touch-callout: none;
			-webkit-user-select: none;
			-khtml-user-select: none;
			-moz-user-select: none;
			-ms-user-select: none;
			user-select: none;
		}
		#content_div{
			margin: 10px;
		}
		table {
		    table-layout: fixed;
		}
		input{
			margin-right: 10px;
		}
		#config_menu{
			margin-left: 10px;
			margin-top: 10px;
			margin-bottom: 10px;
		}
		.notransition {
			-webkit-transition: none !important;
			-moz-transition: none !important;
			-o-transition: none !important;
			-ms-transition: none !important;
			transition: none !important;
		}
		#daterange{
			height: 34px;
			padding: 6px 12px;
			font-size: 14px;
			line-height: 1.42857143;
			color: #555;
			background-color: #fff;
			border: 1px solid #ccc;
			border-radius: 4px;
		}
		#daterange_div{
			margin-top: 7px;
		}
		#area_opciones{
			padding: 10px;
			width: 300px;
			
		}
		.chart{
			float: left;
			width: 50%;
			min-width: 600px;
			height: 500;
		}
		.server_downtime_detail{
			cursor: pointer;
		}
	</style>

</head>

<body>

	<div ng-app="CentinelaApp" ng-controller="CentinelaCtrl as centinela">
		
		<nav class="navbar navbar-default">
		  <div class="container-fluid">

		    <!-- Collect the nav links, forms, and other content for toggling -->
		    <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
		      <div id="daterange_div" class="nav navbar-nav">
		       	<input type="text" id="daterange" ng-model="centinela.range" value="29/09/2016 - 30/09/2016"></input>
		       	<button class="btn btn-primary" ng-click="centinela.getUptimeInfo()" ng-disabled="centinela.loading">Obtener datos centinela</button>
		      </div>

		      <ul class="nav navbar-nav navbar-right">
		      	<li class="dropdown">
		          <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"><span class="glyphicon glyphicon-list" aria-hidden="true"></span> <span class="caret"></span></a>
		          <div class="dropdown-menu" id="area_opciones">
		          	<form class="form-horizontal">
					  <div class="form-group">
					    <label for="inputEmail3" class="col-sm-8 control-label">Servidores por pagina</label>
					    <div class="col-sm-4">
					      <input class="form-control" ng-model="centinela.page_size" type="number" min="1" max="{{centinela.servers.length}}"></input>
					    </div>
					  </div>
					  <div class="form-group">
					    <label for="inputEmail3" class="col-sm-8 control-label">Decimales Uptime</label>
					    <div class="col-sm-4">
					      <input class="form-control" ng-model="centinela.decimals" type="number" min="1" max="4"></input>
					    </div>
					  </div>
					  <div class="form-group">
					    <label for="inputPassword3" class="col-sm-8 control-label">Error</label>
					    <div class="col-sm-4">
					    	<input class="form-control" ng-model="centinela.error_limit"></input>
					    </div>
					  </div>
					  <div class="form-group">
					    <label for="inputPassword3" class="col-sm-8 control-label">Alerta</label>
					    <div class="col-sm-4">
					    	<input class="form-control" ng-model="centinela.warning_limit"></input>
					    </div>
					  </div>
					  <div class="form-group">
					    <label for="inputPassword3" class="col-sm-8 control-label">OK</label>
					    <div class="col-sm-4">
					    	<input class="form-control" ng-model="centinela.ok_limit"></input>
					    </div>
					  </div>
					  <div class="form-group">
					    <div class="col-sm-12">
					    	<input type="checkbox" ng-checked="centinela.reload_periodically"> Actualizar cada {{centinela.reload_period}} minutos
					    </div>
					  </div>
					</form>			    
		          </div>
		        </li>
		      </ul>
		    </div><!-- /.navbar-collapse -->
		  </div><!-- /.container-fluid -->
		</nav>

		<centinela-progress-bar ng-show="centinela.loading"></centinela-progress-bar>

		<div id="content_div" ng-hide="centinela.loading">
			<div class="alert alert-danger" role="alert" ng-show="centinela.not_found_servers.length">
				<label>Servidores no encontrados en centinela: </label>
				<ul ng-repeat="server in centinela.not_found_servers">
					<li>{{server}}</li>
				</ul>
			</div>

			<div class="alert alert-warning" role="alert" ng-show="centinela.api_error_servers.length">
				<label>Servidores con problemas con la API: </label>
				<ul ng-repeat="server in centinela.api_error_servers">
						<li>{{server}}</li>
				</ul>
			</div>

			

			<div class="alert alert-info" role="alert" ng-show="centinela.no_measurement_servers.length">
				<label>Servidores sin medicion para el periodo: </label>
				<ul ng-repeat="server in centinela.no_measurement_servers">
						<li>{{server}}</li>
				</ul>
			</div>

			<!--TABS-->
			<div ng-show="centinela.servers.length">

				<!-- Nav tabs -->
				<ul class="nav nav-tabs" role="tablist">
					<li role="presentation" class="active"><a href="#rawdata" aria-controls="home" role="tab" data-toggle="tab">Datos</a></li>
					<li role="presentation"><a href="#charts" aria-controls="profile" role="tab" data-toggle="tab">Gráficos</a></li>
				</ul>

				<!-- Tab panes -->
				<div class="tab-content">
					<div role="tabpanel" class="tab-pane active" id="rawdata">
						<table class="table table-striped table-hover" ng-show="centinela.servers.length">
							<thead>
								<tr>
									<th>Servidor</th>
									<th>
										<span class="sortable_header" ng-click="centinela.sortType = 'uptime_promedio'; centinela.sortReverse = !centinela.sortReverse">
					        				Uptime Promedio
									        <span ng-show="centinela.sortType == 'uptime_promedio'" ng-class="centinela.sortReverse ? 'fa-caret-down' : 'fa-caret-up'" class="fa"></span>
					      				</span>
									</th>
									<th>
										<span class="sortable_header" ng-click="centinela.sortType = 'tiempo_promedio_por_caida'; centinela.sortReverse = !centinela.sortReverse">
					        				Tiempo Promedio por Caida (minutos)
									        <span ng-show="centinela.sortType == 'tiempo_promedio_por_caida'" ng-class="centinela.sortReverse ? 'fa-caret-down' : 'fa-caret-up'" class="fa"></span>
					      				</span>
									</th>
									<th>Medio</th><th>Uptime</th><th>Downtime</th><th>Sin Medicion</th><th>Con Error</th><th>Numero caidas</th><th>Tiempo caido (minutos)</th></tr>
							</thead>
							<tbody ng-repeat="server in centinela.servers | orderBy:centinela.sortType:centinela.sortReverse | startFrom:(centinela.current_page-1)*centinela.page_size | limitTo:centinela.page_size ">
								<tr ng-repeat="(service_name, service_data) in {'pop3': server.pop3, 'cpanel': server.cpanel}"  ng-class="centinela.getUptimeClass(server.uptime_promedio)">
									<td rowspan="2" ng-hide="$index>0">{{server.ip}}</td>
									<td rowspan="2" ng-hide="$index>0">{{server.uptime_promedio | percentage:centinela.decimals}}</td>
									<td rowspan="2" ng-hide="$index>0">{{server.tiempo_promedio_por_caida | secToMin}}</td>
									<td>{{service_name}}</td>
									<td>{{service_data.uptime | percentage:centinela.decimals}}</td>
									<td>{{service_data.downtime | percentage:centinela.decimals}}</td>
									<td>{{service_data.sinMedicion | percentage:centinela.decimals}}</td>
									<td>{{service_data.conError | percentage:centinela.decimals}}</td>
									<td>{{service_data.numero_caidas}}</td>
									<td>{{service_data.tiempo_caido | secToMin}}</td>

									<td>
										 <span class="glyphicon glyphicon-info-sign server_downtime_detail" title="Ver detalle de caídas" aria-hidden="true" 
										 	ng-if="service_data.numero_caidas"
										 	ng-click="data=centinela.modals.server_detail.data; 
													data.servidor=server.ip; 
													data.caidas=service_data.detallecaidas; 
													data.servicio=service_name;
													centinela.openModal('server_detail');">										
										</span>
									</td>
								</tr>
							</tbody>
						</table>
						<center>
							<nav aria-label="Page navigation">
								<ul class="pagination">
									<li>
										<a href="#" aria-label="Previous" ng-disabled="current_page == 1" ng-click="centinela.setPage(current_page-1)">
											<span aria-hidden="true">&laquo;</span>
										</a>
									</li>
									<li ng-repeat="i in centinela.getTimes(centinela.servers.length/centinela.page_size) track by $index"><a href="#" ng-click="centinela.setPage($index+1)">{{$index+1}}</a></li>
									<li>
										<a href="#" aria-label="Next" ng-disabled="centinela.current_page >= centinela.servers.length/page_size" ng-click="centinela.setPage(centinela.current_page+1)">
											<span aria-hidden="true">&raquo;</span>
										</a>
									</li>
								</ul>
							</nav>
						</center>
					</div>
					<div role="tabpanel" class="tab-pane" id="charts">
						<div class="summary_chart">
							<div id="cpanel_chart" class="chart" ></div>
					 	</div>
					 	<div class="summary_chart">
					 		<div id="pop3_chart" class="chart"></div>
						</div>
					</div>
				</div>

			</div>
		</div>

		<!-- MODALS -->

		<!-- Modal de detalle burbuja del grafico -->
		<div id="chart_detail" class="modal fade" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
		  <div class="modal-dialog" role="document">
		    <div class="modal-content">
		      <div class="modal-header">
		        <button type="button" class="close" ng-click="centinela.closeModal('chart_detail')" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		        <h4 class="modal-title">Caídas día {{centinela.modals.chart_detail.data.day}}</h4>
		        <h5 class="modal-title">Hubo {{centinela.modals.chart_detail.data.segundos_downtime/60 | number : 0 }} minutos de caída entre las {{centinela.modals.chart_detail.data.hour}}:00:00 y las {{centinela.modals.chart_detail.data.hour}}:59:59</h5>
		      </div>
		      <div class="modal-body">
		        <table class='table'><tr><th>Servidor</th><th>Minutos Downtime</th><th>Fecha</th><th></th></tr>
			        <tr ng-repeat="caida in centinela.modals.chart_detail.data.downtime_detail | orderBy: 'inicio'">
			        	<td>{{caida.ip}}</td><td>{{(caida.segundos_downtime/60) | number: 0}}</td><td>{{caida.inicio | date:'dd-MM-yyyy'}}</td><td><span class="glyphicon glyphicon-info-sign" ng-attr-title="Caído desde {{caida.inicio | date:'HH:mm:ss'}} hasta {{caida.fin | date:'HH:mm:ss'}}"></span></td>
		        	</tr>
		        </table>
		      </div>
		    </div><!-- /.modal-content -->
		  </div><!-- /.modal-dialog -->
		</div><!-- /.modal -->

		<!-- Modal de detalle caidas de un servidor -->
		<div id="server_detail" class="modal fade" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
		  <div class="modal-dialog" role="document">
		    <div class="modal-content">
		      <div class="modal-header">
		        <button type="button" class="close" ng-click="centinela.closeModal('server_detail')" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		        <h4 class="modal-title">Caídas de {{centinela.modals.server_detail.data.servicio}} en servidor {{centinela.modals.server_detail.data.servidor}}</h4>
		      </div>
		      <div class="modal-body">
		        <table class='table'><tr><th>Fecha</th><th>Minutos Downtime</th><th>Inicio</th><th>Fin</th><th>Día</th></tr>
			        <tr ng-repeat="caida in centinela.modals.server_detail.data.caidas">
			        	<td>{{caida.inicio | date:'dd-MM-yyyy'}}</td>
			        	<td>{{ (caida.segundos/60) | number:0 }}</td>
			        	<td>{{caida.inicio | date:'HH:mm:ss'}}</td>
			        	<td>{{caida.fin | date:'HH:mm:ss'}}</td>
			        	<td>{{caida.inicio | date:'EEEE' | spanish_dayname}}</td>
			        </tr>
		        </table>
		      </div>
		    </div><!-- /.modal-content -->
		  </div><!-- /.modal-dialog -->
		</div><!-- /.modal -->

	</div><!-- fin CentinelaCtrl-->

	<script src="index.js"></script>

</body>
</html>

<?php require $upOne."/dashboard_footer.html";?>