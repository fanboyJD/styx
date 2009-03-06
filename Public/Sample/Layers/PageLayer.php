<?php
class PageLayer extends Layer {
	
	public function onSave($title){
		$object = $this->Model->findByIdentifier($title);
		
		if(!$object) throw new ValidatorException('onlyedit');
		
		if(!User::hasRight('layer.page.edit')) throw new ValidatorException('rights');
		
		if(!$object->store($this->post)->requireSession()->save()) throw new ValidatorException('data');
		
		$this->Template->append(Lang::get('page.saved', $this->link($object['pagetitle'])));
	}
	
	public function onEdit($title){
		$object = $this->Model->findByIdentifier($title);
		
		if(!$object) throw new ValidatorException('onlyedit');
		
		if(!User::hasRight('layer.index.edit')) throw new ValidatorException('rights');
		
		$object->requireSession()->getForm()->get('action', $this->link($object->getIdentifier(), $this->getDefaultEvent('save')));
		
		/* We put some styling here as we don't want to add a new Template for that :) */
		$this->Template->append('<h1>'.Lang::retrieve('page.modify').'</h1>
			'.Hash::implode($object->getForm()->format()));
	}
	
	public function onView($title){
		$object = null;
		if($title) $object = $this->Model->findByIdentifier($title);
		if(!$object) throw new ValidatorException('pagenotavailable');
		
		if(User::hasRight('layer.page.edit'))
			$this->Template->assign(array(
				'rights' => '<a class="hicon" href="'.$this->link($object['pagetitle'], 'edit').'"><img src="Images/pencil.png" alt="'.Lang::retrieve('edit').'" /></a>',
			));
		
		$this->Template->apply('view')->assign($object);
	}
	
	public function onMenu(){
		$this->Model->findMenuEntries();
		
		$this->Template->apply('menu.php');
	}
	
}