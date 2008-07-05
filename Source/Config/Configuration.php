<?php
define('ONE_DAY', 86400);
define('ONE_WEEK', 604800);

Core::registerClasses('Element', array(
	'Elements', 'Input', 'HiddenInput', 'Button', 'Field', 'TemplateRadioSelect',
	'Radio', 'Textarea', 'Checkbox', 'Select', 'RichText',
));

// Here's the basic configuration
Core::store(array(
	'app.version' => '1',
	
	'debug' => false,
	
	'tpl.standard' => 'tpl',
	'tpl.execute' => array('php', 'phtml'),
	
	'handler' => array('html', 'json', 'xml'),
	
	'identifer.id' => 'id',
	
	'elements.prefix' => 'el',
	
	'user.cookie' => 'app',
	
	'expiration' => 31536000, // A long time
));