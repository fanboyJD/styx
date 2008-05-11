<?php
include('../../../Source/index.php');

Handler::getInstance()->behaviour('html')->setTemplate('index')->assign(array(
	'appName' => Core::retrieve('appName'),
	'appLink' => Core::retrieve('appLink'),
	'body' => '',
))->parse();

Handler::getInstance()->behaviour('xml', 'json')->disable();

/*Handler::getInstance()->behaviour('json')->assign(array(
	'test' => 'a',
	'body' => array(
		'here goes something that could be used for an API :)',
	),
))->filter('body')->parse();

Handler::getInstance()->behaviour('xml')->assign(array(
	'appName' => Core::retrieve('appName'),
	'appMail' => 'christoph.pojer@gmail.com',
	'appLink' => 'http://domain.com',
	'news' => array(
		'something' => array(
			'title' => 'asdf',
			'descr' => 'yes',
		),
	),
))->setTemplate('rss.php')->parse();*/
?>