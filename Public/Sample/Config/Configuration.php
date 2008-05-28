<?php
$_CONFIGURATION = array(
	'app.name' => 'My Sample Application',
	'app.link' => 'http://svn/Framework/trunk/Public/Sample/Public/index.php/',
	'app.link' => 'http://localhost/trunk/Public/Sample/Public/index.php/',
	'app.mail' => 'someone@nowhere.ru',
	
	'debug' => true,
	
	'cache' => array(
		'prefix' => 'sample',
	),
	
	'database' => array(
		'host' => 'localhost',
		'user' => 'root',
		'password' => '',
		'db' => 'framework'
	),
	
	'handler' => array('html', 'json', 'xml'),
	
	'secure' => '32lms/(d902_3-k2"§$jsd',
	
	'languages' => array('en'),
	
	'user.type' => 'cookie',
	'user.cookie' => 'app',
);