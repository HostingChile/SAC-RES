<?php

function stripAccents($word){
  return strtr($word,'áéíóúÁÉÍÓÚ','aeiouAEIOU');
}

//Obtiene el operador a partir del asunto del correo
function obtenerOperador($texto)
{
	if(strpos($texto,'Operators:') !== false)
	{
		$inicio = strpos($texto,'Operators:') + 11;
		$fin = strpos($texto,',',$inicio);
		// $inicio = strrpos($texto, ",")+2;
		// $fin = strrpos($texto,'.',$inicio);
		$largo = $fin - $inicio;
		
		$operador = substr($texto,$inicio,$largo);
	}
	else if(strpos($texto,'Operator:') !== false)
	{
		$inicio = strpos($texto,'Operator:') + 10;
		$fin = strpos($texto,'.',$inicio);
		$largo = $fin - $inicio;
		
		$operador = substr($texto,$inicio,$largo);
	}
	else
		$operador = "";
		
	if(operadorOmitido($operador))
		$operador = "";
	
	return $operador;
}

//Suma las cantidades despues de hacer un 'array_merge_recursive'
function eliminarRecursividad($array)
{
	$respuesta=array();

	foreach($array as $operador=>$contestaciones)
	{
		$cantidad = 0;
		
		if (count($contestaciones) == 1)
			$cantidad = $contestaciones;
		else
			for($i=0; $i < count($contestaciones); $i++)
				$cantidad += $contestaciones[$i];
		
		$respuesta[$operador] = $cantidad;
	}
	
	return $respuesta;
}

//Comprueba si el operador debe ser omitido
function operadorOmitido($operador)
{
	$operadores_omitidos = array("Roni De Souza","Jose Gutierrez","Patricia Diaz","Alfredo Sepulveda","Gerardo Muñoz","Sebastian Marchant");
	
	if(in_array($operador,$operadores_omitidos))
		return true;
	return false;	
}

//Devuelve el nombre de quien está usando cierto operador
function nombreRealChat($operador){
	if($operador == "Humberto Reina" || $operador == "Jhonatan Quiñones")
		$operador = "Jhonathan Quiñones";
	else if($operador == "Marcela Reina")
		$operador = "Lina Quiñones";
		
	return $operador;

}

?>