<?php

require_once('./Initialize.php');

class LangTest extends UnitTestCase {
	
	public $folder;
	public $original;
	public $file;
	
	public function setUp(){
		$this->original = Core::retrieve('app.path');
		// We hack the app.path folder as Fileparser does not allow files from outside
		// The app folder (which is the Sample-App-Folder in case of the Unit-Tests)
		$this->folder = Core::store('app.path', realpath(Core::retrieve('app.public').'/Temp').'/');
		
		$this->file = 'Language/es.lang';
		
		mkdir($this->folder.'/Language');
		touch($this->folder.'/'.$this->file);
	}
	
	public function tearDown(){
		unlink($this->folder.'/'.$this->file);
		rmdir($this->folder.'/Language');
		
		Core::store('app.path', $this->original);
	}
	
	public function testLanguage(){
		
		Lang::setLanguage('es');
		
		$this->assertEqual(Lang::getLanguage(), 'es');
		
	}
	
	public function testRetrieve(){
		file_put_contents($this->folder.'/'.$this->file, "
			hello = '¡Hola!';
		");
		
		Lang::setLanguage('es');
		
		$this->assertEqual(Lang::retrieve('hello'), '¡Hola!');
	}
	
	public function testGet(){
		file_put_contents($this->folder.'/'.$this->file, "
			hello = '¡Hola %s!';
		");
		
		Lang::setLanguage('es');
		
		$this->assertEqual(Lang::get('hello', 'Tester'), '¡Hola Tester!');
	}
	
}