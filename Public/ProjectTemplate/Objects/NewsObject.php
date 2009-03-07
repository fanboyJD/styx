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
					':element' => 'TextArea',
					':public' => true,
					':validate' => array(
						'purify' => true,
						'notempty' => true,
					),
				),
				'id' => array(),
				'uid' => array(),
				'pagetitle' => array(),
				'time' => array(),
			),
		);
	}
	
	protected function onFormCreate(){
		$this->Form->addElements(
			new ButtonElement(array(
				'name' => 'bsave',
				':caption' => Lang::retrieve('save'),
			))
		);
	}
	
	protected function onSave($data){
		if($this->new){
			$data['time'] = time();
			$data['uid'] = User::get('id');
		}
		
		if(isset($data['title'])) $data['pagetitle'] = $this->getPagetitle($data['title']);
		
		return $data;
	}
	
}