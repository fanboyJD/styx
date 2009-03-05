<?php

class PageObject extends DatabaseObject {
	
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
						'purify' => array( // These are the options for the Data-Class method "purify". In this case the classes in the HTML to be kept
							'classes' => array('green', 'blue', 'b', 'icon', 'bold', 'italic'),
						),
						'notempty' => true,
					),
				),
				'id' => array(),
				'pagetitle' => array(),
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
		if(isset($data['title'])) $data['pagetitle'] = $this->getPagetitle($data['title']);
		
		return $data;
	}
	
}