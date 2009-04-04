<?php
class UserManagementLayer extends Layer {
	
	protected function initialize(){
		$this->base = 'admin/user';
	}
	
	public function access(){
		if(!User::hasRight('layer.user'))
			throw new ValidatorException('rights');
		
		return true;
	}
	
	public function onSave($name){
		if(!$this->Model->createOrFindBy($name)->store($this->post)->requireSession()->save())
			throw new ValidatorException('data');
		
		$this->Template->append(Lang::get('user.saved', $this->link()));
	}
	
	public function onEdit($name){
		$object = $this->Model->createOrFindBy($name)->requireSession();
		
		$form = $object->getForm()->set('action', $this->link($object->getIdentifier(), 'save'));
		if($this->isRebound()) $form->setRaw($this->post);
		
		/* We put some styling here as we don't want to add a new Template for that :) */
		$this->Template->append('<h1>'.Lang::retrieve('user.'.($object->isNew() ? 'add' : 'modify')).'</h1>
			'.implode(array_map('implode', $form->format()))
		);
	}
	
	public function onDelete($name){
		if(Request::retrieve('behaviour')!='json')
			throw new ValidatorException('contenttype');
		
		Response::setContentType('json');
		
		try{
			$object = $this->Model->findByIdentifier($name);
			if(!$object) throw new ValidatorException('newsnotavailable');
			$object->requireSession()->checkSession($this->post)->delete();
			
			$this->Template->assign(array(
				'out' => 'success',
				'msg' => Lang::retrieve('user.deleted'),
			));
		}catch(ValidatorException $e){
			$this->Template->assign(array(
				'out' => 'error',
				'msg' => $e->getMessage(),
			));
		}
	}
	
	public function onView(){
		$this->Model->findMany();
		$this->Template->apply('view.php');
	}
	
}