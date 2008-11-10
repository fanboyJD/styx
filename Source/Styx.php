<?php
/*
 * Styx - MIT-style License
 * Author: christoph.pojer@gmail.com
 */

unset($CONFIGURATION);
if(!isset($Paths) || !is_array($Paths))
	$Paths = array(
		'app.path' => realpath('../').DIRECTORY_SEPARATOR,
		'app.public' => realpath('./').DIRECTORY_SEPARATOR,
	);
else
	foreach($Paths as $k => $path)
		if(strrpos($path, '/')!==strlen($path)-1)
			$Paths[$k] = $path.'/'; // Custom Paths may not have a slash at the end

require_once($Paths['app.path'].'/Config/Configuration.php');

if(empty($Paths['path'])) $Paths['path'] = dirname(__FILE__).DIRECTORY_SEPARATOR;

foreach(array('Storage', 'Hash', 'Core', 'String', 'Data') as $v)
	require_once($Paths['path'].'Classes/Core/'.$v.'.php');

spl_autoload_register(array('Core', 'autoload'));

Core::store($Paths);

Core::loadClass('Cache', 'Cache');

if(!String::ends($CONFIGURATION[$use]['app.link'], '/'))
	$CONFIGURATION[$use]['app.link'] .= '/';

require_once($Paths['path'].'/Config/Configuration.php');

if(is_array($CONFIGURATION[$use])){
	Core::store($CONFIGURATION[$use]);
	unset($CONFIGURATION); // We unset the whole array, so no data from it is available any longer
}
unset($Paths);

ini_set('date.timezone', Core::retrieve('timezone'));

Core::initialize();

Core::autoload('Element'); // We need to load Element as there are several Classes that are always needed

Request::initialize();

if(function_exists('initialize')) initialize();

User::initialize();

$get = Request::getInstance()->retrieve('get');
if(!empty($get['m']['package']) && PackageManager::setPackage($get['m']['package'])){
	Page::getInstance()->show();
	
	die;
}

if(!Page::getContentType())
	Page::setDefaultContentType(Core::retrieve('contenttype.default'));

Lang::setLanguage(Request::getLanguage());

PackageManager::assignToPage();

Route::initialize($get, Request::getInstance()->retrieve('post'));

unset($get);