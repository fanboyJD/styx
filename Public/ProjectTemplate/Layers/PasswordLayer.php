<?php
class PasswordLayer extends Layer {
	
	protected $Form;
	protected function initialize(){
		$this->Form = new FormElement(array(
			':elements' => array(
				new InputElement(array(
					'name' => 'pwd',
					'type' => 'password',
					':caption' => Lang::retrieve('user.pwd'),
					':validate' => array(
						'notempty' => true,
					),
				)),
				
				new ButtonElement(array(
					'name' => 'bsave',
					':caption' => Lang::retrieve('save'),
				)),
			)
		));
		
		return array(
			'defaultEvent' => 'edit',
		);
	}
	
	public function onSave(){
		$this->Form->setValue($this->post)->validate();
		
		$pwd = $this->Form->getValue('pwd');
		Database::update('users')->set(array(
			'pwd' => User::getPassword($pwd)
		))->limit(0)->query();
		
		$user = User::retrieve();
		if($user){
			$user['pwd'] = $pwd;
			User::login($user);
		}
		
		$this->Template->append('The password has been saved');
	}
	
	public function onEdit(){
		$this->Form->set('action', $this->link(null, 'save'));
		
		$this->Template->append('<h1>Change Password</h1>
			'.Hash::implode($this->Form->format()));
	}
	
}