<?php

//Recibe el número de extensión del FOP y devuelve el nombre con que aparece en el SAC
function NombreOperador($id)
{
	switch($id)
	{
		case 300:
			$operador = "Roni De Souza";break;
		case 302:
			$operador = "Fabian Castillo";break;
		case 304:
			$operador = "Jhosep Quiñonez";break;
		case 305:
			$operador = "Gerardo Muñoz";break;
		case 306:
			$operador = "Ray Ramos";break;
		case 307:
			$operador = "Takashi Awata";break;
		case 309:
			$operador = "David Herrera";break;
		case 310:
			$operador = "Alfredo Sepulveda";break;
		case 311:
			$operador = "Jose Gutierrez";break;
		case 312:
			$operador = "Patricia Diaz";break;
		case 316:
			$operador = "Fernanda Lopez";break;
		case 317:
			$operador = "Nicolas Gutierrez";break;
		case 319:
			$operador = "Silvia Vivanco";break;
		case 323;
			$operador = "Juan Ortega";break; //Bolivia 1
		case 324:
			$operador = "Luis Acebo";break; //Bolivia 2
		case 325:
			$operador = "Mauricio Lino";break; //Bolivia 3
		case 326:
			$operador = "Marcio Miralles";break; //Bolivia 3
		case 329:
			$operador = "Ariel Flores";break; //Bolivia 4
		case 331:
			$operador = "Mauricio Bravo";break;
		case 333:
			$operador = "Jhosep Quiñonez";break;
		case 334:
			$operador = "Fabian Castillo";break; 
		case 335:
			$operador = "Jose Gutierrez";break;
		case 337:
			$operador = "Alfredo Sepulveda";break;
		case 339:
			$operador = "Jhonathan Quiñones";break;
		case 342:
			$operador = "Daniel Roman";break;
		case 346:
			$operador = "Silvia Vivanco";break;
		case 351:
			$operador = "Ray Ramos";break;
		case 354:
			$operador = "Favio Lopez";break; //Bolivia 5
		case 355:
			$operador = "Roni De Souza";break;
		case 357:
			$operador = "Sebastian Marchant";break;
		case 364:
			$operador = "Jose Moron";break; //Bolivia 5
		case 365:
			$operador = "Maikon Teran";break;
		case 366:
			$operador = "Waldo Flores";break;
		default:
			$operador = $id;break;
	}
	
	if(operadorOmitido($operador))
		$operador = 0;
		
	return $operador;
}

//Comprueba si el operador debe ser omitido
function operadorOmitido($operador)
{
	$operadores_omitidos = array("Roni De Souza","Jose Gutierrez","Patricia Diaz","Alfredo Sepulveda","Gerardo Muñoz");
	
	if(in_array($operador,$operadores_omitidos))
		return true;
	return false;	
}
?>