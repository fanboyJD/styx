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
		
		$object->createForm()->get('action', $this->link($object->getIdentifier(), $this->getDefaultEvent('save')));
		
		$this->Template->apply('edit')->assign(array(
			'headline' => Lang::retrieve('news.'.($object->isNew() ? 'add' : 'modify')),
		))->assign($object->createForm()->format());
	}
	
	public function onDelete($title){
		if(!User::hasRight('layer.index.delete'))
			throw new ValidatorException('rights');
		
		// This way it is required to use "handler;json" in the Querystring. If we left the "if" out the contenttype "json" would be enforced
		if(Request::retrieve('behaviour')!='json')
			throw new ValidatorException('contenttype');
		
		Response::setContentType('json');
		
		try{
			// FIXME when no data is found
			$this->Model->findByIdentifier($title)->delete();
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
		
		$newsModel = Model::create('News');
		if($title){
			$this->Data = array(
				$newsModel->findByIdentifier($title),
			);
			
			if(!count($this->Data))
				throw new ValidatorException('newsnotavailable');
		}else{
			$this->Data = $newsModel->select()->fields('news.*, users.name')->join('news.uid=users.id', 'users', 'left')->limit(0)->order('time DESC');
			
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