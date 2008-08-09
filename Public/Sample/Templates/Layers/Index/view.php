<h1>${lang.menu.news}</h1>
<div class="inner">
<?php
	foreach($this->data as $n){
		echo '<div>';
		
		if($this->hasRight('edit', 'modify'))
			echo '<a class="hicon" href="'.$this->link($n['pagetitle'], 'edit').'"><img src="Images/pencil.png" alt="${lang.edit}"></a>';
		
		if($this->hasRight('delete'))
			echo '<a class="hicon delete" href="'.$this->link($n['pagetitle'], 'delete', 'json').'"><img src="Images/cross.png" alt="${lang.delete}" title="${lang.confirmdelete}"></a>';
		
		echo '<h2>'.$n['title'].'</h2>
				<div>'.$n['content'].'</div>
				<div style="float: right;" class="topp5">
					<small><i>'.Lang::get('news.posted', $this->usernames[$n['uid']], date('r', $n['time'])).'</i></small>
				</div>';
		
		if(!$length) $length = $this->data->length();
		
		if($length>++$i) echo '<hr/>';
		
		echo '</div>';
	}
?>
</div>