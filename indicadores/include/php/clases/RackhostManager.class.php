<?php

class RackhostManager
{
	private static $instancia;
	private $link;
	/*Constructor vacio*/
	private function __construct()
	{	
		$this->link = mysqli_connect("201.148.107.52", "rackhost_rack2", "hosting.,12", "rackhost_backup");
		/* Comprobar la conexi? */
		if (mysqli_connect_errno()) {
		    printf("Fall?la conexi?: %s\n", mysqli_connect_error());
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

	public function execQuery($query)
	{
		return mysqli_query($this->link,$query);
	}

	public function __destruct()
	{
		mysqli_close($this->link);
	}
}




?>