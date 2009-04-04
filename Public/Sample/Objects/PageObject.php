<?php

class PageObject extends DatabaseObject {
	
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