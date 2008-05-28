<?php
	chdir('../Public');
	include('index.php');
	
	$form = new Form(
		array(
			'name' => 'test',
		),
		new Select(array(
			'name' => 'Test',
			'class' => 'test asdf',
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
		new Radio(array(
			':caption' => 'This is some Test!',
			'name' => 'asdf',
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
			'value' => 'blah1',
		)),
		new Checkbox(array(
			':caption' => 'Test',
			'class' => array('helloo', 'tester'),
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
		)),
		new Richtext(array(
			'name' => 'awesoome',
			':caption' => 'This later will be a richtext element!',
		))
	);
	
	echo $form->format();