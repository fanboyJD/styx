<?php

require_once('./Initialize.php');

class Suite extends TestSuite {
	
    public function __construct() {
        $this->TestSuite('Suite');
        
        foreach(glob('./*.php') as $file)
        	if(!in_array(basename($file), array('Initialize.php', 'Suite.php', 'StyxReporter.php')))
        		$this->addFile($file);
    }
}

$Suite = new Suite();
$Suite->run(new StyxReporter(true));
