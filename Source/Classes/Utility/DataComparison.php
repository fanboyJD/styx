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
	 * It is optionally possible to set the field and the sort-order in the constructor already
	 *
	 * @param string $field
	 * @param int $order
	 */
	public function __construct($field = null, $order = true){
		$this->setField($field)->setOrder($order);
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
		$this->order = !$order || $order===-1 ? -1 : 1;
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
		if(!isset($a[$this->field]))
			return isset($b[$this->field]) ? 1*$this->order : 0;
		
		if(!isset($b[$this->field]))
			return isset($a[$this->field]) ? -1*$this->order : 0;
		
		return $a[$this->field]==$b[$this->field] ? 0 : ($a[$this->field]<$b[$this->field] ? -1*$this->order : 1*$this->order);
	}
	
}