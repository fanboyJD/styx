<?php

class UserModule extends Module {
	
	protected function initialize(){
		return array(
			'defaultEvent' => 'login',
			
			'table' => 'users',
			'identifier' => array(
				'external' => 'name',
			),
			'structure' => array(
				'id' => array(),
				'name' => array(),
				'pwd' => array(),
				'session' => array(),
				'rights' => array(),
			),
		);
	}
	
}