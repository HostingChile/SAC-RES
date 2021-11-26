<?php

	$upOne = realpath(__DIR__ . '/..');
	require $upOne."/dashboard_header.php";
	$titulo_pagina_actual = "Intervencion Operaciones"; 
	include "navbar.php";
	include "generaldata.php";
	include "db/SACManager.class.php";
?>

<?php

$formated_info = array();

$cantidad_temas = 10;
$SACManager = SACManager::singleton();
$min_date = '2019-11-14';
$max_date = '2019-11-16';
$query = "SELECT Compania.nombre,COUNT(*), intervino_operaciones FROM Tipificacion,Compania WHERE Tipificacion.id_marca = Compania.id AND '" . $start_date.  "'< Tipificacion.fecha AND  Tipificacion.fecha < '". $end_date . "' GROUP BY id_marca, intervino_operaciones;";
$result = $SACManager->execQuery($query);
$values = [];
$values["Todas"] = [0 => 0, 1 => 0, 2 => 0];
while($row = $result->fetch_array())
{
	$hosting_name = $row[0];
	$quantity = intval($row[1]);
	if($row[2] == NULL){
		$intervino_operaciones = 2;
	}
	else{
		$intervino_operaciones = intval($row[2]);
	}
	$values[$hosting_name][$intervino_operaciones] = $quantity;
	$values["Todas"][$intervino_operaciones] = $values["Todas"][$intervino_operaciones] + $quantity;
}
	$passed_values = json_encode($values);
	$companies = array_keys($values);
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@2.8.0"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"></script>
<script>
	let chart;
	let data;
	document.addEventListener("DOMContentLoaded",function(){
		const passed_values = '<?php echo $passed_values?>';
		data = JSON.parse(passed_values);
		setNoData(data["Todas"][2]);
		const ctx = document.getElementById('chart');
		const filter = "Todas";
		const value_sin_intervencion = data[filter][0] ? data[filter][0] : 0;
		const value_con_intervencion = data[filter][1] ? data[filter][1] : 0;
		chart = new Chart(ctx, {
			type: 'pie',
			data: {
				labels: ['No hubo intervención', 'Hubo intervención'],
				datasets: [{
					data: [value_sin_intervencion, value_con_intervencion],
					backgroundColor: [
						'rgba(54, 162, 235, 0.2)',
						'rgba(255, 99, 132, 0.2)',
					],
					borderColor: [
						'rgba(54, 162, 235, 1)',
						'rgba(255, 99, 132, 1)',
					],
					borderWidth: 1
				}]
			},
			options: {
				responsive:false,
				maintainAspectRatio: false,
				scales: {
					yAxes: [{
						ticks: {
							beginAtZero: true
						}
					}]
				}
			}
		});
	});

	function onSelectChange(){
		const select = document.getElementById("select-company");
		const picked_company = select.options[select.selectedIndex].value;
		setNoData(data[picked_company][2]);
		chart.data.datasets.forEach((dataset) => {
			const original_length = dataset.data.length;
			for(let i=0; i<original_length;i++){
				dataset.data.pop();
			}
		});
		chart.data.datasets.forEach((dataset) => {
			dataset.data.push(data[picked_company][0]);
			dataset.data.push(data[picked_company][1]);
		})
		chart.update();
	}

	function setNoData(nodata_quantity){
		let nodata_element = document.getElementById("nodata");
		let text = "";
		if(nodata_quantity != 0  && nodata_quantity != undefined){
			text = nodata_quantity + " tipificaciones sin datos";
		}
		nodata_element.innerHTML = text;
	}

</script>
<html>
	<head>
		<link rel="stylesheet" type="text/css" href="css/stats.css">
	</head>
	<body>
		<div class="form-group regular-form-group">
			<label class="" for="compañia">Compañia</label>
			<select id="select-company" class="form-control" name="compañia" onChange="onSelectChange()">
				<?php
				foreach($companies as $company){
					echo "<option value='{$company}'>{$company}</option>";
				}
				?>
			</select>
		</div>
		<canvas id="chart" class="centered" width="500" height="500"></canvas>
		<p  id="nodata" class="centered"><?php echo $no_data?> tipificaciones sin datos </p>
	</body>
</html>
