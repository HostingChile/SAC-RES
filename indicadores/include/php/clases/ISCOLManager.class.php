<?php

class ISCOLManager
{
	private static $instancia;
	private $links;
	private $queries = array();
	/*Constructor vacio*/
	private function __construct()
	{
		$conn_string = array("190.96.85.3","sistemas_alvaro","alv15963","sistemas_iscol");

		$this->link = mysqli_connect($conn_string[0],$conn_string[1],$conn_string[2],$conn_string[3]);
		/* Comprobar la conexi? */
		if (mysqli_connect_errno()) 
		{
		    debug("Fall?la conexi? con ISCOL");
		    exit();
		}
		// if (!mysqli_set_charset($this->link, "utf8")) {
		//     printf("Error loading character set utf8: %s\n", mysqli_error($this->link));
		//     exit();
		// }
		
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

	//retorna un arreglo de arreglos...
	//Arreglo principal: registros de todos los WHMCS
	//Arreglo secundario: arreglo asociativo: parametro=>valor
	public function execQuery($query)
	{
		if(isset($this->queries["$query"]))
			return $this->queries["$query"];

		return mysqli_query($this->link,$query);
	}

	
	public function __destruct()
	{
		mysqli_close($this->link);
	}
}

?>