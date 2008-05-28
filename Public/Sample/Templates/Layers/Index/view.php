<?php
	foreach($this->data as $n){
		echo '<b>'.$n['title'].'</b><br/>
			'.$n['content'].'<br/>
			<small><i>'.Lang::get('news.posted', $n['uid'], date('r', $n['time'])).'</i></small><hr/>';
	}