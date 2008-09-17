<?php
	include('../../../Source/Styx.php');
	
	function initialize(){
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
		
		Route::connect(array(
			array('logout', 'equalsAll')
		), array('login', 'logout'));
		
		Route::connect(array(
			array('admin', 'equalsAll')
		), 'admin.php');
	}
	
	$user = User::retrieve();
	if($user) Script::set('User = '.json_encode(array(
			'session' => $user['session'],
		)).';');
	
	if(Handler::behaviour('html'))
		Handler::map()->template('html.php')->assign(array(
			'app.name' => Core::retrieve('app.name'),
			'app.link' => Core::retrieve('app.link'),
			'scripts' => Script::get(),
			
			'source' => 'http://framework.og5.net/dev/browser/trunk/Public/Sample',
			
			'menu' => Layer::run('page', 'menu')->parse(),
			
			'rss' => array(
				'link' => Layer::retrieve('index')->link(null, null, 'xml'),
				'title' => Lang::retrieve('rss.title'),
			),
			
			'user' => ($user ? Lang::get('user.hello', $user['name']).' | <a href="admin">'.Lang::retrieve('user.admin').'</a> | ' : '').'
				<a href="'.Layer::retrieve('login')->link(null, $user ? 'logout' : null).'">'.Lang::retrieve('user.'.($user ? 'logout' : 'login')).'</a>'
		))->parse();
	elseif(Handler::behaviour('json'))
		Handler::map()->substitute('layer')->parse();
	elseif(Handler::behaviour('xml'))
		Handler::map()->assign(array(
			'app.name' => Core::retrieve('app.name'),
			'app.link' => Core::retrieve('app.link'),
			'app.mail' => Core::retrieve('app.mail'),
		))->template('rss.php')->parse();
	else
		Handler::map()->disable();