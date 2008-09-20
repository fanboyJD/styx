<?php
/*
 * Styx::Tree - MIT-style License
 * Author: christoph.pojer@gmail.com
 *
 * Usage: Creates a tree of (database) data to allow certain operations on it
 *
 */


class Tree {
	
	/**
	 * @var QuerySelect
	 */
	private $select;
	private $identifier,
		$recursive = 'cat',
		$tree,
		$populatedTree;

	public function __construct($options = array(
		'select' => null,
		'recursive' => null,
		'identifier' => null,
	)){
		$this->select = $options['select'];
		
		if($options['recursive']) $this->recursive = $options['recursive'];

		$this->identifier = pick($options['identifier'], Core::retrieve('identifier.id'));
	}
	
	private function createTree($cat = 0, $deep = 0, $parents = null){
		if(is_array($this->populatedTree))
			return $this->populatedTree;
		
		if(!$this->tree)
			foreach($this->select as $t)
				$this->tree[$t[$this->recursive]][] = $t;
		
		$array = array();
		if(!is_array($this->tree[$cat]))
			return $array;
		
		if(!$parents) $parents = array();
		if($cat) $parents[] = $cat;
		$parents = array_unique($parents);
		
		foreach($this->tree[$cat] as $k => $data){
			$data['deep'] = $deep;
			$data['parents'] = $parents;
			
			$array[$data[$this->identifier]] = $data;
			
			if(is_array($this->tree[$data[$this->identifier]])){
				$par = $parents;
				$par[] = $data[$this->identifier];
				$array = Hash::extend($array, $this->createTree($data[$this->identifier], $deep+1, $par));
			}
		}
		
		if(!$cat) $this->populatedTree = $array;
		
		return $array;
	}
	
	public function retrieve($id = 0){
		$array = array();
		
		foreach($this->createTree() as $data){
			if($id && ($id==$data[$this->identifier] || in_array($id, $data['parents'])))
				continue;
			
			$array[$data[$this->identifier]] = $data;
		}
		
		return $array;
	}
	
}