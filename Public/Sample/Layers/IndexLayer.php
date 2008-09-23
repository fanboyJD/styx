<?php
class IndexLayer extends Layer {
	
	public $isIndex = false;
	
	public $usernames; // For: View
	
	public function initialize(){
		$this->allowHandler('view', array('html', 'xml'));
		$this->allowHandler('delete', 'json');
		$this->disallowHandler('delete', array('html', 'xml'));
		
		return array(
			'table' => 'news',
			'options' => array(
				'identifier' => array(
					'internal' => 'id',
					'external' => 'pagetitle',
				),
			),
			'form' => new Form(
				new Input(array(
					'name' => 'title',
					':caption' => Lang::retrieve('title'),
				)),
				
				new Textarea(array(
					'name' => 'content',
					':caption' => Lang::retrieve('text'),
					':validate' => array('purify', array( // These are the options for the Data-Class method "purify". In this case the classes in the HTML to be kept
						'classes' => array('green', 'blue', 'b', 'icon', 'bold', 'italic'),
					)),
				)),
				
				new Button(array(
					'name' => 'bsave',
					':caption' => Lang::retrieve('save'),
				)),
				
				new Field(array(
					'name' => 'uid',
				)),
				
				new Field(array(
					'name' => 'pagetitle',
				)),
				
				new Field(array(
					'name' => 'time',
				))
			)
		);
	}
	
	public function onSave(){
		if($this->editing)
			$data = $this->data->where($this->where)->fetch();
		
		$this->form->setValue(array(
			'uid' => $this->editing ? $data['uid'] : User::get('id'),
			'time' => $this->editing ? $data['time'] : time(),
			'pagetitle' => $this->getPagetitle($this->form->getValue('title'), $this->where),
		));
		
		$this->save();
		
		$this->Handler->assign(Lang::get('news.saved', $this->link($this->getValue('pagetitle'))));
	}
	
	public function onEdit(){
		$this->edit();
		
		$this->Handler->template('edit')->assign(array(
			'headline' => Lang::retrieve('news.'.($this->editing ? 'edit' : 'add')),
		))->assign($this->format());
	}
	
	public function onDelete($title){
		if(!User::checkSession($this->post['session'])){
			$this->Handler->assign(array(
				'out' => 'error',
				'msg' => Lang::retrieve('validator.session'),
			));
			
			return;
		}
		
		db::delete($this->table)->where(array(
			'pagetitle' => array($title, 'pagetitle'),
		))->query();
		
		$this->Handler->assign(array(
			'out' => 'success',
			'msg' => Lang::retrieve('deleted'),
		));
	}
	
	public function onView($title){
		$this->data->limit(0)->order('time DESC');
		if($title)
			$this->data->where(array(
				'pagetitle' => array($title, 'pagetitle'),
			))->limit(1);
		else
			$this->isIndex = true;
		
		$this->data->retrieve();
		
		foreach($this->data as $n)
			$users[] = $n['uid'];
		
		foreach(db::select('users')->fields('id, name')->where(Data::in('id', $users))->limit(0) as $user)
			$this->usernames[$user['id']] = $user['name'];
		
		// We check for the used Handler (xml or html) and assign the correct template for it
		$this->Handler->template((Handler::behaviour('xml') ? 'xml' : '').'view.php');
	}
	
	public function populate(){
		/*
			This method gets automatically called by the edit and save handler
			to populate some stuff with data you may need :)
		*/
		
		$this->requireSession(); // Adds an invisible element with the current session so everything is safe :)
	}
}