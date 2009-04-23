<?php
chdir('..');

$Helper = true;
include('./Initialize.php');

$method = Request::retrieve('method');
echo json_encode(array(
	'method' => $method,
	'data' => Request::retrieve($method),
	'get' => Request::retrieve('get'),
));