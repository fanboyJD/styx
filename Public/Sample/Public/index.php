<?php
	$use = 'debug';
	/*$Paths = array(
		'app.path' => realpath('../'),
		'app.public' => realpath('./'),
	);*/
	include('../../../Source/Styx.php');
	
	switch(Response::getContentType()){
		case 'html':
			Layer::create('Page')->fireEvent('menu')->register('pagemenu');
			
			$user = User::retrieve();
			if($user) Script::set('User = '.json_encode(array(
					'session' => $user['session'],
				)).';');
			
			$separator = Core::retrieve('path.separator');
			$request = Request::retrieve('request');
			
			$languages = array();
			foreach(Core::retrieve('languages') as $k => $lang)
				$languages[] = '<a href="'.Response::link($request, array(array('language', $k))).'"'.(Lang::getLanguage()==$k ? ' class="selected"' : '').'><img src="Images/'.$k.'.png" alt="" /></a>';
			
			$action = $user ? 'logout' : 'login';
			
			Page::getInstance()->apply('html')->assign(Core::fetch('app.name', 'app.link'))->assign(array(
				'source' => 'http://styx.og5.net/code/listing.php?repname=Styx+PHP+Framework&path=%2Ftrunk%2FPublic%2FSample%2F',
				
				'rss' => array(
					'link' => Layer::retrieve('index')->link(null, null, 'xml'),
					'title' => Lang::retrieve('rss.title'),
				),
				
				'framework.description' => Layer::retrieve('index')->isIndex ? '<div id="content">'.Lang::retrieve('framework.description').'</div>' : '',
				
				'languages' => implode($languages),
				
				'user' => ($user ? Lang::get('user.hello', $user['name']).' | <a href="admin">'.Lang::retrieve('user.admin').'</a> | ' : '').'
					<a href="'.Layer::retrieve('login')->link(null, $action).'">'.Lang::retrieve('user.'.$action).'</a>',
				
				'styx' => implode(' ', Core::fetch('styx.name', 'styx.version')),
			))->show();
			break;
		case 'json':
			Page::getInstance()->substitute('layer')->show();
			break;
		case 'xml':
			Page::getInstance()->assign(Core::fetch('app.name', 'app.link', 'app.mail'))->apply('rss.php')->show();
			break;
	}