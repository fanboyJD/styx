<?php
class IndexLayer extends Layer {
	
	protected function initialize(){
		return array(
			'model' => 'news',
		);
	}
	
	public function onSave($title){
		$object = $this->Model->createOrFindBy($title)->store($this->post)->requireSession();
		
		if(!User::hasRight('layer.index.edit', $object->isNew() ? 'add' : 'modify'))
			throw new ValidatorException('rights');
		
		if(!$object->save()) throw new ValidatorException('data');
		
		$this->Template->append(Lang::get('news.saved', $this->link($object['pagetitle'])));
	}
	
	public function onEdit($title){
		$object = $this->Model->createOrFindBy($title)->requireSession();
		
		if(!User::hasRight('layer.index.edit', $object->isNew() ? 'add' : 'modify'))
			throw new ValidatorException('rights');
		
		$form = $object->getForm()->set('action', $this->link($object->getIdentifier(), 'save'));
		if($this->isRebound()) $form->setRaw($this->post);
		
		$this->Template->apply('edit')->assign(array(
			'headline' => Lang::retrieve('news.'.($object->isNew() ? 'add' : 'modify')),
		))->assign($object->getForm()->format());
	}
	
	public function onDelete($title){
		if(!User::hasRight('layer.index.delete'))
			throw new ValidatorException('rights');
		
		$object = $this->Model->findByIdentifier($title);
		if(!$object) throw new ValidatorException('data');
		$object->requireSession();
		if(!Hash::length($this->post)){
			$form = new FormElement(array(
				':elements' => array(
					new HiddenElement(array(
						'name' => Core::generateSessionName('news'),
						'value' => User::get('session'),
					)),
					new ButtonElement(array(
						'name' => 'bsave',
						':caption' => Lang::retrieve('delete'),
					)),
				),
			));
			
			$this->Template->append('<h1>'.Lang::retrieve('confirmdelete').'</h1>
				'.Hash::implode($form->format()));
		}else{
			$object->checkSession($this->post)->delete();
			$this->Template->append(Lang::retrieve('deleted'));
		}
	}
	
	public function onView($title){
		Response::setDefaultContentType('html', 'xml');
		
		// We check for the used ContentType (xml or html) and assign the correct template for it
		$contenttype = Response::getContentType();
		
		if($title){
			$this->Model->findByIdentifier($title);
			if(!count($this->Model)) throw new ValidatorException('rights');
		}else{
			if($contenttype=='html')
				$this->paginate()->initialize($this->Model->getLatestNews(), array(
					'per' => 2,
				));
			elseif($contenttype=='xml')
				$this->Model->findMany($this->Model->getLatestNews(10));
		}
	
		$this->Template->apply(($contenttype=='xml' ? 'xml' : '').'view.php');
	}
	
}