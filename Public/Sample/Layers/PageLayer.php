<?php
class PageLayer extends Layer {
	
	public function initialize(){
		return array(
			'cache' => false, // Just for testing purposes. Caching should only be deactivated if the data frequently changes or gets modified externally
		);
	}
	
	public function populate(){
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
				),
			)),
			
			new Button(array(
				'name' => 'bsave',
				':caption' => Lang::retrieve('save'),
			)),
			
			new Field('pagetitle')
		);
		
		$this->requireSession(); // Adds an invisible element with the current session so everything is safe :)
	}
	
	public function onSave(){
		if(!User::hasRight('layer.page.edit'))
			throw new ValidatorException('rights');
		
		if(!$this->editing)
			throw new ValidatorException('onlyedit');
		
		$this->setValue(array(
			'pagetitle' => $this->getPagetitle($this->getValue('title')),
		));
		
		$this->save();
		
		$this->Template->append(Lang::get('page.saved', $this->link($this->getValue('pagetitle'))));
	}
	
	public function onEdit(){
		if(!User::hasRight('layer.page.edit'))
			throw new ValidatorException('rights');
		
		$this->edit();
		
		if(!$this->editing)
			throw new ValidatorException('onlyedit');
		
		/* We put some styling here as we don't want to add a new Template for that :) */
		$this->Template->append('<div class="inner">
			'.implode(array_map('implode', $this->format())).'
			<div class="clear"></div>
			</div>'
		);
	}
	
	public function onView($title){
		$data = null;
		if($title)
			$data = $this->Data->where(array(
				'pagetitle' => array($title, 'pagetitle'),
			))->fetch();
		
		if(!$data){
			$this->Template->assign(Lang::retrieve('page.notavailable'));
			return;
		}
		
		if(User::hasRight('layer.page.edit'))
			$this->Template->assign(array(
				'rights' => '<a class="hicon" href="'.$this->link($data['pagetitle'], 'edit').'"><img src="Images/pencil.png" alt="'.Lang::retrieve('edit').'" /></a>',
			));
		
		$this->Template->apply('view')->assign($data);
	}
	
	public function onMenu(){
		/* We want to cache the menu-entries! */
		$this->Data = db::select($this->table)->fields('title, pagetitle')->limit(0)->order('id ASC');
		
		$this->Template->apply('menu.php');
	}
	
}