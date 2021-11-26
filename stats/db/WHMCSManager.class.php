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
		$conn_strings["Hosting"] = 					array("panel.hosting.cl","panelhos_ardilla","9KnmpoL?-W$&","panelhos_whmcs");
		$conn_strings["PlanetaHosting"] = 			array("planetahosting.cl","igor_userph","whm*2008*.,","igor_whm2019");
		$conn_strings["Hostingcenter"] = 			array("hostingcenter.cl","hcenter_admin","Planeta*1910Center","hcenter_whmcs");
		$conn_strings["iHost"] =					array("ihost.cl","ihostcl_adm","Adm18*.*H","ihostcl_whmcs");
		$conn_strings["PlanetaPeru"] = 				array("panel.planetahosting.pe","planetco_user","admin7440","planetco_whmcs");
		$conn_strings["InkaHosting"] = 				array("panel.inkahosting.com.pe","inkahost_user","inka_.,2013","inkahost_whmcs");
		$conn_strings["1Hosting"] = 				array("panel.1hosting.com.pe","panel1ho_whmuser",")Z3~w#2aWL0g","panel1ho_whmcs");
		$conn_strings["NinjaHosting"] = 			array("panel.ninjahosting.cl","panelnin_ting14","]^k5[OVvr!PZ","panelnin_whmcs");
		$conn_strings["PlanetaColombia"] = 			array("panel.planetahosting.com.co","planecol_desarr","*desarrollo12*","planecol_whmc2");
		$conn_strings["HostingcenterColombia"] = 	array("panel.hostingcenter.com.co","panelhco_desarr","*desarrollohcenter.,*","panelhco_whm");
		$conn_strings["NinjaPeru"] = 				array("panel.ninjahosting.pe","pninjape_adm","qeK(YTu[1xa9","pninjape_whmcs");
		$conn_strings["Dehosting"] = 				array("dehosting.net","dehostin_admin","Hosting.,12***","dehostin_whmcs");
		$conn_strings["NinjaColombia"] = 			array("panel.ninjahosting.com.co","ninjaco_desarrol","*desarrolloninjaco.,*","ninjaco_whmcs81290");
		$conn_strings["HostingPro"] = 				array("panel.hostingpro.com.co","panelhpr_alce","_DA{cRav1y1,","panelhpr_alce");

		$this->links = array();
		foreach ($conn_strings as $marca => $link) 
		{
			$this->links["$marca"] = mysqli_connect($link[0],$link[1],$link[2],$link[3]);
			/* Comprobar la conexi�n */
			if (mysqli_connect_errno()) 
			{
			    debug("Fall� la conexi�n con WHMCS de $marca");
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
	
	/*Protege el singleton de la clonaci�n*/
	public function __clone(){
		trigger_error("Operaci�n Invalida: No puedes clonar una instancia de ". get_class($this) ." class.", E_USER_ERROR );
	}
	
	/*Protege el singleton de la deserializacion*/
	public function __wakeup(){
		trigger_error("Operaci�n Invalida: No puedes deserializar una instancia de ". get_class($this) ." class.");
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

	public function execQueryInBrand($query,$brand)
	{
		$link =  $this->links[$brand];
		$result = mysqli_query($link,$query);
		if($result == false)
			echo "Error al ejecutar query en WHMCS de $brand<br>$query";
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