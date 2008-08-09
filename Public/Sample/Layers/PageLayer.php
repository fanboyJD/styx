<?php
class PageLayer extends Layer {
	
	public function initialize(){
		return array(
			'table' => 'page',
			'options' => array(
				'identifier' => array(
					'internal' => 'id',
					'external' => 'pagetitle',
				),
			),
			'form' => new Form(
				new Input(array(
					'name' => 'title',
					':caption' => Lang::retrieve('title'),
				)),
				
				new Textarea(array(
					'name' => 'content',
					':caption' => Lang::retrieve('text'),
					':validate' => 'purify',
				)),
				
				new Button(array(
					'name' => 'bsave',
					':caption' => Lang::retrieve('save'),
				)),
				
				new Field(array(
					'name' => 'pagetitle',
				))
			)
		);
	}
	
	public function onSave(){
		try{
			if(!$this->editing)
				throw new ValidatorException('onlyedit');
			
			$data = $this->data->where($this->where)->fetch();
			
			$this->form->setValue(array(
				'pagetitle' => $this->getPagetitle($this->form->getValue('title'), $this->where),
			));
			
			$this->save();
			
			$this->Handler->assign(Lang::get('page.saved', $this->link($this->getValue('pagetitle'))));
		}catch(ValidatorException $e){
			$this->Handler->assign($e->getMessage());
		}catch(Exception $e){
			
		}
		
	}
	
	public function onEdit(){
		$this->edit();
		
		if(!$this->editing){
			try{
				throw new ValidatorException('onlyedit');
			}catch(ValidatorException $e){
				$this->Handler->assign($e->getMessage());
			}
			
			return;
		}
		
		$this->Handler->assign($this->format());
	}
	
	public function onView($title){
		if($title){
			$data = $this->data->where(array(
				'pagetitle' => array($title, 'pagetitle'),
			))->fetch();
		}else{
			$this->Handler->assign(Lang::retrieve('page.notavailable'));
			return;
		}
		
		if($this->hasRight('edit'))
			$this->Handler->assign(array(
				'rights' => '<a class="hicon" href="'.$this->link($data['pagetitle'], 'edit').'"><img src="Images/pencil.png" alt="'.Lang::retrieve('edit').'"></a>',
			));
		
		$this->Handler->template('view')->assign($data);
	}
	
	public function onMenu(){
		$this->data->limit(0)->order('id ASC');
		
		$this->Handler->template('menu.php');
	}
	
	public function populate(){
		/*
			This method gets automatically called by the edit and save handler
			to populate some stuff with data you may need :)
		*/
		
		$this->requireSession(); // Adds an invisible element with the current session so everything is safe :)
	}
}