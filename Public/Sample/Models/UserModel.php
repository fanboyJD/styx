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
	
}