<?php

class StyxReporter extends HtmlReporter {
	
	protected $isSuite = false;
	
	public function __construct($isSuite = false){
		$this->isSuite = !!$isSuite;
	}
	
	public function paintHeader($test){
		$Config = Core::fetch('styx.name', 'styx.link', 'app.link');
		
		$this->sendNoCacheHeaders();
		echo $this->trim('
			<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
			"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
			<html xmlns="http://www.w3.org/1999/xhtml">
			<head>
			<title>'.$Config['styx.name'].'</title>
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
			<link rel="shortcut icon" href="'.$Config['app.link'].'favicon.png" />
			<style type="text/css">
				'.$this->getCss().'
			</style>
			</head>
			<body>
			<div>
			<a href="'.$Config['styx.link'].'"><img src="'.$Config['app.link'].'Images/Styxmini.png" alt="'.$Config['styx.name'].'" style="float: right;" /></a>
			<h1>'.$Config['styx.name'].' - '.($this->isSuite ? 'Test-Suite' : 'Unit-Test - '.pathinfo($test, PATHINFO_FILENAME)).'</h1>
		');
        flush();
	}
	
	public function paintFooter($test){
		echo $this->trim('
			<div class="'.($this->getFailCount() ? 'error' : ($this->getExceptionCount() ? 'notice' : 'success')).'">
				'.$this->getTestCaseProgress() . "/" . $this->getTestCaseCount().' test cases complete:
				<strong>'.pick($this->getPassCount(), 0).'</strong> passes, 
				<strong>'.pick($this->getFailCount(), 0).'</strong> fails and 
				<strong>'.pick($this->getExceptionCount(), 0).'</strong> exceptions.
			</div>
			</div>
			</body>
			</html>
		');
	}
	
	public function getCss(){
		/* Borrowed from Blueprint CSS Framework */
		return $this->trim('
			body {
				font-size: 12px;
				color: #222;
				background: #fff;
				font-family: "Helvetica Neue", Arial, Helvetica, sans-serif;
			}
			
			a img { border: 0; }
			
			h1 { font-weight: normal; color: #111; }
			h1 { font-size: 2em; line-height: 1; margin-bottom: 0.5em; }
			
			.error,
			.notice,
			.success { margin: 1em 0; padding: .8em; border: 2px solid #ddd; }
			 
			.fail { color: #8a1f11; font-weight: bold; line-height: 20px; padding-left: 1.2em; }
			
			.error { background: #FBE3E4; color: #8a1f11; border-color: #FBC2C4; }
			.notice { background: #FFF6BF; color: #514721; border-color: #FFD324; }
			.success { background: #E6EFC2; color: #264409; border-color: #C6D880; }
			.error a { color: #8a1f11; }
			.notice a { color: #514721; }
			.success a { color: #264409; }
		');
	}
	
	public function trim($c){
		return str_replace(array("\r", "\t"), '', $c);
	}
	
}