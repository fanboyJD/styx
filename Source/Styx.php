<?php
/*
 * Styx - MIT-style License
 * Author: christoph.pojer@gmail.com
 */
if(!isset($Paths))
	$Paths = array(
		'app.path' => realpath('../').DIRECTORY_SEPARATOR,
		'app.public' => realpath('./').DIRECTORY_SEPARATOR,
	);
else
	foreach($Paths as $k => $path)
		$Paths[$k] = rtrim($path, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR; // Custom Paths might not have a slash at the end

require($Paths['app.path'].'/Config/Configuration.php');

if(empty($Paths['path'])) $Paths['path'] = dirname(__FILE__).DIRECTORY_SEPARATOR;

foreach(array('Storage', 'Hash', 'Core', 'String') as $v)
	require($Paths['path'].'Classes/Core/'.$v.'.php');

spl_autoload_register(array('Core', 'autoload'));

Core::store($Paths);

if(!isset($use) || empty($CONFIGURATION[$use]))
	$use = reset(array_keys($CONFIGURATION));

$CONFIGURATION[$use]['app.link'] = rtrim($CONFIGURATION[$use]['app.link'], '/').'/';

require($Paths['path'].'/Config/Configuration.php');

Core::store($CONFIGURATION[$use]);

unset($use, $Paths, $CONFIGURATION); // Unset the whole array, so no data from it is available any longer

ini_set('date.timezone', Core::retrieve('timezone'));

String::initialize(Core::fetch('feature.mbstring', 'feature.iconv'));
Core::initialize();

Request::initialize();

User::initialize();

Core::fireEvent('initialize');

if(!Response::getContentType())
	Response::setDefaultContentType(Core::retrieve('contenttype.default'));

Lang::setLanguage(Request::getLanguage());

Route::initialize();