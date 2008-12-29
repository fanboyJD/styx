<?php

require_once('./Initialize.php');

class CacheTest extends UnitTestCase {
	
	public function testEngine(){
		$engine = function_exists('eaccelerator_put') ? 'eaccelerator' : false;
		
		$this->assertEqual(Cache::getInstance()->getEngine(), $engine);
	}
	
	public function testCache(){
		$c = Cache::getInstance();
		
		$c->store('Test/Cache', 'random data');
		
		$this->assertEqual($c->retrieve('Test/Cache'), 'random data');
		$c->erase('Test/Cache');
		
		$this->assertNull($c->retrieve('Test/Cache'));
		
		$c->store('Test/Cache', 'random data');
		$c->eraseBy('Test');
		
		$this->assertNull($c->retrieve('Test/Cache'));
		
		
		$c->store('Test/Cache', 'random data', array('tags' => array('random', 'tag')));
		$c->eraseByTag('random');
		
		$this->assertNull($c->retrieve('Test/Cache'));
		
		$c->store('Test/Cache', 'random data');
		$c->eraseAll();
		
		$this->assertNull($c->retrieve('Test/Cache'));
	}
	
	public function testTime(){
		// This sets the cache for 1 second
		$b = new SimpleBrowser();
		$b->get('http://svn/Framework/trunk/Public/Test/Tests/Helper/Cache.php');
		
		sleep(2);
		
		// If the cache is empty it should output "1"
		$b = new SimpleBrowser();
		$b->get('http://svn/Framework/trunk/Public/Test/Tests/Helper/Cache.php?check=true');
		
		$this->assertEqual($b->getContent(), 1);
	}
	
}