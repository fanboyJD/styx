<?php
class IndexLayer extends Layer {
	
	public function initialize(){
		return array(
			'table' => 'news',
			'options' => array(
				'identifier' => 'id',
			),
			'form' => new Form(array(
					'name' => 'Index',
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
		if($this->editing){
			$data = $this->data->where($this->where)->fetch();
			$this->Handler->assign(array('Editing: '.$data['title']));
		}
		
		$this->form->setValue(array(
			'time' => time(),
			'pagetitle' => $this->getPagetitle($this->form->getValue('title'), $this->where),
		));
		
		try{
			$this->save();
			
			$this->Handler->assign(array('saved' => 'Some Output: Saved! Id: '.db::getInstance()->getId()));
		}catch(ValidatorException $e){
			//print_r($e->error);
		}catch(Exception $e){
			
		}
		
	}
	
	public function onAdd(){
		$this->Handler->template('edit')->assign($this->add());
	}
	
	public function onEdit(){
		/*$this->edit(array(
			'edit' => array('uid' => 1),
			'preventDefault' => true,
		));*/
		$out = $this->edit();
		
		if($this->editing)
			$this->Handler->assign('Editing: '.$this->form->getValue('title'));
		
		$this->Handler->assign($out);
	}
	
	public function onView(){
		$this->data->limit(0)->order('time DESC')->retrieve();
		
		$this->Handler->template('view.php');
	}
}