<?php
/*
 * Styx::QuerySelect - MIT-style License
 * Author: christoph.pojer@gmail.com
 *
 * Usage: Handles and processes SELECT SQL-Statements
 *
 */

class QuerySelect extends QueryHandler implements Iterator {
	
	protected $cache = array(),
		$queried = false;
	
	public function __construct($table){
		$this->Types = array('select');
		
		parent::__construct($table, 'select');
	}

	protected function formatFields(){
		$data = $this->Storage->retrieve('fields', '*');
		
		return is_array($data) ? implode(',', $data) : $data;
	}
	
	protected function formatOrder(){
		$data = $this->Storage->retrieve('order');
		
		return $data ? ' ORDER BY '.implode(',', Hash::splat($data)) : '';
	}
	
	/**
	 * @param array $data
	 * @return QuerySelect
	 */
	public function fields(){
		unset($this->formatted);
		
		$data = Hash::args(func_get_args());
		$this->Storage->store('fields', $data);
		
		return $this;
	}
	
	/**
	 * @param mixed $data
	 * @return QuerySelect
	 */
	public function order(){
		unset($this->formatted);
		
		$data = Hash::args(func_get_args());
		$this->Storage->store('order', $data);
		
		return $this;
	}
	
	public function format(){
		if($this->formatted) return $this->formatted;
		
		$out = parent::format(true);
		
		return $this->formatted = 'SELECT '.$this->formatFields().' FROM '.$this->table.$out[0].$this->formatOrder().$out[1];
	}
	
	public function fetch($type = null){
		$this->Storage->retrieve('limit', array(0, 1)); // To overcome big queries
		
		return db::getInstance()->fetch($this->query(), $type);
	}
	
	public function count($field = null){
		$count = $this->fields('COUNT('.($field ? $field : Core::retrieve('identifier.internal')).')')->limit(0)->fetch(MYSQL_NUM);
		
		return pick($count[0], 0);
	}
	
	public function retrieve(){
		$this->queried = false;
		
		return $this->cache = pick(db::getInstance()->retrieve($this->format()), array());
	}
	
	public function rewind(){
		if(!$this->queried){
			$this->retrieve();
			$this->queried = true;
		}
		
		reset($this->cache);
	}
	
	public function current(){
		return current($this->cache);
	}
	
	public function key(){
		return key($this->cache);
	}
	
	public function next(){
		return next($this->cache);
	}
	
	public function valid(){
		return !is_null($this->key());
	}
	
	public function reset(){
		return reset($this->cache);
	}
	
	public function length(){
		if(!$this->queried) $this->rewind();
		
		return sizeof($this->cache);
	}
	
}