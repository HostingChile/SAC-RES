<?php


class AsteriskManager
{
	private static $instancia;
	private $link_astdb;
	private $link_encuestas;
	/*Constructor vacio*/
	private function __construct()
	{	
		try{
			$this->link_astdb = mysqli_connect("192.168.110.254", "desarrollo", "Gaspar.,12", "asteriskcdrdb");
			/* Comprobar la conexión */
			if (!$this->link_astdb) {
			    echo "Error: Unable to connect to MySQL." . PHP_EOL;
			    echo "Debugging errno: " . mysqli_connect_errno() . PHP_EOL;
			    echo "Debugging error: " . mysqli_connect_error() . PHP_EOL;
			    exit;
			}
		}catch(Exception $e){
			exit("Error al conectarse a la BD de Asterisk: ".$e->getMessage());
		}
	}

	/*Singleton*/
	public static function singleton(){
		if ( !self::$instancia instanceof self)
			self::$instancia = new self;
		return self::$instancia;
	}
	
	/*Protege el singleton de la clonación*/
	public function __clone(){
		trigger_error("Operación Invalida: No puedes clonar una instancia de ". get_class($this) ." class.", E_USER_ERROR );
	}
	
	/*Protege el singleton de la deserializacion*/
	public function __wakeup(){
		trigger_error("Operación Invalida: No puedes deserializar una instancia de ". get_class($this) ." class.");
	}

	public function execQueryASTDB($query)
	{
		return mysqli_query($this->link_astdb,$query);
	}

	public function execQueryEncuestas($query)
	{
		return mysqli_query($this->link_encuestas,$query);
	}

	function llamadasRecibidasEmpresa($mes,$año)
	{
		// $query = "SELECT SUBSTR(dstchannel,5,3) as agente, count(*) as cantidad FROM cdr 
  //               WHERE MONTH(end) = $mes AND YEAR(end) = $año
  //               AND disposition = 'ANSWERED'
  //               AND dstchannel != '' 
  //               AND dcontext != 'interno'
  //               AND dcontext != 'from-pstn'
  //               AND lastapp = 'Queue'";

        $query = "SELECT 
        				MAX(C.uniqueid) AS last_uniqueid, 
                        SUBSTRING_INDEX(GROUP_CONCAT(dst ORDER BY calldate DESC), ',', 1) AS extension,
                        C.* 
                    FROM cdr C WHERE 
                        recordingfile != '' AND 
                        (dst LIKE '5__' OR cnum LIKE '5__') AND 
                        disposition = 'ANSWERED' AND 
                        MONTH(calldate) = $mes AND 
                        YEAR(calldate) = $año 
                    GROUP BY recordingfile 
                    ORDER BY calldate DESC";

        $result = $this->execQueryASTDB($query);
        // $llamadas_contestadas = mysqli_fetch_array($result,MYSQL_ASSOC)["cantidad"];
        $llamadas_contestadas = sizeof(mysqli_fetch_array($result,MYSQL_ASSOC));
        if(is_null($llamadas_contestadas))
            return false;
        return $llamadas_contestadas;
	}

	function llamadasOperador($id_fop,$mes,$año)
	{
		$query = "SELECT SUBSTR(dstchannel,5,3) as agente, count(*) as cantidad FROM cdr 
                WHERE MONTH(end) = $mes AND YEAR(end) = $año
                AND disposition = 'ANSWERED'
                AND dstchannel != '' 
                AND dcontext != 'interno'
                AND dcontext != 'from-pstn'
                AND src != ''
                AND lastapp = 'Queue'
                AND  SUBSTR(dstchannel,5,3) = $id_fop";
        $result = $this->execQueryASTDB($query);
        $llamadas_contestadas = mysqli_fetch_array($result,MYSQL_ASSOC)["cantidad"];
        if(is_null($llamadas_contestadas))
            return false;
        return $llamadas_contestadas;
	}

	function llamadasOperadorTransferencia($id_fop,$mes,$año)
	{

		$query = "SELECT SUBSTR(dstchannel,5,3) as agente, count(*) as cantidad FROM cdr 
                WHERE MONTH(end) = $mes AND YEAR(end) = $año
                AND disposition = 'ANSWERED'
                AND dstchannel != '' 
                AND dcontext != 'interno'
                AND dcontext != 'from-pstn'
                AND src != ''
                AND lastapp = 'Queue'
                AND  SUBSTR(dstchannel,5,3) = $id_fop";

        $result = $this->execQueryASTDB($query);
        $llamadas_contestadas = mysqli_fetch_array($result,MYSQL_ASSOC)["cantidad"];

		/*$query = "SELECT SUBSTR(dstchannel,5,3) as agente, count(*) as cantidad FROM cdr 
                WHERE MONTH(end) = $mes AND YEAR(end) = $año
                AND disposition = 'ANSWERED'
                AND dstchannel != '' 
                AND dcontext != 'interno'
                AND dcontext != 'from-pstn'
                AND (dcontext LIKE 'hosting%' OR dcontext LIKE 'planeta%')
                AND src != ''
                AND lastapp = 'Queue'
                AND  SUBSTR(dstchannel,5,3) = $id_fop";*/
        
        
        //cuento para transferencia solo las llamadas de planeta y hosting hasta el 15-05-2015
                /*
		$query = "SELECT SUBSTR(dstchannel,5,3) as agente, count(*) as cantidad FROM cdr 
	                WHERE MONTH(end) = $mes AND YEAR(end) = $año
	                AND disposition = 'ANSWERED'
	                AND dstchannel != '' 
	                AND dcontext != 'interno'
	                AND dcontext != 'from-pstn'
	                AND (dcontext LIKE 'hosting%' OR dcontext LIKE 'planeta%')
					AND end < DATE('2015-05-16')
	                AND src != ''
	                AND lastapp = 'Queue'
	                AND  SUBSTR(dstchannel,5,3) = $id_fop";

	  	$result = $this->execQueryASTDB($query);
	  	$llamadas_contestadas_temp = mysqli_fetch_array($result,MYSQL_ASSOC)["cantidad"];
	  	if(!is_null($llamadas_contestadas_temp))
	  		$llamadas_contestadas = $llamadas_contestadas_temp;

		$QUERY = "SELECT SUBSTR(dstchannel,5,3) as agente, count(*) as cantidad FROM cdr 
	                WHERE MONTH(end) = $mes AND YEAR(end) = $año
	                AND disposition = 'ANSWERED'
	                AND dstchannel != '' 
	                AND dcontext != 'interno'
	                AND dcontext != 'from-pstn'
					AND end > DATE('2015-05-15')
	                AND src != ''
	                AND lastapp = 'Queue'
	                AND  SUBSTR(dstchannel,5,3) = $id_fop";
        $result = $this->execQueryASTDB($query);
        $llamadas_contestadas_temp = mysqli_fetch_array($result,MYSQL_ASSOC)["cantidad"];
	  	if(!is_null($llamadas_contestadas_temp))
	  		$llamadas_contestadas += $llamadas_contestadas_temp;
	  		*/

        if(is_null($llamadas_contestadas))
            return false;

        return $llamadas_contestadas;
	}

	public function __destruct()
	{
		mysqli_close($this->link_astdb);
	}
}




?>