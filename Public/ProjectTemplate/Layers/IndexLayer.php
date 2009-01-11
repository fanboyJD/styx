<?php
class IndexLayer extends Layer {
	
	public function initialize(){
		return array(
			'table' => 'news',
		);
	}
	
	public function populate(){
		if($this->event=='delete'){
			$this->Form->addElements(
				new Button(array(
					'name' => 'bsave',
					':caption' => Lang::retrieve('delete'),
				))
			);
		}else{
			$this->Form->addElements(
				new Input(array(
					'name' => 'title',
					':caption' => Lang::retrieve('title'),
					':validate' => array(
						'sanitize' => true,
						'notempty' => true,
					),
				)),
				
				new Textarea(array(
					'name' => 'content',
					':caption' => Lang::retrieve('text'),
					':validate' => array(
						'purify' => array( // These are the options for the Data-Class method "purify". In this case the classes in the HTML to be kept
							'classes' => array('green', 'blue', 'b', 'icon', 'bold', 'italic'),
						),
						'notempty' => true,
					),
				)),
				
				new Button(array(
					'name' => 'bsave',
					':caption' => Lang::retrieve('save'),
				)),
				
				new Field('uid'),
				new Field('pagetitle'),
				new Field('time')
			);
		}
		
		$this->requireSession(); // Adds an invisible element with the current session so everything is safe :)
	}
	
	public function onSave(){
		if(!User::hasRight('layer.index.edit'))
			throw new ValidatorException('rights');
		
		$this->setValue(array(
			'uid' => $this->editing ? $this->content['uid'] : User::get('id'),
			'time' => $this->editing ? $this->content['time'] : time(),
			'pagetitle' => $this->getPagetitle($this->getValue('title')),
		));
		
		$this->save();
		
		$this->Template->append(Lang::get('news.saved', $this->link($this->getValue('pagetitle'))));
	}
	
	public function onEdit(){
		$this->edit();
		
		if(!User::hasRight('layer.index.edit', $this->editing ? 'modify' : 'add'))
			throw new ValidatorException('rights');
		
		$this->Template->apply('edit')->assign(array(
			'headline' => Lang::retrieve('news.'.($this->editing ? 'modify' : 'add')),
		))->assign($this->format());
	}
	
	public function onDelete($title){
		if(!User::hasRight('layer.index.delete'))
			throw new ValidatorException('rights');
		
		if(!Hash::length($this->post)){
			$this->prepare();
			
			if(!$this->editing)
				throw new ValidatorException('data');
			
			$this->Template->append('<h1>'.Lang::retrieve('confirmdelete').'</h1>
				'.implode(array_map('implode', $this->format())));
		}else{
			$this->delete();
			$this->Template->append(Lang::retrieve('deleted'));
		}
	}
	
	public function onView($title){
		Response::setDefaultContentType('html', 'xml');
		
		// We check for the used ContentType (xml or html) and assign the correct template for it
		$contenttype = Response::getContentType();
		
		$this->Data->fields('news.*, users.name')->join('news.uid=users.id', 'users', 'left')->limit(0)->order('time DESC');
		if($title)
			$this->Data->where(array(
				'pagetitle' => array($title, 'pagetitle'),
			))->limit(1);
		elseif($contenttype=='html')
			$this->paginate()->initialize($this->Data, array(
				'per' => 2,
			));
		elseif($contenttype=='xml')
			$this->Data->limit(10);
		
		$this->Template->apply(($contenttype=='xml' ? 'xml' : '').'view.php');
	}
	
}