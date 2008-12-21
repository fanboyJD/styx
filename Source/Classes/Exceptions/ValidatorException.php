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
		static $content = null, $default;
		if(!$content) $content = pick(Lang::retrieve('validator.content'), '%s');
		if(!$default) $default = Lang::retrieve('validator.default');
		
		$error = Hash::args(func_get_args());
		
		parent::__construct(sprintf($content, sprintf(pick(Lang::retrieve('validator.'.$error[0]), $default), !empty($error[2]) ? $error[2] : '')));
	}
}