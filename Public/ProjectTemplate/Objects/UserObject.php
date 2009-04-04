<?php

class UserObject extends DatabaseObject {
	
	protected function onSave($data){
		if($this->new) throw new ValidatorException('data');
		
		return $data;
	}
	
	public function updateSession($session = null){
		$this->modify(array(
			'session' => $session,
		))->save();
		
		return $this;
	}
	
	public function getLoginData(){
		return array(
			'name' => $this->Data['name'],
			'pwd' => $this->Data['pwd'],
			'session' => $this->Data['session'],
		);
	}
	
	public function getRights(){
		return $this->Data['rights'];
	}
	
	public function getSession(){
		return $this->Data['session'];
	}
	
}