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
		$table,
		
		$events = array(
			'view' => 'view',
			'save' => 'save',
			'error' => 'error',
		),
		
		$javascript = array(
			'helper' => '',
			'helpername' => '',
		),
		
		$options = array(
			'identifier' => null,
			'javascript' => array(),
		),
		
		$methods = array(),
		
		$event = null,
		$where = null,
		$editing = false;
	
	public $get = array(),
		$post = array();
	
	/**
	 * @var QuerySelect
	 */
	protected $data;
	
	protected static $Layers = array(
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
		
		if(!is_subclass_of($class, 'Layer'))
			return false;
		
		if($isRouted && method_exists($class, 'hide') && call_user_func(array($class, 'hide')))
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
		if(!self::$Layers['Instances'][$layer])
			return Layer::run($layer);
		
		return self::$Layers['Instances'][$layer];
	}
	
	public function __construct($name){
		$this->layername = $this->name = $this->table = $name;
		if(in_array($this->layername, self::$Layers['List']))
			$this->layername = Data::pagetitle($this->layername, array(
				'contents' => self::$Layers['List'],
			));
		
		self::$Layers['List'][] = $this->layername;
		self::$Layers['Instances'][$this->layername] = $this;
		
		$initialize = $this->initialize();
		
		foreach(get_class_methods($this) as $m)
			if(startsWith($m, 'on') && strlen($m)>=3)
				$this->methods[] = strtolower(substr($m, 2));
		
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
		
		$this->javascript['helper'] = 'Helpers.'.$this->form->options['id'];
		$this->javascript['helpername'] = 'Helper';
	}
	
	public function initialize(){
		/*return array(
			'table' => '',
			'options' => array(
			
			),
			'form' => new Form()
		);*/
	}
	
	public function handle($event, $get = null, $post = null){
		if(!in_array($event, $this->methods)){
			$default = $this->getDefaultEvent('view');
			
			$get['p'][$default] = $event;
			$event = $default;
		}
		
		$event = array(strtolower($event));
		$event[] = 'on'.ucfirst($event[0]);
		
		$this->data = db::select($this->table);
		$this->Handler = Handler::map('layer.'.$this->layername)->base('Layers', ucfirst($this->name))->object($this);
		
		$this->get = $get ? $get : $_GET;
		$this->post = $post ? $post : $_POST;
		$this->event = $event[0];
		
		$exec = true;
		if($this->event==$this->getDefaultEvent('save')){
			if(is_array($this->post) && sizeof($this->post))
				$this->prepareData($this->post);
			else
				$exec = false;
				/* throw some error */
		}
		
		if(method_exists($this, $event[1]) && $exec)
			$this->{$event[1]}($this->get['p'][$event[0]]);
		/*else
			show some Error*/
		
		return $this;
	}
	
	/* EditHandler Begin */
	public function edit($options = array(
		'edit' => null,
		'preventDefault' => false,
	)){
		if(!$options['edit'] && !$options['preventDefault'] && $this->event && $this->get['p'][$this->event])
			$options['edit'] = array(
				$this->options['identifier']['external'] => array($this->get['p'][$this->event], $this->options['identifier']['external']),
			);
		
		if($options['edit'] && Validator::check($options['edit']) && $this->table){
			$data = db::select($this->table)->where($options['edit'])->fetch();
			if($data){
				$this->form->addElement(new HiddenInput(array(
					'name' => $this->options['identifier']['internal']
				)));
				
				$this->form->setValue($data);
				$this->editing = true;
			}
		}
		
		$this->form->get('action', $this->name.'/'.$this->getDefaultEvent('save').($data ? ':'.$data[$this->options['identifier']['external']] : ''));
		
		/*Hash::extend($options = array(
			'fields' => $this->form->getFields(array('js' => true)),
		), $this->options['javascript']);
		
		Script::set("
			".$this->javascript['helper']." = new ".$this->javascript['helpername']."('".$this->form->options['id']."', ".json_encode($options).");
			".$this->form->getEvents($this->javascript['helper'])."
		");*/
		
		return $this->form->format();
	}
	
	public function add($options = null){
		$array = array('preventDefault' => true);
		return $this->edit($options ? Hash::extend($options, $array) : $array);
	}
	/* EditHandler End */
	
	/* SaveHandler Begin */
	public function prepareData($data){
		if($data[$this->options['identifier']['internal']]){
			$this->where = array(
				$this->options['identifier']['internal'] => array($data[$this->options['identifier']['internal']], $this->options['identifier']['internal']),
			);
			
			unset($data[$this->options['identifier']['internal']]);
			$this->editing = true;
		}
		
		$this->form->setValue($data, true);
	}
	
	public function save($where = null, $options = array(
		'preventDefault' => false,
	)){
		if(!$where) $where = $this->where;
		
		if($options['preventDefault']) unset($where);
		
		$validate = $this->form->validate();
		if($validate!==true) throw new ValidatorException($validate);
		
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
	
	public function link($title, $action = null){
		$default = $this->getDefaultEvent('view');
		if(!$action || !in_array($action, $this->methods))
			$action = $default;
		
		
		return $this->name.'/'.(in_array($title, $this->methods) || $action!=$default ? $action.Core::retrieve('path.separator') : '').$title;
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
	
}