<?php

chdir('..');

$Helper = true;
include('./Initialize.php');

$c = Cache::getInstance();
if(!empty($_GET['check']))
	echo pick($c->retrieve('CacheTest/Helper'), 0);
else
	$c->store('CacheTest/Helper', 1, 2); // We store that value for two seconds