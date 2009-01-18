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
	'styx.link' => 'http://styx.og5.net',
	'styx.version' => '0.2beta',
	
	'path.separator' => ':',
	
	'app.version' => '1',
	
	'prefix' => 'styxapp',
	
	'debug' => false,
	'database.cache' => true, // Determines whether to use caching of database results or not
	
	'template.default' => 'tpl',
	'template.execute' => array('php', 'phtml'),
	'template.regex' => '/\\$\{([A-z0-9\.:\s|]+)\}/i',
	'template.encode' => array('$' => '&#36;'), // $ is escaped because user input might contain ${...} for Template-substitution. This prevents bad input.
	
	'cache' => array(
		'engine' => 'apc',
	),
	
	'user' => array(
		'type' => 'cookie',
		'table' => 'users',
		'fields' => array('name', 'pwd', 'session'),
		'session' => 'session',
		'rights' => 'rights',
	),
	
	'layer.default' => array(
		'layer' => 'index',
		'event' => 'view'
	),
	
	'languages.cookie' => 'language',
	'languages.querystring' => 'language',
	
	'contenttype.default' => 'html',
	
	'contenttype.querystring' => 'handler',
	
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
	
	'feature.mbstring' => function_exists('mb_strlen'), // We check for support of a random mb_* method
	'feature.iconv' => function_exists('iconv'),
));