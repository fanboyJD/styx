<?php
define('ONE_DAY', 86400);
define('ONE_WEEK', 604800);

Core::registerClasses('Element', array(
	'Elements', 'Input', 'HiddenInput', 'Button', 'Field', 'Radio', 'Textarea',
	'Checkbox', 'Select', 'RichText', 'OptionList',
));

// Here's the basic configuration
Core::store(array(
	'path.separator' => ':',
	
	'app.version' => '1',
	
	'debug' => false,
	
	'tpl.standard' => 'tpl',
	'tpl.execute' => array('php', 'phtml'),
	
	'handler' => array('html', 'json', 'xml'),
	
	'identifier.id' => 'id',
	
	'elements.prefix' => 'el',
	
	'user.cookie' => 'app',
	
	'expiration' => 31536000, // A long time
));