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
		 * @var Paginate
		 */
		$Paginate = null,
		
		$isMainLayer = false,
		$name,
		$base,
		$layername,
		
		$rebound = false,
		$rebounds = array(),
		
		$methods = array(),
		$event = null,
		
		$options = array(
			/*'model' => null,*/
			'rebound' => true,
			'defaultEvent' => 'view',
			'defaultEditEvent' => 'edit',
			'preventPass' => array('post'), // Prevents passing post/get variable if the layer is not the Mainlayer
		);
	
	public $get = array(),
		$post = array();
	
	/**
	 * @return Layer
	 */
	public static function create($layer){
		return Core::classExists($layer .= 'layer') && is_subclass_of($layer, 'layer') ? new $layer : false;
	}
	
	/**
	 * @return Layer
	 */
	public static function retrieve($layer){
		static $Instances;
		
		return empty($Instances[$layer = strtolower($layer)]) ? $Instances[$layer] = Layer::create($layer) : $Instances[$layer];
	}
	
	protected function __construct(){
		$this->base = $this->name = ucfirst(substr(get_class($this), 0, -5));
		$this->layername = strtolower($this->name);
		$this->methods = Core::getMethods($this->layername.'layer');
		
		$initialize = $this->initialize();
		if(is_array($initialize)) Hash::extend($this->options, $initialize);
		
		$model = isset($this->options['model']) ? $this->options['model'] : strtolower($this->name);
		if($model) $this->Model = Model::create($model);
		unset($this->options['model']);
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
		$event = String::toLower($event);
		if(!in_array($event, $this->methods)){
			$default = $this->options['defaultEvent'];
			
			$this->get[$default] = $event;
			$event = $default;
		}
		
		$this->Template = Template::map()->base('Layers', $this->name)->bind($this);
		$this->event = $event;
		
		try{
			if(!$this->access()) return $this;
			
			$this->{'on'.ucfirst($event)}(isset($this->get[$event]) ? $this->get[$event] : null);
		}catch(Exception $e){
			$this->rebound($e->getMessage());
		}
		
		return $this;
	}
	
	protected function rebound($message){
		if(!$this->rebound && $this->options['rebound'] && Hash::length($this->post)){
			$this->rebound = true;
			
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
		return $this->rebound;
	}
	
	public function paginate($class = null){
		if($this->Paginate && strtolower(get_class($this->Paginate))==strtolower(pick($class, 'Paginate')))
			return $this->Paginate;
		
		return $this->Paginate = Paginate::retrieve($class)->bind($this)->model($this->Model);
	}
	
	public function link($title = null, $event = null, $options = null, $showEvent = false){
		static $Configuration;	
		if(!$Configuration)
			$Configuration = array(
				'contenttype.querystring' => Core::retrieve('contenttype.querystring')
			);
		
		if((is_array($title) || is_object($title)) && $this->Model) $title = $title[$this->Model->getIdentifier('external')];
		
		if($options && !is_array($options) && !empty($Configuration['contenttype.querystring']))
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