<?php
abstract class Layer {
	/**
	 * A Form-Element instance
	 *
	 * @var form
	 */
	public $form;
	
	public $name,
		$table,
		$templates = array(
			'save' => '',
			'edit' => '',
		),
		$events = array(
			'save' => array('save'),
			'edit' => array('edit'),
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
		
		$this->options = Util::extend($this->options, $initialize['options']);
		
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
	
	public function handler($event, $get, $post){
		$handler = strtolower($event);
		$event = 'on'.ucfirst($handler);
		
		if($this->hasEvent('save', $handler) && is_array($post) && sizeof($post)){
			
		}elseif($this->hasEvent('edit', $handler)){
			$pass = true;
			if(method_exists($this, $event))
				$pass = $this->{$event}($get, $post);
			
			if(!$pass || ($pass && !$pass['error']))
				echo $this->edit($pass['edit'], $handler, $get);
			elseif($pass['error'])
				echo $pass['error'];
		}else{
			
			//echo $this->{$event}();
			
		}
		
	}
	
	public function edit($v = null, $handler = null, $get = null){
		if(!$v && $handler && $get['p'][$handler])
			$v = array(
				$this->options['identifier']['external'] => array($get['p'][$handler], $this->options['identifier']['external'])
			);
		
		if(Validator::check($v))
			$this->form->setValue(db::getInstance()->select($this->table)->where($v)->fetch());
		
		$options = Util::extend(array(
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
}
?>