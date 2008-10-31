<?php
$CONFIGURATION['debug'] = array(
	'path.separator' => ';',
	
	'app.name' => 'Styx Unit Test',
	'app.link' => 'http://svn/Framework/trunk/Public/Sample/Test',
	'app.mail' => 'someone@nowhere.ru',
	'app.version' => '0.2dev',
	
	'debug' => true,
	
	'prefix' => 'test',
	
	'database' => array(
		'host' => 'localhost',
		'user' => 'root',
		'password' => '',
		'db' => 'test'
	),
	
	'secure' => 'Some secure test',
	
	'languages' => array(
		'en' => array('en-us', 'en-gb', 'en'),
		'de' => array('de-de', 'de-at', 'de-ch', 'de'),
	),
	
	'rights.layer' => array(
		'index' => array(
			'edit' => array(
				'add' => 1,
				'modify' => 1,
			),
			'delete' => 1,
		),
	),
);