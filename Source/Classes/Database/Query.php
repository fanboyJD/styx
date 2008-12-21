<?php
/*
 * Styx::Query - MIT-style License
 * Author: christoph.pojer@gmail.com
 *
 * Usage: Handles and processes UPDATE/INSERT/DELETE SQL-Statements
 *
 */

class Query {
	
	protected $Types = array('update', 'insert', 'delete'),
		$Storage,
		$type = '',
		$table = '',
		$formatted = null;
	
	public function __construct($table, $type){
		if(!in_array($type, $this->Types))
			$type = 'update';
		
		$this->table = $table;
		$this->type = $type;
		
		/*
		 * We cannot extend from Storage due to the
		 * use of store/retrieve in QuerySelect
		 */
		$this->Storage = new Storage();
	}
	
	/*
	 *	UPDATE and INSERT allow two input methods:
	 *		(string) "myField='something', myOtherField=10"
	 *		array(field=>value)
	 */
	protected function formatSet(){
		$data = $this->Storage->retrieve('set');
		if(!is_array($data))
			return $data;
		
		foreach($data as $k => $v)
			$out[] = $k.'='.($v!==null ? "'".Data::add(is_array($v) ? (count($v[1]) ? Data::call($v[0], $v[1]) : $v[0]) : $v)."'" : 'NULL');
		
		return implode(', ', $out);
	}
	
	/*
	 *	WHERE allows the following input methods:
	 *		(int) 15 => "id='15'"
	 *		(string) "id='15'"
	 *		array(id=>15, 'AND', Query::in('uid', array(1, 2, 3)))
	 *		array(array('id' => 5), 'OR', array('id' => 6))
	 */
	protected function formatWhere($deep = null){
		if($deep) $data = &$deep;
		else $data = $this->Storage->retrieve('where');
		
		if(!is_array($data) && is_string($data))
			return ' WHERE '.($data ? $data : 1);
		elseif(!$deep && !$data)
			return '';
		
		foreach($data as $k => $v){
			if(!ctype_digit((string)$k))
				$out[] = $k.'='.($v!==null ? "'".Data::add(is_array($v) ? (count($v[1]) ? Data::call($v[0], $v[1]) : $v[0]) : $v)."'" : 'NULL');
			elseif(is_array($v))
				$out[] = '('.$this->formatWhere($v).')';
			else
				$out[] = $v;
		}
		
		return (!$deep ? ' WHERE ' : '').($out ? implode(' ', $out) : 1);
	}
	
	protected function formatLimit(){
		$type = in_array($this->type, array('update', 'delete'));
		
		// This is awesome: through Storage it sets the limit value only if it has not been set yet
		$limit = $this->Storage->retrieve('limit', $type ? array(0, 1) : null);
		if(!$limit || (!$limit[0] && !$limit[1]))
			return '';
		
		return 'LIMIT '.(!$type ? $limit[0].',' : '').$limit[1];
	}
	
	/**
	 * @param array $data
	 * @return Query
	 */
	public function set($data){
		unset($this->formatted);
		
		$this->Storage->store('set', $data);
		
		return $this;
	}
	
	/**
	 * @return Query
	 */
	public function where(){
		unset($this->formatted);
		
		$data = Hash::args(func_get_args());
		$this->Storage->store('where', $data);
		
		return $this;
	}
	
	/**
	 * @param mixed $limit
	 * @param mixed $val
	 * @return Query
	 */
	public function limit($limit, $val = null){
		unset($this->formatted);
		
		if($val)
			$limit = array($limit, $val);
		elseif(!is_array($limit))
			$limit = array(0, $limit);
		
		$this->Storage->store('limit', $limit);
		
		return $this;
	}
	
	public function format($part = false){
		if(!empty($this->formatted)) return $this->formatted;
		
		$out = $this->formatWhere();
		
		if($this->type=='update')
			$out = 'UPDATE '.$this->table.' SET '.$this->formatSet().$out;
		elseif($this->type=='insert')
			$out = 'INSERT INTO '.$this->table.' SET '.$this->formatSet().$out;
		elseif($this->type=='delete')
			$out = 'DELETE FROM '.$this->table.$out;
		
		if($part) return array($out, ' '.$this->formatLimit());
		
		return $this->formatted = $out.' '.$this->formatLimit();
	}
	
	public function query(){
		$query = db::getInstance()->query($this->format());
		
		if($this->type!='select') Cache::getInstance()->eraseBy('QueryCache', $this->table.'_');
		
		return $query;
	}
	
	public function __toString(){
		return $this->format();
	}
	
	public static function in($key, $array){
		$array = array_unique(Hash::splat($array));
		$length = Hash::length($array);
		
		if($length>=2)
			foreach($array as $k => $v)
				$array[$k] = "'".Data::add($v)."'";
		
		return $length<2 ? $key."='".($length==1 ? reset($array) : '')."'" : $key.' IN ('.implode(',', $array).')';
	}
	
}