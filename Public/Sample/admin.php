<?php
	if(!User::retrieve())
		return;
	
	$get = Request::getInstance()->retrieve('get');
	if($get['p']['do']=='cache'){
		Cache::getInstance()->eraseAll(true);
		$msg = 'erased';
	}
	
	Handler::map()->assign(array(
		'layer' => '<div class="inner">
				<h1>'.Lang::retrieve('user.admin').'</h1>
				'.($msg ? '<span class="go icon">'.Lang::retrieve('admin.'.$msg).'</span><br/><br/>' : '').'
				<a class="go icon" href="'.Layer::retrieve('index')->link(null, 'edit').'">'.Lang::retrieve('news.add').'</a><br/>
				<a class="go icon" href="'.Core::retrieve('app.link').'admin/do'.Core::retrieve('path.separator').'cache">'.Lang::retrieve('admin.cache').'</a>
			</div>',
	));