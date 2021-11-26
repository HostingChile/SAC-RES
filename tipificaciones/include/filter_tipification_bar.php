<!-- <script type="text/javascript" src="//cdn.jsdelivr.net/jquery/1/jquery.min.js"></script> -->
<script type="text/javascript" src="//cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<!-- <link rel="stylesheet" type="text/css" href="//cdn.jsdelivr.net/bootstrap/latest/css/bootstrap.css" /> -->

<!-- font awesome -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">

<!--  range datepicker -->
<script type="text/javascript" src="//cdn.jsdelivr.net/bootstrap.daterangepicker/2/daterangepicker.js"></script>
<link rel="stylesheet" type="text/css" href="//cdn.jsdelivr.net/bootstrap.daterangepicker/2/daterangepicker.css" />

<!-- js y css locales -->
<script type="text/javascript" src="<?= $root_dir_name;?>/tipificaciones/include/js/default_filter_bar.js" charset="UTF-8"></script>
<link rel="stylesheet" type="text/css" href="<?= $root_dir_name;?>/tipificaciones/include/css/default_filter_bar.css">


<?php
//formatea un texto en formato 'd/m/Y' a formato 'Y/m/d'
function formatDateString($date){
    $separator = strpos($date, "/") !== false ? "/" : "-";
    return implode($separator, array_reverse(explode($separator, trim($date))));
}

require_once __DIR__ ."/../DB/SACManager.class.php";
$tipification_manager = SACManager::singleton();

//valores del filtrado por _GET
$dominio_get = isset($_GET["dominio"]) ? trim($_GET["dominio"]) : "";
$rango_fecha_get = isset($_GET["rango_fecha"]) ? trim($_GET["rango_fecha"]) : "";
$servidor_get = isset($_GET["servidor"]) ? trim($_GET["servidor"]) : "";
$operador_get = isset($_GET["operador"]) ? trim($_GET["operador"]) : -1;
$compañia_get = isset($_GET["compañia"]) ? trim($_GET["compañia"]) : -1;
$tipocontacto_get = isset($_GET["tipocontacto"]) ? trim($_GET["tipocontacto"]) : -1;
$problema_solucionado_get = isset($_GET["problema_solucionado"]) ? trim($_GET["problema_solucionado"]) : -1;
$intervino_operaciones_get = isset($_GET["intervino_operaciones"]) ? trim($_GET["intervino_operaciones"]) : -1;
$categoria_get = isset($_GET["categoria"]) ? trim($_GET["categoria"]) : -1; //Categoría del contacto (por ejemplo cambio de plan, reactivación de servicio, etc)


//si no hay filtros seleccionados, elijo solamente las tipificaciones de hoy
date_default_timezone_set("America/Santiago");
$today_date = date("d/m/Y");
$today_month = date("m");
$today_year = date("Y");
if($rango_fecha_get == "" && $detalle_get == "" && $monto_desde_get == "" && $monto_hasta_get == ""){
	$rango_fecha_get = "$today_date - $today_date";
}

?>

<div class="row">
    <form class="form-inline filter_bar">
        <div class="form-group large-form-group">
            <label class="" for="dominio">Dominio</label>
            <input type="text" name="dominio" value="<?=$dominio_get?>"/>
        </div>
        <div class="form-group regular-form-group">
            <label class="" for="rango_fecha">Fecha de Tipificacion</label>
            <input type="text" name="rango_fecha" class="date_range_picker" value="<?=$rango_fecha_get?>"/>
        </div>
        <div class="form-group regular-form-group">
            <label class="" for="servidor">Servidor</label>
            <input type="text" name="servidor" value="<?=$servidor_get?>"/>
        </div>
     	<div class="form-group regular-form-group">
            <label class="" for="operador">Operador</label>
            <select class="form-control" name="operador">
            <?php
                $query = "SELECT DISTINCT(operador) as operador FROM Tipificacion ORDER BY operador";
                $result_operador = $tipification_manager->execQuery($query);

                $selected_operador = $operador_get == -1 ? "selected" : "";
                echo "<option value='-1' $selected_operador>Todos</option>";

                while($operador = mysqli_fetch_array($result_operador, MYSQLI_ASSOC)){
                    $selected_operador = $operador_get == $operador["operador"] ? "selected" : "";
                    echo "<option value='{$operador["operador"]}' $selected_operador>{$operador["operador"]}</option>";
                }
            ?>
            </select>
        </div>
        <div class="form-group regular-form-group">
            <label class="" for="compañia">Compañia</label>
            <select class="form-control" name="compañia">
            <?php
                $query = "SELECT id, nombre FROM Compania";
                $result_compañia = $tipification_manager->execQuery($query);

                $selected_compañia = $compañia_get == -1 ? "selected" : "";
                echo "<option value='-1' $selected_compañia>Todas</option>";

                while($compañia = mysqli_fetch_array($result_compañia, MYSQLI_ASSOC)){
                    $selected_compañia = $compañia_get == $compañia["id"] ? "selected" : "";
                    echo "<option value='{$compañia["id"]}' $selected_compañia>{$compañia["nombre"]}</option>";
                }
            ?>
            </select>
        </div>
        <div class="form-group regular-form-group">
            <label class="" for="tipocontacto">Medio</label>
            <select class="form-control" name="tipocontacto">
            <?php
                $query = "SELECT id, nombre FROM TipoContacto";
                $result_tipocontacto = $tipification_manager->execQuery($query);

                $selected_tipocontacto = $tipocontacto_get == -1 ? "selected" : "";
                echo "<option value='-1' $selected_tipocontacto>Todos</option>";

                while($tipocontacto = mysqli_fetch_array($result_tipocontacto, MYSQLI_ASSOC)){
                    $selected_tipocontacto = $tipocontacto_get == $tipocontacto["id"] ? "selected" : "";
                    echo "<option value='{$tipocontacto["id"]}' $selected_tipocontacto>{$tipocontacto["nombre"]}</option>";
                }
            ?>
            </select>
        </div>
        <div class = "form-group regular-form-group">
            <label class="" for="categoria">Categoría</label>
            <select class="form-control" name="categoria">
            <?php
                $query = "SELECT id, nombre FROM CategoriaTipificacion";
                $result_categoria = $tipification_manager->execQuery($query);
                $parents_query = "SELECT DISTINCT id_padre FROM CategoriaTipificacion WHERE id_padre IS NOT NULL;"; //verifica que categorias son solo un medio para acceder a otras, por ejemplo soporte y ventas. No son valores reales, asi que se descartan
                $results_parents = $tipification_manager->execQuery($parents_query);
                $category_parents = [];
                $parents_array = [];
                while ($row = mysqli_fetch_array($results_parents, MYSQLI_ASSOC)) {
                    $parents_array[] = $row["id_padre"];
                  }
                $selected_categoria = $categoria_get == -1 ? "selected" : "";
                echo "<option value='-1' $selected_categoria>Todas</option>";
                while($categoria = mysqli_fetch_array($result_categoria, MYSQLI_ASSOC)){
                    if(!(in_array($categoria["id"],$parents_array))){
                        $selected_categoria = $categoria_get == $categoria["id"] ? "selected" : "";
                        echo "<option value='{$categoria["id"]}' $selected_categoria>{$categoria["nombre"]}</option>";
                    }
                }
            ?>
            </select>
        </div>
        <div class="form-group regular-form-group">
            <label class="" for="operador">Problema Solucionado</label>
            <select class="form-control" name="problema_solucionado">
            <?php
                $opciones = array(-1 => "Todos", 0 => "Problema no solucionado", 1 => "Problema solucionado");
                foreach ($opciones as $valor => $texto) {
                	$selected_problema_solucionado = $problema_solucionado_get == $valor ? "selected" : "";
                    echo "<option value='{$valor}' $selected_problema_solucionado>{$texto}</option>";
                }
            ?>
            </select>
        </div>
        <div class="form-group regular-form-group">
            <label class="" for="operador">Intervino Operaciones</label>
            <select class="form-control" name="intervino_operaciones">
            <?php
                $opciones = array(-1 => "Todos", 0 => "No intervino operaciones", 1 => "Intervino operaciones");
                foreach ($opciones as $valor => $texto) {
                	$selected_intervino_operaciones = $intervino_operaciones_get == $valor ? "selected" : "";
                    echo "<option value='{$valor}' $selected_intervino_operaciones>{$texto}</option>";
                }
            ?>
            </select>
        </div>
        
        <button id="btn_submit_form" type="submit" class="btn btn-primary">Filtrar</button>
    </form>
</div>

<?php

//obtengo un arreglo con todos los abonos con los filtros seleccionados

$query = "SELECT T.*, CT.nombre as categoria_tipificacion, TC.img AS img_tipocontacto, C.image AS img_compania FROM Tipificacion T 
    LEFT JOIN TipoContacto TC ON T.id_tipocontacto = TC.id 
    LEFT JOIN CategoriaTipificacion CT ON T.id_categoria = CT.id
    LEFT JOIN Compania C ON C.id = T.id_marca
    WHERE ";
if($dominio_get != ""){
    $query .= "T.dominio LIKE '$dominio_get%' AND  ";
}
if($rango_fecha_get != ""){
	$fechas_rango = explode("-", $rango_fecha_get);
	$query .= "T.fecha >= '".formatDateString($fechas_rango["0"])." 00:00:00' AND T.fecha <= '".formatDateString($fechas_rango["1"])." 23:59:59' AND  ";
}
if($servidor_get != ""){
	$query .= "(T.ip_servidor = '$servidor_get' OR T.ip_servidor LIKE '$servidor_get %') AND  ";
}
if($operador_get != -1){
    $query .= "T.operador = '$operador_get' AND  ";
}
if($compañia_get != -1){
    $query .= "C.id = '$compañia_get' AND  ";
}
if($tipocontacto_get != -1){
    $query .= "TC.id = '$tipocontacto_get' AND  ";
}
if($problema_solucionado_get != -1){
	$query .= "T.problema_solucionado = '$problema_solucionado_get' AND  ";
}
if($intervino_operaciones_get != -1){
	$query .= "T.intervino_operaciones = '$intervino_operaciones_get' AND  ";
}
if($categoria_get != -1){
    $query .= "(CT.id = '$categoria_get') AND ";
}


$query = substr($query, 0, -5);
$query.= " ORDER BY T.id DESC";

$query = preg_replace( "/\r|\n/", "", $query);
echo "<script>console.log(\"$query\");</script>";

$result_tipificaciones = $tipification_manager->execQuery($query);
?>
