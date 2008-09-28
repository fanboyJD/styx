<?php
/*
 * Styx::QueryCache - MIT-style License
 * Author: christoph.pojer@gmail.com
 *
 * Usage: Extends the QuerySelect Class to automatically Cache the fetched data
 *
 */

class QueryCache extends QuerySelect {
	
	private function getCache($type = null){
		return Cache::getInstance()->retrieve('QueryCache', $this->table.'_'.md5($this->format().$type));
	}
	
	private function setCache($content, $type = null){
		return Cache::getInstance()->store('QueryCache', $this->table.'_'.md5($this->format().$type), $content);
	}
	
	public function fetch($type = null){
		$this->Storage->retrieve('limit', array(0, 1)); // To overcome big queries
		
		$cache = $this->getCache($type);
		
		return $cache ? $cache : $this->setCache(parent::fetch($type), $type);
	}
	
	public function retrieve(){
		$this->queried = false;
		
		$cache = $this->getCache();
		
		return $cache ? $this->cache = $cache : $this->setCache(parent::retrieve());
	}
	
}