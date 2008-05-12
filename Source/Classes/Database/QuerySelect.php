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
	public function fields($data){
		unset($this->formatted);
		
		$this->Storage->store('fields', $data);
		
		return $this;
	}
	
	/**
	 * @param mixed $data
	 * @return QuerySelect
	 */
	public function order($data){
		unset($this->formatted);
		
		$this->Storage->store('order', $data);
		
		return $this;
	}
	
	public function format(){
		if($this->formatted) return $this->formatted;
		
		$out = parent::format();
		
		return $this->formatted = 'SELECT '.$this->formatFields().' FROM '.$this->table.$out.$this->formatOrder();
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
		return db::getInstance()->retrieve($this->format());
	}
	
	
	public function rewind(){
		if(!$this->queried){
			$this->cache = $this->retrieve();
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

}
?>