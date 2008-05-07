<?php
class IndexLayer extends Layer {
	
	public function initialize(){
		return array(
			'table' => 'news',
			'options' => array(
				'identifier' => 'id',
			),
			'form' => new Form(array(
					
				),
				new Input(array(
					'name' => 'title',
					':caption' => 'Titel',
				)),
				new Input(array(
					'name' => 'uid',
					':caption' => 'UserID',
					':validate' => 'id',
				)),
				new Textarea(array(
					'name' => 'content',
					'cols' => 20,
					'rows' => 5,
					':caption' => 'Text',
					':validate' => 'specialchars',
				)),
				new Button(array(
					'name' => 'bsave',
					':caption' => 'EinTest',
				)),
				new Field(array(
					'name' => 'pagetitle',
				)),
				new Field(array(
					'name' => 'time',
				))
			)
		);
	}
	
	public function onSave(){
		// Need a way to distinguish between edit/noedit -> this->where enough?
		// -> Should be possible to access the edited row
		// ?-> print_r(db::getInstance()->select($this->table)->where($this->where)->fetch());
		return;$this->form->setValue(array(
			'time' => time(),
			'pagetitle' => $this->getPagetitle($this->form->getValue('title'), $this->where),
		));
		
		// Here should go an Exception Handler =)
		$ret = $this->save();
		if($ret!==true){
			// Validation Problem [...]
			return;
		}
		
		$this->Handler->assign(array(
			'Some Output: Saved!', 'Id: ', db::getInstance()->getId(),
		));
	}
	
	public function onAdd(){
		$this->Handler->assign($this->add());
	}
	
	public function onEdit(){
		$this->Handler->assign($this->edit());
		/*return array(
			'edit' => array('uid' => 1),
			'preventDefault' => true,
		);*/
		
	}
	
	public function onView(){
		return 'This is Some Default Thing!';
	}
}
?>