<?php

class Application {
	
	public function onInitialize(){
		User::handle(); // Automatically sign-on the user if login data is provided
		
		Script::set('
			if(!window.console) window.console = console = {log: $empty};
		', true);
		
		PackageManager::add('style.css', array(
			'type' => 'css',
			'files' => array('style', 'forms'),
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