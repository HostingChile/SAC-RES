<?php
	$upOne = realpath(__DIR__ . '/..');
	require $upOne."/dashboard_header.php";

	$yubikey_username = utf8_decode($_SESSION["user_name"]);
?>

<style>
	.info_label{
		padding: 5px;
	}
</style>

<div ng-app="ShiftsApp" ng-controller="ShiftController as ctrl">
	<div>
		<div class="alert alert-danger">
	  		<strong>Si se presenta una emergencia, comunicarse solo:</strong> 
	  		<ul>
	  			<li>Si estuviera un servidor alertando por mas de 15 minutos (8 alertas consecutivas en centinela), tanto por pop y cpanel</li>
	  			<li>Si estuviera un servidor alertando por mas de 25 minutos (12 alertas consecutivas en centinela) sólo de pop (vtr y gtd) o solo cpanel (vtr y gtd).</li>
	  		</ul>
	  	</div>
	  	<div class="alert alert-info">
	  		<strong>El protocolo es el siguiente:</strong><br>
	  		Llamar directamente al encargado de turno insistentemente por unos 5 minutos (las veces que sea necesario hasta que conteste en ese período de tiempo). SOLO si no logran comunicarse llamar al numero de respaldo.<br>
			En caso, que sean housing o dedicado, la persona de turno en la oficina deberá poner pantalla al equipo con problemas, resolver y/o contener de la mejor forma hasta el dia siguiente.
		</div>
		
		<div ng-if="ctrl.error" class="alert alert-danger">{{ctrl.error}}</div>
		<div ng-if="!ctrl.shifts.length" class="alert alert-info"><b>ATENCIÓN: </b>No hay personal de operaciones de turno actualmente</div>
	
		<table class="table" ng-if="ctrl.shifts">
			<tr>
				<th>Inicio</th>
				<th>Fin</th>
				<th>Sysadmin</th>
				<th>Sysadmin de Respaldo</th>
			</tr>
			<tr ng-repeat="shift in ctrl.shifts">
				<td>{{shift.start_time}}</td>
				<td>{{shift.end_time}}</td>
				<td>
					{{shift.sysadmin.name}} 
					<a href="tel:{{shift.sysadmin.phone}}" class="label label-primary info_label">
						<i class="fa fa-phone"></i> {{shift.sysadmin.phone}}
					</a>
					&nbsp;
					<a href="mailto:{{shift.sysadmin.email}}" class="label label-danger info_label">
						<i class="fa fa-envelope"></i>	{{shift.sysadmin.email}}
					</a>
				</td>
				<td>
					{{shift.backup_sysadmin.name}} 
					<a href="tel:{{shift.backup_sysadmin.phone}}" class="label label-primary info_label">
						<i class="fa fa-phone"></i> {{shift.backup_sysadmin.phone}}
					</a>
					&nbsp;                        
					<a href="mailto:{{shift.backup_sysadmin.email}}" class="label label-danger info_label">
						<i class="fa fa-envelope"></i>	{{shift.backup_sysadmin.email}}
					</a>
				</td>
			</tr>
		</table>
	</div>
</div>

<script src="//ajax.googleapis.com/ajax/libs/angularjs/1.2.18/angular.min.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/moment.js/2.10.3/moment.js"></script>

<script type="text/javascript">

var app = angular.module("ShiftsApp", []);

app.config(function ($httpProvider) {
  	$httpProvider.defaults.headers.post["Content-Type"] = "application/x-www-form-urlencoded;charset=utf-8";
	$httpProvider.defaults.transformRequest = [function(data) {
	    return angular.isObject(data) && String(data) !== "[object File]" ? $.param(data) : data;
	}];
});

app.controller("ShiftController", function($scope, $http){ 
	var self = this;
	self.shifts = [];
	self.error = '';

	parameters = {};
	if(moment().format("H") > 20){
		unique_date = moment().add(1, 'day').format("DD/MM/YYYY")+" 01:00";
		parameters = {'start': unique_date, 'end': unique_date};
	}

	$http.post(rutaAjaxOperaciones+"/shifts_manager/shift", parameters)
	.success(function(data) {
		console.log(data); 
		if(data.success){
			self.shifts = data.shifts;
		}else{
			self.error = data.errors;
		}
   }).error(function(msg, code) {
		self.error = "Error al consultar por los turnos de operaciones";
		console.log(msg);
   });
})
.$inject = ['$scope','$http'];

</script>


<?php
require $upOne."/dashboard_footer.html";
?>