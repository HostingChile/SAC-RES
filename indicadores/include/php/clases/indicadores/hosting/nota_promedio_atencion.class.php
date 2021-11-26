<?php


class nota_promedio_atencion extends Indicador
{
    protected function calcularIndicador($operador,$mes,$año) 
    {
        $ISCOLManager = ISCOLManager::singleton();
        $marca = $operador->getNombre();
        if($marca != "Todas")
        {
        	$message = "[[No Considerar]] No se calcula para marcas particulares";
			$this->setError($message);
            throw new Exception($message);
        }
            

        $meses = array(1 => "Enero", 2 => "Febrero", 3 => "Marzo",
                    4 => "Abril", 5 => "Mayo", 6 => "Junio",
                    7 => "Julio", 8 => "Agosto", 9 => "Septiembre",
                    10 => "Octubre", 11 => "Noviembre", 12 => "Diciembre");
        $fecha = $meses[$mes]."-".$año;
        //obtengo las notas totales del ISCOL para el mes actual
        $query = "SELECT amabilidad_total, eficiencia_total, cantidad_total FROM calificaciones WHERE operador = 'TOTAL' AND fecha = '$fecha'";
        $result = $ISCOLManager->execQuery($query);
        if( mysqli_num_rows($result) != 1)
            return $this->setError("Error al obtener las notas del ISCOL");
        $row = mysqli_fetch_array($result,MYSQL_ASSOC);
		   
		$nota_amabilidad = $row["amabilidad_total"];
		$nota_eficiencia = $row["eficiencia_total"];
        $encuestas_recibidas = $row["cantidad_total"];

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
			return $this->setError('No hay encuestas recibidas');
		
		return (0.3*($total_amabilidad / $total_encuestas_recibidas) + 0.7*($total_eficiencia / $total_encuestas_recibidas));
	}
}

?>