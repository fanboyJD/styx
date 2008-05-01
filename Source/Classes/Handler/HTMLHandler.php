<?php
class HTMLHandler {
	
	private $template,
		$assigned = array();
	
	public function __construct(){
		Handler::setHeader('Content-Type', 'text/html; charset=utf8');
	}
	
	public function setTemplate($template){
		$this->template = Template::map(get_class(), $template);
	}
	
	public function getTemplate(){
		return $this->template;
	}
	
}
?>