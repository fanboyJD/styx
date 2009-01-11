<?php
	$paginate = $this->paginate()->parse();
	
	if($paginate) echo $paginate.'<div class="clear"></div>';
	
	foreach($this->Data as $n){
		echo '<div class="inner">';
		
		if(User::hasRight('layer.index.edit.modify'))
			echo '<a class="hicon" href="'.$this->link($n, 'edit').'"><img src="Images/pencil.png" alt="${lang.edit}" /></a>';
		
		if(User::hasRight('layer.index.delete'))
			echo '<a class="hicon delete" href="'.$this->link($n, 'delete', 'json').'" rel="'.$this->generateSessionName().'"><img src="Images/cross.png" alt="${lang.delete}" title="${lang.confirmdelete}" /></a>';
		
		echo '<h1>'.$n['title'].'</h1>
				<div>'.(!empty($n['picture']) ? '<img src="'.$n['picture'].'" class="articleimg" alt="" />' : '').$n['content'].'</div>
				<div style="float: right;" class="topp5">
					<small class="b"><i>'.Lang::get('news.posted', $n['name'], date('d.m.Y - H:i', $n['time'])).'</i></small>
				</div>
				<div class="clear"></div>
			</div>';
	}
	
	if($paginate) echo $paginate.'<div class="clear"></div>';
?>