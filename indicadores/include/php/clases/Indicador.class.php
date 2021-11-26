<?php

abstract class Indicador{

	protected $id;
	protected $nombre;
	protected $medio;
    protected $medio_string;//el medio en formato 'chat','ticket' o 'telefono'
	protected $explicacion;
	protected $excepciones;
	protected $ponderacion;
    protected $cantidadDecimales;

	protected $datosIndicador; //valor de los datos necesarios para calcularlo, obtenido por calcularIndicador();
	protected $valorIndicador; //valor final, calculado por evaluarIndicador();
	protected $nota;
	
	protected $last_error;

	protected $error_log;

	//Calcula el resultado del indicador, devolviendo un arreglo con los datos necesarios para evaluarlo
    abstract protected function calcularIndicador($operador,$mes,$año);
    
	//Evalua el resultado del indicador a partir de los datos obtenidos
	abstract protected function evaluarIndicador($data);
	
    public function __construct($data_row) {
    	$this->id = $data_row["id"];
		$this->nombre = $data_row["nombre"];
		$this->medio = $data_row["medio"];
        //obtengo el string del medio
        $database = DBManager::singleton();
        $query = "SELECT * FROM medios WHERE id = ".$this->medio;
        $result = $database->execQuery($query);
        $data = mysqli_fetch_array($result,MYSQLI_ASSOC);
        $this->medio_string = $data["nombre"];
        //
		$this->explicacion = $data_row["explicacion"];
		$this->excepciones = $data_row["excepcion"];
		$this->ponderacion = $data_row["ponderacion"];
        $this->cantidadDecimales = $data_row["cantidad_decimales"];
    }

	final public function getInfo(){
    	return array('id' => $this->id, 
    				'nombre' => $this->nombre,
    				'medio' => $this->medio_string ,
    				'explicacion' => $this->explicacion,
    				'excepciones' => $this->excepciones,
    				'ponderacion' => $this->ponderacion,
                    'cantidadDecimales' => $this->cantidadDecimales
    				);
    }

    final public function getValorIndicador(){
        return $this->valorIndicador;
    }

     final public function getDatosIndicador(){
        return $this->datosIndicador;
    }

    //Retorna el puntaje obtenido para el indicador. Tomando el valor del indicador y los rangos definidos.
    final public function notaObtenida($operador,$mes,$año){
        $this->setError("");
		$this->valorIndicador = array();
		debug("<div style='margin-left:20px;padding:5px;border:2px solid gray;border-radius:5px;'>",false);
		debug("<b>{$this->nombre}</b>");
    	$this->datosIndicador = $this->calcularIndicador($operador,$mes,$año);		
		
		if($this->datosIndicador === false)
		{
			debug("datosIndicador es false</div>");
			return false;
		}

		$this->valorIndicador = $this->evaluarIndicador(array($this->datosIndicador));

		debug("<div style='padding:5px;display:inline-block;background-color:rgb(223, 223, 223)'>",false);
		foreach ($this->datosIndicador as $key => $value)
			debug("$key = $value");
		debug("<b>Valor: {$this->valorIndicador}</b></div>");
		debug("</div>");

		if($this->valorIndicador === false)
			return false;
			
        $this->valorIndicador = round($this->valorIndicador,$this->cantidadDecimales);
		
		//Obtener los rangos desde la BD
    	$database = DBManager::singleton();
    	$query = "SELECT id,id_indicador,limite_inferior,limite_superior,nota FROM rangos WHERE id_indicador = {$this->id}";
        $result = $database->execQuery($query);
    	if(mysqli_num_rows($result) == 0){
			$this->last_error = 'No se pudieron obtener los rangos desde la BBDD para el indicador';
			return false;
		}
    	while(list($id,$id_indicador,$limite_inferior,$limite_superior,$nota) = mysqli_fetch_array($result,MYSQLI_NUM)){
    		if($this->valorIndicador >= $limite_inferior && $this->valorIndicador <= $limite_superior){
    			debug("Nota:{$this->valorIndicador} -> Rango $limite_inferior-$limite_superior");
				$this->nota = $nota;
                break;
            }
    	}
		
		//Guardar los datos en el historial
		if(!$this->guardarValoresIndicador($operador,$mes,$año))
		{
			$this->last_error = 'Error al guardar valor del indicador en la BBDD';
			return false;
		}

		
    	return $this->nota * $this->ponderacion/100;
    }
	
	//Retorna un valor al azar v?ido para el indicador 
	final public function calcularIndicadorRandom(){
		$database = DBManager::singleton();
    	$query = "SELECT MIN(limite_inferior), MAX(limite_superior) FROM rangos WHERE id_indicador = {$this->id}";
        $result = $database->execQuery($query);
		if(mysqli_num_rows($result) != 1)
			return false;
		
		list($min,$max) = mysqli_fetch_array($result,MYSQLI_NUM);
		
		return mt_rand($min,$max);
	}
	
	//Retorna el puntaje obtenido para el indicador. Tomando el valor del indicador GENERADO AL AZAR y los rangos definidos.
	final public function notaObtenidaRandom(){
    	$this->valorIndicador = $this->calcularIndicadorRandom();
    	//Obtener los rangos desde la BD
    	$database = DBManager::singleton();
    	$query = "SELECT id,id_indicador,limite_inferior,limite_superior,nota FROM rangos WHERE id_indicador = {$this->id}";
        $result = $database->execQuery($query);
    	if(mysqli_num_rows($result) == 0)
    		return false;
    	while(list($id,$id_indicador,$limite_inferior,$limite_superior,$nota) = mysqli_fetch_array($result,MYSQLI_NUM)){
    		if($this->valorIndicador >= $limite_inferior && $this->valorIndicador <= $limite_superior){
    			$this->nota = $nota;
            }
    	}
    	return $this->nota * $this->ponderacion/100;
    }

	//Devuelve el ultimo error
	final public function ultimoError(){
		return $this->last_error;
	} 

    protected function setError($error){
        $this->last_error = $error;
        return false;
    }

	final private function guardarValoresIndicador($operador,$mes,$año){
		$database = DBManager::singleton();
		$success = true;
		
		//$data = utf8_encode($data);
		$data = str_replace("'", "´", json_encode($this->datosIndicador));
		

		//Guardo los registros de la nota de los indicadores en la BD
		$query = "INSERT INTO historial_indicadores (id_operador, id_indicador, mes, año, nota, datos) 
					VALUES ('".$operador->getId()."', '{$this->id}', '$mes', '$año', '{$this->nota}','$data')";
					//ON DUPLICATE KEY UPDATE nota = '{$this->nota}', datos = '$data';";
		//debug($query);
		$resultado = $database->execQuery($query);
		if(!$resultado)
			debug($query);
		$success = $success && $resultado;

		return $success;
	}

	//Retorna TRUE si es un d? que NO SE DEBE CONSIDERAR. Adem? pobla la variable $message con los distintos motivos de por qu?no se considera.
	//Todos los operadores = -1
	//Todas las ?eas = -1
	//Todos los medios = -1
	//Todas las marcas = 'Todas'
	final protected function diaValido($datetime,$brand,$operador){
		$message = '';
		
		$datetime = strtotime($datetime);
		$fecha_dia_consultado = date('Y-m-d H:i:s', $datetime);
		
		$database = DBManager::singleton();
		$id_operador = $operador->getId();
		$id_area = $operador->getIdArea();
		
		$query = "SELECT IFNULL(CONCAT(O.nombre,' ',O.apellido),'Todos') as operador,
					CONCAT(D.comentario,' ( ',D.inicio,' - ', D.fin,' )') as comentario, 
					IFNULL(M.nombre, 'Todos') AS medio, 
					IFNULL(A.nombre, 'Todas') AS area, 
					IFNULL(D.marca, 'Todas') AS marca 
				FROM dias_invalidos D 
				LEFT JOIN medios M ON D.id_medio = M.id 
				LEFT JOIN areas A ON D.id_area = A.id
				LEFT JOIN operadores O ON D.id_operador = O.id
				WHERE 
				(D.id_operador = $id_operador OR D.id_operador = -1) AND
				(D.id_area = $id_area OR D.id_area = -1) AND
				(D.id_medio = {$this->medio} OR D.id_medio = -1) AND
				(D.marca = '$brand' OR D.marca = 'Todas') AND
				'$fecha_dia_consultado' BETWEEN D.inicio AND D.fin;";			
		
		$result_mensajes = $database->execQuery($query);
		
		while($row = mysqli_fetch_array($result_mensajes, MYSQLI_ASSOC))
			$message .= "{$row['comentario']}. <b>Operador:</b> {$row['operador']}. <b>Area:</b> {$row['area']}. <b>Medio:</b> {$row['medio']}. <b>Marca:</b> {$row['marca']}.";
			
		if($message != ''){
			debug("No se considera el dia $fecha_dia_consultado por: $message");
			return false;
		}
			
		return true;
	}

	final protected function enColacion($datetime,$operador)
	{
		$datetime = strtotime($datetime);
		$fecha_dia_consultado = date('Y-m-d H:i:s', $datetime);
		
		$database = DBManager::singleton();
		$id_operador = $operador->getId();

		$query = "SELECT * from colaciones where id_operador = $id_operador and '$fecha_dia_consultado' BETWEEN inicio AND fin";
		$result = $database->execQuery($query);

		$colaciones = mysqli_num_rows($result);
		if($colaciones>1)
		{
			debug("HAY DOS COLACIONES QUE CALZAN ($fecha_dia_consultado)");
			return false;
		}
		return $colaciones == 1;


	}
}

?>