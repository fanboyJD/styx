<?php
	Handler::map()->assign(array(
		'layer' => '<div class="inner">
				<h1>'.Lang::retrieve('user.admin').'</h1>
				<a class="go icon" href="'.Layer::retrieve('index')->link(null, 'edit').'">'.Lang::retrieve('news.add').'</a>
			</div>',
	));