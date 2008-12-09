<?php
class PageLayer extends Layer {
	
	public function initialize(){
		return array(
			'table' => 'page',
			'options' => array(
				'cache' => false, // Just for testing purposes. Caching should only be deactivated if the data frequently changes or gets modified externally
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
					':validate' => array('purify', array( // These are the options for the Data-Class method "purify". In this case the classes in the HTML to be kept
						'classes' => array('green', 'blue', 'b', 'icon', 'bold', 'italic'),
					)),
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
		if(!$this->editing)
			throw new ValidatorException('onlyedit');
		
		$this->setValue(array(
			'pagetitle' => $this->getPagetitle($this->getValue('title')),
		));
		
		$this->save();
		
		$this->Template->assign(Lang::get('page.saved', $this->link($this->getValue('pagetitle'))));
	}
	
	public function onEdit(){
		$this->edit();
		
		if(!$this->editing)
			throw new ValidatorException('onlyedit');
		
		/* We put some styling here as we don't want to add a new Template for that :) */
		$this->Template->assign('<div class="inner">
			'.Data::implode($this->format()).'
			<div class="clear"></div>
			</div>'
		);
	}
	
	public function onView($title){
		if($title){
			$data = $this->Data->where(array(
				'pagetitle' => array($title, 'pagetitle'),
			))->fetch();
		}
		
		if(!$data['id']){
			$this->Template->assign(Lang::retrieve('page.notavailable'));
			return;
		}
		
		if(User::hasRight('layer.page.edit'))
			$this->Template->assign(array(
				'rights' => '<a class="hicon" href="'.$this->link($data['pagetitle'], 'edit').'"><img src="Images/pencil.png" alt="'.Lang::retrieve('edit').'"></a>',
			));
		
		$this->Template->apply('view')->assign($data);
	}
	
	public function onMenu(){
		$this->Data->limit(0)->order('id ASC');
		
		$this->Template->apply('menu.php');
	}
	
	public function populate(){
		/*
			This method gets automatically called by the edit and save handler
			to populate some stuff with data you may need :)
		*/
		
		$this->requireSession(); // Adds an invisible element with the current session so everything is safe :)
	}
}