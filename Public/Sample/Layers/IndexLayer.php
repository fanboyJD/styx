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
				new Textarea(array(
					'name' => 'content',
					':caption' => 'Text',
					'cols' => 20,
					'rows' => 5,
				)),
				new Button(array(
					'name' => 'bsave',
					':caption' => 'EinTest',
				))
			)
		);
	}
	
	public function onEdit(){
		
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