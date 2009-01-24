<?php
class UserLayer extends Layer {
	
	public function initialize(){
		$this->base = 'admin/user';
		
		return array(
			'identifier' => array(
				'internal' => 'id',
				'external' => 'name',
			),
			'table' => 'users',
		);
	}
	
	public function populate(){
		$this->Form->addElements(
			new Input(array(
				'name' => 'name',
				':caption' => Lang::retrieve('user.name'),
				':validate' => array(
					'pagetitle' => true,
				),
			)),
			
			new Input(array(
				'name' => 'password',
				'type' => 'password',
				':alias' => true,
				':caption' => Lang::retrieve('user.pwd'),
			)),
			
			new Button(array(
				'name' => 'bsave',
				':caption' => Lang::retrieve('save'),
			)),
			
			new Field('pwd')
		);
		
		$this->requireSession(); // Adds an invisible element with the current session so everything is safe :)
	}
	
	public function access(){
		if(!User::hasRight('layer.user'))
			throw new ValidatorException('rights');
		
		return true;
	}
	
	public function onSave(){
		$pw = $this->getValue('password');
		$this->setValue(array(
			'pwd' => !$pw && $this->editing ? $this->content['pwd'] : User::getPassword($pw),
		));
		
		$this->save();
		
		$this->Template->append(Lang::get('user.saved', $this->link()));
	}
	
	public function onEdit(){
		$this->edit();
		
		if($this->editing)
			$this->Form->getElement('password')->set(':add', Lang::retrieve('user.pwdempty'));
		
		/* We put some styling here as we don't want to add a new Template for that :) */
		$this->Template->append('<h1>'.Lang::retrieve('user.'.($this->editing ? 'modify' : 'add')).'</h1>
			'.implode(array_map('implode', $this->format()))
		);
	}
	
	public function onDelete($title){
		if(Request::retrieve('behaviour')!='json')
			throw new ValidatorException('contenttype');
		
		Response::setContentType('json');
		
		try{
			$this->delete();
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
		$this->Template->apply('view.php');
	}
	
}