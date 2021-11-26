<?php


class clientes_cambian_competencia extends Indicador
{
    public function calcularIndicador($operador,$mes,$año) 
    {
       	$asteriskManager = AsteriskManager::singleton();
        //obtengo el id del operador
        $query = "SELECT * FROM Operadores WHERE nombre = '".$operador->getNombre()."' AND apellido = '".$operador->getApellido()."'";
        $result = $asteriskManager->execQueryASTDB($query);
        if(mysqli_num_rows($result) != 1)
            return $this->setError("Error al obtener el id fop del operador ".$operador->nombreCompleto());

        $id_operador = mysqli_fetch_array($result,MYSQL_ASSOC)["id_fop"];

        //obtengo el total de llamados recibidos por el operador
    	$llamadas_contestadas = $asteriskManager->llamadasOperador($id_operador,$mes,$año);
        if($llamadas_contestadas === false)
            return $this->setError("El operador ".$operador->nombreCompleto()." no tiene llamadas contestadas");
       
        //obtengo el total de transferencias telefonicas del operador (solo las )
        $query = "SELECT COUNT(*) as transferencias FROM transferencias
        	WHERE YEAR(fecha) = $año AND MONTH(fecha) = $mes AND operador = '$id_operador' AND origen <> 'interno'";
        $result = $asteriskManager->execQueryEncuestas($query);
        if( mysqli_num_rows($result) == NULL)
            return $this->setError("Error al obtener transferencias telefonicas del operador ".$operador->nombreCompleto());

        $transferencias = mysqli_fetch_array($result,MYSQL_ASSOC)["transferencias"];

        return array("transferencias" => $transferencias, "contestadas" => $llamadas_contestadas);
    }

	//array([0] => array('transferencias' => X, 'contestadas' => Y), [1] => array (...)
    protected function evaluarIndicador($data)
    {
        $total_transferencias = 0;
		$total_llamados_contestados = 0;
		
		foreach($data as $mes){
			$total_transferencias += $mes['transferencias'];
			$total_llamados_contestados += $mes['contestadas'];
		}
		
		if(!$total_llamados_contestados)
			return $this->setError('No hay llamadas recibidas');
		
		return ($total_transferencias / $total_llamados_contestados) * 100;
    }
}

?>