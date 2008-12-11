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
	
	public function store($key, $value = null){
		if(is_array($key)){
			foreach($key as $k => $val)
				$this->store($k, $val);
			
			return $key;
		}
		
		if(empty($this->Storage[$key]) || $this->Storage[$key]!=$value){
			if($value) $this->Storage[$key] = $value;
			else unset($this->Storage[$key]);
		}
		
		return $value;
	}
	
	public function retrieve($key, $value = null){
		if($value && empty($this->Storage[$key]))
			return $this->store($key, $value);
		
		return !empty($this->Storage[$key]) ? $this->Storage[$key] : null;
	}
	
	public function erase($key){
		unset($this->Storage[$key]);
	}
	
	public function eraseBy($key){
		foreach($this->Storage as $k => $v)
			if(String::starts($k, $key))
				unset($this->Storage[$k]);
	}
	
	public function eraseAll(){
		$this->Storage = array();
	}
	
}