<?php
	foreach($this->data as $n){
		echo '<b>'.$n['title'].'</b><br/>
			'.$n['content'].'<br/>
			<small><i>Posted by uid '.$n['uid'].' on '.date('r', $n['time']).'</i></small><hr/>';
	}
?>