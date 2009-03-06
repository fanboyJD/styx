<?php

class UserObject extends DatabaseObject {
	
	protected function initialize(){
		return array(
			'table' => 'users',
			'identifier' => array(
				'external' => 'name',
			),
			'structure' => array(
				'name' => array(),
				'id' => array(),
				'pwd' => array(),
				'session' => array(),
				'rights' => array(),
			),
		);
	}
	
	protected function onSave($data){
		if($this->new) throw new ValidatorException('data');
		
		return $data;
	}
	
	public function updateSession($session){
		$this->modify(array(
			'session' => $session,
		))->save();
	}
	
}