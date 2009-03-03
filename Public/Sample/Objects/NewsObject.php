<?php

class NewsObject extends DatabaseObject {
	
	protected function initialize(){
		return array(
			'structure' => array(
				'title' => array(
					':caption' => Lang::retrieve('title'),
					':public' => true,
					':validate' => array(
						'sanitize' => true,
						'notempty' => true,
					),
				),
				'content' => array(
					':caption' => Lang::retrieve('text'),
					':public' => true,
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
	
	protected function onSave($data){
		$data['time'] = $this->new ? time() : $this['time'];
		$data['uid'] = $this->new ? User::get('id') : $this['uid'];
		
		if(isset($data['title'])) $data['pagetitle'] = $this->getPagetitle($this['title']);
		
		return $data;
	}
	
}