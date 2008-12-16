<?php
/*
 * Styx::Layer - MIT-style License
 * Author: christoph.pojer@gmail.com
 *
 * Usage: Responsible for all kind of data in- and output
 *
 */

abstract class Layer extends Runner {

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
		
		$options = array(
			/*'table' => null,*/
			'cache' => true,
			'identifier' => null,
		),
		
		$methods = array(),
		
		$event = null,
		$where = null,
		$content = null,
		$editing = false;
	
	public $get = array(),
		$post = array();
	
	/**
	 * @return Layer
	 */
	public static function create($layername){
		if(!$layername || !Core::autoload($layername.'layer'))
			return false;
		
		$layername = strtolower($layername);
		$class = $layername.'layer';
		
		if(!is_subclass_of($class, 'layer'))
			return false;
		
		$layer = new $class($layername);
		
		return $layer;
	}
	
	/**
	 * @return Layer
	 */
	public static function retrieve($layer){
		static $Instances;
		
		$layer = strtolower($layer);
		if(empty($Instances[$layer]))
			return $Instances[$layer] = Layer::create($layer);
		
		return $Instances[$layer];
	}
	
	protected function __construct($name){
		$this->base = $this->name = ucfirst($name);
		$this->table = $this->layername = strtolower($name);
		
		foreach(get_class_methods($this) as $method)
			if(String::starts($method, 'on') && strlen($method)>=3)
				array_push($this->methods, strtolower(substr($method, 2)));
		
		$this->Form = new Form();
		
		$initialize = $this->initialize();
		Hash::splat($initialize);
		
		if(isset($initialize['table']))
			$this->table = pick($initialize['table']);
		
		if(isset($initialize['options']) && is_array($initialize['options']))
			Hash::extend($this->options, $initialize['options']);
		
		if(empty($this->options['identifier']))
			$this->options['identifier'] = array(
				'internal' => Core::retrieve('identifier.internal'),
				'external' => Core::retrieve('identifier.external'),
			);
		elseif(!is_array($this->options['identifier']))
			$this->options['identifier'] = array(
				'internal' => $this->options['identifier'],
				'external' => $this->options['identifier'],
			);
	}
	
	protected function initialize(){}
	
	protected function populate(){}
	
	/**
	 * @return Layer
	 */
	public function fire($event, $get = null, $post = null){
		$event = strtolower($event);
		if(!in_array($event, $this->methods)){
			$default = $this->getDefaultEvent('view');
			
			$get['p'][$default] = $event;
			$event = $default;
		}
		
		$this->Data = $this->table ? db::select($this->table, $this->options['cache']) : array();
		$this->Template = Template::map()->base('Layers', $this->name)->bind($this);
		
		foreach(array('get', 'post') as $v)
			$this->{$v} = Hash::length($$v) ? $$v : Request::retrieve($v);
		
		try{
			$this->event = $event;
			
			if(Request::getMethod()=='post'){
				if($this->post) $this->prepare($this->post);
				else throw new ValidatorException('data');
			}
			
			$this->{'on'.ucfirst($event)}(isset($this->get['p'][$event]) ? $this->get['p'][$event] : null);
		}catch(ValidatorException $e){
			$assign = $e->getMessage();
			
			if($this->Template->hasFile()){
				$prefix = Core::retrieve('elements.prefix');
				$assign = array(($prefix ? $prefix.'.' : '').'form.message' => $assign);
			}
			
			$this->Template->assign($assign);
		}
		
		return $this;
	}
	
	public function edit($options = array(
		'preventDefault' => false,
	)){
		if(empty($options['preventDefault']) && Request::getMethod()!='post') $this->prepare(null, true);
		
		$this->Form->get('action', $this->link(!empty($this->content[$this->options['identifier']['external']]) ? $this->content[$this->options['identifier']['external']] : null, $this->getDefaultEvent('save')));
	}
	
	public function add($options = null){
		$array = array('preventDefault' => true);
		
		return $this->edit($options ? Hash::extend($options, $array) : $array);
	}
	
	public function validate(){
		$validate = $this->Form->validate();
		
		if($validate===true) return;
		
		foreach($this->Form->prepare() as $name => $value)
			$this->post[$name] = $value;
		
		$event = $this->getDefaultEvent('edit');
		$this->get['p'][$event] = isset($this->get['p'][$this->event]) ? $this->get['p'][$this->event] : null;
		
		$this->fire($event, $this->get, $this->post);
		
		throw new ValidatorException($validate);
	}
	
	public function prepare($data = null, $fill = false){
		if($this->event && !empty($this->get['p'][$this->event]) && $this->table){
			$this->content = db::select($this->table, $this->options['cache'])->where(array(
				$this->options['identifier']['external'] => array($this->get['p'][$this->event], $this->options['identifier']['external']),
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
	
	public function save($where = null){
		if(!$this->checkSession())
			throw new ValidatorException('session');
		
		if(!$where) $where = $this->where;
		
		$this->validate();
		
		$data = $this->Form->prepare();
		if(!Hash::length($data)) throw new ValidatorException('data');
		
		if(!$this->table) return;
		
		if($where) $query = db::update($this->table)->where($where);
		else $query = db::insert($this->table);
		
		$query->set($data)->query();
	}
	
	public function delete($where = null){
		if(!$this->editing)
			throw new ValidatorException('data');
		
		if(!$this->checkSession())
			throw new ValidatorException('session');
		
		if(!$where) $where = $this->where;
		
		db::delete($this->table)->where($this->where)->query();
	}
	
	public function getDefaultEvent($event){
		return !empty($this->events[$event]) ? $this->events[$event] : null;
	}
	
	public function setDefaultEvent($event, $name){
		return $this->events[$event] = strtolower($name);
	}
	
	public function getPagetitle($title, $where = null, $options = array()){
		if(!$where) $where = $this->where;
		
		if($this->table)
			$options['contents'] = Hash::extend(Hash::splat($options['contents']), db::select($this->table, $this->options['cache'])->fields(array_unique($this->options['identifier']))->retrieve());
		
		if($where[$this->options['identifier']['internal']])
			$options[$this->options['identifier']['internal']] = Data::call($where[$this->options['identifier']['internal']][0], $where[$this->options['identifier']['internal']][1]);
		
		$options['identifier'] = $this->options['identifier'];
		
		return Data::pagetitle($title, $options);
	}
	
	public function link($title = null, $event = null, $options = null){
		static $Configuration;	
		if(!$Configuration)
			$Configuration = array(
				'default' => $this->getDefaultEvent('view'),
				'contenttype.querystring' => Core::retrieve('contenttype.querystring')
			);
		
		if(is_array($title)) $title = $title[$this->options['identifier']['external']];
		
		if($options && !is_array($options) && $Configuration['contenttype.querystring'])
			$options = array($Configuration['contenttype.querystring'] => $options);
		
		if(!$event || !in_array($event, $this->methods))
			$event = $Configuration['default'];
		
		$base = array(strtolower($this->name));
		if($title || ($event && $event!=$Configuration['default'])){
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
			'value' => Request::getMethod()=='post' && !empty($this->post[$name]) ? $this->post[$name] : User::get($uconfig['session']),
			':alias' => true,
		)));
	}
	
	public function checkSession(){
		$el = $this->Form->getElement($this->generateSessionName());
		
		return !$el || ($el && User::checkSession($el->getValue()));
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