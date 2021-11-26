<?php
// Turn off all error reporting
error_reporting(0);

class SingleWHMCSManager
{
	private static $instancias = array();
	private $link;
	private $queries = array();
	/*Constructor vacio*/
	private function __construct($brand){
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

		$conn_string = $conn_strings["$brand"];
		$this->link = mysqli_connect($conn_string[0],$conn_string[1],$conn_string[2],$conn_string[3]);
		if (mysqli_connect_errno()) 
		{
		    echo "Falló la conexión con WHMCS de $brand: ".mysqli_connect_error()." (".mysqli_connect_errno().")";
		    exit();
		}
		if (!mysqli_set_charset($this->link, "utf8")) {
		    printf("Error loading character set utf8: %s\n", mysqli_error($this->link));
		    exit();
		}
	}

	/*Singleton*/
	public static function singleton($brand){
		if ( !self::$instancias[$brand] instanceof self)
			self::$instancias[$brand] = new self($brand);
		return self::$instancias[$brand];
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
		$result = mysqli_query($this->link,$query);
		if($result == false)
			return false;
		while($row = mysqli_fetch_array($result,MYSQLI_ASSOC))
		{
			$ret[] = $row;
		}

		
		$this->queries["$query"] = $ret;
		return $ret;
	}

	public function execQueryAlone($query){
		return mysqli_query($this->link,$query);
	}
	
	public function __destruct()
	{
		mysqli_close($this->link);
	}
}

?>
