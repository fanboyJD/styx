<div id="menuleft"></div>
<ul id="menu">
<?php
foreach($this->Data as $data)
	echo '<li><a href="'.$this->link($data['pagetitle']).'">'.$data['title'].'</a></li>';
?>
</ul>