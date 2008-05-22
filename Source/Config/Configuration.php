<?php
define('ONE_DAY', 86400);
define('ONE_WEEK', 604800);

Core::registerClasses('Element', array(
	'Elements', 'Input', 'HiddenInput', 'Button', 'Field', 'Radio',
	'Textarea', 'Checkbox', 'Select', 'RichText',
));

// Here's the basic configuration
Core::store(array(
	'debugMode' => false,
	'tpl.standard' => 'tpl',
	'tpl.execute' => array('php', 'phtml'),
	
	'identifer.id' => 'id',
	
	'elements.prefix' => 'el',
));
?>