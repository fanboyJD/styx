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
			'javascript' => array(),
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
	
	public function handle($event, $get, $post){
		$event = array(strtolower($event));
		$event[] = 'on'.ucfirst($event[0]);
		
		$this->Handler = Handler::getInstance('layer.'.$this->name);
		
		if($this->hasEvent('save', $event[0]) && is_array($post) && sizeof($post)){
			
		}elseif($this->hasEvent('edit', $event[0])){
			$pass = $this->{$event[1]}($get, $post, $event[0]);
			
			if(!$pass || ($pass && !$pass['error']))
				$this->edit($get, $post, $event[0], $pass);
			elseif($pass['error'])
				$this->onError($get, $post, $event[0], $pass);
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
			$data = db::getInstance()->select($this->table)->where($pass['edit'])->fetch();
			if($data){
				$this->form->addElement(new HiddenInput(array(
					'name' => $this->options['identifier']['internal']
				)));
				
				$this->form->setValue($data);
			}
		}
		
		array_extend($options = array(
			'fields' => $this->form->getFields(array('js' => true)),
		), $this->options['javascript']);
		
		Script::set("
			".$this->javascript['helper']." = new ".$this->javascript['helpername']."('".$this->form->options['id']."', ".json_encode($options).");
			".$this->form->getEvents($this->javascript['helper'])."
		");
		
		$this->Handler->assign($this->form->format($this->getTemplate('edit')));
	}
	
	public function save($data, $options = array(
		'noDefault' => false,
		'update' => null
	)){
		$validation = $this->form->validate($data);
		$dbdata = $this->form->prepareDatabaseData($data);
		
		if($validation===true && $this->table && !$options['noDefault']){
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
	
}
?>