<?php
	include('../../../Source/index.php');
	
	if(Handler::behaviour('html'))
		Handler::map()->setTemplate('index')->assign(array(
			'appName' => Core::retrieve('appName'),
			'appLink' => Core::retrieve('appLink'),
			'body' => '',
		))->parse();
	else
		Handler::map()->disable();
	
	/*
	if(Handler::behaviour('json'))
		Handler::map()->assign(array(
			'test' => 'a',
			'body' => array(
				'here goes something that could be used for an API :)',
			),
		))->filter('body')->parse();
	
	if(Handler::behaviour('xml'))
		Handler::map()->assign(array(
			'appName' => Core::retrieve('appName'),
			'appMail' => 'christoph.pojer@gmail.com',
			'appLink' => 'http://domain.com',
			'news' => array(
				'something' => array(
					'title' => 'asdf',
					'descr' => 'yes',
				),
			),
		))->setTemplate('rss.php')->parse();*/
?>