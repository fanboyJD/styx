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
		 * @var Form
		 */
		$Form,
		/**
		 * @var QuerySelect
		 */
		$Data,
	
		/**
		 * @var Template
		 */
		$Template = null,
		
		/**
		 * @var Paginate
		 */
		$Paginate = null,
		
		$isMainLayer = false,
		$name,
		$base,
		$table,
		$layername,
		
		$events = array(
			'view' => 'view',
			'edit' => 'edit',
			'add' => 'edit',
			'save' => 'save',
		),
		
		$rebounds = array(
			
		),
		
		$options = array(
			/*'table' => null,*/
			'rebound' => true,
			'cache' => true,
			'preventPass' => array('post'), // Prevents passing post/get variable if the layer is not the Mainlayer
			'identifier' => null,
		),
		
		$methods = array(),
		
		$request = null,
		$event = null,
		$where = null,
		$content = null,
		$editing = false;
	
	public $get = array(),
		$post = array();
	
	/**
	 * @return Layer
	 */
	public static function create($layer){
		if(!Core::autoload($layer .= 'layer'))
			return false;
		
		return is_subclass_of($layer, 'layer') ? new $layer() : false;
	}
	
	/**
	 * @return Layer
	 */
	public static function retrieve($layer){
		static $Instances;
		
		$layer = strtolower($layer);
		
		return empty($Instances[$layer]) ? $Instances[$layer] = Layer::create($layer) : $Instances[$layer];
	}
	
	protected function __construct(){
		$this->base = $this->name = ucfirst(substr(get_class($this), 0, -5));
		$this->table = $this->layername = strtolower($this->name);
		
		$this->methods = Core::getMethods($this->layername.'layer');
		
		$this->request = Request::retrieve('method');
		$this->Form = new Form();
		
		$initialize = $this->initialize();
		
		if(isset($initialize['table'])){
			$this->table = pick($initialize['table']);
			unset($initialize['table']);
		}
		
		if(is_array($initialize)) Hash::extend($this->options, $initialize);
		
		if(!is_array($this->options['preventPass']))
			$this->options['preventPass'] = array();
		
		$this->options['identifier'] = Core::getIdentifier($this->options['identifier']);
	}
	
	protected function initialize(){}
	
	protected function populate(){}
	
	protected function access(){
		return true;
	}
	
	/**
	 * @return Layer
	 */
	public function fireEvent($event, $get = null, $post = null){
		foreach(array('get', 'post') as $v)
			$this->{$v} = Hash::length($$v) ? $$v : ($this->isMainLayer() || !in_array($v, $this->options['preventPass']) ? Request::retrieve($v) : array());
		
		// Event may use some UTF-8 special chars, but there is no method with that, but we still play nice with String::toLower and mbstring
		$event = String::toLower($event);
		if(!in_array($event, $this->methods)){
			$default = $this->getDefaultEvent('view');
			
			$this->get[$default] = $event;
			$event = $default;
		}
		
		$this->Template = Template::map()->base('Layers', $this->name)->bind($this);
		$this->event = $event;
		
		try{
			if(!$this->access()) return $this;
			$this->Data = $this->table ? $this->select() : array();
			
			if($this->request=='post' && Hash::length($this->post))
				$this->prepare($this->post);
			
			$this->{'on'.ucfirst($event)}(isset($this->get[$event]) ? $this->get[$event] : null);
		}catch(Exception $e){
			$this->rebound($e);
		}
		
		return $this;
	}
	
	public function rebound($e){
		static $rebound;
		
		if(!$rebound && $this->options['rebound'] && $this->request=='post' && Hash::length($this->post)){
			$rebound = true;
			foreach($this->Form->prepare() as $name => $value)
				$this->post[$name] = $this->Form->getElement($name)->get('type')=='password' ? null : $value;
			
			$event = $this->getReboundEvent($this->event);
			if(!$event) $event = $this->getDefaultEvent('edit');
			
			if($event){
				$this->get[$event] = isset($this->get[$this->event]) ? $this->get[$this->event] : null;
				
				$this->fireEvent($event, $this->get, $this->post);
			}
		}
		
		$assign = $e->getMessage();
		
		if($this->Template->hasFile()){
			$prefix = Core::retrieve('elements.prefix');
			$this->Template->assign(array(($prefix ? $prefix.'.' : '').'form.message' => $assign));
		}else{
			$this->Template->append($assign, true);
		}
	}
	
	public function edit($options = array(
		'preventDefault' => false,
	)){
		if(empty($options['preventDefault']) && $this->request!='post') $this->prepare(null, true);
		else $this->populate();
		
		$this->Form->get('action', $this->link(!empty($this->content[$this->options['identifier']['external']]) ? $this->content[$this->options['identifier']['external']] : null, $this->getDefaultEvent('save')));
	}
	
	public function add($options = null){
		$this->populate();
		
		$array = array('preventDefault' => true);
		
		return $this->edit($options ? Hash::extend($options, $array) : $array);
	}
	
	public function prepare($data = null, $fill = false){
		if($this->event && !empty($this->get[$this->event]) && $this->table){
			$this->content = Database::select($this->table, $this->options['cache'])->where(array(
				$this->options['identifier']['external'] => array($this->get[$this->event], $this->options['identifier']['external']),
			))->fetch();
			
			if($this->content){
				$this->where = array(
					$this->options['identifier']['internal'] => array($this->content[$this->options['identifier']['internal']], $this->options['identifier']['internal']),
				);
				
				$this->editing = true;
			}
		}
		
		$this->populate();
		
		$this->setValue(!$data && $fill && $this->content ? $this->content : $data, true);
	}
	
	public function validate(){
		if(!$this->checkSession())
			throw new ValidatorException('session');
		
		$validate = $this->Form->validate();
		
		if($validate!==true)
			throw new ValidatorException($validate);
		
		$data = $this->Form->prepare();
		if(!Hash::length($data))
			throw new ValidatorException('data');
		
		return $data;
	}
	
	public function save($where = null){
		if(!$where) $where = $this->where;
		
		$data = $this->validate();
		
		if(!$this->table) return;
		
		if($where) $query = Database::update($this->table)->where($where);
		else $query = Database::insert($this->table);
		
		$query->set($data)->query();
	}
	
	public function delete($where = null){
		if(!$this->editing)
			throw new ValidatorException('data');
		
		if(!$this->checkSession())
			throw new ValidatorException('session');
		
		if(!$where) $where = $this->where;
		
		Database::delete($this->table)->where($this->where)->query();
	}
	
	public function getDefaultEvent($event){
		return !empty($this->events[$event]) ? $this->events[$event] : null;
	}
	
	public function setDefaultEvent($event, $name){
		return $this->events[$event] = strtolower($name);
	}
	
	public function getReboundEvent($from){
		return !empty($this->rebounds[$from]) ? $this->rebounds[$from] : null;
	}
	
	public function setReboundEvent($event, $to){
		return $this->rebounds[$event] = strtolower($to);
	}
	
	public function getPagetitle($title, $where = null, $options = array()){
		if(!$where) $where = $this->where;
		
		if($this->table)
			$options['contents'] = Hash::extend(Hash::splat($options['contents']), Database::select($this->table, $this->options['cache'])->fields(array_unique($this->options['identifier']))->retrieve());
		
		if(!empty($where[$this->options['identifier']['internal']]))
			$options['id'] = Data::call($where[$this->options['identifier']['internal']][0], $where[$this->options['identifier']['internal']][1]);
		
		$options['identifier'] = $this->options['identifier'];
		
		return Data::pagetitle($title, $options);
	}
	
	public function getIdentifier($identifier = null){
		return $identifier && !empty($this->options['identifier'][$identifier]) ? $this->options['identifier'][$identifier] : $this->options['identifier'];
	}
	
	public function setMainLayer(){
		$this->isMainLayer = true;
		
		return $this;
	}
	
	public function isMainLayer(){
		return $this->isMainLayer;
	}
	
	public function paginate($class = null){
		if($this->Paginate && strtolower(get_class($this->Paginate))==strtolower(pick($class, 'Paginate')))
			return $this->Paginate;
		
		return $this->Paginate = Paginate::retrieve($class)->bind($this);
	}
	
	public function link($title = null, $event = null, $options = null, $showEvent = false){
		static $Configuration;	
		if(!$Configuration)
			$Configuration = array(
				'default' => $this->getDefaultEvent('view'),
				'contenttype.querystring' => Core::retrieve('contenttype.querystring')
			);
		
		if(is_array($title) || is_object($title)) $title = $title[$this->options['identifier']['external']];
		
		if($options && !is_array($options) && !empty($Configuration['contenttype.querystring']))
			$options = array($Configuration['contenttype.querystring'] => $options);
		
		if(!$event || !in_array($event, $this->methods))
			$event = $Configuration['default'];
		
		$base = array(String::toLower($this->base));
		if($title || ($event && ($event!=$Configuration['default'] || $showEvent))){
			if(!$title) $base[] = $event;
			else if(in_array($title, $this->methods) || $event!=$Configuration['default']) $base[] = array($event, $title);
			else $base[] = $title;
		}
		
		return Response::link($options, $base);
	}
	
	protected function generateSessionName(){
		return $this->name.'_session_'.sha1($this->name.'.'.Core::retrieve('secure'));
	}
	
	public function requireSession(){
		$name = $this->generateSessionName();
		if($this->Form->getElement($name)) return;
		
		$uconfig = Core::retrieve('user');
		
		return $this->Form->addElement(new HiddenInput(array(
			'name' => $name,
			'value' => $this->request=='post' && !empty($this->post[$name]) ? $this->post[$name] : User::get($uconfig['session']),
			':alias' => true,
		)));
	}
	
	public function checkSession(){
		$el = $this->Form->getElement($this->generateSessionName());
		
		return !$el || ($el && User::checkSession($el->getValue()));
	}
	
	public function select(){
		return Database::select($this->table, $this->options['cache']);
	}
	
	/* Form methods mapping */
	
	public function format(){
		return $this->Form->format();
	}
	
	public function getValue($name){
		return $this->Form->getValue($name);
	}
	
	public function setValue($data, $raw = false){
		$this->Form->setValue($data, $raw);
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