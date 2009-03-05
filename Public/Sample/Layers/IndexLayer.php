<?php
class IndexLayer extends Layer {
	
	public $isIndex = false;
	
	public function initialize(){
		return array(
			'model' => 'news',
		);
	}
	
	public function populate(){
		$this->requireSession(); // Adds an invisible element with the current session so everything is safe :)
	}
	
	public function onSave($title){
		$object = $this->Model->createOrFindBy($title)->store($this->post);
		
		if(!User::hasRight('layer.index.edit', $object->isNew() ? 'add' : 'modify'))
			throw new ValidatorException('rights');
		
		if(!$object->save()) throw new ValidatorException('data');
		
		$this->Template->append(Lang::get('news.saved', $this->link($object['pagetitle'])));
	}
	
	public function onEdit($title){
		$object = $this->Model->createOrFindBy($title);
		
		if(!User::hasRight('layer.index.edit', $object->isNew() ? 'add' : 'modify'))
			throw new ValidatorException('rights');
		
		$object->getForm()->get('action', $this->link($object->getIdentifier(), $this->getDefaultEvent('save')));
		
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
			// FIXME needs testing
			$object = $this->Model->findByIdentifier($title);
			if(!$object) throw new ValidatorException('newsnotavailable');
			$object->delete();
			
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
			$this->Data = array($this->Model->findByIdentifier($title));
			if(!$this->Data[0]) throw new ValidatorException('newsnotavailable');
		}else{
			$this->Data = $this->Model->findLatestNews();
			
			if($contenttype=='html'){
				$this->paginate()->initialize($this->Data, array(
					'per' => 2,
				));
				
				$this->isIndex = true;
			}elseif($contenttype=='xml'){
				$this->Data->limit(10);
			}
		}
	
		$this->Template->apply(($contenttype=='xml' ? 'xml' : '').'view.php');
	}
	
}