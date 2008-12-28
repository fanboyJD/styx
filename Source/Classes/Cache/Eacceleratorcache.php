<?php
/*
 * Styx::Eacceleratorcache - MIT-style License
 * Author: christoph.pojer@gmail.com
 *
 * Usage: Cache-Interface for the eAccelerator-extension
 *
 */


class Eacceleratorcache {
	
	private $Configuration = array(
			'prefix' => null,
			'root' => null,
		);
	
	public function __construct($Configuration){
		$Configuration['prefix'] .= '/';
		
		$this->Configuration = $Configuration;
	}
	
	public function retrieve($id){
		return eaccelerator_get($this->Configuration['prefix'].$id);
	}
	
	public function store($id, $content, $ttl = null){
		eaccelerator_put($this->Configuration['prefix'].$id, $content, $ttl);
	}
	
	public function erase($array){
		foreach($array as $id)
			eaccelerator_rm($this->Configuration['prefix'].$id);
	}
	
}