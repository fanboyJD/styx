<?php
/**
 * Styx::Storage - Provides an interface to store and retrieve data
 *
 * @package Styx
 * @subpackage Core
 *
 * @license MIT-style License
 * @author Christoph Pojer <christoph.pojer@gmail.com>
 */

class Storage {
	
	/**
	 * Holds all the stored variables
	 *
	 * @var array
	 */
	protected $Storage = array();
	
	/**
	 * Stores a single value or an array of key/value pairs
	 *
	 * @param array|string $array
	 * @param mixed $value
	 * @return mixed
	 */
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
	
	/**
	 * Returns the value for a given key. If the second parameter is set,
	 * it stores it and returns it only if the given key has not been set yet
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return mixed
	 */
	public function retrieve($key, $value = null){
		if($value && empty($this->Storage[$key]))
			return $this->store($key, $value);
		
		return !empty($this->Storage[$key]) ? $this->Storage[$key] : null;
	}
	
	/**
	 * Removes the given key from the storage
	 *
	 * @param string $key
	 */
	public function erase($key){
		unset($this->Storage[$key]);
	}
	
}