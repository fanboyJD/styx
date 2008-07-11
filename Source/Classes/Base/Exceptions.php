<?php
class ValidatorException extends Exception {
	
	public function __construct($error){
		Hash::splat($error);
		$lang = Lang::retrieve('validator.'.$error[0]);
		if(!$lang) $lang = Lang::retrieve('validator.default');
		
		parent::__construct(sprintf($lang, $error[2]));
	}
}

class NoTableException extends Exception {}

class NoDataException extends Exception {}