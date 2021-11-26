<?php

class SACManager
{
	private static $instancia;
	private $link;
	/*Constructor vacio*/
	private function __construct()
	{
		$this->link = mysqli_connect("sistemas.hosting.cl", "sistemas_callc", "sys1830", "sistemas_sac");
		/* Comprobar la conexi�n */
		if (mysqli_connect_errno()) {
		    printf("Fall� la conexi�n: %s\n", mysqli_connect_error());
		    exit();
		}
		if (!mysqli_set_charset($this->link, "utf8")) {
		    printf("Error loading character set utf8: %s\n", mysqli_error($this->link));
		    exit();
		}
	}

	/*Singleton*/
	public static function singleton(){
		if ( !self::$instancia instanceof self)
			self::$instancia = new self;
		return self::$instancia;
	}
	
	/*Protege el singleton de la clonaci�n*/
	public function __clone(){
		trigger_error("Operaci�n Invalida: No puedes clonar una instancia de ". get_class($this) ." class.", E_USER_ERROR );
	}
	
	/*Protege el singleton de la deserializacion*/
	public function __wakeup(){
		trigger_error("Operaci�n Invalida: No puedes deserializar una instancia de ". get_class($this) ." class.");
	}

	public function execQuery($query)
	{
		return mysqli_query($this->link,$query);
	}

	public function lastInsertId()
	{
		return mysqli_insert_id($this->link);
	}

	public function __destruct()
	{
		mysqli_close($this->link);
	}
}

?>
