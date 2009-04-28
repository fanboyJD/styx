<?php
/*
 * Styx::Layer - MIT-style License
 * Author: christoph.pojer@gmail.com
 *
 * Usage: Responsible for all kind of data in- and output
 *
 */

abstract class LayerPrototype extends Runner {

	protected 
		/**
		 * @var array
		 */
		$Data = array(),
	
		/**
		 * @var Template
		 */
		$Template = null,
		
		/**
		 * @var Model
		 */
		$Model,
		
		/**
		 * @var Module
		 */
		$Module,
		
		/**
		 * @var Paginate
		 */
		$Paginate = null,
		
		$name,
		$base,
		$layername,
		
		$isMainLayer = false,
		$isRebound = false,
		$rebounds = array(),
		
		$methods = array(),
		$event = null,
		
		$options = array();
	
	public $get = array(),
		$post = array();
	
	/**
	 * @return Layer
	 */
	public static function create($name, $single = false){
		return Core::classExists($layer = $name.'layer') ? new $layer($name, $single) : false;
	}
	
	/**
	 * @return Layer
	 */
	public static function retrieve($layer){
		static $Instances;
		
		return empty($Instances[$layer = strtolower($layer)]) ? $Instances[$layer] = Layer::create($layer, true) : $Instances[$layer];
	}
	
	protected function __construct($name, $single = false){
		$this->base = $this->name = ucfirst($name);
		$this->layername = strtolower($this->name);
		$this->methods = Core::getMethods($this->layername.'layer');
		
		$this->Module = $single ? Module::create($this->getModuleName()) : Module::retrieve($this->getModuleName());
		if($this->Module){
			$this->Model = $this->Module->getModel();
			$this->options = $this->Module->getOptions();
		}
		
		$this->initialize();
	}
	
	protected function getModuleName(){
		return $this->name;
	}
	
	protected function initialize(){}
	protected function access(){ return true; }
	
	/**
	 * @return Layer
	 */
	public function fireEvent($event, $get = null, $post = null){
		foreach(array('get', 'post') as $v)
			if(!$this->{$v})
				$this->{$v} = Hash::length($$v) ? $$v : ($this->isMainLayer || !in_array($v, $this->options['preventPass']) ? Request::retrieve($v) : array());
		
		// Event may use some UTF-8 special chars and there is no method with that, but we still play nice with String::toLower and mbstring
		$fireEvent = String::toLower($event);
		if(!in_array($fireEvent, $this->methods)){
			$default = $this->options['defaultEvent'];
			
			$this->get[$default] = $event;
			$fireEvent = $default;
		}
		
		$this->Template = Template::map()->base('Layers', $this->name)->bind($this);
		$this->event = $fireEvent;
		
		try{
			if(!$this->access()) return $this;
			
			$this->{'on'.ucfirst($fireEvent)}(isset($this->get[$fireEvent]) ? $this->get[$fireEvent] : null);
		}catch(Exception $e){
			$this->rebound($e->getMessage());
		}
		
		return $this;
	}
	
	protected function rebound($message){
		if(!$this->isRebound && $this->options['rebound'] && Hash::length($this->post)){
			$this->isRebound = true;
			
			$event = $this->getReboundEvent($this->event);
			if(!$event) $event = $this->options['defaultEditEvent'];
			
			if($event && in_array($event, $this->methods)){
				$this->get[$event] = isset($this->get[$this->event]) ? $this->get[$this->event] : null;
				
				$this->fireEvent($event, $this->get, $this->post);
			}
		}
		
		if($this->Template->hasFile()){
			$prefix = Core::retrieve('elements.prefix');
			$this->Template->assign(array(($prefix ? $prefix.'.' : '').'form.message' => $message));
		}else{
			$this->Template->prepend($message);
		}
	}
	
	public function setMainLayer(){
		$this->isMainLayer = true;
		
		return $this;
	}
	
	public function isMainLayer(){
		return $this->isMainLayer;
	}
	
	protected function getReboundEvent($from){
		return !empty($this->rebounds[$from]) ? $this->rebounds[$from] : null;
	}
	
	protected function setReboundEvent($event, $to){
		return $this->rebounds[$event] = strtolower($to);
	}
	
	public function isRebound(){
		return $this->isRebound;
	}
	
	public function paginate($class = null){
		if($this->Paginate && strtolower(get_class($this->Paginate))==strtolower(pick($class, 'Paginate')))
			return $this->Paginate;
		
		return $this->Paginate = Paginate::retrieve($class)->bind($this)->object($this->Model);
	}
	
	public function link($title = null, $event = null, $options = null, $showEvent = false){
		static $Configuration;	
		if(!$Configuration)
			$Configuration = array(
				'identifier' => $this->Model instanceof Model ? $this->Model->getIdentifier('external') : Core::retrieve('identifier.external'),
				'contenttype.querystring' => Core::retrieve('contenttype.querystring')
			);
		
		if(is_array($title) || is_object($title)) $title = $title[$Configuration['identifier']];
		
		if($options && is_scalar($options) && !empty($Configuration['contenttype.querystring']))
			$options = array($Configuration['contenttype.querystring'] => $options);
		
		if(!$event || !in_array($event, $this->methods))
			$event = $this->options['defaultEvent'];
		
		$base = array(strtolower($this->base));
		if($title || ($event && ($event!=$this->options['defaultEvent'] || $showEvent))){
			if(!$title) $base[] = $event;
			else if(in_array($title, $this->methods) || $event!=$this->options['defaultEvent']) $base[] = array($event, $title);
			else $base[] = $title;
		}
		
		return Response::link($options, $base);
	}
	
	public function parse($return = true){
		return $this->Template->parse($return);
	}
	
	public function register($name = null){
		if($name) $this->layername = $name;
		
		Page::getInstance()->register('layer.'.$this->layername, $this->Template);
		
		return $this;
	}
	
	public function deregister(){
		Page::getInstance()->deregister($this->Template);
		
		return $this;
	}
	
}