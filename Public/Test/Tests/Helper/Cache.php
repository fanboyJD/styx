<?php

chdir('..');

include('./Initialize.php');

$c = Cache::getInstance();
if(!empty($_GET['check']))
	echo $c->retrieve('CacheTest/Helper')==false;
else
	$c->store('CacheTest/Helper', true, 1); // We store that value for one second