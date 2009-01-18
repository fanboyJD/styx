<?php
class PasswordLayer extends Layer {
	
	public function initialize(){
		$this->setDefaultEvent('view', 'edit');
		
		return array(
			'identifier' => 'id',
			'table' => 'users',
		);
	}
	
	public function populate(){
		$this->Form->addElements(
			new Input(array(
				'name' => 'pwd',
				'type' => 'password',
				':caption' => Lang::retrieve('user.pwd'),
				':validate' => array(
					'notempty' => true,
				),
			)),
			
			new Button(array(
				'name' => 'bsave',
				':caption' => Lang::retrieve('save'),
			))
		);
	}
	
	public function onSave(){
		$this->setValue(array(
			'pwd' => User::getPassword($this->getValue('pwd')),
		));
		
		$data = $this->validate();
		
		Database::update($this->table)->set($data)->limit(0)->query();
		
		$user = User::retrieve();
		if($user){
			$user['pwd'] = $this->getValue('pwd');
			User::login($user);
		}
		
		$this->Template->append('The password has been saved');
	}
	
	public function onEdit(){
		$this->edit(array('preventDefault' => true));
		
		$this->Template->append('<h1>Change Password</h1>
			'.implode(array_map('implode', $this->format())));
	}
	
}