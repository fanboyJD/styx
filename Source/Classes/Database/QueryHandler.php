<?php
class QueryHandler {
	
	protected static $Types = array('update', 'insert', 'delete');
	
	protected $Storage,
		$type = '',
		$table = '',
		$formatted = null;
	
	public function __construct($table, $type){
		if(!in_array($type, self::$Types))
			$type = 'select';
		
		$this->type = $type;
		$this->table = $table;
		
		// We cannot extend from DynamicStorage du to the
		// use of store/retrieve in QuerySelect
		$this->Storage = new DynamicStorage();
	}
	
	/*
		UPDATE and INSERT allow two input methods:
			fields = (string) "myField='something', myOtherField=10"
			fields = array(field=>value)
	*/
	
	protected function formatSet(){
		$data = $this->Storage->retrieve('set');
		if(!is_array($data))
			return $data;
		
		foreach($data as $k => $v)
			$out[] = $k.'='.($v!==null ? "'".Data::add(is_array($v) ? (sizeof($v[1]) ? Data::call($v[0], $v[1]) : $v[0]) : $v)."'" : 'NULL');
		
		return implode(', ', $out);
	}
	
	/*
		SELECT allows the following input methods:
			data = (int) 15 => "id='15'"
			data = (string) "id='15'"
			data = array(id=>15, 'AND', Data::in('uid', array(1, 2, 3)))
			data = array(array('id' => 5), 'OR', array('id' => 6))
	*/
	protected function formatWhere($deep = null){
		if($deep)
			$data = &$deep;
		else
			$data = $this->Storage->retrieve('where');
		
		if(!is_array($data) && is_string($data))
			return 'WHERE '.($data ? $data : 1);
		elseif(!$deep && !$data)
			return '';
		
		foreach($data as $k => $v){
			if(!ctype_digit((string)$k))
				$out[] = $k.'='.($v!==null ? "'".Data::add(is_array($v) ? (sizeof($v[1]) ? Data::call($v[0], $v[1]) : $v[0]) : $v)."'" : 'NULL');
			elseif(is_array($v))
				$out[] = '('.$this->formatWhere($v).')';
			else
				$out[] = $v;
		}
		
		return (!$deep ? 'WHERE ' : '').($out ? implode(' ', $out) : 1);
	}
	
	protected function formatLimit(){
		$type = in_array($this->type, array('update', 'delete'));
		
		// This is awesome: through DynamicStorage it sets the limit value only if it has not been set yet
		$limit = $this->Storage->retrieve('limit', $type ? array(0, 1) : null);
		if(!$limit || (!$limit[0] && !$limit[1]))
			return '';
		
		return 'LIMIT '.(!$type ? $limit[0].',' : '').$limit[1];
	}
	
	/**
	 * @param array $data
	 * @return QueryHandler
	 */
	public function set($data){
		unset($this->formatted);
		
		$this->Storage->store('set', $data);
		
		return $this;
	}
	
	/**
	 * @param array $data
	 * @return QueryHandler
	 */
	public function where(){
		$data = func_get_args();
		if(sizeof($data)==1) $data = splat($data[0]);
		
		unset($this->formatted);
		
		$this->Storage->store('where', $data);
		
		return $this;
	}
	
	/**
	 * @param mixed $limit
	 * @param mixed $val
	 * @return QueryHandler
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
		if($this->formatted) return $this->formatted;
		
		$out = ' '.$this->formatWhere();
		
		if($this->type=='update')
			$out = 'UPDATE '.$this->table.' SET '.$this->formatSet().$out;
		elseif($this->type=='insert')
			$out = 'INSERT INTO '.$this->table.' SET '.$this->formatSet().$out;
		elseif($this->type=='delete')
			$out = 'DELETE FROM '.$this->table.$out;
		
		if($part) return array($out, ' '.$this->formatLimit());
		
		return $this->formatted = $out.' '.$this->formatLimit();
	}
	
	public function __toString(){
		return $this->format();
	}
	
	public function query(){
		return db::getInstance()->query($this->format());
	}
	
}