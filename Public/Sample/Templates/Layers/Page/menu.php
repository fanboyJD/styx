<div id="menuleft"></div>
<ul id="menu">
<li><a href="<?php Response::link(); ?>">News</a></li>
<?php
foreach($this->Model as $data)
	echo '<li><a href="'.$this->link($data['pagetitle']).'">'.$data['title'].'</a></li>';
?>
</ul>