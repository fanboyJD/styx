<?php
define('ONE_DAY', 86400);
define('ONE_WEEK', 604800);

Core::registerClasses('element', array(
	'elements', 'input', 'button', 'field', 'radio', 'textarea', 'checkbox', 'select'
));
Core::registerClasses('validator', array(
	'parser', 'formatter',
));

// Here's the basic configuration
/*Core::store(array(
	'debugMode' => false,
));*/
?>