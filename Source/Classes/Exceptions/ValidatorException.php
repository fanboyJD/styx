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
	 * The namespace to be used in the Language-File
	 *
	 * @var string
	 */
	protected $namespace = 'validator';
	/**
	 * Takes the key of any validator.* language string and a value to substitute %s inside that string
	 * <b>Example:</b> throw new ValidatorException('password', $el->get(':caption'));
	 */
	public function __construct(){
		static $content = null, $default;
		if(!$content) $content = pick(Lang::retrieve('validator.content'), '%s');
		if(!$default) $default = Lang::retrieve($this->namespace.'.default');
		
		$error = Hash::args(func_get_args());
		parent::__construct(sprintf($content, sprintf(pick(Lang::retrieve($this->namespace.'.'.$error[0]), $default), !empty($error[1]) ? $error[1] : '')));
	}
}