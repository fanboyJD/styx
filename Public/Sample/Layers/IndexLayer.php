<?php
class IndexLayer extends Layer {
	
	public function initialize(){
		return array(
			'options' => array(
				
			),
			'form' => new Form(array(
					
				),
				new Input(array(
					'name' => 'yea',
					':caption' => 'Test',
				)),
				new Button(array(
					'name' => 'buttonia',
					':caption' => 'EinTest',
				))
			)
		);
	}
	
	public function onEdit(){
		
	}
	
	public function onView(){
		
	}
	
	public function onDefault(){
		return 'This is Some Default Thing!';
	}
}
?>