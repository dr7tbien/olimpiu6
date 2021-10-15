<?php 
require_once 'defines/CDH_awsDefinesAll.php';

class CDH_Logger{
	
	public function log($mensaje, $fichero = NULL){
		#hace 5 intentos para escribir el mensaje
		$ct_intentos = 0;
		while($ct < 5){
			if (file_put_contents($fichero, $mensaje, FILE_APPEND)) {
				break;
				return true;
			}
			$ct++;
		}
		return false;
	}

	public function logUser($mensaje){
		return $this->log($mensaje, CDH_LOGGER_USER);
	}

	public function logAdmin($mensaje){
		return $this->log($mensaje, CDH_LOGGER_ADMIN);
	}
}
?>