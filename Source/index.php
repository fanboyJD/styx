<?php
	require_once('./Classes/Core/coreClass.php');
	Core::loadClass('Base', 'Cache');
	Core::loadClass('Base', 'Util');
	
	require_once('./Config/Configuration.php');
	
	if(is_array($_CONFIGURATION)){
		Core::store($_CONFIGURATION);
		unset($_CONFIGURATION);
	}
	
	Core::initialize(realpath('./'));
	
	function __autoload($class){
		Core::autoloadClass($class);
	}
?>