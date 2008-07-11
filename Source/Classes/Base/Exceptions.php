<?php
class ValidatorException extends Exception {
	
	protected static $init = false,
		$prefix = null,
		$suffix = null;
	
	public function __construct($error){
		if(!self::$init){
			self::$prefix = Lang::retrieve('validator.prefix');
			self::$suffix = Lang::retrieve('validator.suffix');
			
			self::$init = true;
		}
		Hash::splat($error);
		$lang = Lang::retrieve('validator.'.$error[0]);
		if(!$lang) $lang = Lang::retrieve('validator.default');
		
		parent::__construct(self::$prefix.sprintf($lang, $error[2]).self::$suffix);
	}
}

class NoTableException extends Exception {}

class NoDataException extends Exception {}