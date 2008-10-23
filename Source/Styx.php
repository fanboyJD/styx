<?php
/*
 * Styx - MIT-style License
 * Author: christoph.pojer@gmail.com
 */

$_CONFIGURATION = null;
if(!is_array($Paths))
	$Paths = array(
		'app.path' => realpath('../').DIRECTORY_SEPARATOR,
		'app.public' => realpath('./').DIRECTORY_SEPARATOR,
	);
else
	foreach($Paths as $k => $path)
		if(strrpos($path, '/')===strlen($path)-1)
			$Paths[$k] = $path.'/'; // Custom Paths may not have a slash at the end

require_once($Paths['app.path'].'/Config/Configuration.php');

if(!$Paths['path']) $Paths['path'] = dirname(__FILE__).DIRECTORY_SEPARATOR;
set_include_path(get_include_path().PATH_SEPARATOR.$Paths['path']);

foreach(array('Storage', 'Hash', 'Core', 'String') as $v)
	require_once('Classes/Core/'.$v.'.php');

spl_autoload_register(array('Core', 'autoload'));

Core::store($Paths);

Core::loadClass('Core', 'Data');
Core::loadClass('Cache', 'Cache');

require_once($Paths['path'].'/Config/Configuration.php');


if(is_array($_CONFIGURATION)){
	Core::store($_CONFIGURATION);
	unset($_CONFIGURATION);
}
unset($Paths);

ini_set('date.timezone', Core::retrieve('timezone'));

Core::initialize();

Core::autoload('Element'); // We need to load Element as there are several Classes that are always needed

if(function_exists('initialize')) initialize();

Request::initialize();

$get = Request::retrieve('get');

Handler::setHandlers(Core::retrieve('handler'));

User::initialize();

if($get['m']['package'] && PackageManager::setPackage($get['m']['package'])){
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