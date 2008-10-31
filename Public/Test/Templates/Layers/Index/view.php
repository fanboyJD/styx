<?php
	foreach($this->data as $n){
		echo '<div class="inner">';
		
		if($this->hasRight('edit', 'modify'))
			echo '<a class="hicon" href="'.$this->link($n['pagetitle'], 'edit').'"><img src="Images/pencil.png" alt="${lang.edit}"></a>';
		
		if($this->hasRight('delete'))
			echo '<a class="hicon delete" href="'.$this->link($n['pagetitle'], 'delete', 'json').'"><img src="Images/cross.png" alt="${lang.delete}" title="${lang.confirmdelete}"></a>';
		
		echo '<h1>'.$n['title'].'</h1>
				<div>'.$n['content'].'</div>
				<div style="float: right;" class="topp5">
					<small class="b"><i>'.Lang::get('news.posted', $this->usernames[$n['uid']], date('d.m.Y - H:i', $n['time'])).'</i></small>
				</div>
				<div class="clear"></div>
			</div>';
	}
?>