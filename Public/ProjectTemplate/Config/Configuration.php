<?php
$CONFIGURATION['debug'] = array(
	'path.separator' => ';', // On Linux you can safely use : But on Windows using : with Apache results in a strange bug
	
	'app.name' => 'My Application',
	'app.link' => 'http://localhost/Styx/Public/ProjectTemplate/Public',
	'app.mail' => 'my@email.com',
	'app.version' => '0.1',
	
	'debug' => true, // When debug is set to true no cache is used etc.
	'database.cache' => false, // If false, no queries are cached, only useful for debugging, should be changed to true later on
	
	'prefix' => 'application', // Some unique [a-z] prefix for your Application
	
	'database' => array(
		'host' => 'localhost',
		'user' => 'root',
		'password' => '',
		'db' => 'application',
	),
	
	'secure' => '-insecure-', // This should be a safe, unique key for your application that shouldn't be exposed as it is used for additional security
	'languages' => array(
		'en' => array('en-us', 'en-gb', 'en'),
	),
	
);

// The settings for the "production server"
$CONFIGURATION['production'] = array_merge($CONFIGURATION['debug'], array(
	'debug' => false,
	'database.cache' => true,
	'app.link' => 'http://my.application.com',
	
	'database' => array(
		'host' => 'localhost',
		'user' => 'myusername',
		'password' => 'mypassword',
		'db' => 'application',
	),
	
));