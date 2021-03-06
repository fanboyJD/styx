<?php
class IndexLayer extends Layer {
	
	public $isIndex = false;
	
	protected function getModuleName(){
		return 'news';
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
		
		// This way it is required to use "handler;json" in the Querystring. If we left the "if" out the contenttype "json" would be enforced
		if(Request::retrieve('behaviour')!='json')
			throw new ValidatorException('contenttype');
		
		Response::setContentType('json');
		
		try{
			$object = $this->Model->findByIdentifier($title);
			if(!$object) throw new ValidatorException('newsnotavailable');
			$object->requireSession()->checkSession($this->post)->delete();
			
			$this->Template->assign(array(
				'out' => 'success',
				'msg' => Lang::retrieve('deleted'),
			));
		}catch(ValidatorException $e){
			$this->Template->assign(array(
				'out' => 'error',
				'msg' => $e->getMessage(),
			));
		}
	}
	
	public function onView($title){
		Response::setDefaultContentType('html', 'xml');
		
		// We check for the used ContentType (xml or html) and assign the correct template for it
		$contenttype = Response::getContentType();
		
		if($title){
			$this->Model->findByIdentifier($title);
			if(!count($this->Model)) throw new ValidatorException('newsnotavailable');
		}else{
			if($contenttype=='html'){
				$this->paginate()->initialize($this->Model->getLatestNews(), array(
					'per' => 2,
				));
				$this->isIndex = true;
			}elseif($contenttype=='xml'){
				$this->Model->findMany($this->Model->getLatestNews(10));
			}
		}
	
		$this->Template->apply(($contenttype=='xml' ? 'xml' : '').'view.php');
	}
	
}