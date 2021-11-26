<?php

class Calculador{

	private $operador;
	private $indicadores;
	
	private $medios;
	private $ponderadores;
	
	private $notasIndicadores = array();
	private $notasMedio = array();
	private $notaFinal = 0;
	
	public function __construct($operador,$indicadores){
		$this->operador = $operador;
		
		$this->medios = array();
		$this->indicadores = array();
		foreach($indicadores as $indicador){
			$this->indicadores[$indicador->getInfo()['medio']][] = $indicador;
			$this->medios[] = $indicador->getInfo()['medio'];
		}
		$this->medios = array_unique($this->medios);
	}

	final public function getResults()
    {
        return array('indicadores' => $this->notasIndicadores,
        			'medios' => $this->notasMedio,
        			'final'=>$this->notaFinal );
    }
	
	final public function getPonderadores()
    {
        return $this->ponderadores;
    }
	
	/*Retorna el valor final para el operador, en el mes solicitado. NO define si gana el bono o no, solo entrega el valor*/
	public function calcularCalificacionBono($mes,$año){
		//No entrar si se solicita una fecha muy antigua
		//if($mes == 3) //--------> Fecha de primer mes a considerar HARDCODEADO fecha 
		//	return false;
	
		/*
		if(!$this->existenRegistros($mes,$año)){
			$año_anterior = $mes == 1 ? $año - 1 : $año;
			$mes_anterior = $mes == 1 ? 12 : $mes - 1;
			$this->calcularCalificacionBono($mes_anterior,$año_anterior);		
		}
		*/
		
		//Calcula los ponderadores del operador para el mes en cuestion
		$this->calcularPonderadores($mes,$año);
		$this->notasMedio = array();
				
		foreach($this->indicadores as $medio => $indicadoresmedio){
			if(!isset($this->ponderadores[$medio]) || $this->ponderadores[$medio] == 0)
			{
					debug("<h4> - Eximido de $medio: Ponderador 0</h4>",false);
					continue;
			}
			debug("<h4> - Ponderador $medio: ".$this->ponderadores[$medio]."</h4>",false);
			
			$puntajesExcepcion = 0;
			foreach($indicadoresmedio as $indicador){
				showMessage("Calculando el indicador <b>{$indicador->getInfo()['nombre']}</b> de <b>{$this->operador->nombreCompleto()}</b> del mes <b>$mes-$año</b><br>");
				try
				{
					// $this->notasMedio[$indicador->getInfo()['medio']] += $indicador->NotaObtenidaRandom();
					$nota_obtenida = $indicador->NotaObtenida($this->operador,$mes,$año);
					
					if($nota_obtenida === false)
					{
						debug("Error calculando el indicador <b>{$indicador->getInfo()['nombre']}</b> de <b>{$this->operador->nombreCompleto()}</b>: <i>{$indicador->ultimoError()}</i>");
						//exit;
					}
					
					$this->notasIndicadores[$indicador->getInfo()['id']] = $nota_obtenida;
					$this->notasMedio[$indicador->getInfo()['medio']] += $nota_obtenida;


				}
				catch(Exception $e)
				{
					if(strpos($e->getMessage(), "[[No Considerar]]") !== false)
					{
						$puntajesExcepcion += $indicador->getInfo()["ponderacion"];
						debug("Entro a [[No Considerar]] en {$indicador->getInfo()["nombre"]}: Se reparten sus {$indicador->getInfo()["ponderacion"]} puntos a los otros</div>");
						$this->notasIndicadores[$indicador->getInfo()['id']] = false;
					}
					else
						exit($e->getMessage());
				}
			}
			//recalcula las notas de los indicadores del medio considerando la excepciones
			$factor = 100/(100-$puntajesExcepcion);
			if($factor != 1)
			{
				debug("Multiplicando nota de medio ".$indicador->getInfo()['medio']." por factor $factor");  
				$this->notasMedio[$indicador->getInfo()['medio']] = round($this->notasMedio[$indicador->getInfo()['medio']]*$factor,0);
			}
			foreach($indicadoresmedio as $indicador)
				if($this->notasIndicadores[$indicador->getInfo()['id']] !== false)
				{
					if($factor != 1)
					{
						debug("Multiplicando ".$indicador->getInfo()['nombre']." por factor $factor ($puntajesExcepcion)");  
						$nota_nueva = round($this->notasIndicadores[$indicador->getInfo()['id']]*$factor,0);
						$this->notasIndicadores[$indicador->getInfo()['id']] = $nota_nueva;
						//hago update al registro de la BD
						$database = DBManager::singleton();
						$query = "UPDATE historial_indicadores SET nota = nota * $factor
									WHERE mes = $mes AND año = $año 
									AND id_indicador = {$indicador->getInfo()['id']} 
									AND id_operador =  {$this->operador->getId()}";
						$resultado = $database->execQuery($query);
					}
				}
		}
		
		foreach($this->notasMedio as $medio => $nota)
			$this->notaFinal += $nota * $this->ponderadores[$medio];
		
		if(!$this->guardarPonderadores($mes,$año))
			exit("Error al guardar registros de ponderadores en la BD");

		if(!$this->guardarNotaFinal(round($this->notaFinal,2),$mes,$año))
			exit("Error al guardar nota final en la BD");
		
		return round($this->notaFinal,2);
	}

	private function calcularPonderadoresTmp($mes,$año){
		$this->ponderadores = array('chat' => 1/3, 'ticket' => 1/3, 'telefono' => 1/3);
	}
	/*Calcula los ponderadores para el operador, segun los turnos*/
	//TODO: implementar
	private function calcularPonderadores($mes,$año){
		showMessage("Calculando ponderadores de {$this->operador->nombreCompleto()} para el $mes-$año");
		$database = DBManager::singleton();
		//seteo los ponderadores en 0
		$this->ponderadores = array();
		$medios_validos = "";
		foreach ($this->medios as $medio)
		{
			$medios_validos .= "'$medio',";
			$this->ponderadores[$medio] = 0;
		}
		if(sizeof($this->medios) == 1)
			$this->ponderadores[$this->medios[0]] = 1;
		$medios_validos = substr($medios_validos, 0,-1);

		//obtengo el total de tiempo dedicado por el operador entre todos sus turnos del mes
		//falla en caso que un cliente tenga tres turnos, del mismo medio en exactamente el mismo horario y fecha....ahi cuenta N-1 turnos como v?idos (en vez de solo uno)
		$query = "SELECT SUM(TIMESTAMPDIFF(SECOND, T.inicio, T.fin)) AS tiempo_dedicado, M.nombre 
					FROM turnos T LEFT JOIN medios M
					ON T.medio = M.id
					WHERE id_operador = {$this->operador->getId()} AND MONTH(inicio) = $mes AND YEAR(inicio) = $año 
					AND T.id NOT IN(
						SELECT T1.id FROM turnos T1 LEFT JOIN turnos T2 
						ON T1.id_operador = {$this->operador->getId()} AND T1.id_operador = T2.id_operador AND T1.medio = T2.medio AND MONTH(T1.inicio) = $mes AND YEAR(T1.inicio) = $año
						WHERE T1.id <> T2.id  AND (T1.inicio >= T2.inicio AND T2.fin < T2.fin) OR (T1.inicio > T2.inicio AND T1.fin <= T2.fin)
						UNION
						SELECT T1.id FROM turnos T1 LEFT JOIN turnos T2 
						ON T1.id_operador = {$this->operador->getId()} AND T1.id_operador = T2.id_operador AND T1.medio = T2.medio AND MONTH(T1.inicio) = $mes AND YEAR(T1.inicio) = $año
						WHERE T1.id <> T2.id AND T1.inicio = T2.inicio AND T1.fin = T2.fin
						GROUP BY T1.id_operador,T1.medio,T1.inicio,T1.fin
					)
					AND M.nombre IN ($medios_validos)
				";
		$resultado = $database->execQuery($query);
		$tiempo_dedicado_total = mysqli_fetch_array($resultado,MYSQLI_ASSOC)["tiempo_dedicado"];
		//obtengo el tiempo dedicado por medio
		$query .= "GROUP BY T.medio";
		$resultado = $database->execQuery($query);
		while($tiempo_dedicado_medio = mysqli_fetch_array($resultado,MYSQLI_ASSOC))
		{
			$medio = $tiempo_dedicado_medio["nombre"];
			$tiempo_dedicado = $tiempo_dedicado_medio["tiempo_dedicado"];
			//seteo el ponderador segun la formula tiempo_medio/tiempo_total
			$this->ponderadores[$medio] = $tiempo_dedicado/$tiempo_dedicado_total;
		}

		// foreach ($this->medios as $medio)
		// 	debug("Ponderador $medio: ".$this->ponderadores[$medio]);
	}
	
	/*Revisa si existen registros para el operador para la fecha en cuestion*/
	private function existenRegistros($mes,$año){
		$database = DBManager::singleton();
		$id_operador = $this->operador->getId();
		//$query = "SELECT max(CONCAT(año,"-",mes)) AS fecha FROM historial WHERE id_operador = $id_operador";
		$query = "SELECT * FROM historial_indicadores WHERE id_operador = $id_operador AND año = $año AND mes = $mes";
		debug($query);
		$resultado = $database->execQuery($query);
		return mysqli_num_rows($resultado)>0;
	}
	
	/*Guarda el registro para el operador, par el indicador en la fecha indicada*/
	private function guardarPonderadores($mes,$año){
		$database = DBManager::singleton();
		$success = true;
		
		//Guardar los ponderadores en el historial
		foreach ($this->ponderadores as $medio => $ponderacion) {
			$query = "INSERT INTO historial_ponderadores (`id_operador`, `medio`, `mes`, `año`, `ponderador`) 
							VALUES ('".$this->operador->getId()."', '$medio', '$mes', '$año', '$ponderacion')"; 
							//ON DUPLICATE KEY UPDATE ponderador='$ponderacion';";
			$resultado = $database->execQuery($query);
			$success = $success && $resultado;
		}
		
		return $success;
	}

	/*Guarda la nota final del operador*/
	private function guardarNotaFinal($nota,$mes,$año){
		$database = DBManager::singleton();
		$success = true;
		
		//Guardar la nota final 
		$query = "INSERT INTO notas_finales (`id_operador`, `mes`, `año`, `nota_final`) 
						VALUES ('".$this->operador->getId()."', '$mes', '$año', '$nota')";
		return $database->execQuery($query);
	}
	
}

?>