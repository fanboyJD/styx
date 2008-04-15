<?php
class Validator {
	
	private static $Instance;
	
	private function __construct(){}
	private function __clone(){}
	
	public static function call($t, $data){
		if(!self::$Instance)
			self::$Instance = new Validator();
		
		if(method_exists(self::$Instance, $t[0]))
			return self::$Instance->{$t[0]}($data, $t[1]);
		
		return true;
	}
	
	public static function pagetitle($data){
		return util::getTitle($data)==$data;
	}
	
	public static function mail($data){
		if(!$data)
			return false;
		$strpos = strpos($data, '@');
		if(!$strpos || $strpos==strlen($data))
			return false;
		
		$strpos = strpos($data, '.');
		if(!$strpos || $strpos==strlen($data))
			return false;
		
		if(strpos($data, '"') || strpos($data, "'") || strpos($data, '\\') || strpos($data, '/'))
			return false;
		
		return true;
	}
	
	public static function pwd($data){
		if(settype($data, 'string') && strlen($data)>=4)
			return true;
		return 'pwdlength';
	}
	
	public static function username($data, $options = array(
		'login' => false
	)){
		if(!settype($data, 'string') || strlen($data)<3 || strlen($data)>16)
			return 'uname1';
		if($data!=self::pagetitle($data))
			return 'uname2';
		
		$usr = util::getUsr($data);
		if($usr['id']){
			if($options['login'])
				return true;
			else
				return 'uname3';
		}
		return ($options['login'] ? 'uname' : true);
	}
	
	public static function numeric($data){
		return db::numeric($data)>0;
	}
	
	public static function numericrange($data, $options){
		$data = db::numeric($data);
		if(($data || ($data==0 && is_numeric($data))) && $data>=$options[0] && $data<=$options[1])
			return true;
		return false;
	}
	
	public static function bool($data){
		return self::numericrange($data, array(0, 1));
	}
	
	public static function homepage($data){
		if(!util::startsWith($data, 'http://') && !util::startsWith($data, 'https://'))
			return false;
		
		return true;
	}
	
	public static function date($data){
		$data = db::numericArray(explode('.', $data));
		if(!$data[0] || !$data[1] || !$data[2])
			return false;
		$time = mktime(0, 0, 0, $data[1], $data[0], $data[2]);
		if($time>time())
			return false;
		return true;
	}
	
}

/* PARSER */
class Parser {
	
	private static $Instance;
	
	private function __construct(){}
	private function __clone(){}
	
	public static function call($t, $data){
		if(!self::$Instance)
			self::$Instance = new Parser();
		
		if(method_exists(self::$Instance, $t[0]))
			return self::$Instance->{$t[0]}($data, $t[1]);
		
		return $data;
	}
	
	public static function pagetitle($data){
		return util::getTitle($data);
	}
	
	public static function mail($data){
		return db::add($data);
	}
	
	public static function username($data){
		return self::pagetitle($data);
	}
	
	public static function bool($data){
		$ret = false;
		if($data=='true')
			$ret = true;
		if(db::numeric($data))
			$ret = true;
		else
			$ret = false;
		
		return $ret;
	}
	
	public static function numeric($data){
		return db::numeric($data);
	}
	
	public static function numericrange($data, $options){
		$data = db::numeric($data);
		return ($data>=$options[0] && $data<=$options[1] ? $data : 0);
	}
	
	public static function specialchars($data){
		//db::strip because we do db::add it in the abstractionlayer again :D
		return db::strip(db::addh($data));
	}
	
	public static function homepage($data){
		return self::specialchars($data);
	}
	
	public static function date($data){
		$data = db::numericArray(explode('.', $data));
        if(!$data[0] || !$data[1] || !$data[2])
        	return null;
        $time = mktime(0, 0, 0, $data[1], $data[0], $data[2]);
        
        //date is higher than 1905
        if($time<-2051222961)
        	return null;
        
        return $time;
	}
	
}

/* FORMATTER */
class Formatter {
	
	public static
		$days = array('Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'),
		$months = array('', 'Jänner', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember');
	
	private static $Instance;
	
	private function __construct(){}
	private function __clone(){}
	
	public static function call($t, $data, $options){
		if(!self::$Instance)
			self::$Instance = new Formatter();
		
		if(method_exists(self::$Instance, $t[0]))
			return self::$Instance->{$t[0]}($data, $options);
		
		return '';
	}
	
	public static function time($data, $options = array(
		'short' => false,
		'hours' => true,
		'nodays' => false,
	)){
		return (!$options['nodays'] ? self::$days[date('w', $data)].', ' : '').date('d.', $data).($options['short'] ? date('m', $data) : ' '.self::$months[date('n', $data)].date(' Y', $data)).($options['hours'] ? date(' - H<\s\u\p>i</\s\u\p>', $data) : '');
	}
	
	public static function user($data, $options = array(
		'system' => false,
	)){
		if($options['system'] && !db::numeric($data))
			return lang::$global['system'];
		else
			return self::username(util::getUsr($data));
	}
	
	public static function username($data){
		return $data['name'] ? '<a class="u" href="user/'.$data['name'].'" style="background-image: url(\'uimg/'.($data['img'] ? 's/'.$data['img'] : 'no.png').'\');">'.$data['name'].'</a>' : '';
	}
	
	public static function regex($data, $options = array(
		'noUbb' => false,
		'ubb' => null,
	)){
		if(!$data) return '';
		/* @var $c cache */
		$c = cache::getInstance();
		$regexp = $c->get('global', 'regex');
		if(!$regexp){
			/* @var $db db */
			$db = db::getInstance();
			$sql = $db->getSelect('regex');
			$query = $db->hquery($sql, 'regex');
			while($regex = $db->next('regex')){
				$regexp[0][] = $regex['regex'];
				$regexp[1][] = '<img src=\"img/smilies/'.$regex['rpl'].'.gif\" alt=\"\" title=\"'.$regex['regex'].'\" />';
			}
			$c->set('global', 'regex', $regexp, ONE_WEEK*4, true);
		}
		array_push($regexp[0], "\r\n", "\n", "\r");
		array_push($regexp[1], '<br/>', '<br/>', '<br/>');
		$data = str_replace($regexp[0], $regexp[1], $data);
		if(!$options['noUbb']){
			$ubb = new ubb($options['ubb']);
			$data = $ubb->parse($data);
		}
		return db::strip($data);
	}
	
	public static function seconds($data, $options = array()){
		$names = array(
			array('Sekunde', 'Sekunden'),
			array('Minute', 'Minuten'),
			array('Stunde', 'Stunden'),
			array('Tag', 'Tage')
		);
		
		$time = array();
		$time[0] = $data;
		$out = null;
		for($i=0;$i<=2;$i++){
			$mod = ($i==2 ? 24 : 60);
			if($time[$i]>=$mod){
				$time[$i+1] = floor($time[$i]/$mod);
				$time[$i] = $time[$i]%$mod;
			}
			if($time[$i]) $out[] = $time[$i].' '.$names[$i][($time[$i]==1 ? 0 : 1)];
		}
		krsort($out);
		return (is_array($out) ? implode(', ', $out) : null);
	}
	
}
?>