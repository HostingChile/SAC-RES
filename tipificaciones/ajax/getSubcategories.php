<?php

if(!isset($_POST["category_id"]))
	exit(json_encode(array("success" => false, "message" => "No se recibio una categoria")));

//conexion a la BD
$upOne = realpath(__DIR__ . '/..');
require $upOne.'/DB/SACManager.class.php';
$SACManager = SACManager::singleton();

//obtengo los hijos de la categoria seleccionada
$category_id = $_POST["category_id"];

if($category_id == 0){
	$where = " WHERE ISNULL(C1.id_padre) ";
}
else{
	$where = " WHERE C1.id_padre = $category_id ";
}
$query = "SELECT C1.id, C1.nombre as title, IF(ISNULL(C2.id_padre),false,true) as hasChildren FROM Categoria C1 LEFT JOIN Categoria C2 ON C1.id = C2.id_padre $where GROUP BY C1.id";
$result = $SACManager->execQuery($query);
$categorias = array();
foreach ($result as $categoria) {
	$categorias[] = $categoria;
}

if(sizeof($categorias) == 0)
	exit(json_encode(array("success" => false, "message" => "No hay subcategorias asociadas")));

exit(json_encode(array("success" => true, "categories" => $categorias)));


?>