<?php
/**
 * Styx::UploadException - This exception gets thrown inside {@link Upload::move} so the Developer can
 * catch it inside a Layer-Event at any time. It behaves exactly like {@link ValidatorException} except that
 * it uses the "upload" namespace inside of the Language-Files
 * 
 * @package Styx
 * @subpackage Exceptions
 *
 * @license MIT-style License
 * @author Christoph Pojer <christoph.pojer@gmail.com>
 */

class UploadException extends ValidatorException {

	public function __construct(){
		$this->namespace = 'upload';
		
		$args = Hash::args(func_get_args());
		
		parent::__construct($args);
	}

}