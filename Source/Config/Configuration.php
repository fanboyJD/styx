<?php
define('ONE_DAY', 86400);
define('ONE_WEEK', 604800);

Env::registerClasses('element', array(
	'elements', 'input', 'button', 'field', 'radio', 'textarea', 'checkbox', 'select'
));
Env::registerClasses('validator', array(
	'parser', 'formatter',
));

//Here's the basic configuration
Env::store(array(
	'debugMode' => true,
));
?>