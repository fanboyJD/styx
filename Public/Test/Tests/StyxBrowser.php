<?php

class StyxBrowser extends SimpleBrowser {
	
	public function put($url, $parameters = false) {
		if(!is_object($url)) $url = new SimpleUrl($url);
		if($this->getUrl()) $url = $url->makeAbsolute($this->getUrl());
		return $this->load($url, new SimplePutEncoding($parameters));
	}
	
	public function delete($url, $parameters = false) {
		if(!is_object($url)) $url = new SimpleUrl($url);
		if($this->getUrl()) $url = $url->makeAbsolute($this->getUrl());
		return $this->load($url, new SimpleDeleteEncoding($parameters));
	}
	
}

class SimplePutEncoding extends SimplePostEncoding {
	
	public function getMethod(){
		return 'PUT';
	}
	
}

class SimpleDeleteEncoding extends SimpleGetEncoding {
	
	public function getMethod(){
		return 'DELETE';
	}
	
}