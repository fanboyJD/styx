<?php
/*
 * Styx::Storage - MIT-style License
 * Author: christoph.pojer@gmail.com
 *
 * Usage: Provides an interface to store certain data
 *
 */

class Storage {
	
	protected $Storage = array();
	
	public function store($array, $value = null){
		if(!is_array($array))
			$array = array($array => $value);
		
		foreach($array as $key => $value)
			if(empty($this->Storage[$key]) || $this->Storage[$key]!=$value){
				if($value) $this->Storage[$key] = $value;
				else unset($this->Storage[$key]);
			}
		
		return Hash::length($array)==1 ? $value : $array;
	}
	
	public function retrieve($key, $value = null){
		if($value && empty($this->Storage[$key]))
			return $this->store($key, $value);
		
		return !empty($this->Storage[$key]) ? $this->Storage[$key] : null;
	}
	
	public function erase($key){
		unset($this->Storage[$key]);
	}
	
}