<?php
error_reporting(0);

//conexion a la BD
$upOne = realpath(__DIR__ . '/..');
require $upOne.'/DB/SACManager.class.php';
$SACManager = SACManager::singleton();

$categorias = getCategoryTree(0);

if(sizeof($categorias) == 0)
	exit(json_encode(array("success" => false, "message" => "No hay subcategorias asociadas")));

exit(json_encode(array("success" => true, "categories" => $categorias)));

/*Recursiva: retorna un arreglo ascoiativo id, title y children en caso que corresponda*/
function getCategoryTree($father_id)
{
	if($father_id == 0)
	{
		//elijo a las categorias raiz que esten activas	
		$query = "SELECT C1.id, C1.nombre as title, IF(ISNULL(C2.id_padre),false,true) as hasChildren 
			FROM CategoriaTipificacion C1 LEFT JOIN CategoriaTipificacion C2 ON C1.id = C2.id_padre 
			WHERE ISNULL(C1.id_padre) AND C1.activo = 1 GROUP BY C1.id";
	}
	else{
		//elijo a los hijos del padre que esten activos
		$query = "SELECT C1.id, C1.nombre as title, IF(ISNULL(C2.id_padre),false,true) as hasChildren 
			FROM CategoriaTipificacion C1 LEFT JOIN CategoriaTipificacion C2 ON C1.id = C2.id_padre 
			WHERE C1.id_padre = $father_id AND C1.activo = 1 GROUP BY C1.id";
	}

	$SACManager = SACManager::singleton();
	$categories = $SACManager->execQuery($query);

	$all_categories = array();
	
	foreach ($categories as $category) {
		$curr_category = array();
		$curr_category["id"] = $category["id"];
		$curr_category["title"] = $category["title"];
		if($category["hasChildren"] == "1"){
			$curr_category["children"] = getCategoryTree($category["id"]);
		}
		$all_categories[] = $curr_category;
	}

	return $all_categories;

}


?>