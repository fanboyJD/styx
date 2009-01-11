<?php
	if(!User::retrieve())
		return;
	
	$get = Request::retrieve('get');
	
	if(!empty($get['do']) && in_array($get['do'], array('cache', 'allcache'))){
		Cache::getInstance()->eraseAll($get['do']=='allcache');
		$msg = $get['do'].'erased';
	}
	
	Page::getInstance()->assign(array(
		'layer' => '<h1>'.Lang::retrieve('admin.admin').'</h1>
			'.(!empty($msg) ? Lang::retrieve('admin.'.$msg).'<br/><br/>' : '').'
			<a href="'.Response::link(array('admin' => null, 'do' => 'cache')).'">'.Lang::retrieve('admin.cache').'</a><br/>
			<a href="'.Response::link(array('admin' => null, 'do' => 'allcache')).'">'.Lang::retrieve('admin.allcache').'</a>
			',
	));