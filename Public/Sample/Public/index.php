<?php
	$use = 'debug';
	if($use=='production'){
		// Don't forget to adjust the Database-Settings :)
		$Paths = array(
			'app.path' => '/var/wusch/Sample/',
			'app.public' => realpath('./'),
		);
		
		include('/var/wusch/Styx/Styx.php');
	}else{
		include('../../../Source/Styx.php');
	}
	
	switch(Response::getContentType()){
		case 'html':
			//Layer::create('Page')->fireEvent('menu')->register('pagemenu');
			
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
				'rss' => array(
					'link' => Layer::retrieve('index')->link(null, null, 'xml'),
					'title' => Lang::retrieve('rss.title'),
				),
				
				'scripts' => Script::get(),
				
				'framework.description' => Layer::retrieve('index')->isIndex ? '<div id="content">'.Lang::retrieve('framework.description').'</div>' : '',
				
				'languages' => implode($languages),
				
				/*'user' => ($user ? Lang::get('user.hello', $user['name']).' | <a href="admin">'.Lang::retrieve('user.admin').'</a> | ' : '').'
					<a href="'.Layer::retrieve('login')->link(null, $action).'">'.Lang::retrieve('user.'.$action).'</a>',*/
				
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