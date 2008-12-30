<?php
/*
 * Styx::DataIterator - MIT-style License
 * Author: christoph.pojer@gmail.com
 *
 * Usage: Creates a tree out of flat data to iterate over it
 *
 */

class DataIterator implements RecursiveIterator, Countable  {
	
	private
		$Data,
		$Current,
		
		$options = array();

	public function __construct($data, $options = null){
		$this->initialized = true;
		
		$this->options = $options;
		
		$this->Data = $data;
		$this->Current = $data[$this->options['current']];
	}
	
	public static function retrieve($data, $options = null){
		$default = array(
			'identifier' => null,
			'parent' => 'parent',
			'current' => 0,
		);
		
		Hash::extend($default, $options);
		
		if(empty($default['identifier']))
			$default['identifier'] = Core::retrieve('identifier.internal');
		
		$content = array();
		
		foreach($data as $d)
			$content[!empty($d[$default['parent']]) ? $d[$default['parent']] : 0][] = $d;
		
		return new RecursiveIteratorIterator(new DataIterator($content, $default), RecursiveIteratorIterator::SELF_FIRST);
	}
	
	public function hasChildren(){
		$current = current($this->Current);
		
		return !empty($this->Data[$current[$this->options['identifier']]]) && !!Hash::length($this->Data[$current[$this->options['identifier']]]);        
	}
	
	public function getChildren(){
		$current = current($this->Current);
		$options = $this->options;
		
		return new DataIterator($this->Data, Hash::extend($options, array(
			'current' => $current[$options['identifier']],
		)));
	}
	
	public function rewind(){
		reset($this->Current);
	}

	public function current(){
		return current($this->Current);
	}
	
	public function key(){
		return key($this->Current);
	}
	
	public function next(){
		return next($this->Current);
	}
	
	public function valid(){
		return !is_null($this->key());
	}
	
	public function reset(){
		return reset($this->Current);
	}
	
	public function count(){
		return count($this->Current);
	}
	
}