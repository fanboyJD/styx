<?php
	$paginate = count($this->Model) ? '' : $this->paginate()->parse();
	
	if($paginate) echo $paginate.'<div class="clear"></div>';
	
	$edit = User::hasRight('layer.index.edit.modify');
	$delete = User::hasRight('layer.index.delete');
	if($delete) $session = Core::generateSessionName($this->Module->getName('object'));
	foreach($this->Model as $k => $n){
		echo '<div'.($k ? ' style="margin-top: 20px;"' : '').'>';
		
		if($edit) echo '<a class="hicon" href="'.$this->link($n, 'edit').'"><img src="Images/pencil.png" alt="${lang.edit}" /></a>';
		if($delete) echo '<a class="hicon delete" href="'.$this->link($n, 'delete', 'json').'" rel="'.$session.'"><img src="Images/cross.png" alt="${lang.delete}" title="${lang.confirmdelete}" /></a>';
		
		echo '<h1>'.$n['title'].'</h1>
				<div>'.($n['picture'] ? '<img src="'.$n['picture'].'" class="articleimg" alt="" />' : '').$n['content'].'</div>
				<div style="float: right; padding-top: 5px;">
					<small><i>'.Lang::get('news.posted', $n->getUsername(), date('d.m.Y - H:i', $n['time'])).'</i></small>
				</div>
				<div class="clear"></div>
			</div>';
	}
	
	if($paginate) echo $paginate;