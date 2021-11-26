<?php


class AsteriskManager
{
	private static $instancia;
	private $link_astdb;
	private $link_encuestas;
	/*Constructor vacio*/
	private function __construct()
	{	
		$this->link_astdb = mysqli_connect("190.153.249.226", "root", "hosting.,12", "ASTDB");
		/* Comprobar la conexi? */
		if (mysqli_connect_errno()) {
		    printf("Fall?la conexi?: %s\n", mysqli_connect_error());
		    exit();
		}
		if (!mysqli_set_charset($this->link_astdb, "utf8")) {
		    printf("Error loading character set utf8: %s\n", mysqli_error($this->link_astdb));
		    exit();
		}

		$this->link_encuestas = mysqli_connect("190.153.249.226", "root", "hosting.,12", "encuesta");
		/* Comprobar la conexi? */
		if (mysqli_connect_errno()) {
		    printf("Fall?la conexi?: %s\n", mysqli_connect_error());
		    exit();
		}
		if (!mysqli_set_charset($this->link_encuestas, "utf8")) {
		    printf("Error loading character set utf8: %s\n", mysqli_error($this->link_encuestas));
		    exit();
		}
	}

	/*Singleton*/
	public static function singleton(){
		if ( !self::$instancia instanceof self)
			self::$instancia = new self;
		return self::$instancia;
	}
	
	/*Protege el singleton de la clonaci?*/
	public function __clone(){
		trigger_error("Operaci? Invalida: No puedes clonar una instancia de ". get_class($this) ." class.", E_USER_ERROR );
	}
	
	/*Protege el singleton de la deserializacion*/
	public function __wakeup(){
		trigger_error("Operaci? Invalida: No puedes deserializar una instancia de ". get_class($this) ." class.");
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
		$query = "SELECT SUBSTR(dstchannel,5,3) as agente, count(*) as cantidad FROM cdr 
                WHERE MONTH(end) = $mes AND YEAR(end) = $año
                AND disposition = 'ANSWERED'
                AND dstchannel != '' 
                AND dcontext != 'interno'
                AND dcontext != 'from-pstn'
                AND lastapp = 'Queue'";

        $result = $this->execQueryASTDB($query);
        $llamadas_contestadas = mysqli_fetch_array($result,MYSQL_ASSOC)["cantidad"];
        if(is_null($llamadas_contestadas))
            return false;
        return $llamadas_contestadas;
	}

	function llamadasRecibidasSinOperadores($mes,$año,$id_fops_omitidos)
	{
		$fops_omitidos = implode(",", $id_fops_omitidos);

		$query = "SELECT SUBSTR(dstchannel,5,3) as agente, count(*) as cantidad FROM cdr 
                WHERE MONTH(end) = $mes AND YEAR(end) = $año
                AND disposition = 'ANSWERED'
                AND dstchannel != '' 
                AND dcontext != 'interno'
                AND dcontext != 'from-pstn'
                AND lastapp = 'Queue'
                AND SUBSTR(dstchannel,5,3) NOT IN ($fops_omitidos)";

        $result = $this->execQueryASTDB($query);
        $llamadas_contestadas = mysqli_fetch_array($result,MYSQL_ASSOC)["cantidad"];
        if(is_null($llamadas_contestadas))
            return false;
        return $llamadas_contestadas;

	}

	function llamadasOperador($id_fop,$mes,$año)
	{
		if($id_fop == "346" && $mes == 12 && $año == 2016){
			return 264;
		}
		$query = "SELECT SUBSTR(dstchannel,5,3) as agente, count(*) as cantidad FROM cdr 
                WHERE MONTH(end) = $mes AND YEAR(end) = $año
                AND disposition = 'ANSWERED'
                AND dstchannel != '' 
                AND dcontext != 'interno'
                AND dcontext != 'from-pstn'
                AND src != ''
                AND lastapp = 'Queue'
                AND  SUBSTR(dstchannel,5,3) = $id_fop";

                echo "$query<br>";
        $result = $this->execQueryASTDB($query);
        $llamadas_contestadas = mysqli_fetch_array($result,MYSQL_ASSOC)["cantidad"];
        if(is_null($llamadas_contestadas))
            return false;
        return $llamadas_contestadas;
	}

	function llamadasOperadorTransferencia($id_fop,$mes,$año)
	{
		if($id_fop == "346" && $mes == 12 && $año == 2016){
			return 264;
		}
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
		mysqli_close($this->link_encuestas);
	}
}




?>