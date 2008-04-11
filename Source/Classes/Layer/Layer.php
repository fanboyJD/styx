<?php
abstract class Layer {
	/**
	 * A Form-Element instance
	 *
	 * @var form
	 */
	public $form;
	/**
	 * A Viewer instance
	 *
	 * @var viewer
	 */
	protected $viewer;
	
	public $helper = '',
		$user = null,
		$sid = null,
		$isPreview = null,									//just for preview bugfixes :)
		$child = false,										//child or parent?
		$options = array(
			'permissions' => array(							//permissions
				'general' => array(							//takes
					'rights' => 1,							//general, view, save
					'none' => false,
				),
				'save' => array(
					'session' => true,						//Checks if the user passed a session (not for general, only helpful for save)
				),
				/*
				'delete' => array(
					
				),
				'view' => array(
					
				)*/
			),
			'editing' => false,								//Stuff for edition
			'delete' => false,								//Add Delete buttons?
			'preview' => false,
			'table' => '',									//table to modify
			'viewername' => 'viewer',						//Name of viewer class
			'viewer' => array()								//Options for the viewer
		);
	
	public function __construct(){
		$initialize = $this->initialize();
		
		$this->options = Util::extend($this->options, $initialize['options']);
		$this->form = $initialize['form'];
		
		$this->helper = 'Helpers.'.$this->form->options['id'];
		$this->helpername = 'Helper';
	}
	
	public function initialize(){
		/*return array(
			'options' => array(
			
			),
			'form' => new Form()
		);*/
	}
	
	public function edit($v = null, $vars = array()){
		if(!$this->checkPermissions('edit'))
			return;
		
		$data = abstractionlayer::prepareData($v);
		if($data){
			/* @var $db db */
			$db = db::getInstance();
			$view = $db->select($this->options['table'], $data);
			$this->form->setValue($view);
		}
		$options = Util::extend(array(
			'fields' => $this->form->getFields(array('js' => true)),
		), $this->options['jsoptions']);
		
		if($this->options['permissions']['save']['session'])
			$options['session'] = 1;
		if($this->options['helperevents'])
			$options['events'] = $this->options['helperevents'];
			
		script::set("
			".$this->helper." = new ".$this->helpername."('".$this->form->options['id']."', ".json_encode($options).");
			".$this->form->getEvents($this->helper)."
		");
		
		return $this->form->get($this->options['edit'], $vars);
	}
	
	public function save($data, $options = array(
		'noDefault' => false,
		'update' => null
	)){
		if(!$this->checkPermissions('save'))
			return false;
		
		$validation = $this->form->validate($data);
		$dbdata = $this->form->prepareDatabaseData($data);
		
		if($validation===true && $this->options['table'] && !$options['noDefault']){
			/* @var $db db */
			$db = db::getInstance();
			if($options['update'])
				$db->update($this->options['table'], $options['update'], $dbdata);
			else
				$db->insert($this->options['table'], $dbdata);
		}
		if($this->child)
			return array(
				'validation' => $validation,
				'data' => $dbdata,
			);
	}
	
	//this methods are empty! They have to be defined separate in every child!
	public function delete(){}
	public function quote(){}
	public function lock(){}
	
	public function view(){
		if(!$this->checkPermissions('view'))
			return;
		
		if(!$this->viewer){
			if(!class_exists($this->options['viewername']) || !util::startsWith($this->options['viewername'], 'viewer'))
				$this->options['viewername'] = 'viewer';
			
			$this->viewer = new $this->options['viewername']($this, $this->options['viewer']);
		}
		return $this->viewer;
	}
	
	public function preview($data, $user){
		if(!$this->options['preview'] || !$this->checkPermissions('save'))
			return;
		
		$this->options['delete'] = false;
		$p = $this->save($data, array(
			'noDefault' => true,
		));
		$this->isPreview = true;
		$view = $p['validation']!==true ? null : $this->view()->one(null, array(
			'data' => $p['data'],
		));
		$this->isPreview = false;
		
		return array(
			's' => $p['validation']!==true ? $p['s'] : $p['validation'],
			'p' => $view,
		);
	}
	
	public function checkPermissions($type, $ignoreSession = false){
		if($type=='lock'){
			$type = 'delete';
			$ignoreSession = true;
		}elseif(in_array($type, array('edit', 'editing', 'quote'))){
			if(!$this->options['permissions'][$type])
				$type = 'save';
			$ignoreSession = true;
		}elseif($type=='delete' && !$this->checkPermissions('save', $ignoreSession)){
			return false;
		}
		if(is_array($this->options['permissions'][$type]) && !$this->options['permissions'][$type]['none']){
			if($this->options['permissions'][$type]['rights']>0 && $this->user['rights']<$this->options['permissions'][$type]['rights'])
				return false;
			if(!$ignoreSession && $this->options['permissions'][$type]['session'] && !userutil::checkSession($this->sid))
				return false;
		}
		if(!$this->options['permissions']['general']['none']){
			if($this->options['permissions']['general']['rights']>0 && $this->user['rights']<$this->options['permissions']['general']['rights'])
				return false;
		}
		return true;
	}
	
	public static function prepareData($v){
		if(is_array($v))
			foreach($v as $key => $val){
				if(db::numeric($key) || $key===0){
					$data[] = $val;
				}elseif(is_array($val)){
					if($val[1]){
						if(!validator::call(array($val[1]), $val[0]))
							return false;
						$data[$key] = db::add(formatter::call($val[1], $val[0]));
					}elseif($val[2]){
						$data[$key] = array(
							$val[0],
							'operator' => $val[2]
						);
					}else{
						$data[$key] = $val[0];
					}
				}else{
					$data[$key] = $val;
				}
			}
		
		return $data;
	}
	
	public static function isLayer(){
		return true;
	}
}
?>