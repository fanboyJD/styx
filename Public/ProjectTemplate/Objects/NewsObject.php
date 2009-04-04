<?php

class NewsObject extends DatabaseObject {
	
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