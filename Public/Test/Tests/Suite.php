<?php

require_once('./Initialize.php');

class Suite extends TestSuite {
	
    public function __construct() {
        $this->TestSuite('Suite');
        
        foreach(glob('./*.php') as $file)
        	if(!in_array(basename($file), array('Initialize.php', 'Suite.php')))
        		$this->addFile($file);
    }
}

$Suite = new Suite();
$Suite->run(new HtmlReporter());
