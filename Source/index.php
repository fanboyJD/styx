<?php
	require_once('../Config/Configuration.php');
	
	$path = dirname(__FILE__).'/';
	set_include_path(get_include_path().PATH_SEPARATOR.$path);
	
	require_once('Classes/Core/coreClass.php');
	
	Core::store('path', $path);
	Core::store('appPath', realpath('../'));
	
	Core::loadClass('Base', 'Cache');
	Core::loadClass('Base', 'Util');
	
	require_once($path.'Config/Configuration.php');
	unset($path);
	if(is_array($_CONFIGURATION)){
		Core::store($_CONFIGURATION);
		unset($_CONFIGURATION);
	}
	
	Core::initialize();
	
	function __autoload($class){
		Core::autoloadClass($class);
	}
?>