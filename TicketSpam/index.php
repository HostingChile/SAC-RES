<?php
	$upOne = realpath(__DIR__ . '/..');
	require $upOne."/dashboard_header.php";
	require $upOne.'/tipificaciones/DB/SACManager.class.php';
	$SACManager = SACManager::singleton();
?>
<head>
	<meta charset="UTF-8">
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
	<script src="./cambiarle_el_nombre.js"></script>
	<link rel="stylesheet" href="index.css">
</head>
<body>
	<h1> Control Filtro Spam Tickets WHMCS </h1>
	<p> Quita emails del filtro de spam de un WHMCS </p>
	<div class="container-fluid">
		<div class="row">
			<div class="col-sm">
				<label class="col-sm-1 control-label">Marca</label>
			</div>
			<div class="col-sm">
				<?php
					$query = "SELECT * FROM Compania WHERE nombre <> 'Dehosting'";
					$result = $SACManager->execQuery($query);
					$class = "";
					foreach ($result as $compañia) {
						$image_id = 'brand_image_' . $compañia["id"];
						echo "<img class='custom_select_option $class' 
									id='{$image_id}' 
									src='../tipificaciones/include/img/marcas/{$compañia["image"]}'
									brand-name='{$compañia["nombre"]}'
									onclick='brandClicked(". $image_id .")'
									>";
						$class = "";
					}
				?>
			</div>
		</div>
	</div>

	<div class="input-group">
		<input id="search_email_input" type="text" class="form-control" placeholder="Buscar email...">
		<span class="input-group-btn">
		<button class="btn btn-default" type="button" onclick="checkWhmcsTicketMailFilter()">Buscar</button>
		</span>
	</div><!-- /input-group -->

	<div>
		<div>
			<nav id = "result_bar" class = "navbar navbar-default">
				<div class = "navbar-header">
					<a id="searched_email" class="navbar-brand"></a>
				</div>
				<button id="result" type="button" class="btn btn-danger navbar-btn">Quitar del filtro de Spam</button>
			</nav>
		</div>
		<div id="alert" class="alert" role="alert"></div>
	</div>

	
</body>
<?php

require "../dashboard_footer.html";
?>
