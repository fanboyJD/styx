<?php
	chdir('../Public');
	include('index.php');
	
	$form = new Form(
		array(
			'name' => 'test',
		),
		new Select(array(
			'name' => 'Test',
			':elements' => array(
				array(
					'value' => 'blah',
					':caption' => 'test',
				),
				array(
					'value' => 'blah1',
					':caption' => '1',
				),
				
				array(
					'value' => 'blah2',
					':caption' => '2',
				),
			),
			'value' => 'blah2',
		)),
		new Checkbox(array(
			':caption' => 'Test',
			'name' => 'Asdf',
			'value' => 'Hey',
			':default' => 'Hey',
		)),
		new Input(array(
			'name' => 'yea',
			':caption' => 'hey!',
		)),
		new Textarea(array(
			'name' => 'HarHar',
		)),
		new Button(array(
			'name' => 'send',
			':caption' => 'blahblah',
		))
	);
	
	echo $form->format();
?>