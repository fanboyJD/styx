<?php
/*
 * Styx::Rights - MIT-style License
 * Author: christoph.pojer@gmail.com
 *
 * Usage: Rights management for logged-in users
 *
 */

class Rights {
	
	private $rights = array();
	
	public function __construct($rights = null){
		if($rights) $this->setRights($rights);
	}
	
	public function setRights($rights){
		if($rights && !is_array($rights)) $rights = json_decode($rights, true);
		
		$this->rights = Hash::flatten(Hash::splat($rights));
	}
	
	private function checkRight($rights){
		if(!is_array($rights)) return false;
		
		foreach($rights as $right){
			$prefix[] = $right;
			$check = implode('.', $prefix);
			
			if(!empty($this->rights[$check]) && $this->rights[$check]==1)
				return true;
		}
		
		return false;
	}
	
	public function hasRight(){
		$args = Hash::args(func_get_args());
		
		return $this->checkRight($args);
	}
	
}