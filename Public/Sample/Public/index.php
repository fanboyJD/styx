<?php
	$use = 'debug';
	/*$Paths = array(
		'app.path' => realpath('../'),
		'app.public' => realpath('./'),
	);*/
	include('../../../Source/Styx.php');
	
	$user = User::retrieve();
	if($user) Script::set('User = '.json_encode(array(
			'session' => $user['session'],
		)).';');
	
	Layer::create('Page')->fireEvent('menu')->register('pagemenu');
	
	switch(Response::getContentType()){
		case 'html':
			Page::getInstance()->apply('html.php')->assign(Core::fetch('app.name', 'app.link'))->assign(array(
				'source' => 'http://framework.og5.net/dev/browser/trunk/Public/Sample',
				
				'rss' => array(
					'link' => Layer::retrieve('index')->link(null, null, 'xml'),
					'title' => Lang::retrieve('rss.title'),
				),
				
				'user' => ($user ? Lang::get('user.hello', $user['name']).' | <a href="admin">'.Lang::retrieve('user.admin').'</a> | ' : '').'
					<a href="'.Layer::retrieve('login')->link(null, $user ? 'logout' : null).'">'.Lang::retrieve('user.'.($user ? 'logout' : 'login')).'</a>',
				
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