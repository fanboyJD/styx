<?php
/*
 * Styx::QuerySelect - MIT-style License
 * Author: christoph.pojer@gmail.com
 *
 * Usage: Handles and processes SELECT SQL-Statements
 *
 */

class QuerySelect extends Query implements Iterator, Countable {
	
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
	
	/**
	 * @return QuerySelect
	 */
	public function fields(){
		unset($this->formatted);
		
		$data = Hash::args(func_get_args());
		$this->Storage->store('fields', $data);
		
		return $this;
	}
	
	/**
	 * @return QuerySelect
	 */
	public function order(){
		unset($this->formatted);
		
		$data = Hash::args(func_get_args());
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
	public function join($on, $table, $type = null){
		unset($this->formatted);
		
		$this->Storage->store('join', array(
			'on' => $on,
			'table' => $table,
			'type' => $type,
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
		
		return db::getInstance()->fetch($this->query(), $type);
	}
	
	public function quantity($field = null){
		$query = clone $this;
		
		$count = $query->fields("COUNT('".($field ? $field : Core::retrieve('identifier.internal'))."')")->fetch(MYSQL_NUM);
		
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
	
	public function count(){
		if(!$this->queried) $this->rewind();
		
		return count($this->cache);
	}
	
}