<?php
abstract class Layer extends Runner {
	/**
	 * @var Form
	 */
	protected $form;
	
	/**
	 * @var Handler
	 */
	protected $Handler = null;
	
	protected $name,
		$layername,
		$base,
		$baseRights = 'edit',
		$table,
		
		$events = array(
			'view' => 'view',
			'save' => 'save',
		),
		
		$options = array(
			'identifier' => null,
		),
		
		$handlers = array('html'),
		$allowedHandlers = array(),
		$disallowedHandlers = array(),
		
		$methods = array(),
		$rights = null,
		
		$event = null,
		$where = null,
		$editing = false,
		$preparation = false;
	
	public $get = array(),
		$post = array();
	
	/**
	 * @var QuerySelect
	 */
	protected $data;
	
	protected static $Config,
		$Layers = array(
			'List' => array(),
			'Instances' => array(),
		);
	
	/**
	 * @return Layer
	 */
	public static function run($layerName, $event = null, $get = null, $post = null, $isRouted = false){
		if(!$layerName || !Core::autoload($layerName.'layer'))
			return false;
		
		$layerName = strtolower($layerName);
		$class = ucfirst($layerName).'Layer';
		
		if(!class_exists($class) || !is_subclass_of($class, 'Layer'))
			return false;
		
		if($isRouted && call_user_func(array($class, 'hide')))
			return false;
		
		$layer = new $class($layerName);
		
		if($event) $layer->handle($event, $get, $post);
		
		return $layer;
	}
	
	public static function hide(){
		return false;
	}
	
	/**
	 * @return Layer
	 */
	public static function retrieve($layer){
		$layer = strtolower($layer);
		if(!self::$Layers['Instances'][$layer])
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
			if(startsWith($m, 'on') && strlen($m)>=3)
				$this->methods[] = strtolower(substr($m, 2));
		
		$initialize = $this->initialize();
		Hash::splat($initialize);
		
		if(key_exists('table', $initialize))
			$this->table = $initialize['table'] ? $initialize['table'] : null;
		
		if(is_array($initialize['options'])) Hash::extend($this->options, $initialize['options']);
		
		if(!$this->options['identifier']){
			$id = Core::retrieve('identifier.id');
			$this->options['identifier'] = array(
				'internal' => $id,
				'external' => $id,
			);
		}elseif(!is_array($this->options['identifier'])){
			$this->options['identifier'] = array(
				'internal' => $this->options['identifier'],
				'external' => $this->options['identifier'],
			);
		}
		
		$this->form = $initialize['form'];
		
		$rights = Core::retrieve('rights.layer');
		if(is_array($rights[$this->name]))
			foreach(Hash::flatten($rights[$this->name]) as $name => $right)
				$this->rights[strtolower($name)] = true;
		elseif($rights[$this->name])
			$this->rights = 1;
	}
	
	public function initialize(){
		/*return array(
			'table' => '',
			'options' => array(
			
			),
			'form' => new Form()
		);*/
	}
	
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
		
		$this->data = db::select($this->table);
		$this->Handler = Handler::map('layer.'.$this->layername)->base('Layers', ucfirst($this->name))->bind($this);
		
		$this->get = $get ? $get : $_GET;
		$this->post = $post ? $post : $_POST;
		$this->event = $event[0];
		
		$exec = true;
		
		if($this->event==$this->getDefaultEvent('save')){
			if(is_array($this->post) && sizeof($this->post))
				$this->prepareData($this->post);
			else
				$exec = $this->error('data');
		}
		
		if(!method_exists($this, $event[1])){
			$ev = $this->getDefaultEvent('view');
			$event = array($ev, 'on'.ucfirst($ev));
			if(!method_exists($this, $event[1]))
				$exec = $this->error('handler');
		}
		
		/* When the allowed/disabled Handler arrays are set it checks for their contents
		   otherwise it checks for the global array that is responsible for the whole layer
		*/
		if($this->hasCustomHandler($event[0])){
			if(!Handler::behaviour($this->allowedHandlers[$event[0]]) && Handler::behaviour($this->disallowedHandlers[$event[0]]))
				$exec = $this->error('handler');
		}elseif(!Handler::behaviour($this->handlers)){
			$exec = $this->error('handler');
		}
		
		if($exec){
			if($this->hasRight($event[0])) $this->{$event[1]}($this->get['p'][$event[0]]);
			else $this->error('rights');
		}
		
		return $this;
	}
	
	/* EditHandler Begin */
	public function edit($options = array(
		'edit' => null,
		'preventDefault' => false,
	)){
		if(!$this->hasRight(pick($this->baseRights, $this->event), 'add'))
			return $this->error('rights');
		
		if(!$options['edit'] && !$options['preventDefault'] && $this->event && $this->get['p'][$this->event] && $this->hasRight(pick($this->baseRights, $this->event), 'modify'))
			$options['edit'] = array(
				$this->options['identifier']['external'] => array($this->get['p'][$this->event], $this->options['identifier']['external']),
			);
		
		if($options['edit'] && Validator::check($options['edit']) && $this->table){
			$data = db::select($this->table)->where($options['edit'])->fetch();
			if($data){
				$this->form->addElement(new HiddenInput(array(
					'name' => $this->options['identifier']['internal'],
					':alias' => true,
				)));
				
				$this->setValue($data);
				$this->where = $options['edit'];
				$this->editing = true;
			}
		}
		
		$this->populate();
		
		$this->form->get('action', $this->link($data[$this->options['identifier']['external']], $this->getDefaultEvent('save')));
	}
	
	public function add($options = null){
		$array = array('preventDefault' => true);
		return $this->edit($options ? Hash::extend($options, $array) : $array);
	}
	/* EditHandler End */
	
	/* SaveHandler Begin */
	public function validate(){
		$validate = $this->form->validate();
		if($validate!==true) throw new ValidatorException($validate);
	}
	
	public function prepareData($data){
		$this->preparation = true;
		
		if($data[$this->options['identifier']['internal']] && $this->hasRight(pick($this->baseRights, $this->event), 'modify')){
			$this->where = array(
				$this->options['identifier']['internal'] => array($data[$this->options['identifier']['internal']], $this->options['identifier']['internal']),
			);
			
			unset($data[$this->options['identifier']['internal']]);
			$this->editing = true;
		}
		
		$this->populate();
		
		$this->setValue($data, true);
	}
	
	public function save($where = null, $options = array(
		'preventDefault' => false,
	)){
		if(!$this->hasRight(pick($this->baseRights, $this->event), 'add'))
			throw new ValidatorException('rights');
		
		if(!$this->checkSession())
			throw new ValidatorException('session');
		
		if(!$where) $where = $this->where;
		
		if($options['preventDefault']) unset($where);
		
		$this->validate();
		
		$data = $this->form->prepareData();
		if(!$data) throw new NoDataException();
		elseif(!$this->table) throw new NoTableException();
		
		if($where) db::update($this->table)->set($data)->where($where)->query();
		else db::insert($this->table)->set($data)->query();
	}
	/* SaveHandler End */
	
	public function getDefaultEvent($event){
		return $this->events[$event];
	}
	
	public function setDefaultEvent($event, $name){
		return $this->events[$event] = strtolower($name);
	}
	
	public function getPagetitle($title, $where){
		if($this->table)
			$options['contents'] = db::select($this->table)->fields('id, pagetitle')->retrieve();
		
		if($where[$this->options['identifier']['internal']])
			$options['id'] = Data::call($where[$this->options['identifier']['internal']][0], $where[$this->options['identifier']['internal']][1]);
		
		return Data::pagetitle($title, $options);
	}
	
	public function link($title = null, $event = null, $handler = null){
		/* Yes, you heard me: Layer static, not self */
		if(!Layer::$Config)
			Layer::$Config = array(
				'path.separator' => Core::retrieve('path.separator'),
			);
		
		if(!$title && !$event) return $this->base;
		
		$default = $this->getDefaultEvent('view');
		if(!$event || !in_array($event, $this->methods))
			$event = $default;
		
		if(!$title) return $this->base.'/'.$event;
		
		if($handler){
			if($this->hasCustomHandler($event)){
				if(!in_array($handler, $this->allowedHandlers[$event]))
					unset($handler);
			}elseif(!in_array($handler, $this->handlers)){
				unset($handler);
			}
		}
		return ($handler ? 'handler'.Layer::$Config['path.separator'].$handler.'/' : '').$this->base.'/'.(in_array($title, $this->methods) || $event!=$default ? $event.Layer::$Config['path.separator'] : '').$title;
	}
	
	public function hasRight(){
		$args = Hash::args(func_get_args());
		
		if(!$this->rights || (is_array($this->rights) && !$this->rights[implode('.', $args)]))
			return true;
		
		array_unshift($args, 'layer', $this->name);
		return User::hasRight($args);
	}
	
	public function error($error){
		try {
			throw new ValidatorException($error);
		}catch(ValidatorException $e){
			$this->Handler->assign($e->getMessage());
		}
		
		return false;
	}
	
	public function generateSessionName(){
		return $this->name.'_session_'.md5($this->name.'.'.Core::retrieve('secure'));
	}
	
	public function requireSession(){
		$name = $this->generateSessionName();
		
		return $this->form->addElement(new HiddenInput(array(
			'name' => $name,
			'value' => $this->preparation ? $this->post[$name] : User::get(Core::retrieve('user.sessionfield')),
			':alias' => true,
		)));
	}
	
	public function checkSession(){
		$name = $this->generateSessionName();
		$el = $this->getElement($name);
		
		return !$el || ($el && User::checkSession($el->getValue()));
	}
	
	protected function populate(){
		/* doSomething(); */
	}
	
	public function allowHandler($methods, $handler){
		foreach(Hash::splat($methods) as $method){
			if(!in_array($method, $this->methods)) continue;
			
			$this->allowedHandlers[$method] = array_unique(array_merge(Hash::splat($this->allowedHandlers[$method]), Hash::splat($handler)));
		}
	}
	
	public function disallowHandler($methods, $handler){
		foreach(Hash::splat($methods) as $method){
			if(!in_array($method, $this->methods)) continue;
			
			$this->disallowedHandlers[$method] = array_unique(array_merge(Hash::splat($this->disallowedHandlers[$method]), Hash::splat($handler)));
		}
	}
	
	public function hasCustomHandler($event){
		return sizeof(Hash::splat($this->allowedHandlers[$event])) || sizeof(Hash::splat($this->disabledHandlers[$event]));
	}
	
	/**
	 * This Method parses the Handler of the given Layer and
	 * removes it from the Handler-Instances
	 */
	public function parse($return = true, $remove = true){
		$out = $this->Handler->parse($return);
		
		if($remove) Handler::remove($this->Handler->getName());
		
		return $out;
	}
	
	/* Form methods: mapped */
	
	public function format(){
		return $this->form->format();
	}
	
	public function getValue($name){
		return $this->form->getValue($name);
	}
	
	public function setValue($data, $raw = false){
		$this->form->setValue($data, $raw);
	}
	
	/**
	 * @param string $name
	 * @return Element
	 */
	public function getElement($name){
		return $this->form->getElement($name);
	}
	
}