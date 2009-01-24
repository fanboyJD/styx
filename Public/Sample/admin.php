<?php
	if(!User::retrieve())
		return;
	
	$get = Request::retrieve('get');
	
	if(!empty($get['do']) && in_array($get['do'], array('cache', 'allcache'))){
		Cache::getInstance()->eraseAll($get['do']=='allcache');
		$msg = $get['do'].'erased';
	}
	
	Page::getInstance()->assign(array(
		'layer' => '<h1>'.Lang::retrieve('user.admin').'</h1>
			'.(!empty($msg) ? '<span class="go icon">'.Lang::retrieve('admin.'.$msg).'</span><br/><br/>' : '').'
			<a class="go icon" href="'.Layer::retrieve('index')->link(null, 'edit').'">'.Lang::retrieve('news.add').'</a><br/>
			<a class="go icon" href="admin/user">'.Lang::retrieve('admin.user').'</a><br/>
			<a class="go icon" href="'.Response::link(array('admin' => null, 'do' => 'cache')).'">'.Lang::retrieve('admin.cache').'</a><br/>
			<a class="go icon" href="'.Response::link(array('admin' => null, 'do' => 'allcache')).'">'.Lang::retrieve('admin.allcache').'</a>
			',
	));