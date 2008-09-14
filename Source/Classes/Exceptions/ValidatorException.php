<?php
class ValidatorException extends Exception {
	
	protected static $content = false;
	
	public function __construct($error){
		if(self::$content===false)
			self::$content = pick(Lang::retrieve('validator.content'), null);
		
		Hash::splat($error);
		$lang = Lang::retrieve('validator.'.$error[0]);
		if(!$lang) $lang = Lang::retrieve('validator.default');
		
		$lang = sprintf($lang, $error[2]);
		
		parent::__construct(self::$content ? sprintf(self::$content, $lang) : $lang);
	}
}