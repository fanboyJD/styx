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
					':validate' => 'purify',
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
		
		try{
			$this->save();
			
			$this->Handler->assign(Lang::get('news.saved', $this->link($this->getValue('pagetitle'))));
		}catch(ValidatorException $e){
			$this->Handler->assign($e->getMessage());
		}catch(Exception $e){
			
		}
		
	}
	
	public function onEdit(){
		$this->edit();
		
		$this->Handler->assign($this->format());
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
		
		foreach(db::select('users')->fields('id, name')->where(Data::in('id', $users))->limit(0)->retrieve() as $user)
			$this->usernames[$user['id']] = $user['name'];
		
		if(Handler::behaviour('html'))
			$this->Handler->template('view.php');
		elseif(Handler::behaviour('xml'))
			$this->Handler->template('xmlview.php');
	}
	
	public function populate(){
		/*
			This method gets automatically called by the edit and save handler
			to populate some stuff with data you may need :)
		*/
		
		$this->requireSession(); // Adds an invisible element with the current session so everything is safe :)
	}
}