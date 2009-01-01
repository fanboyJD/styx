<?php
/**
 * Styx::ValidatorException - This exception is raised mostly somewhere inside a Layer, can be thrown at any time inside an event of a Layer
 * 
 * @package Styx
 * @subpackage Exceptions
 *
 * @license MIT-style License
 * @author Christoph Pojer <christoph.pojer@gmail.com>
 */

class ValidatorException extends Exception {
	
	/**
	 * Takes the key of any validator.* language string and a value to substitute %s inside that string
	 * <b>Example:</b> throw new ValidatorException('password', $el->get(':caption'), 'Some Text to append after the Exception Text');
	 */
	public function __construct(){
		static $content = null, $namespace, $default;
		
		if(!$content) $content = pick(Lang::retrieve('validator.content'), '%s');
		if(!$namespace) $namespace = String::toLower(String::sub(get_class($this), 0, -9));
		if(!$default) $default = Lang::retrieve($namespace.'.default');
		
		$error = Hash::args(func_get_args());
		parent::__construct(sprintf($content, sprintf(pick(Lang::retrieve($namespace.'.'.$error[0]), $default), !empty($error[1]) ? $error[1] : '')).(!empty($error[2]) ? $error[2] : ''));
	}
}