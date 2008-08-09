<?php
	Handler::map()->assign(array(
		'layer' => '
			<a class="go icon" href="'.Layer::retrieve('index')->link(null, 'edit').'">'.Lang::retrieve('news.add').'</a>
		',
	));