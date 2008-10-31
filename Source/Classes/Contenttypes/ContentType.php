<?php
/*
 * Styx::ContentType - MIT-style License
 * Author: christoph.pojer@gmail.com
 *
 * Usage: Base class for other content-types
 *
 */

class ContentType {
	
	protected $type = null;
	
	public function __construct(){
		$this->type = substr(strtolower(get_class($this)), 0, -7);
	}
	
	public function getType(){
		return $this->type;
	}
	
	public function isExtended(){
		return false;
	}
	
	public function getHeaders(){
		return array();
	}
	
	public function process($content){
		return $content;
	}
	
}