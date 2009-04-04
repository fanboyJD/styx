<?php

class UserObject extends DatabaseObject {
	
	protected function onInsert($data){
		throw new ValidatorException('data');
	}
	
	protected function onSave($data){
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