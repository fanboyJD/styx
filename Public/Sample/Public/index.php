<?php
	$use = 'debug';
	/*$Paths = array(
		'app.path' => realpath('../'),
		'app.public' => realpath('./'),
	);*/
	include('../../../Source/Styx.php');
	
	function initialize(){
		User::handle(); // Automatically sign-on the user if login data is provided
		
		Script::set('
			if(!window.console) window.console = console = {log: $empty};
			
			var Config = '.json_encode(array(
				'separator' => Core::retrieve('path.separator'),
			)).';
		', true);
		
		PackageManager::add('package1.js', array(
			'type' => 'js',
			'files' => array('mootools', 'site'),
		));
		
		PackageManager::add('style.css', array(
			'type' => 'css',
			'files' => array('style', 'forms'),
		));
		
		/*
			The following js/css packages are only served for Internet Explorer version 6
			It is also possible to require 'login' => true so the package will only
			be sent to logged in users.
		*/
		PackageManager::add('ie.js', array(
			'type' => 'js',
			'files' => 'iepngfix_tilebg',
			'require' => array(
				'browser' => 'ie',
				'version' => 6
			),
		));
		PackageManager::add('ie.css', array(
			'type' => 'css',
			'files' => 'ie',
			'require' => array(
				'browser' => 'ie',
				'version' => 6
			),
		));
		
		Route::connect('logout', array(
			'layer' => 'login',
			'event' => 'logout',
		));
		
		Route::connect('admin', array(
			'include' => 'admin.php'
		));
	}
	
	$user = User::retrieve();
	if($user) Script::set('User = '.json_encode(array(
			'session' => $user['session'],
		)).';');
	
	if(Response::getContentType()=='html')
		Page::getInstance()->apply('html.php')->assign(Core::fetch('app.name', 'app.link'))->assign(array(
			'scripts' => Script::get(),
			
			'source' => 'http://framework.og5.net/dev/browser/trunk/Public/Sample',
			
			'menu' => Layer::create('Page')->fire('menu')->parse(),
			
			'rss' => array(
				'link' => Layer::retrieve('index')->link(null, null, 'xml'),
				'title' => Lang::retrieve('rss.title'),
			),
			
			'user' => ($user ? Lang::get('user.hello', $user['name']).' | <a href="admin">'.Lang::retrieve('user.admin').'</a> | ' : '').'
				<a href="'.Layer::retrieve('login')->link(null, $user ? 'logout' : null).'">'.Lang::retrieve('user.'.($user ? 'logout' : 'login')).'</a>',
			
			'styx' => implode(' ', Core::fetch('styx.name', 'styx.version')),
		))->show();
	elseif(Response::getContentType()=='json')
		Page::getInstance()->substitute('layer')->show();
	elseif(Response::getContentType()=='xml')
		Page::getInstance()->assign(Core::fetch('app.name', 'app.link', 'app.mail'))->apply('rss.php')->show();