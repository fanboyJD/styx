<?php
	include('../../../Source/index.php');
	
	function initialize(){
		PackageManager::add('package1.js', array(
			'type' => 'js',
			'files' => 'mootools',
		));
		
		PackageManager::add('style.css', array(
			'type' => 'css',
			'files' => 'style',
		));
	}
	
	if(Handler::behaviour('html'))
		Handler::map()->template('index')->assign(array(
			'app.name' => Core::retrieve('app.name'),
			'app.link' => Core::retrieve('app.link'),
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
			'app.name' => Core::retrieve('app.name'),
			'app.link' => Core::retrieve('app.link'),
			'app.mail' => Core::retrieve('app.mail'),
			'news' => array(
				'something' => array(
					'title' => 'asdf',
					'descr' => 'yes',
				),
			),
		))->setTemplate('rss.php')->parse();
	*/