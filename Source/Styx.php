<?php
/*
 * Styx - MIT-style License
 * Author: christoph.pojer@gmail.com
 */

$_CONFIGURATION = null;
require_once('../Config/Configuration.php');

$path = dirname(__FILE__).'/';
set_include_path(get_include_path().PATH_SEPARATOR.$path);

require_once('Classes/Core/Storage.php');
require_once('Classes/Core/Core.php');
require_once('Classes/Core/String.php');

spl_autoload_register(array('Core', 'autoload'));

Core::store('path', $path);
Core::store('app.path', realpath('../').'/');

Core::loadClass('Cache', 'Cache');
Core::loadClass('Base', 'Data');

require_once($path.'Config/Configuration.php');
unset($path);

if(is_array($_CONFIGURATION)){
	Core::store($_CONFIGURATION);
	unset($_CONFIGURATION);
}

Core::initialize();

Core::autoload('Element'); // We need to load Element as there are several Classes that are always needed

if(function_exists('initialize')) initialize();

Request::initialize();

$get = Request::retrieve('get');

Handler::setHandlers(Core::retrieve('handler'));

User::initialize();

if(PackageManager::has($get['m']['package'])){
	PackageManager::setPackage($get['m']['package']);
	
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
		if($get['m']['lang'] && sizeof($languages) && in_array($get['m']['lang'], $languages))
			Lang::setLanguage($get['m']['lang']);
	}
	
	unset($languages);
	
	Handler::setType($get['p']['handler']);
	Handler::setHeader();
	
	PackageManager::assignToMaster();
	
	Route::initialize($get, Request::retrieve('post'));
}

unset($get);