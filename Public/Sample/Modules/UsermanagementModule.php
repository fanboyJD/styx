<?php

class UsermanagementModule extends Module {
	
	protected function onInitialize(){
		return array(
			'table' => 'users',
			'identifier' => array(
				'external' => 'name',
			),
		);
	}
	
	protected function onStructureCreate(){
		return array(
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
		);
	}
	
}