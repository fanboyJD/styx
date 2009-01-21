<?php
/**
 * Styx::DataComparison - Compares multi-dimensional arrays and sorts them accordingly
 *
 * @package Styx
 * @subpackage Utility
 *
 * @license MIT-style License
 * @author Christoph Pojer <christoph.pojer@gmail.com>
 */

class DataComparison {
	
	/**
	 * The data to sort/compare
	 *
	 * @var array
	 */
	private $Data;
	/**
	 * The field to be sorted
	 *
	 * @var string
	 */
	private $field;
	/**
	 * Is -1 when doing a descending sort and 1 otherwise
	 *
	 * @var int
	 */
	private $order = 1;
	
	/**
	 * @param array $data The data to sort/compare
	 */
	public function __construct($data){
		$this->Data = $data;
	}
	
	/**
	 * The field that is used to sort the data
	 *
	 * @param string $field
	 * @return DataComparison
	 */
	public function setField($field){
		$this->field = $field;
		
		return $this;
	}
	
	/**
	 * Is true for ascending and false for descending sorting
	 *
	 * @param int $order
	 * @return DataComparison
	 */
	public function setOrder($order){
		$this->order = $order ? 1 : -1;
		
		return $this;
	}
	
	/**
	 * Sorts the array by the given key
	 *
	 * @param string $a
	 * @param string $b
	 * @return int
	 */
	public function sort($a, $b){
		if(!isset($this->Data[$a][$this->field]))
			return isset($this->Data[$b][$this->field]) ? 1*$this->order : 0;
		
		if(!isset($this->Data[$b][$this->field]))
			return isset($this->Data[$a][$this->field]) ? -1*$this->order : 0;
		
		$a = $this->Data[$a][$this->field];
		$b = $this->Data[$b][$this->field];
		
		return $a==$b ? 0 : ($a<$b ? -1*$this->order : 1*$this->order);
	}
	
}