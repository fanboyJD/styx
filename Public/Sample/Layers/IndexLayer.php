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
				new Button(array(
					'name' => 'bsave',
					':caption' => 'EinTest',
				))
			)
		);
	}
	
	public function onEdit(){
		
		/*return array(
			'edit' => array('uid' => 1)
		);*/
		
	}
	
	public function onView(){
		return 'This is Some Default Thing!';
	}
}
?>