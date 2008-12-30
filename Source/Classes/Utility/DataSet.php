<?php
/*
 * Styx::DataSet - MIT-style License
 * Author: christoph.pojer@gmail.com
 *
 * Usage: Interface for specific operations (pagination etc.) and database-data abstraction
 *
 */

class DataSet implements Iterator, Countable {
	
	protected $Data = array(),
		$Subset = array(),
		$Storage,
		
		$queried = false;
	
	public function __construct($data){
		$this->Data = $data;
		
		$this->Storage = new Storage();
	}
	
	private function getArguments($args){
		$array = array();
		foreach($args as $arg)
			foreach(array_map('trim', explode(',', $arg)) as $field)
				$array[] = $field;
		
		return Hash::length($array) ? $array : null;
	}
	
	/**
	 * @return QuerySelect
	 */
	public function fields(){
		$data = Hash::args(func_get_args());
		$this->Storage->store('fields', $data);
		
		return $this;
	}
	
	/**
	 * @return QuerySelect
	 */
	public function order(){
		$data = Hash::args(func_get_args());
		$this->Storage->store('order', $data);
		
		return $this;
	}
	
	/**
	 * @param mixed $limit
	 * @param mixed $val
	 * @return Query
	 */
	public function limit($limit, $val = null){
		if($val) $limit = array($limit, $val);
		elseif(!is_array($limit)) $limit = array(0, $limit);
		
		$this->Storage->store('limit', $limit);
		
		return $this;
	}
	
	public function quantity(){
		return count($this->Data);
	}
	
	public function retrieve(){
		$this->queried = false;
		
		$this->Subset = array();
		$subset = $this->Data;
		
		/* Order */
		$order = $this->Storage->retrieve('order');
		if($order && $sequence = $this->getArguments($order))
			foreach($sequence as $seq){
				$seq = array_map('trim', explode(' ', $seq));
				if(empty($seq[0])) continue;
				
				uksort($subset, array(new DataComparison($subset, !empty($seq[1]) && String::toLower($seq[1])=='desc'), $seq[0]));
			}
		
		/* Limit */
		$limit = $this->Storage->retrieve('limit');
		if(is_array($limit) && ($limit[0] || $limit[1])){
			reset($this->Data);
			if($limit[0])
				for($i=0;$i<$limit[0];$i++)
					next($subset);
			
			for($i=0;$i<$limit[1];$i++){
				if(($v = current($subset))===false) break;
				$this->Subset[key($subset)] = $v;
				next($subset);
			}
		}else
			$this->Subset = $subset;
		
		unset($subset);
		
		/* Fields */
		$fields = $this->Storage->retrieve('fields');
		if($fields && $checks = $this->getArguments($fields))
			foreach($this->Subset as $k => $data){
				$filtered = array();
				foreach($data as $key => $value)
					if(in_array($key, $checks))
						$filtered[$key] = $value;
				
				$this->Subset[$k] = $filtered;
			}
		
		
		return $this->Subset;
	}
	
	public function rewind(){
		if(!$this->queried){
			$this->retrieve();
			$this->queried = true;
		}
		
		reset($this->Subset);
	}
	
	public function current(){
		return current($this->Subset);
	}
	
	public function key(){
		return key($this->Subset);
	}
	
	public function next(){
		return next($this->Subset);
	}
	
	public function valid(){
		return !is_null($this->key());
	}
	
	public function reset(){
		return reset($this->Subset);
	}
	
	public function count(){
		if(!$this->queried) $this->retrieve();
		
		return count($this->Subset);
	}
	
}

class DataComparison {
	
	private $Data,
		$desc;
	
	public function __construct($data, $desc = false){
		$this->Data = $data;
		
		$this->desc = $desc ? -1 : 1;
	}
	
	public function __call($key, $args){
		if(!isset($this->Data[$args[0]][$key]))
			return isset($this->Data[$args[1]][$key]) ? 1*$this->desc : 0;
		
		if(!isset($this->Data[$args[1]][$key]))
			return isset($this->Data[$args[0]][$key]) ? -1*$this->desc : 0;
		
		$a = $this->Data[$args[0]][$key];
		$b = $this->Data[$args[1]][$key];
		
		return $a==$b ? 0 : ($a<$b ? -1*$this->desc : 1*$this->desc);
	}
	
}