<?php
/**
 * Styx::DataSet - Interface for specific operations (pagination etc.) and database-data abstraction
 *
 * @package Styx
 * @subpackage Utility
 *
 * @license MIT-style License
 * @author Christoph Pojer <christoph.pojer@gmail.com>
 */

class DataSet implements Iterator, ArrayAccess, Countable {
	
	/**
	 * The whole array the DataSet operates on
	 *
	 * @var array
	 */
	protected $Data = array();
	/**
	 * The subset of {@link $Data} selected by calling limit/order/fields on the original data
	 *
	 * @var array
	 */
	protected $Subset = array();
	/**
	 * A Storage-Instance
	 *
	 * @var Storage
	 */
	protected $Storage;
	/**
	 * Internally used to check if the subset for the given options has already been created
	 *
	 * @var bool
	 */
	protected $queried = false;
	
	/**
	 * @param array $data The initial data for the DataSet
	 */
	public function __construct($data){
		$this->Data = $data;
		
		$this->Storage = new Storage();
	}
	
	/**
	 * Parses order/fields input
	 *
	 * @param array $args
	 * @return array
	 */
	private function getArguments($args){
		$array = array();
		foreach($args as $arg)
			foreach(array_map('trim', explode(',', $arg)) as $field)
				$array[] = $field;
		
		return Hash::length($array) ? $array : null;
	}
	
	/**
	 * Limits the result to only return the given fields
	 *
	 * @see QuerySelect::fields
	 * @return DataSet
	 */
	public function fields(){
		$this->queried = false;
		
		$data = Hash::args(func_get_args());
		$this->Storage->store('fields', $data);
		
		return $this;
	}
	
	/**
	 * Sorts the results by the desired order
	 *
	 * @see QuerySelect::order
	 * @return DataSet
	 */
	public function order(){
		$this->queried = false;
		
		$data = Hash::args(func_get_args());
		$this->Storage->store('order', $data);
		
		return $this;
	}
	
	/**
	 * Limits the data, exactly resembles the MySQL Limit expression
	 *
	 * @see Query::limit
	 * @param mixed $limit
	 * @param mixed $val
	 * @return DataSet
	 */
	public function limit($limit, $val = null){
		$this->queried = false;
		
		if($val) $limit = array($limit, $val);
		elseif(!is_array($limit)) $limit = array(0, $limit);
		
		$this->Storage->store('limit', $limit);
		
		return $this;
	}
	
	/**
	 * Returns the count of all data stored in this DataSet
	 *
	 * @return int
	 */
	public function quantity(){
		return count($this->Data);
	}
	
	/**
	 * Returns the defined subset of the given data
	 *
	 * @return array
	 */
	public function retrieve(){
		$this->queried = false;
		
		$this->Subset = array();
		$subset = $this->Data;
		
		/* Order */
		$order = $this->Storage->retrieve('order');
		if($order && $sequence = $this->getArguments($order)){
			$DataComparison = new DataComparison($subset);
			foreach($sequence as $seq){
				$seq = array_map('trim', explode(' ', $seq));
				if(empty($seq[0])) continue;
				
				$DataComparison->setField($seq[0])->setOrder(!(!empty($seq[1]) && String::toLower($seq[1])=='desc'));
				uksort($subset, array($DataComparison, 'sort'));
			}
		}
		/* Limit */
		$limit = $this->Storage->retrieve('limit');
		if(is_array($limit) && ($limit[0] || $limit[1])){
			$i = -1;
			foreach($subset as $k => $v){
				$i++;
				if($limit[0] && $i<$limit[0]) continue;
				
				$this->Subset[$k] = $v;
				if($i>$limit[1]) break;
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
	
	/**
	 * Adds a value to the DataSet
	 *
	 * @param array $value
	 * @param mixed $key
	 * @return DataSet
	 */
	public function push($value, $key = null){
		$this->queried = false;
		
		if(isset($key)) $this->Data[$key] = $value;
		else $this->Data[] = $value;
		
		return $this;
	}
	
	/**
	 * Removes a value from the DataSet by its key
	 *
	 * @param mixed $key
	 * @return DataSet
	 */
	public function pop($key){
		$this->queried = false;
		
		unset($this->Data[$key]);
		
		return $this;
	}
	
	/**
	 * Removes one or more values of the DataSet by the given value itself
	 *
	 * @param array $value
	 * @return DataSet
	 */
	public function remove($value){
		$this->queried = false;
		
		Hash::remove($this->Data, $value);
		
		return $this;
	}
	
	public function offsetExists($offset){
		return isset($this->Data[$offset]);
	}
	
	public function offsetSet($offset, $value){
		$this->Data[$offset] = $value;
	}
	
	public function offsetGet($offset){
		return isset($this->Data[$offset]) ? $this->Data[$offset] : null;
	}
	
	public function offsetUnset($offset){
		unset($this->Data[$offset]);
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