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
	
	public function onSave($data, $validate, $where){
		if($validate!==true){
			$this->Handler->assign(array('error' => 'Yuck! Something got wrong: '))->assign(implode(' - ', $validate));
			return;
		}
		
		$this->form->setValue(array(
			'time' => time(),
			'pagetitle' => $this->getPagetitle($data['title'], $where),
		));
		
		$this->save();
		
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