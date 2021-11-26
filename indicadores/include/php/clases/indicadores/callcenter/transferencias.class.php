<?php


class transferencias extends Indicador
{
    public function calcularIndicador($operador,$mes,$año) 
    {
       	$asteriskManager = AsteriskManager::singleton();

        if($mes >= 2 && $año == 2017){
            return array("transferencias" => 100, "contestadas" => 100, "contactos_fallidos" => 0);
        }
        
        if($operador->isJefeDeArea())
        {
             //obtengo el id del operador
            $query = "SELECT * FROM Operadores WHERE nombre = '".$operador->getNombre()."' AND apellido = '".$operador->getApellido()."'";
            $result = $asteriskManager->execQueryASTDB($query);
            if(mysqli_num_rows($result) != 1)
                return $this->setError("Error al obtener el id fop del operador ".$operador->nombreCompleto());

            $id_operador = mysqli_fetch_array($result,MYSQL_ASSOC)["id_fop"];

            $llamadas_contestadas = $asteriskManager->llamadasRecibidasSinOperadores($mes,$año,array("$id_operador"));
            if($llamadas_contestadas == false)
                return $this->setError("La empresa no tiene llamadas contestadas");

            //veo las tipificaciones de contacto fallido de telefono
            $SACManager = SACManager::singleton();
            $query = "SELECT COUNT(*) as tipificaciones_fallido FROM cliente C 
                WHERE YEAR(fecha) = $año AND MONTH(fecha) = $mes AND tipollamado = 2 AND C.tipo = 'Contacto Fallido'";
            $result = $SACManager->execQuery($query);
            if( mysqli_num_rows($result) != 1)
                return $this->setError("Error al obtener tipificaciones de contacto fallido telefonicas");
            $tipificaciones_fallido = mysqli_fetch_array($result,MYSQL_ASSOC)["tipificaciones_fallido"];

            //obtengo el total de transferencias telefonicas
            $query = "SELECT COUNT(*) as transferencias FROM transferencias
                WHERE YEAR(fecha) = $año AND MONTH(fecha) = $mes AND origen <> 'interno' AND operador != '$id_operador'";
            $result = $asteriskManager->execQueryEncuestas($query);
            if( mysqli_num_rows($result) == NULL)
                return $this->setError("Error al obtener transferencias telefonicas del operador ".$operador->nombreCompleto());

            $transferencias = mysqli_fetch_array($result,MYSQL_ASSOC)["transferencias"];
        }
        else
        {
             //obtengo el id del operador
            $query = "SELECT * FROM Operadores WHERE nombre = '".$operador->getNombre()."' AND apellido = '".$operador->getApellido()."'";
            $result = $asteriskManager->execQueryASTDB($query);
            if(mysqli_num_rows($result) != 1)
                return $this->setError("Error al obtener el id fop del operador ".$operador->nombreCompleto());

            $id_operador = mysqli_fetch_array($result,MYSQL_ASSOC)["id_fop"];

            //obtengo el total de llamados recibidos por el operador
            $llamadas_contestadas = $asteriskManager->llamadasOperadorTransferencia($id_operador,$mes,$año);
            if($llamadas_contestadas === false)
                return $this->setError("El operador ".$operador->nombreCompleto()." no tiene llamadas contestadas");

            //veo las tipificaciones de contacto fallido de telefono (id 3)
            $SACManager = SACManager::singleton();
            $query = "SELECT COUNT(*) as tipificaciones_fallido FROM Tipificacion
                WHERE YEAR(fecha) = $año AND MONTH(fecha) = $mes AND operador = '".$operador->nombreCompleto()."' AND id_tipocontacto = 2 AND id_categoria = 3";
            $result = $SACManager->execQuery($query);
            if( mysqli_num_rows($result) != 1)
                return $this->setError("Error al obtener tipificaciones de contacto fallido telefonicas del operador ".$operador->nombreCompleto());
            $tipificaciones_fallido = mysqli_fetch_array($result,MYSQL_ASSOC)["tipificaciones_fallido"];

            //obtengo el total de transferencias telefonicas del operador (solo las )
            $query = "SELECT COUNT(*) as transferencias FROM transferencias
                WHERE YEAR(fecha) = $año AND MONTH(fecha) = $mes AND operador = '$id_operador' AND origen <> 'interno'";
            $result = $asteriskManager->execQueryEncuestas($query);
            if( mysqli_num_rows($result) == NULL)
                return $this->setError("Error al obtener transferencias telefonicas del operador ".$operador->nombreCompleto());

            $transferencias = mysqli_fetch_array($result,MYSQL_ASSOC)["transferencias"];
        }

        
       

        return array("transferencias" => $transferencias, "contestadas" => $llamadas_contestadas, "contactos_fallidos" => $tipificaciones_fallido);
    }

	//array([0] => array('transferencias' => X, 'contestadas' => Y), [1] => array (...)
    protected function evaluarIndicador($data)
    {
        $total_transferencias = 0;
		$total_llamados_contestados = 0;
		
		foreach($data as $mes){
			$total_transferencias += $mes['transferencias'];
			$total_llamados_contestados += $mes['contestadas'];
            $total_llamados_contestados -= $mes['contactos_fallidos'];
		}
		
		if(!$total_llamados_contestados)
			return $this->setError('No hay llamadas recibidas');
		
		return ($total_transferencias / $total_llamados_contestados) * 100;
    }
}

?>