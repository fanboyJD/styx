<?php

require_once('./Initialize.php');

/*class UserModel extends Model {
	
	protected function initialize(){
		return array(
			
		);
	}
	
}*/

class ModelTest extends UnitTestCase {
	
	public function testUserModel(){
		error_reporting(E_ALL);
		$news = new NewsModel();
		$myNews = $news->findByIdentifier('Test');
		
		$this->assertTrue($myNews instanceof NewsObject);
		$this->assertEqual($myNews['id'], 2);
	}
	
	public function testCreateModel(){
		$myNews = Model::create('news')->find(array(
			'where' => array('id' => 1),
		));
		
		$this->assertEqual($myNews['id'], 1);
	}
	
}