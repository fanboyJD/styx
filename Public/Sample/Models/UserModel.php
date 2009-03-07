<?php

class UserModel extends DatabaseModel {
	
	protected function initialize(){
		return array(
			'table' => 'users',
			'identifier' => array(
				'external' => 'name',
			),
		);
	}
	
	public function findByNameAndPwd($name, $pwd){
		return $this->find(array(
			'where' => array(
				'name' => $name,
				'AND',
				'pwd' => User::getPassword($pwd),
			),
		));
	}
	
	public function findByLoginData($data){
		return $data ? $this->find(array(
			'where' => array(
				'name' => $data['name'],
				'AND',
				'pwd' => $data['pwd'],
				'AND',
				'session' => $data['session'],
			),
		)) : false;
	}
	
}