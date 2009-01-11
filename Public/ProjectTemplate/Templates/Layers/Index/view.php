<?php
	$paginate = $this->paginate()->parse();
	
	echo $paginate;
	
	$count = count($this->Data);
	foreach($this->Data as $n)
		echo '<h1>'.$n['title'].' <span>
				'.Lang::get('news.posted', $n['name'], date('d.m.Y - H:i', $n['time'])).'
				'.(User::hasRight('layer.index.edit.modify') ? '<a href="'.$this->link($n, 'edit').'">${lang.edit}</a>' : '').'
				'.(User::hasRight('layer.index.delete') ? '<a href="'.$this->link($n, 'delete').'">${lang.delete}</a>' : '').'
			</span></h1>
			'.$n['content'].
			(empty($this->get[$this->event]) ? '<br/><br/><a href="'.$this->link($n).'">'.Lang::get('news.read', $n['title']).'</a><br/>' : '')
			.'<br/>';
	
	echo $paginate;
?>