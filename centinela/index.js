$(function() {
    $('#daterange').daterangepicker({
		autoApply: true,
	    "ranges": {
	        "Hoy": [ moment(), moment().add(1, 'days') ],
	        "Ayer": [ moment().subtract(1, 'days'), moment() ],
	        "Últimos 7 días": [ moment().subtract(6, 'days'), moment().add(1,'days') ],
	        // "Últimos 30 días": [ moment().subtract(29, 'days'), moment() ],
	        "Mes actual": [ moment().startOf('month'), moment().add(1, 'month').startOf('month') ],
	        "Mes pasado": [ moment().subtract(1, 'month').startOf('month'), moment().startOf('month') ],
	    },
	    "locale": {
	        "format": "DD/MM/YYYY",
	        "separator": " - ",
	        "applyLabel": "Aplicar",
	        "cancelLabel": "Borrar",
	        "fromLabel": "Desde",
	        "toLabel": "Hasta",
	        "customRangeLabel": "Personalizado",
	        "daysOfWeek": ["Do","Lu","Ma","Mi","Ju","Vi","Sa"],
	        "monthNames": ["Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre"],
	        "firstDay": 1
	    }
	});
});

var app = angular.module('CentinelaApp', []);

app.config(function($httpProvider) {
    
    // $httpProvider.defaults.useXDomain = true; //Enable cross domain calls
    $httpProvider.defaults.headers.post['Content-Type'] = 'application/x-www-form-urlencoded;charset=utf-8';
	$httpProvider.defaults.transformRequest = [function(data) {
		return angular.isObject(data) && String(data) !== '[object File]' ? $.param(data) : data;
	}];
});

//ANGULAR DIRECTIVES
app.directive('centinelaProgressBar', function(CentinelaService){
	return {
		restrict: 'E',
		scope: {},
		controller: function($scope){
			var self = this;
			$scope.$watch(
				function(){
					return CentinelaService.getProgress();
				}, 
				function(newValue){
					self.progress = newValue+'%';
				});
		},
		controllerAs: 'ctrl',
		template: 	'<div class="progress"><div class="progress-bar" role="progressbar" ng-style="{width: ctrl.progress}" ng-class="{notransition: ctrl.progress===\'0%\'}"></div></div>'
	}
})

//ANGULAR SERVICES
app.factory('CentinelaService', function($q, $http){

	var servers = {'ok': [], 'no_measurement': [], 'not_found': [], 'api_error': []};
	var api_path = "http://sistemas.hosting.cl/SAC/centinela/ajax/centinelainfo.php";
	var progress = 0;
	var total_servers;
	var queried_servers = 0;

	function getServersInfo(servers_list, begin_date, end_date){

		total_servers = servers_list.length;
		queried_servers = 0;
		progress = 0;
		//reset all the servers arrays
		servers['ok'].length = 0;
		servers['no_measurement'].length = 0;
		servers['not_found'].length = 0;
		servers['api_error'].length = 0;

		var promises = [];

		servers_list.forEach(function(server) {
			promises.push(getServerInfo(server, begin_date, end_date));
		});

		return $q.all(promises);
	}

	function getServerInfo(server, begin_date, end_date) {

		var numero_caidas_pop3 = 0;
		var tiempo_caido_pop3 = 0;

		var numero_caidas_cpanel = 0;
	    var tiempo_caido_cpanel = 0;

	    var api_data = {"servidor": server, "inicio": begin_date, "fin": end_date};

	    console.log("Obteniendo info del servidor "+server, api_data);

	    // return $http.post(api_path, api_data).then(function(data){console.log("POST Exitoso", data);}, function(data){console.log("POST Fallo", data);});

		return 	$http.post( api_path, api_data)
				.then(function(response){
					var data = response.data;
					console.log(data);
					queried_servers++;
					progress = queried_servers/total_servers*100;
					if(data.Exitosa){
						//calculo el uptime, numero de caidas, tiempo caido promedio y por aplicacion (esto sirve para una vez) --> otras alternativas http://stackoverflow.com/questions/26087509/angularjs-count-filtered-items
				    	if(data.servicios === null){
				    		servers['not_found'].push(server);
				    		return;
				    	}
				    	
				    	//determino la fecha de incio para ir sumandole los segundos de los eventos
				    		

				    	["pop3", "cpanel"].forEach(function(service){
				    		console.log("####",service,"####")
				    		var fecha_inicio_rango = new Date(begin_date);
					    	fecha_inicio_rango.setMinutes(fecha_inicio_rango.getMinutes() + fecha_inicio_rango.getTimezoneOffset())
					    	data.servicios[service].tiempo_caido = 0;
					    	data.servicios[service].numero_caidas = 0;
					    	data.servicios[service].detallecaidas = [];
					    	angular.forEach(data.servicios[service].rangos, function(rango){
					    		fecha_fin_rango = new Date(fecha_inicio_rango.getTime());
					    		fecha_fin_rango.setSeconds(fecha_inicio_rango.getSeconds() + rango.segundos);
						        if(rango.estado == 0){
						        	data.servicios[service].numero_caidas++;
						        	data.servicios[service].tiempo_caido += rango.segundos;
						        	console.log("caida")
						        	 console.log({"inicio": fecha_inicio_rango, "fin": fecha_fin_rango,  "segundos": rango.segundos});
						        	data.servicios[service].detallecaidas.push({"inicio": fecha_inicio_rango, "fin": fecha_fin_rango,  "segundos": rango.segundos});
						        }
						        else{
						        }
						       
						        
						        fecha_inicio_rango = fecha_fin_rango
						    });
				    	})

					    data.servicios.numero_caidas_promedio = (data.servicios.pop3.numero_caidas + data.servicios.cpanel.numero_caidas)/2;
					    data.servicios.tiempo_caido_promedio = (data.servicios.pop3.tiempo_caido + data.servicios.cpanel.tiempo_caido)/2;
					    data.servicios.tiempo_promedio_por_caida =  data.servicios.numero_caidas_promedio === 0 ? 0 : data.servicios.tiempo_caido_promedio/data.servicios.numero_caidas_promedio;

					    data.servicios.uptime_promedio = (data.servicios.pop3.uptime + data.servicios.cpanel.uptime)/(data.servicios.pop3.uptime + data.servicios.cpanel.uptime + data.servicios.pop3.downtime + data.servicios.cpanel.downtime);	
						
						if(data.servicios.cpanel.sinMedicion == 1 && data.servicios.pop3.sinMedicion == 1){
							servers['no_measurement'].push(data.servicios.ip);
						}
						else{
							servers['ok'].push(data.servicios);
						}

					}else{
						console.log("Respuesta de la API no fue exitosa para el servidor "+server+ "("+api_path+")");
						console.log(data);
						servers['api_error'].push(server);
					}
				},
				function(error){
					console.log("Fallo el llamado a la API desde el servidor "+server+ "("+api_path+")");
					servers['api_error'].push(server);
				})
	};

	return {
		getServersInfo: getServersInfo, 
		servers: servers,
		getProgress: function(){ return progress; }
	};
})


//ANGULAR FILTERS

// This filter makes the assumption that the input will be in decimal form (i.e. 17% is 0.17).
app.filter('percentage', ['$filter', function ($filter) {
  return function (input, decimals) {
    return $filter('number')(input * 100, decimals) + '%';
  };
}]);

app.filter('secToMin', ['$filter', function ($filter) {
  return function (input) {
    return $filter('number')(input/60, 0);
  };
}]);

app.filter('startFrom', function() {
    return function(input, start) {
        start = +start; //parse to int
        return input.slice(start);
    }
});

app.filter('spanish_dayname', function() {
    return function(english_dayname) {
    	var dayname_mapping = {"Monday": "Lunes","Tuesday": "Martes","Wednesday": "Miércoles","Thursday": "Jueves","Friday": "Viernes","Saturday": "Sábado","Sunday": "Domingo"};
        return dayname_mapping[english_dayname];
    }
});

app.controller('CentinelaCtrl', function($q, CentinelaService, $interval, $http) {

	var self = this;

	//ANGULAR VARIABLES

	//boolean if is loading data
	self.loading = false;

	//modals
	self.modals = {};
	self.modals.chart_detail = {'is_open': false, 'data' : {'day': '', 'hour': 0, 'minutes_downtime': 0, 'downtime_detail': []}};
	self.modals.server_detail = {'is_open': false, 'data': {'caidas': [], 'servidor': ''}};

	//sort table
	self.sortType     = 'uptime_promedio'; // set the default sort type
  	self.sortReverse  = false;  // set the default sort order

  	//centinela info
  	var today = new Date();
  	var today_formated =  today.getDate()+'/'+(today.getMonth()+1)+'/'+today.getFullYear();
  	today.setDate(today.getDate() + 1);
  	var tomorrow_formated =  today.getDate()+'/'+(today.getMonth()+1)+'/'+today.getFullYear();

  	self.range = today_formated+" - "+tomorrow_formated;
	
  	self.servers = CentinelaService.servers['ok'];
	self.no_measurement_servers = CentinelaService.servers['no_measurement'];
	self.not_found_servers = CentinelaService.servers['not_found'];
	self.api_error_servers = CentinelaService.servers['api_error'];

    //pagination
 	self.current_page = 1;
    self.page_size = 15;

    //decimals to show in uptime info
    self.decimals = 2;

    //error,warning,ok uptime limits
    self.error_limit = 0.9900;
    self.warning_limit = 0.9970;
    self.ok_limit = 0.9999;

    //reload page automatically parameters
    self.reload_periodically = true;
    self.reload_period = 15;

    //load the table info if automatic refresh is enabled
    $interval(function(){
    	if(self.reload_periodically && !self.loading){
    		console.log("timeout ");
    		self.getUptimeInfo();
    	}
    }, self.reload_period*60*1000);

	//ANGULAR FUNCTIONS

	self.openModal = function(modal_id){
		console.log("abriendo modal "+modal_id);
		console.log("data", self.modals[modal_id].data);
		self.modals[modal_id].is_open = true;
		$("#"+modal_id).modal('show');
	}

	self.closeModal = function(modal_id){
		console.log("cerrando modal "+modal_id);
		self.modals[modal_id].data = {};
		self.modals[modal_id].is_open = false;
		$("#"+modal_id).modal('hide');
	}


    self.numberOfPages = function(){
    	console.log("servidores: "+self.servers.length);
    	if(!self.servers.length){
    		return 1;
    	}
    	console.log("Hay "+Math.ceil(self.servers.length/self.page_size)+" paginas");
        return Math.ceil(self.servers.length/self.page_size);                
    }

    self.getTimes = function(times){
    	if(isNaN(times)){
    		return new Array(1);
    	}
    	return new Array(Math.ceil(times));
    }

    self.setPage = function(page){
    	if( page < 1 ||  page > Math.ceil(self.servers.length / self.page_size)){
    		console.log("se omite seteo de pagina "+page);
    		return;
    	}
    	console.log("cambiando a pagina "+page)
    	self.current_page = page;
    }

    self.getUptimeClass = function(uptime){

    	if(uptime < self.error_limit){
    		return "danger";
    	}
    	if(uptime < self.warning_limit){
    		return "warning";
    	}
    	if(uptime > self.ok_limit){
    		return "success";
    	}
    	return "";
    }

    self.getUptimeInfo = function(){

    	self.loading = true;

    	var fechas = self.range.split('-');

    	var begin_date = fechas[0].trim().split("/").reverse().join("-");
    	var end_date = fechas[1].trim().split("/").reverse().join("-");

		$http.get( 'http://rack.hosting.cl/centinela/getservers.php')
		.then(function(response){
			console.log(response);
			var data = response.data;
			if(data.success){
				//["201.148.105.50"] 
				//data.servidores
				CentinelaService.getServersInfo(data.servidores , begin_date, end_date)
		    	.finally(function(){
		    		google.charts.load('current', {'packages':['corechart']});
    				google.charts.setOnLoadCallback(self.drawCharts);
		    		// self.drawCharts();

		    		//saco el logo de loading
		    		self.loading = false;
		    	});
			}
			else{
				self.loading = false;
				alert("error al obtener el listado de servidores");
			}
		}, function(error){
			self.loading = false;
			alert("error al obtener el listado de servidores");
		});

    	// var servers = ["190.196.70.188","190.96.85.11","190.96.85.114","190.96.85.13","190.96.85.132","190.96.85.14","190.96.85.152","190.96.85.157","190.96.85.164","190.96.85.167","190.96.85.17","190.96.85.18","190.96.85.186","190.96.85.19","190.96.85.194","190.96.85.21","190.96.85.211","190.96.85.226","190.96.85.228","190.96.85.239","190.96.85.242","190.96.85.3","190.96.85.31","190.96.85.40","190.96.85.41","190.96.85.42","190.96.85.43","190.96.85.47","190.96.85.5","190.96.85.50","190.96.85.51","190.96.85.57","190.96.85.62","190.96.85.7","190.96.85.77","190.96.85.78","190.96.85.79","200.63.102.230","200.63.102.244","201.148.104.100","201.148.104.101","201.148.104.102","201.148.104.103","201.148.104.104","201.148.104.105","201.148.104.107","201.148.104.108","201.148.104.109","201.148.104.110","201.148.104.111","201.148.104.112","201.148.104.113","201.148.104.114","201.148.104.115","201.148.104.2","201.148.104.3","201.148.104.4","201.148.104.52","201.148.104.53","201.148.104.6","201.148.104.7","201.148.105.12","201.148.105.13","201.148.105.14","201.148.105.15","201.148.105.17","201.148.105.18","201.148.105.19","201.148.105.2","201.148.105.20","201.148.105.21","201.148.105.23","201.148.105.24","201.148.105.25","201.148.105.26","201.148.105.28","201.148.105.29","201.148.105.3","201.148.105.31","201.148.105.32","201.148.105.33","201.148.105.34","201.148.105.40","201.148.105.41","201.148.105.42","201.148.105.43","201.148.105.45","201.148.105.48","201.148.105.49","201.148.105.5","201.148.105.50","201.148.105.51","201.148.105.52","201.148.105.53","201.148.105.54","201.148.105.55","201.148.105.56","201.148.105.58","201.148.105.59","201.148.105.6","201.148.105.62","201.148.105.7","201.148.105.70","201.148.105.8","201.148.105.9","201.148.106.4","201.148.106.5","201.148.107.140","201.148.107.160","201.148.107.40","201.148.107.95","67.225.178.78","69.64.57.139"];
    	// var servers = ["190.196.70.188","190.96.85.11"];
    	
    }

    self.drawCharts = function(){
    	console.log("Empezando a dibujar graficos");
    	//genero un arreglo asociativo para guardar los detalles de las caidas por servicio servicio->dia-hora->{'segundos_downtime', 'caidas'}
    	console.log("=========================")
		var downtimeDetail = {'pop3': {}, 'cpanel': {}};
		["pop3", "cpanel"].forEach(function(service){
			for(dia = 1; dia <= 7; dia++){
				for(hora = 0; hora < 24; hora++){
					downtimeDetail[service][dia+"-"+hora] = {"segundos_downtime": 0, 'caidas': []};
				}
			}
		});


		console.log("Servers to draw chart", self.servers);

		//por cada caida de cada servidor para cada tipo de servicio, agrego la info a 'downtime detail'
		["pop3", "cpanel"].forEach(function(service){
			console.log("==========",service,"=========");
			self.servers.forEach(function(server){
				server[service].detallecaidas.forEach(function(detallecaida){
					console.log("Revisando detalle de caida", detallecaida);
					var start = new Date(detallecaida.inicio.getTime());
				    var end = new Date(detallecaida.fin.getTime());

				    while(start < end){
				    	var segundos_actual = start.getSeconds();
				    	var minutos_actual = start.getMinutes();
						var hora_actual = start.getHours();
						var dia_actual = start.getDay() == 0 ? 7 : start.getDay();//0: sunday (lo cambia a 7), 6: saturday
						var segundos_downtime;
						//si el fin esta en esta misma hora
						if(hora_actual == end.getHours()){
							segundos_downtime = (end.getMinutes() - minutos_actual)*60 + (end.getSeconds() - segundos_actual);
						}
						//si el fin esta en una hora posterior
						else{
							segundos_downtime = (60 - minutos_actual)*60 - segundos_actual;
						}
						console.log("Agregando registro a hora", hora_actual, "start",start, "end", end);
						console.log("downtime: ", segundos_downtime, "segundos")      

						downtimeDetail[service][dia_actual+"-"+hora_actual].segundos_downtime += segundos_downtime;
						downtimeDetail[service][dia_actual+"-"+hora_actual].caidas.push({'ip': server.ip, 'inicio': detallecaida.inicio, 'fin': detallecaida.fin, 'segundos_downtime': segundos_downtime})

						//loop a la siguiente hora
						start.setHours(start.getHours() + 1);
						start.setMinutes(0);
						start.setSeconds(0);
				    }
				})
			})
		})


		var charts = {};
		//generate the charts for each service
		var daynames = {1: 'Lunes', 2: 'Martes', 3: 'Miercoles', 4: 'Jueves', 5: 'Viernes', 6: 'Sabado', 7: 'Domingo'};
		["pop3", "cpanel"].forEach(function(service){
			console.log("generando grafico de ",service);	
			var chartInfo = [['ID', 'Hora',    'Dia de Semana',   'Rango caida',   'Minutos Caidos']];

			Object.keys(downtimeDetail[service]).forEach(function (key) {

			   var segundos_downtime = downtimeDetail[service][key].segundos_downtime;
			   if(segundos_downtime > 0){
					var detalle_fecha = key.split("-");
					chartInfo.push(['', +detalle_fecha[1], +detalle_fecha[0], 'a', segundos_downtime/60]);
			   }
			})

			console.log(chartInfo);

			if(chartInfo.length == 1){
				chartInfo = [];
			}

			var data = google.visualization.arrayToDataTable(chartInfo);
			
			var day_ticks = [];
			day_ticks.push({v: 0, f: ''});
			Object.keys(daynames).forEach(function (key) {
			   day_ticks.push({v: key, f: daynames[key]});
			});
			day_ticks.push({v: 8, f: ''});
		
			var options = {
				width: 750,
				height: 500,
				// titlePosition: 'none',
				title: capitalize(service)+' - Caídas de servidores',
				hAxis: {title: 'Hora', ticks: [0,4,8,12,16,20,24], viewWindow: {min: -2, max: 26}},
				vAxis: {title: '', ticks: day_ticks},
				bubble: {textStyle: {fontSize: 11}},
				tooltip: {trigger: 'none'},
				legend: {position: 'none'},
			};

			var chart = new google.visualization.BubbleChart(document.getElementById(service+'_chart'));
			google.visualization.events.addListener(chart, 'select', function(){
				return onSelectedBubble(service);
			});
			chart.draw(data, options);
			charts[service] = {'chart': chart, 'data': data};

			console.log("Grafico dibujado");
		});

		

		function onSelectedBubble(service){
			var chart = charts[service].chart;
			var data = charts[service].data;
			console.log("selected bubble !!!", service);
			if(typeof chart.getSelection()[0] === 'undefined'){
			  console.log("seleccion indefinida----nada que hacer");
			  return;
			}
			console.log("selected bubble !!!", chart.getSelection()[0]);
			var selectedRow = chart.getSelection()[0].row;
			var selectedData = data.Tf[selectedRow].c;
			var hora = selectedData[1].v;
			var dia = selectedData[2].v;
			var dayname = daynames[dia];

			console.log("Filtrando", "dia", dayname, dia, "hora", hora);
			var hourInfo = downtimeDetail[service][dia+"-"+hora];

			self.modals.chart_detail.data.day = dayname; 
			self.modals.chart_detail.data.hour = hora; 
			self.modals.chart_detail.data.segundos_downtime = hourInfo.segundos_downtime;
			self.modals.chart_detail.data.downtime_detail = hourInfo.caidas;

			console.log("Detalle antes de abrir modal", self.modals.chart_detail.data);
			angular.element(document.getElementById('chart_detail')).scope().$apply();
			self.openModal('chart_detail');

			chart.setSelection({});
		}
    }
});


function capitalize(s){
    return s[0].toUpperCase() + s.slice(1);
}