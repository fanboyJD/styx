<?php

class NewsObject extends DatabaseObject {
	
	public function getUsername(){
		return !empty($this->Garbage['user.name']) ? $this->Garbage['user.name'] : null;
	}
	
	protected function onFormCreate(){
		$this->Form->addElements(
			new UploadElement(array(
				'name' => 'image',
				':caption' => Lang::retrieve('news.file'),
			)),
			
			new ButtonElement(array(
				'name' => 'bsave',
				':caption' => Lang::retrieve('save'),
			))
		);
	}
	
	protected function onInsert($data){
		$data['time'] = time();
		$data['uid'] = User::get('id');
		
		return $data;
	}
	
	protected function onSave($data){
		if(Upload::exists('image')){
			$upload = Upload::move('image', 'Files/', array(
				'size' => 1024*512,
				'mimes' => array('image/gif', 'image/png', 'image/jpeg'),
			));
			$img = new Image($upload);
			$data['picture'] = 'Files/'.basename($img->resize(120)->save()->getPathname());
		}
		
		if(isset($data['title'])) $data['pagetitle'] = $this->getPagetitle($data['title']);
		
		return $data;
	}
	
}