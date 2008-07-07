<?php
class QuerySelect extends QueryHandler implements Iterator {
	
	private $cache = array(),
		$queried = false;
	
	public function __construct($table){
		self::$Types[] = 'select';
		
		parent::__construct($table, 'select');
	}

	protected function formatFields(){
		$data = $this->Storage->retrieve('fields', '*');
		
		return is_array($data) ? implode(',', $data) : $data;
	}
	
	protected function formatOrder(){
		$data = $this->Storage->retrieve('order');
		
		return $data ? ' ORDER BY '.implode(',', splat($data)) : '';
	}
	
	/**
	 * @param array $data
	 * @return QuerySelect
	 */
	public function fields(){
		unset($this->formatted);
		
		$fields = func_get_args();
		if(sizeof($fields)==1) $fields = splat($fields[0]);
		
		$this->Storage->store('fields', $fields);
		
		return $this;
	}
	
	/**
	 * @param mixed $data
	 * @return QuerySelect
	 */
	public function order(){
		unset($this->formatted);
		
		$data = func_get_args();
		if(sizeof($data)==1) $data = splat($data[0]);
		
		$this->Storage->store('order', $data);
		
		return $this;
	}
	
	public function format(){
		if($this->formatted) return $this->formatted;
		
		$out = parent::format(true);
		
		return $this->formatted = 'SELECT '.$this->formatFields().' FROM '.$this->table.$out[0].$this->formatOrder().$out[1];
	}
	
	public function fetch($type = null){
		// To overcome big queries
		$this->Storage->retrieve('limit', array(0, 1));
		
		return db::getInstance()->fetch($this->query(), $type);
	}
	
	public function store($key = 0){
		db::getInstance()->store($this->format(), $key);
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
	
}