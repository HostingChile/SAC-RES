<?php

class Deal{
	

	private $api_token, $company_domain;


	public function __construct(){
		$this->api_token = '65ced92764c75610953cc696de6ccd7c636b114d';
		$this->company_domain = 'hostingcl-e92252'; 

    }

	public function create($arreglo, $peticion){

		//URL for Deal listing with your $company_domain and $api_token variables
		$url = 'https://'.$this->company_domain.'.pipedrive.com/v1/'.$peticion.'?api_token=' . $this->api_token;
		 
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $arreglo);
		 
		//echo 'Enviando Datos......' . PHP_EOL;
		 
		$output = curl_exec($ch);
		curl_close($ch);
		 
		// Create an array from the data that is sent back from the API
		// As the original content from server is in JSON format, you need to convert it to PHP array
		$result = json_decode($output, true);

		if(!empty($result["data"]["id"])){
			return $result;
		}

		return false;
		 

	}

	public function delete($peticion){

		//URL for Deal listing with your $company_domain and $api_token variables
		$url = 'https://'.$this->company_domain.'.pipedrive.com/v1/'.$peticion.'?api_token=' . $this->api_token;
		 
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, false);
		 
		//echo 'Enviando Datos......' . PHP_EOL;
		 
		$output = curl_exec($ch);
		curl_close($ch);
		 
		// Create an array from the data that is sent back from the API
		// As the original content from server is in JSON format, you need to convert it to PHP array
		$result = json_decode($output, true);

		if(!empty($result["data"]["id"])){
			return $result;
		}

		return false;
		 

	}

	public function get_all_deals(){
 
		//URL for Deal listing with your $company_domain and $api_token variables
		$url = 'https://'.$this->company_domain.'.pipedrive.com/v1/deals?limit=500&api_token=' . $this->api_token;
		  
		//GET request
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		  
		$output = curl_exec($ch);
		curl_close($ch);
		  
		// Create an array from the data that is sent back from the API
		// As the original content from server is in JSON format, you need to convert it to a PHP array
		$result = json_decode($output, true);
		 
		// Check if data returned in the result is not empty
		if ($result['data'] == null) {
		    return false;
		}
		return $result;

	}

}