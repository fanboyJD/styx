<?php

class UserObject extends DatabaseObject {
	
	protected function initialize(){
		return array(
			'table' => 'users',
			'structure' => array(
				'name' => array(
					':caption' => Lang::retrieve('user.name'),
					':public' => true,
					':validate' => array(
						'pagetitle' => true,
					),
				),
				'id' => array(),
				'pwd' => array(),
				'session' => array(),
				'rights' => array(
					':default' => '[]',
				),
			),
		);
	}
	
	protected function onFormCreate(){
		$this->Form->addElements(
			new InputElement(array(
				'name' => 'password',
				'type' => 'password',
				':caption' => Lang::retrieve('user.pwd'),
			)),
			
			new ButtonElement(array(
				'name' => 'bsave',
				':caption' => Lang::retrieve('save'),
			))
		);
	}
	
	protected function onSave($data){
		if($this->new || isset($this->Garbage['password']))
			$data['pwd'] = User::getPassword($this->Garbage['password']);
		
		return $data;
	}
	
}