<?php

require_once('./Initialize.php');

class ModelTest extends UnitTestCase {
	
	public function testNewsModel(){
		$news = new NewsModel();
		$myNews = $news->findByIdentifier('Test');
		
		$this->assertTrue($myNews instanceof NewsObject);
		$this->assertEqual($myNews['id'], 2);
		$this->assertFalse($myNews->isNew());
	}
	
	public function testCreateModel(){
		$myNews = Model::create('News')->find(array(
			'where' => array('id' => 1),
		));
		
		$this->assertEqual($myNews['id'], 1);
	}
	
}