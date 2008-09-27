<?php
/*
 * Styx::ValidatorException - MIT-style License
 * Author: christoph.pojer@gmail.com
 *
 * Usage: This exception is raised mostly somewhere inside a Layer
 *
 */


class ValidatorException extends Exception {
	
	protected static $content = null;
	
	public function __construct(){
		if(!self::$content) self::$content = pick(Lang::retrieve('validator.content'), '%s');
		
		$error = Hash::args(func_get_args());
		
		$lang = Lang::retrieve('validator.'.$error[0]);
		if(!$lang) $lang = Lang::retrieve('validator.default');
		
		parent::__construct(sprintf(self::$content, sprintf($lang, $error[2])));
	}
}