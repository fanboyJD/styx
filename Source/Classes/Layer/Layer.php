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
		$layername,
		$base,
		$table,
		
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
	
	private static $Layers = array(
			'List' => array(),
			'Instances' => array(),
		);
	
	/**
	 * @return Layer
	 */
	public static function run($layerName, $event = null, $get = null, $post = null){
		if(!$layerName || !Core::autoload($layerName.'layer'))
			return false;
		
		$layerName = strtolower($layerName);
		$class = ucfirst($layerName).'Layer';
		
		if(!is_subclass_of($class, 'Layer'))
			return false;
		
		$layer = new $class($layerName);
		
		if($event) $layer->handle($event, $get, $post);
		
		return $layer;
	}
	
	/**
	 * @return Layer
	 */
	public static function retrieve($layer){
		$layer = strtolower($layer);
		if(empty(self::$Layers['Instances'][$layer]))
			return Layer::run($layer);
		
		return self::$Layers['Instances'][$layer];
	}
	
	public function __construct($name){
		$this->base = $this->layername = $this->name = $this->table = $name;
		if(in_array($this->layername, self::$Layers['List']))
			$this->layername = Data::pagetitle($this->layername, array(
				'contents' => self::$Layers['List'],
			));
		
		self::$Layers['List'][] = $this->layername;
		self::$Layers['Instances'][$this->layername] = $this;
		
		foreach(get_class_methods($this) as $m)
			if(String::starts($m, 'on') && strlen($m)>=3)
				$method = $this->methods[] = strtolower(substr($m, 2));
		
		$initialize = $this->initialize();
		Hash::splat($initialize);
		
		if(key_exists('table', $initialize))
			$this->table = pick($initialize['table']);
		
		if(isset($initialize['options']) && is_array($initialize['options'])) Hash::extend($this->options, $initialize['options']);
		
		if(!$this->options['identifier'])
			$this->options['identifier'] = array(
				'internal' => Core::retrieve('identifier.internal'),
				'external' => Core::retrieve('identifier.external'),
			);
		elseif(!is_array($this->options['identifier']))
			$this->options['identifier'] = array(
				'internal' => $this->options['identifier'],
				'external' => $this->options['identifier'],
			);
		
		$prefix = Core::retrieve('elements.prefix');
		
		$this->Form = $initialize['form'];
	}
	
	public function initialize(){}
	
	protected function populate(){}
	
	/**
	 * @return Layer
	 */
	public function handle($event, $get = null, $post = null){
		if(!in_array($event, $this->methods)){
			$default = $this->getDefaultEvent('view');
			
			$get['p'][$default] = $event;
			$event = $default;
		}
		
		$event = array(strtolower($event));
		$event[] = 'on'.ucfirst($event[0]);
		
		$this->Data = $this->table ? db::select($this->table, $this->options['cache']) : array();
		$this->Template = Template::map()->base('Layers', ucfirst($this->name))->bind($this);
		Page::getInstance()->register('layer.'.$this->layername, $this->Template);
		
		$this->get = Hash::length($get) ? $get : Request::retrieve('get');
		$this->post = Hash::length($post) ? $post : Request::retrieve('post');
		
		try{
			if(!method_exists($this, $event[1])){
				$ev = $this->getDefaultEvent('view');
				$event = array($ev, 'on'.ucfirst($ev));
				if(!method_exists($this, $event[1]))
					throw new ValidatorException('contenttype');
			}
			
			$this->event = $event[0];
			
			if(Request::getMethod()=='post'){
				if($this->post) $this->prepare($this->post);
				else throw new ValidatorException('data');
			}
			
			$this->{$event[1]}(isset($this->get['p'][$event[0]]) ? pick($this->get['p'][$event[0]]) : null);
		}catch(ValidatorException $e){
			$this->Template->assign($e->getMessage());
		}
		
		return $this;
	}
	
	public function edit($options = array(
		'edit' => null,
		'preventDefault' => false,
	)){
		if(empty($options['edit']) && !$options['preventDefault'] && $this->event && $this->get['p'][$this->event])
			$options['edit'] = array(
				$this->options['identifier']['external'] => array($this->get['p'][$this->event], $this->options['identifier']['external']),
			);
		
		if(!empty($options['edit']) && Validator::check($options['edit']) && $this->table){
			$data = db::select($this->table, $this->options['cache'])->where($options['edit'])->fetch();
			if($data){
				$this->setValue($data);
				$this->content = $data;
				$this->where = $options['edit'];
				$this->editing = true;
			}
		}
		
		$this->populate();
		
		$this->Form->get('action', $this->link(!empty($data[$this->options['identifier']['external']]) ? $data[$this->options['identifier']['external']] : null, $this->getDefaultEvent('save')));
	}
	
	public function add($options = null){
		$array = array('preventDefault' => true);
		
		return $this->edit($options ? Hash::extend($options, $array) : $array);
	}
	
	public function validate(){
		$validate = $this->Form->validate();
		
		if($validate!==true) throw new ValidatorException($validate);
	}
	
	public function prepare($data){
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
		
		$this->setValue($data, true);
	}
	
	public function save($where = null, $options = array(
		'preventDefault' => false,
	)){
		if(!$this->checkSession())
			throw new ValidatorException('session');
		
		if(!$where && !$options['preventDefault'])
			$where = $this->where;
		
		$this->validate();
		
		$data = $this->Form->prepare();
		if(!$data) throw new ValidatorException('data');
		
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
		
		if(!$where)
			$where = $this->where;
		
		db::delete($this->table)->where($this->where)->query();
	}
	
	public function getDefaultEvent($event){
		return pick($this->events[$event]);
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
		
		$base = array($this->base);
		if($title || ($event && $event!=$Configuration['default'])){
			if(!$title)
				$base[] = $event;
			else if(in_array($title, $this->methods) || $event!=$Configuration['default'])
				$base[] = array($event, $title);
			else
				$base[] = $title;
		}
		
		return Response::link($options, $base);
	}
	
	protected function generateSessionName(){
		return $this->name.'_session_'.sha1($this->name.'.'.Core::retrieve('secure'));
	}
	
	public function requireSession(){
		$name = $this->generateSessionName();
		if($this->getElement($name))
			return;
		
		$uconfig = Core::retrieve('user');
		
		return $this->Form->addElement(new HiddenInput(array(
			'name' => $name,
			'value' => Request::getMethod()=='post' && !empty($this->post[$name]) ? $this->post[$name] : User::get($uconfig['session']),
			':alias' => true,
		)));
	}
	
	public function checkSession(){
		$el = $this->getElement($this->generateSessionName());
		
		return !$el || ($el && User::checkSession($el->getValue()));
	}
	
	/**
	 * This Method parses the Template and
	 * deregisters its reference inside the Page-Class
	 */
	public function parse($return = true, $remove = true){
		$out = $this->Template->parse($return);
		
		if($remove) Page::getInstance()->deregister('layer.'.$this->layername);
		
		return $out;
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
	
	/**
	 * @param string $name
	 * @return Element
	 */
	public function getElement($name){
		return $this->Form->getElement($name);
	}
	
}