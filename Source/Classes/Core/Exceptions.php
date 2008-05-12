<?php
class ValidatorException extends Exception {
	
	public $error = array();
	
	public function __construct($error){
		$this->error = $error;
		
		parent::__construct();
	}
}

class NoTableException extends Exception {}

class NoDataException extends Exception {}
?>