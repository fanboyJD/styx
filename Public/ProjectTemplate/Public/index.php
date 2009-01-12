<?php
	$use = 'debug';
	
	include('../../../Source/Styx.php');
	/**/if(file_exists('./Setup.php')) include('./Setup.php');
	
	switch(Response::getContentType()){
		case 'html':
			Page::getInstance()->apply('html.php')->assign(Core::fetch('app.name', 'app.link'))->assign(array(
				'rss' => array(
					'link' => Layer::retrieve('index')->link(null, null, 'xml'),
					'title' => Lang::retrieve('rss.title'),
				),
				
				'styx' => Lang::get('running', implode(' ', Core::fetch('styx.name', 'styx.version'))),
			))->show();
			break;
		case 'json':
			Page::getInstance()->substitute('layer')->show();
			break;
		case 'xml':
			Page::getInstance()->assign(Core::fetch('app.name', 'app.link', 'app.mail'))->apply('rss.php')->show();
			break;
	}