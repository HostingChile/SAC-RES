<?php


class nota_telefono extends Indicador
{
    protected function calcularIndicador($operador,$mes,$año) 
    {
        $asteriskManager = AsteriskManager::singleton();

        if($mes >= 2 && $año == 2017){
            return array('nota_amabilidad' => 4, 'nota_eficiencia' => 4, 'encuestas_recibidas' => 100);
        }

        if($operador->isJefeDeArea())
        {

            //obtengo el id del operador
            $query = "SELECT * FROM Operadores WHERE nombre = '".$operador->getNombre()."' AND apellido = '".$operador->getApellido()."'";
            $result = $asteriskManager->execQueryASTDB($query);
            if(mysqli_num_rows($result) != 1)
                return $this->setError("Error al obtener el id fop del operador ".$operador->nombreCompleto());
            $id_operador = mysqli_fetch_array($result,MYSQL_ASSOC)["id_fop"];

            $condicion_agente = "agente != '$id_operador' AND ";

             //obtengo la cantidad de notas obtenidas
            $query = "SELECT count(*) as totalencuestas FROM encuestas 
                        WHERE YEAR(DATE) = $año AND MONTH(DATE) = $mes AND agente != '$id_operador'";
            $result = $asteriskManager->execQueryEncuestas($query);
            $encuestas_recibidas = mysqli_fetch_array($result,MYSQL_ASSOC)["totalencuestas"];
            if(is_null($encuestas_recibidas))
                return $this->setError("Error al obtener encuestas recibidas del operador ".$operador->nombreCompleto());
        }
        else
        {
            //obtengo el id del operador
            $query = "SELECT * FROM Operadores WHERE nombre = '".$operador->getNombre()."' AND apellido = '".$operador->getApellido()."'";
            $result = $asteriskManager->execQueryASTDB($query);
            if( mysqli_num_rows($result) != 1)
                return $this->setError("Error al obtener el id fop del operador ".$operador->nombreCompleto());
            $id_operador = mysqli_fetch_array($result,MYSQL_ASSOC)["id_fop"];
            
            $condicion_agente = "agente = '$id_operador' AND ";

            //obtengo las llamadas contestadas por el operador
            $llamadas_contestadas = $asteriskManager->llamadasOperador($id_operador,$mes,$año);
            if($llamadas_contestadas == false)
                return $this->setError("El operador ".$operador->nombreCompleto()." no tiene llamadas contestadas");
           
            //obtengo la cantidad de notas obtenidas
            $query = "SELECT count(*) as totalencuestas FROM encuestas 
                        WHERE agente = '$id_operador' AND YEAR(DATE) = $año AND MONTH(DATE) = $mes";
            $result = $asteriskManager->execQueryEncuestas($query);
            $encuestas_recibidas = mysqli_fetch_array($result,MYSQL_ASSOC)["totalencuestas"];
            if(is_null($encuestas_recibidas))
                return $this->setError("Error al obtener encuestas recibidas del operador ".$operador->nombreCompleto());
            
            //Si es que notas obtenidas < 10% llamadas contestadas && notas obtenidas < 10, o no se reciben llamadas, este indicador no cuenta y se reparte entre los otros
            if(($encuestas_recibidas < 10 && ($encuestas_recibidas < 0.1*$llamadas_contestadas)) || !$llamadas_contestadas) {
                $message = "[[No Considerar]] El operador ".$operador->nombreCompleto()." tiene $llamadas_contestadas llamadas y solamente $encuestas_recibidas encuestas";
                $this->setError($message);
                throw new Exception($message);
            }
        }


        //Obtengo la suma de las encuestas para el mes y año del operador recien obtenido
        $query = "SELECT AVG(nota1) as amabilidad, AVG(nota2) as eficiencia, AVG(nota1*0.3+nota2*0.7) as notafinal FROM encuestas 
        			WHERE $condicion_agente YEAR(DATE) = $año AND MONTH(DATE) = $mes";
					
		$result = $asteriskManager->execQueryEncuestas($query);
		$row = mysqli_fetch_array($result,MYSQL_ASSOC);
        $notafinal = $row["notafinal"];
        if(is_null($notafinal))
           return $this->setError("No hay encuestas telefonicas");
		   
		$nota_amabilidad = $row["amabilidad"];
		$nota_eficiencia = $row["eficiencia"];

        $nota_encuesta = 0.3 * $nota_amabilidad + 0.7 * $nota_eficiencia;
			
		return array('nota_amabilidad' => $nota_amabilidad, 'nota_eficiencia' => $nota_eficiencia, 'encuestas_recibidas' => $encuestas_recibidas);
    }
    
    //array([0] => array('nota_amabilidad' => X, 'nota_eficiencia' => Y, 'encuestas_recibidas' => Z), [1] => array (...)
	protected function evaluarIndicador($data) {
		$total_encuestas_recibidas = 0;
		$total_amabilidad = 0;
		$total_eficiencia = 0;
		
		foreach($data as $mes){
			$total_encuestas_recibidas += $mes['encuestas_recibidas'];
			$total_amabilidad += $mes['encuestas_recibidas'] * $mes['nota_amabilidad'];
			$total_eficiencia += $mes['encuestas_recibidas'] * $mes['nota_eficiencia'];
		}
		
		if(!$total_encuestas_recibidas)
			return $this->setError('No hay encuestas de telefono recibidas');
		
		return (0.3*($total_amabilidad / $total_encuestas_recibidas) + 0.7*($total_eficiencia / $total_encuestas_recibidas));
	}
}

?>