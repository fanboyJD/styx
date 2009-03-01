<?php

class NewsObject extends DatabaseObject {
	
	public function initialize(){
		return array(
			'structure' => array(
				'title' => array(
					':caption' => Lang::retrieve('title'),
					':validate' => array(
						'sanitize' => true,
						'notempty' => true,
					),
				),
				'content' => array(
					':caption' => Lang::retrieve('text'),
					':validate' => array(
						'purify' => array( // These are the options for the Data-Class method "purify". In this case the classes in the HTML to be kept
							'classes' => array('green', 'blue', 'b', 'icon', 'bold', 'italic'),
						),
						'notempty' => true,
					),
				),
				'id' => array(),
				'uid' => array(),
				'pagetitle' => array(),
				'time' => array(),
				'picture' => array(),
			),
		);
	}
	
	public function onSave($data){
		// Just for testing purposes so far
		$data['time'] = time();
		$data['pagetitle'] = Data::pagetitle($this->retrieve('title'));
		
		return $data;
	}
	
}