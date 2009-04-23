<?php

abstract class StyxUnitTest extends UnitTestCase {
	
	public function getTestURL(){
		return rtrim(Request::getUrl(), '/').'/';
	}
	
}