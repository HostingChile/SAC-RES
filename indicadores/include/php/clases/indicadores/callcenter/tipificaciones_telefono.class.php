<?php

class tipificaciones_telefono extends Indicador
{
	//Las tipificaciones de teléfono son TIPO 2 en el SAC

    protected function calcularIndicador($operador,$mes,$año) 
    {
    	$asteriskManager = AsteriskManager::singleton();

        if($mes >= 2 && $año == 2017){
            return array("tipificaciones" => 100, "contestadas" => 100);
        }
        
        if($operador->isJefeDeArea())
        {

            //obtengo el id del operador
            $query = "SELECT * FROM Operadores WHERE nombre = '".$operador->getNombre()."' AND apellido = '".$operador->getApellido()."'";
            $result = $asteriskManager->execQueryASTDB($query);
            if(mysqli_num_rows($result) != 1)
                return $this->setError("Error al obtener el id fop del operador ".$operador->nombreCompleto());
            $id_operador = mysqli_fetch_array($result,MYSQL_ASSOC)["id_fop"];

            //obtengo el total de llamados recibidos por la empresa
            $llamadas_contestadas = $asteriskManager->llamadasRecibidasSinOperadores($mes,$año,array("$id_operador"));
            if($llamadas_contestadas == false)
                return $this->setError("La empresa no tiene llamadas contestadas");
           
            //obtengo el total de tipificaciones telefonicas
            $SACManager = SACManager::singleton();
            $query = "SELECT COUNT(*) as tipificaciones FROM Tipificacion  
                WHERE YEAR(fecha) = $año AND MONTH(fecha) = $mes AND operador != '".$operador->nombreCompleto()."' AND id_tipocontacto = 2";
            $result = $SACManager->execQuery($query);
            if( mysqli_num_rows($result) != 1)
                return $this->setError("Error al obtener tipificaciones telefonicas de la empresa");
            $tipificaciones = mysqli_fetch_array($result,MYSQL_ASSOC)["tipificaciones"];
        }
        else
        {
            //obtengo el id del operador
            $query = "SELECT * FROM Operadores WHERE nombre = '".$operador->getNombre()."' AND apellido = '".$operador->getApellido()."'";
            $result = $asteriskManager->execQueryASTDB($query);
            if( mysqli_num_rows($result) != 1)
                return $this->setError("Error al obtener el id fop del operador ".$operador->nombreCompleto());
            $id_operador = mysqli_fetch_array($result,MYSQL_ASSOC)["id_fop"];

            //obtengo el total de llamados recibidos por el operador
        	$llamadas_contestadas = $asteriskManager->llamadasOperador($id_operador,$mes,$año);
            if($llamadas_contestadas == false)
                return $this->setError("El operador ".$operador->nombreCompleto()." no tiene llamadas contestadas");
           
            //obtengo el total de tipificaciones telefonicas del operador
            $SACManager = SACManager::singleton();
            $query = "SELECT COUNT(*) as tipificaciones FROM Tipificacion 
            	WHERE YEAR(fecha) = $año AND MONTH(fecha) = $mes AND operador = '".$operador->nombreCompleto()."' AND id_tipocontacto = 2";
            $result = $SACManager->execQuery($query);
            if( mysqli_num_rows($result) != 1)
                return $this->setError("Error al obtener tipificaciones telefonicas del operador ".$operador->nombreCompleto());
            $tipificaciones = mysqli_fetch_array($result,MYSQL_ASSOC)["tipificaciones"];
        }

        

		$porcentaje_tipificacion = ( $tipificaciones / $llamadas_contestadas ) * 100; 
		return array("tipificaciones" => $tipificaciones, "contestadas" => $llamadas_contestadas);
    }

	//array([0] => array('tipificaciones' => X, 'contestadas' => Y), [1] => array (...)
    protected function evaluarIndicador($data)
    {
		$total_llamados_a_tipificar = 0;
		$total_llamados_tipificados = 0;
		
		foreach($data as $mes){
			$total_llamados_a_tipificar += $mes['contestadas'];
			$total_llamados_tipificados += $mes['tipificaciones'];
		}
		
		if(!$total_llamados_a_tipificar)
			return $this->setError('No hay llamadas recibidas');
		
		return ($total_llamados_tipificados / $total_llamados_a_tipificar) * 100;	
    }
}

?>