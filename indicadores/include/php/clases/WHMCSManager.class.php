<?php

class WHMCSManager
{
	private static $instancia;
	private $links;
	private $queries = array();
	/*Constructor vacio*/
	private function __construct()
	{
		$conn_strings = array();
		$conn_strings["Hosting"] = array("190.96.85.228","hostingc_fabian","hosting.,","hostingc_whmcsnew");
		$conn_strings["PlanetaHosting"] = array("201.148.105.50","igor_userph","whm*2008*.,","igor_whmph");
		$conn_strings["HostingCenter"]=array("190.96.85.114","hcenter_admin","Planeta*1910Center","hcenter_whmcs");
		$conn_strings["PlanetaPeru"] = array("201.148.107.40","planetco_user","admin7440","planetco_whmcs");
		$conn_strings["InkaHosting"]= array("201.148.107.40","inkahost_user","inka_.,2013","inkahost_whmcs");
		$conn_strings["NinjaHosting"]= array("panel.ninjahosting.cl","panelnin_ting14","]^k5[OVvr!PZ","panelnin_whmcs");
		$conn_strings["HostingPro"]= array("panel.hostingpro.com.co","panelhpr_alce","_DA{cRav1y1,","panelhpr_alce");

		$this->links = array();
		foreach ($conn_strings as $marca => $link) 
		{
			$this->links["$marca"] = mysqli_connect($link[0],$link[1],$link[2],$link[3]);
			/* Comprobar la conexi? */
			if (mysqli_connect_errno()) 
			{
			    debug("Fall?la conexion con WHMCS de $marca");
			    exit();
			}
			if (!mysqli_set_charset($this->links["$marca"], "utf8")) {
			    printf("Error loading character set utf8: %s\n", mysqli_error($this->links["$marca"]));
			    exit();
			}
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

	//retorna un arreglo de arreglos...
	//Arreglo principal: registros de todos los WHMCS
	//Arreglo secundario: arreglo asociativo: parametro=>valor
	public function execQuery($query)
	{
		if(isset($this->queries["$query"]))
			return $this->queries["$query"];

		$ret = array();
		foreach ( $this->links as $marca => $link) 
		{
			$result = mysqli_query($link,$query);
			if($result == false)
				debug("Error al ejecutar query en WHMCS de $marca<br>$query");
			while($row = mysqli_fetch_array($result,MYSQL_ASSOC))
			{
				$ret[] = $row;
			}

		}
		$this->queries["$query"] = $ret;
		return $ret;
	}

	//retorna un arreglo asociativo: marca->resultado de query en WHMCS
	public function execQueryByBrand($query)
	{
		if(isset($this->queries["$query"]))
			return $this->queries["$query"];

		$ret = array();
		foreach ( $this->links as $marca => $link) 
		{
			$result = mysqli_query($link,$query);
			if($result == false)
				debug("Error al ejecutar query en WHMCS de $marca<br>$query");
			$whmcs_data = array();
			while($row = mysqli_fetch_array($result,MYSQL_ASSOC))
				$whmcs_data[] = $row;
			$ret[$marca] = $whmcs_data;
		}
		$this->queries["$query"] = $ret;
		return $ret;
	}

	//retorna arreglo con resultado de query en WHMCS
	public function execQueryInBrand($query, $brand)
	{
		$link = $this->links["$brand"];
		$result = mysqli_query($link,$query);
		if($result == false)
			debug("Error al ejecutar query en WHMCS de $brand<br>$query");
		$whmcs_data = array();
		while($row = mysqli_fetch_array($result,MYSQL_ASSOC))
			$whmcs_data[] = $row;
		return $whmcs_data;
	}

	//
	public function execSumQuery($query,$sumname)
	{
		if(isset($this->queries["$query"]))
			return $this->queries["$query"];
		$sum = 0;
		foreach ( $this->links as $marca => $link) 
		{
			$result = mysqli_query($link,$query);
			if($result == false)
				debug("Error al ejecutar query en WHMCS de $marca<br>$query");

			$data = mysqli_fetch_array($result,MYSQL_ASSOC);
			$sum += $data["$sumname"];
		}
		$this->queries["$query"] = $sum;
		return $sum;
	}
	
	public function __destruct()
	{
		foreach ($this->links as $link) 
			mysqli_close($link);
	}
}

?>