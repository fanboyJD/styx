<?php
	define('ONE_DAY', 86400);
	define('ONE_WEEK', 604800);
	
	Core::registerClasses('Element', array(
		'Elements', 'Input', 'HiddenInput', 'Button', 'Field', 'Radio',
		'Textarea', 'Checkbox', 'Select', 'RichText',
	));
	Core::registerClasses('Validator', array(
		'Parser', 'Formatter',
	));
	
	// Here's the basic configuration
	/*Core::store(array(
		'debugMode' => false,
	));*/
?>