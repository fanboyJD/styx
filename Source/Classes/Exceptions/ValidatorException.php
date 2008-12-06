<?php
/*
 * Styx::ValidatorException - MIT-style License
 * Author: christoph.pojer@gmail.com
 *
 * Usage: This exception is raised mostly somewhere inside a Layer
 *
 */


class ValidatorException extends Exception {
	
	public function __construct(){
		static $content = null;
		if(!$content) $content = pick(Lang::retrieve('validator.content'), '%s');
		
		$error = Hash::args(func_get_args());
		
		$lang = Lang::retrieve('validator.'.$error[0]);
		if(!$lang) $lang = Lang::retrieve('validator.default');
		
		parent::__construct(sprintf($content, sprintf($lang, !empty($error[2]) ? $error[2] : '')));
	}
}