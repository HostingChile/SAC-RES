<?php

class MailManager{

	private static $instancia;
	private $hostname;
	private $username;
	private $password;
	private $mails;
	private $last_error = '';
	
	/*Constructor vacio*/
	private function __construct(){
		require('config.php');
	
		$this->hostname = $mail_hostname;
		$this->username = $mail_username;
		$this->password = $mail_password;
		$this->mails = array();
	}
	
	/*Entrega los correos desde $start_date(incluida) hasta $end_date (no inlcuída)*/
	/*Sólo los descarga si no se hizo la misma consulta*/
	function obtenerCorreosPorFecha($start_date, $end_date){
		//Formateo de fecha y definicion de query
		$start_timestamp = strtotime($start_date);
		if(!$start_timestamp){
			$this->setearError('Fecha de inicio inválida');
			return false;
		}
		$end_timestamp = strtotime($end_date);
		if(!$end_timestamp){
			$this->setearError('Fecha de fin inválida');
			return false;
		}
		
		$start_day = date('j',$start_timestamp);
		$start_month = $this->nombreMesIngles(date('n',$start_timestamp));
		$start_year = date('Y',$start_timestamp);
		
		$end_day = date('j',$end_timestamp);
		$end_month = $this->nombreMesIngles(date('n',$end_timestamp));
		$end_year = date('Y',$end_timestamp);
		
		$start_date = "$start_day $start_month $start_year";
		$end_date = "$end_day $end_month $end_year";
		
		$query = 'SINCE "'.$start_date.'" BEFORE "'.$end_date.'"';
		
		//Si no estan los correos ya descargados, se descargan
		if(!array_key_exists($query,$this->mails)){		
			$inbox = @imap_open($this->hostname,$this->username,$this->password,OP_READONLY);
			if(!$inbox){
				$this->setearError('No se pudo establecer la conexión IMAP: '.imap_last_error());
				return false;
			}

			$emails = imap_search($inbox, $query); //Para Mayo-14 usar: 'SINCE "1 May 2014" BEFORE "1 Jun 2014"'
			
			if(!$emails){
				$this->setearError('Error al buscar los correos: '.imap_last_error());
				return false;
			}
			
			foreach($emails as $email){
				$body = imap_body($inbox,$email);
				$header = imap_fetch_overview($inbox,$email); // http://php.net/manual/es/function.imap-fetch-overview.php
				
				$this->mails[$query][] = array('body' => $body, 'header' => $header);
			}
			
			imap_close($inbox);
		}
		
		return $this->mails[$query];
	}
	
	/*Recibe el número mes y devuelve el nombre de 3 letras en inglés. 1 -> Jan .. 12 -> Dec*/
	private function nombreMesIngles($num){
		switch($num){
			case 1:return "Jan";break;
			case 2:return "Feb";break;
			case 3:return "Mar";break;
			case 4:return "Apr";break;
			case 5:return "May";break;
			case 6:return "Jun";break;
			case 7:return "Jul";break;
			case 8:return "Aug";break;
			case 9:return "Sep";break;
			case 10:return "Oct";break;
			case 11:return "Nov";break;
			case 12:return "Dec";break;
		}
	}

	/*Setea el valor del ultimo error. Si no se manda argumento guarda el ultimo error de imap*/
	private function setearError($msg = null){	
		$this->last_error = is_null($msg) ? imap_last_error() : $msg;
	}
	
	/*Devuelve el ultimo error ocurrido*/
	function ultimoError(){
		return $this->last_error;
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
}

?>