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
	
	protected function onSave($data){
		if(Upload::exists('image')){
			$upload = Upload::move('image', 'Files/', array(
				'size' => 1024*512,
				'mimes' => array('image/gif', 'image/png', 'image/jpeg'),
			));
			$img = new Image($upload);
			$data['picture'] = 'Files/'.basename($img->resize(120)->save()->getPathname());
		}
		
		if($this->new){
			$data['time'] = time();
			$data['uid'] = User::get('id');
		}
		
		if(isset($data['title'])) $data['pagetitle'] = $this->getPagetitle($data['title']);
		
		return $data;
	}
	
}