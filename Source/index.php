<?php
	$_CONFIGURATION = null;
	require_once('../Config/Configuration.php');
	
	$path = dirname(__FILE__).'/';
	set_include_path(get_include_path().PATH_SEPARATOR.$path);
	
	require_once('Classes/Core/Methods.php');
	require_once('Classes/Core/Storage.php');
	require_once('Classes/Core/Core.php');
	
	spl_autoload_register(array('Core', 'autoload'));
	
	Core::store('path', $path);
	Core::store('app.path', realpath('../').'/');
	
	Core::loadClass('Base', 'Cache');
	Core::loadClass('Base', 'Data');
	require_once('Classes/Base/Exceptions.php');
	
	require_once($path.'Config/Configuration.php');
	unset($path);
	if(is_array($_CONFIGURATION)){
		Core::store($_CONFIGURATION);
		unset($_CONFIGURATION);
	}
	
	Core::initialize();
	
	if(function_exists('initialize')) initialize();
	
	Core::pollute();
	
	Handler::setHandlers(Core::retrieve('handler'));
	
	if(PackageManager::has($_GET['package'])){
		PackageManager::setPackage($_GET['package']);
		
		Handler::useExtendedTypes();
		
		Handler::setType(PackageManager::getType());
		Handler::setHeader();
		
		Handler::map()->parse();
		
		die;
	}else{
		$languages = Core::retrieve('languages');
		
		if(is_array($languages)){
			/* We set the first language in the array as default language */
			Lang::setLanguage(array_shift($languages));
			
			/* And overwrite with the selected language, in that way it keeps missing language strings */
			if($_GET['p']['lang'] && sizeof($languages) && in_array($_GET['p']['lang'], $languages))
				Lang::setLanguage($_GET['p']['lang']);
		}
		
		User::initialize();
		
		Handler::setType($_GET['p']['handler']);
		Handler::setHeader();
		
		PackageManager::assignToMaster();
		
		Route::initialize($_GET, $_POST);
	}