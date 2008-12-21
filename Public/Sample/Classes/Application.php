<?php

class Application {
	
	public function onInitialize(){
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
	
	public function onPageShow(){
		Page::getInstance()->assign(array(
			'scripts' => Script::get(),
		));
	}
	
}