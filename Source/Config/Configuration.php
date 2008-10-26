<?php
/*
 * Styx - MIT-style License
 * Author: christoph.pojer@gmail.com
 */

define('ONE_DAY', 86400);
define('ONE_WEEK', 604800);

// Here's the basic configuration
Core::store(array(
	'styx.name' => 'Styx PHP Framework',
	'styx.version' => '0.2dev',
	
	'path.separator' => ':',
	
	'app.version' => '1',
	
	'prefix' => 'styxapp',
	
	'debug' => false,
	
	'template.default' => 'tpl',
	'template.execute' => array('php', 'phtml'),
	
	'user' => array(
		'type' => 'cookie',
		'table' => 'users',
		'fields' => array('name', 'pwd', 'session'),
		'session' => 'session',
		'rights' => 'rights',
	),
	
	'handler' => array('html', 'json', 'xml'),
	
	'identifier.internal' => 'id',
	'identifier.external' => 'pagetitle',
	
	'elements.prefix' => 'el',
	
	'expiration' => 31536000, // A long time
	'cookie' => array(
		'expire' => 8640000,
		'path' => '/',
		/*'domain' => '',
		'secure' => '',
		'httponly' => '',*/
	),
	
	'timezone' => 'Europe/Vienna',
));