<h1>${lang.admin.user}</h1>
<?php
echo '<a class="go icon" href="'.$this->link(null, 'edit').'">'.Lang::retrieve('user.add').'</a><br/><br/>';

$edit = Lang::retrieve('edit');
$delete = Lang::retrieve('delete');
$confirmdelete = Lang::retrieve('user.confirmdelete');
foreach($this->Data as $user)
	echo '<div class="anchor">
			<span class="icon go">'.$user['name'].'</span>
			<small>
				[<a class="modifier" href="'.$this->link($user, 'edit').'"><img src="Images/pencil.png" alt="" />'.$edit.'</a>]
				[<a class="modifier delete" href="'.$this->link($user, 'delete', 'json').'" rel="'.$this->generateSessionName().'"><img src="Images/cross.png" alt="" title="'.$confirmdelete.'" />'.$delete.'</a>]
			</small>
		</div>';
?>