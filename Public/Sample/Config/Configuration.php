<?php
$_CONFIGURATION = array(
	'path.separator' => ';', // On Linux you can safely use : But on Windows using : with Apache results in a strange bug
	
	'app.name' => 'Styx Framework Application',
	'app.link' => 'http://svn/Framework/trunk/Public/Sample/Public/',
	'app.mail' => 'someone@nowhere.ru',
	'app.version' => '0.1',
	
	'debug' => true, // When debug is set to true no cache is used etc.
	
	'cache' => array(
		'prefix' => 'sample', // Some unique prefix for your Application
	),
	
	'database' => array(
		'host' => 'localhost',
		'user' => 'root',
		'password' => '',
		'db' => 'framework'
	),
	
	'secure' => '32lms/(d902_3-k2"§$jsd', // This should be unique to your application and shouldn't be exposed since it is used for security reasons
	
	'languages' => array('en'),
	
	'user.type' => 'cookie',
	'user.cookie' => 'sample',
	'user.table' => 'users',
	'user.fields' => array('name', 'pwd'),
	'user.sessionfield' => 'session',
	'user.rightsfield' => 'rights',
	
	'rights.layer' => array(
		'index' => array( // You can specify different rights for the IndexLayer like only adding or adding/modifying but not deleting
			'edit' => array(
				'add' => 1,
				'modify' => 1,
			),
			'delete' => 1,
		),
		
		'page' => array(
			'edit' => 1 // You need rights to edit entries in the PageLayer
		),
	),
);