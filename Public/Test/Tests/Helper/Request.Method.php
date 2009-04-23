<?php
chdir('..');

$Helper = true;
include('./Initialize.php');

echo Request::retrieve('method');