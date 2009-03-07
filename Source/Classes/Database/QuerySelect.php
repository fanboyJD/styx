<?php
/*
 * Styx::QuerySelect - MIT-style License
 * Author: christoph.pojer@gmail.com
 *
 * Usage: Handles and processes SELECT SQL-Statements
 *
 */

class QuerySelect extends Query implements Iterator, ArrayAccess, Countable {
	
	protected $Data = array(),
		$queried = false;
	
	public function __construct($table){
		$this->Types = array('select');
		
		parent::__construct($table, 'select');
	}
	
	protected function formatFields(){
		$data = $this->Storage->retrieve('fields', '*');
		
		return is_scalar($data) ? $data : implode(',', $data);
	}
	
	protected function formatOrder(){
		$data = $this->Storage->retrieve('order');
		
		return $data ? ' ORDER BY '.implode(',', Hash::splat($data)) : '';
	}
	
	protected function formatGroup(){
		$data = $this->Storage->retrieve('group');
		
		return $data ? ' GROUP BY '.$data : '';
	}
	
	protected function formatHaving(){
		$data = $this->Storage->retrieve('having');
		
		return $data ? ' HAVING '.$data : '';
	}
	
	protected function formatJoin(){
		$data = $this->Storage->retrieve('join');
		
		if(empty($data['on']) || empty($data['table']))
			return;
		
		return $data ? (!empty($data['type']) && in_array($data['type'], array('LEFT', 'RIGHT', 'INNER', 'OUTER')) ? ' '.strtoupper($data['type']) : '').
			' JOIN '.$data['table'].' ON ('.$data['on'].')' : '';
	}
	
	public function set($data){}
	
	/**
	 * @return QuerySelect
	 */
	public function fields($data){
		unset($this->formatted);
		
		$this->Storage->store('fields', $data);
		
		return $this;
	}
	
	/**
	 * @return QuerySelect
	 */
	public function order($data){
		unset($this->formatted);
		
		$this->Storage->store('order', $data);
		
		return $this;
	}
	
	/**
	 * @param mixed $data
	 * @return QuerySelect
	 */
	public function group($data){
		unset($this->formatted);
		
		$this->Storage->store('group', $data);
		
		return $this;
	}
	
	/**
	 * @param mixed $data
	 * @return QuerySelect
	 */
	public function having($data){
		unset($this->formatted);
		
		$this->Storage->store('having', $data);
		
		return $this;
	}

	/**
	 * @param mixed $data
	 * @return QuerySelect
	 */
	public function join($on, $table = null, $type = null){
		if(!$table && !$type) $args = Hash::args(func_get_args());
		else $args = array($on, $table, $type);
		
		if(empty($args[1]) || empty($args[2])) return;
		
		unset($this->formatted);
		
		$this->Storage->store('join', array(
			'on' => $args[0],
			'table' => $args[1],
			'type' => $args[2],
		));
		
		return $this;
	}
	
	public function format(){
		if(!empty($this->formatted)) return $this->formatted;
		
		$out = parent::format(true);
		
		return $this->formatted = 'SELECT '.$this->formatFields().' FROM '.$this->table.
			$this->formatJoin().$out[0].$this->formatOrder().$this->formatGroup().$this->formatHaving().$out[1];
	}
	
	public function fetch($type = null){
		$this->Storage->retrieve('limit', array(0, 1)); // To overcome big queries
		
		return Database::getInstance()->fetch($this->query(), $type);
	}
	
	public function quantity($field = null){
		$query = clone $this;
		
		$count = $query->fields("COUNT('".($field ? $field : Core::retrieve('identifier.internal'))."')")->fetch(MYSQL_NUM);
		
		return pick($count[0], 0);
	}
	
	public function retrieve(){
		$this->queried = false;
		
		return $this->Data = pick(Database::getInstance()->retrieve($this->format()), array());
	}
	
	private function populate(){
		if($this->queried && !empty($this->formatted))
			return;
		
		$this->retrieve();
		$this->queried = true;
	}
	
	public function offsetSet($offset, $value){
		$this->populate();
		$this->Data[$offset] = $value;
	}
	
	public function offsetGet($offset){
		$this->populate();
		return isset($this->Data[$offset]) ? $this->Data[$offset] : null;
	}
	
	public function offsetExists($offset){
		$this->populate();
		return isset($this->Data[$offset]);
	}
	
	public function offsetUnset($offset){
		$this->populate();
		unset($this->Data[$offset]);
	}
	
	public function rewind(){
		$this->populate();
		reset($this->Data);
	}
	
	public function current(){
		return current($this->Data);
	}
	
	public function key(){
		return key($this->Data);
	}
	
	public function next(){
		return next($this->Data);
	}
	
	public function valid(){
		return !is_null(key($this->Data));
	}
	
	public function reset(){
		return reset($this->Data);
	}
	
	public function count(){
		$this->populate();
		return count($this->Data);
	}
	
}