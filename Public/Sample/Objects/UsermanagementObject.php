<?php

class UsermanagementObject extends DatabaseObject {
	
	protected function initialize(){
		return array(
			'table' => 'users',
			'identifier' => array(
				'external' => 'name',
			),
			'structure' => array(
				'name' => array(
					':caption' => Lang::retrieve('user.name'),
					':public' => true,
					':validate' => array(
						'pagetitle' => true,
						'notempty' => true,
					),
				),
				'id' => array(),
				'pwd' => array(
					':caption' => Lang::retrieve('user.pwd'),
				),
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
				':add' => !$this->new ? Lang::retrieve('user.pwdempty') : '',
			)),
			
			new ButtonElement(array(
				'name' => 'bsave',
				':caption' => Lang::retrieve('save'),
			))
		);
	}
	
	protected function onSave($data){
		if($this->new && empty($this->Garbage['password']))
			throw new ValidatorException('notempty', Lang::retrieve('user.pwd'));
		
		if($this->new || isset($this->Garbage['password']))
			$data['pwd'] = User::getPassword($this->Garbage['password']);
		
		return $data;
	}
	
}