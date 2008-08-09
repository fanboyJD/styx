<ul id="menu">
<?php
foreach($this->data as $data)
	echo '<li id="m'.$data['id'].'"><a href="'.$this->link($data['pagetitle']).'">'.$data['title'].'</a></li>';
?>
</ul>