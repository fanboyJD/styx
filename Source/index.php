<?php
	require_once('../Config/Configuration.php');
	
	$path = dirname(__FILE__).'/';
	set_include_path(get_include_path().PATH_SEPARATOR.$path);
	
	require_once('Classes/Core/Methods.php');
	require_once('Classes/Core/Storage.php');
	require_once('Classes/Core/Core.php');
	
	spl_autoload_register(array('Core', 'autoload'));
	
	Core::store('path', $path);
	Core::store('appPath', realpath('../'));
	
	Core::loadClass('Base', 'Cache');
	Core::loadClass('Base', 'Data');
	
	require_once($path.'Config/Configuration.php');
	unset($path);
	if(is_array($_CONFIGURATION)){
		Core::store($_CONFIGURATION);
		unset($_CONFIGURATION);
	}
	
	Core::initialize();
	Core::pollute();
	
	db::getInstance(Core::retrieve('database'));
	
	Handler::getInstance()->setType($_GET['p']['handler']);
	
	Route::initialize($_GET, $_POST);
?>