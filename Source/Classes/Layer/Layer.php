<?php
abstract class Layer {
	/**
	 * A Form-Element instance
	 *
	 * @var Form
	 */
	public $form;
	
	public $name,
		$table,
		$templates = array(
			'save' => '',
			'edit' => '',
			'new' => '',
			'error' => '',
		),
		$events = array(
			'save' => array('save'),
			'edit' => array('edit'),
			'new' => array('new'),
		),
		$javascript = array(
			'helper' => '',
			'helpername' => '',
		),
		$options = array(
			'identifier' => null,
		);
	
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
		
		if(!$this->form->options['action'])
			$this->form->options['action'] = $this->name.'/'.reset($this->events['save']);
		
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
	
	public function __call($name, $args){
		return preg_match('/^on[A-Z]/', $name) ? true : false;
	}
	
	public function onError($get = null, $post = null, $handler = null, $pass = null){
		
	}
	
	public function handler($event, $get, $post){
		$handler = strtolower($event);
		$event = 'on'.ucfirst($handler);
		
		if($this->hasEvent('save', $handler) && is_array($post) && sizeof($post)){
			
		}elseif($this->hasEvent('edit', $handler)){
			$pass = $this->{$event}($get, $post, $handler);
			
			if(!$pass || ($pass && !$pass['error']))
				$this->edit($get, $post, $handler, $pass);
			elseif($pass['error'])
				$this->onError($get, $post, $handler, $pass);
		}else{
			
			//echo $this->{$event}();
			
		}
		
	}
	
	public function edit($get = null, $post = null, $handler = null, $pass = null){
		if(!$pass['edit'] && !$pass['preventDefault'] && $handler && $get['p'][$handler])
			$pass['edit'] = array(
				$this->options['identifier']['external'] => array($get['p'][$handler], $this->options['identifier']['external'])
			);
		
		if(Validator::check($pass['edit'])){
			$data = db::getInstance()->select($this->table)->where($v)->fetch();
			if($data){
				$this->form->addElement(new HiddenInput(array(
					'name' => $this->options['identifier']['internal']
				)));
				
				$this->form->setValue($data);
			}
		}
		
		$options = array_extend(array(
			'fields' => $this->form->getFields(array('js' => true)),
		), $this->options['jsoptions']);
		
		Script::set("
			".$this->javascript['helper']." = new ".$this->javascript['helpername']."('".$this->form->options['id']."', ".json_encode($options).");
			".$this->form->getEvents($this->javascript['helper'])."
		");
		
		return $this->form->format($this->getTemplate('edit'));
	}
	
	public function save($data, $options = array(
		'noDefault' => false,
		'update' => null
	)){
		$validation = $this->form->validate($data);
		$dbdata = $this->form->prepareDatabaseData($data);
		
		if($validation===true && $this->table && !$options['noDefault']){
			/* @var $db db */
			$db = db::getInstance();
			if($options['update'])
				$db->update($this->table, $options['update'], $dbdata);
			else
				$db->insert($this->table, $dbdata);
		}
		
		return array(
			'validation' => $validation,
			'data' => $dbdata,
		);
	}
	
	public function addEvent($type, $ev){
		if(!$this->hasEvent($type, $ev)) array_push($this->events[$type], $ev);
	}
	
	public function removeEvent($type, $ev){
		array_remove($this->events[$type], $ev);
	}
	
	public function hasEvent($type, $ev){
		return in_array($ev, $this->events[$type]);
	}
	
	public function setTemplate($type, $tpl){
		$this->templates[$type] = $tpl;
	}
	
	public function getTemplate($type){
		return $this->templates[$type];
	}
	
	public static function execute(){
		include(func_get_arg(0));
	}
}
?>