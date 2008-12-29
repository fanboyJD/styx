<?php
/*
 * Styx::Paginate - MIT-style License
 * Author: christoph.pojer@gmail.com
 *
 * Usage: Dissects contents to several pages
 *
 */

class Paginate {
	
	private
		/**
		 * @var QuerySelect
		 */
		$Data,
		/**
		 * @var Layer
		 */
		$Layer,
		
		$options = array(
			'start' => 0,
			'per' => 10,
			'key' => 'start',
			'template' => null,
		),
		
		$initialized = false,
		
		$count = 0;

	public function initialize($data, $options){
		$this->initialized = true;
		
		$this->Data = $data;
		
		Hash::extend($this->options, $options);
		
		$this->options['per'] = Data::id($this->options['per']);
		
		if($this->Layer && !$this->options['start'] && !empty($this->Layer->get[$this->options['key']]))
			$this->options['start'] = $this->Layer->get[$this->options['key']];
		
		$this->options['start'] = Data::id($this->options['start'], $this->options['per']);
	}
	
	public static function retrieve($class){
		$self = String::toLower(__CLASS__);
		if(!$class || !Core::classExists($class) || (String::toLower($class)!=$self && !is_subclass_of($class, $self)))
			$class = $self;
		
		return new $class;
	}
	
	public function parse(){
		if(!$this->initialized) return;
		
		$this->count = $this->Data->quantity($this->Layer->getIdentifier('internal'));
		
		if($this->options['start']>=$this->count)
			$this->options['start'] = 0;
		
		$this->Data->limit($this->options['start'], $this->options['per']);
		
		if($this->count<=$this->options['per'] && !$this->options['start']) return;
		
		return Template::map(pick($this->options['template'], array('Paginate', 'Default.php')))->bind($this->Layer)->parse(true);
	}
	
	public function getPrevious(){
		return floor($this->options['start']/$this->options['per'])>=1 ? $this->options['start']-$this->options['per'] : false;
	}
	
	public function getNext(){
		return $this->count-$this->options['per']>$this->options['start'] ? $this->options['start']+$this->options['per'] : false;
	}
	
	public function getCurrent(){
		return floor($this->options['start']/$this->options['per'])+1;
	}
	
	public function getCount(){
		return ceil($this->count/$this->options['per']);
	}

	public function getStart(){
		return $this->options['start'];
	}
	
	public function getPer(){
		return $this->options['per'];
	}
	
	public function getKey(){
		return $this->options['key'];
	}
	
	/**
	 * @return Paginate
	 */
	public function bind($bind){
		if(is_object($bind)) $this->Layer = $bind;
		
		return $this;
	}
	
}