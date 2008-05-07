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
		$table,
		$events = array(
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
		);
	
	private $event = null,
		$get = array(),
		$post = array(),
		$where = null;
	
	public static function run($layerName, $event, &$get = null, &$post = null){
		if(!$layerName || !Core::autoload($layerName, 'Layers'))
			return false;
		
		$layerName = strtolower($layerName);
		$class = ucfirst($layerName).'Layer';
		
		if(!is_subclass_of($class, 'Layer'))
			return false;
		
		$layer = new $class($layerName);
		$layer->handle($event, $get, $post);
		
		return $layer;
	}
	
	public function __construct($name){
		$this->name = $this->table = $name;
		
		$initialize = $this->initialize();
		
		if($initialize['table'])
			$this->table = $initialize['table'];
		
		array_extend($this->options, $initialize['options']);
		
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
	
	public function handle($event, &$get = null, &$post = null){
		$event = array(strtolower($event));
		$event[] = 'on'.ucfirst($event[0]);
		
		$this->Handler = Handler::getInstance('layer.'.$this->name);
		
		$this->get = $get ? $get : $_GET;
		$this->post = $post ? $post : $_POST;
		$this->event = $event[0];
		
		if($this->getDefaultEvent('save')==$this->event && is_array($this->post) && sizeof($this->post))
			$this->prepareData($this->post);
		
		$this->{$event[1]}();
	}
	
	/* EditHandler Begin */
	public function edit($pass = null){
		if(!$pass['edit'] && !$pass['preventDefault'] && $this->event && $this->get['p'][$this->event])
			$pass['edit'] = array(
				$this->options['identifier']['external'] => array($this->get['p'][$this->event], $this->options['identifier']['external']),
			);
		
		if(Validator::check($pass['edit']) && $this->table){
			$data = db::getInstance()->select($this->table)->where($pass['edit'])->fetch();
			if($data){
				$this->form->addElement(new HiddenInput(array(
					'name' => $this->options['identifier']['internal']
				)));
				
				$this->form->setValue($data);
			}
		}
		
		$this->form->get('action', $this->name.'/'.$this->getDefaultEvent('save').($data ? ':'.$data[$this->options['identifier']['internal']] : ''));
		
		/*array_extend($options = array(
			'fields' => $this->form->getFields(array('js' => true)),
		), $this->options['javascript']);
		
		Script::set("
			".$this->javascript['helper']." = new ".$this->javascript['helpername']."('".$this->form->options['id']."', ".json_encode($options).");
			".$this->form->getEvents($this->javascript['helper'])."
		");*/
		
		return $this->form->format();
	}
	
	public function add($pass = null){
		$array = array('preventDefault' => true);
		return $this->edit($pass ? array_extend($pass, $array) : $array);
	}
	/* EditHandler End */
	
	/* SaveHandler Begin */
	public function prepareData($data){
		if($data[$this->options['identifier']['internal']]){
			$this->where = array(
				$this->options['identifier']['internal'] => array($data[$this->options['identifier']['internal']], $this->options['identifier']['internal']),
			);
			
			unset($data[$this->options['identifier']['internal']]);
		}
		
		$this->form->setValue($data, true);
	}
	
	public function save($where = null){
		if(!$where) $where = $this->where;
		
		$validate = $this->form->validate();
		if($validate!==true)
			return $validate;
		
		$data = $this->form->prepareData();
		if(!$data || !$this->table)
			return;
		
		if($where)
			db::getInstance()->update($this->table)->set($data)->where($where)->query();
		else
			db::getInstance()->insert($this->table)->set($data)->query();
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
			$options['contents'] = db::getInstance()->select($this->table)->fields('id, pagetitle')->retrieve();
		
		if($where[$this->options['identifier']['internal']])
			$options['id'] = Data::call($where[$this->options['identifier']['internal']][0], $where[$this->options['identifier']['internal']][1]);
		
		return Data::pagetitle($title, $options);
	}
}
?>