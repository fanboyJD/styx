<?php

class UserModule extends Module {
	
	protected function onInitialize(){
		return array(
			'defaultEvent' => 'login',
			
			'table' => 'users',
			'identifier' => array(
				'external' => 'name',
			),
		);
	}
	
	protected function onStructureCreate(){
		return array(
			'id' => array(),
			'name' => array(),
			'pwd' => array(),
			'session' => array(),
			'rights' => array(),
		);
	}
	
}