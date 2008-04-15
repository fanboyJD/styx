<?php
	function __autoload($class){
		Core::autoload($class);
	}
	
	function array_remove(&$array, $value){
		$i = array_search($value, $array);
		if($i!==false) unset($array[$i]);
	}
	
	require_once('../Config/Configuration.php');
	
	$path = dirname(__FILE__).'/';
	set_include_path(get_include_path().PATH_SEPARATOR.$path);
	
	require_once('Classes/Core/Storage.php');
	require_once('Classes/Core/Core.php');
	
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
	
	Route::initialize($_GET, $_POST);
?>