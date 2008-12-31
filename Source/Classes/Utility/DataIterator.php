<?php
/**
 * Styx::DataIterator - Creates a tree out of flat data to iterate over it
 * 
 * @package Styx
 * @subpackage Utility
 *
 * @license MIT-style License
 * @author Christoph Pojer <christoph.pojer@gmail.com>
 */

class DataIterator implements RecursiveIterator, Countable  {
	
	/**
	 * @var array
	 */
	private $Data;
	/**
	 * The array of the current tree-children
	 *
	 * @var array
	 */
	private $Current;
	private $options = array();

	/**
	 * @param array $data
	 * @param array $options
	 */
	public function __construct($data, $options = null){
		$this->initialized = true;
		
		$this->options = $options;
		
		$this->Data = $data;
		$this->Current = $data[$this->options['current']];
	}
	
	/**
	 * Primarily used to create a fully functional DataIterator-Tree on which
 	 * foreach() can be used on
	 *
	 * @param array $data
	 * @param array $options
	 * @return RecursiveIteratorIterator
	 */
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