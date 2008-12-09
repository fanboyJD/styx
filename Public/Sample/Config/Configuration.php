<?php
$CONFIGURATION['debug'] = array(
	'path.separator' => ';', // On Linux you can safely use : But on Windows using : with Apache results in a strange bug
	
	'app.name' => 'Styx Framework Application',
	'app.link' => 'http://svn/Framework/trunk/Public/Sample/Public',
	'app.mail' => 'someone@nowhere.ru',
	'app.version' => '0.2dev',
	
	'debug' => true, // When debug is set to true no cache is used etc.
	
	'prefix' => 'sample', // Some unique [a-z] prefix for your Application
	
	/*'cache' => array(
		// Here are maybe some options for other engines
	),*/
	
	'database' => array(
		'host' => 'localhost',
		'user' => 'root',
		'password' => '',
		'db' => 'framework'
	),
	
	'secure' => '32lms/(d902_3-k2"§$jsd', // This should be unique to your application and shouldn't be exposed since it is used for additional security
	
	'languages' => array(
		'en' => array('en-us', 'en-gb', 'en'),
		'de' => array('de-de', 'de-at', 'de-ch', 'de'),
	),
	
);

$CONFIGURATION['production'] = array_merge($CONFIGURATION['debug'], array(
	'debug' => false,
	'app.link' => 'http://styx.og5.net',
	
	'database' => array(
		'host' => 'localhost',
		'user' => 'wusch',
		'password' => '',
		'db' => 'wusch_framework'
	),
	
));