<?php

class Operador{

	//Propiedades
	private $id;
	private $nombre;
	private $apellido;
	private $hora_trabajo;
	private $pais;
	private $turnos;
	private $id_area;
	
	//Metodos
	
	/*Contructor*/
	public function __construct($params) {
		$this->id = $params['id'];
		$this->nombre = $params['nombre'];
		$this->apellido = $params['apellido'];
		$this->hora_trabajo = $params['hora_trabajo'];
		$this->pais = $params['pais'];
		$this->id_area = $params['id_area'];
		$this->jefe_de_area = $params['jefe_de_area'];
		
		$this->turnos = '';
	}
	
	/*Devuelve el nombre compelto del operador*/
	public function nombreCompleto(){
		return $this->nombre.' '.$this->apellido;
	}

	/*Devuelve el nombre del operador*/
	public function getNombre(){
		return $this->nombre;
	}

	/*Devuelve el apellido del operador*/
	public function getApellido(){
		return $this->apellido;
	}

	//Devuelve el ID del operador
	public function getId(){return $this->id;}
	
	//Devuelve el ID del area del operador
	public function getIdArea(){return $this->id_area;}

	//Devuelve si es jefe de area
	public function isJefeDeArea()
	{		
		if($this->jefe_de_area == 1)
			return true;
		return false;
	}
}

?>