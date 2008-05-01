<?php
class Util {
	private static $once = false;
	
	private function __construct(){}
	private function __clone(){}
	
	public static function endsWith($string, $look){
		return strrpos($string, $look)===strlen($string)-strlen($look);
	}
	
	public static function startsWith($string, $look){
		return strpos($string, $look)===0;
	}
	
	public static function multiImplode($array, $k = null){
		$imploded = array();
		foreach($array as $key => $val){
			if(is_array($val)){
				$val = self::multiImplode($val, ($k ? $k.'.' : '').$key);
				$imploded = array_merge($imploded, $val[0]);
			}else
				$imploded[($k ? $k.'.' : '').$key] = $val;
		}
		return $k===null ? $imploded : array($imploded, $key);
	}
	
	public static function extend($src, $extended){
		if(!is_array($extended))
			return $src;
		
		foreach($extended as $key => $val)
			$src[$key] = is_array($val) ? self::extend($src[$key], $val) : $val;
		
		return $src;
	}
	
	/*public static function sendMail($options = array(
		'title' => '',
		'tpl' => '',
		'to' => '',
		'settingslink' => false,
		'vars' => array(),
	)){
		if(!self::$once){
			self::$once = true;
			mt_srand((double)microtime()*1000000);
		}
		$error_reporting = ini_set('error_reporting', 0);
		$msg = '<img src="'.config::$_PAGE.'img/elysion.png" alt="'.config::$_SITENAME.'" />'."\n\n"
		.template::getInstance($options['tpl'])->assign($options['vars'])->parse(1)."\n\n"
		.($options['settingslink'] ? str_replace('{page}', config::$_PAGE, lang::$mail['settings'])."\n\n" : '')
		.lang::$mail['legal']."\n".config::$_PAGE."page/legal";
		
		$boundary = uniqid('elysion'.mt_rand(1, 10000));
		$headers = "From: ".config::$_NOREPLY."\n"
		."MIME-Version: 1.0\n"
		."Content-Type: multipart/alternative; boundary = ".$boundary."\n\n"
		."This is a MIME encoded message.\n\n"
		."--".$boundary."\nContent-Type: text/plain; charset=UTF-8\nContent-Transfer-Encoding: base64\n\n"
		.chunk_split(base64_encode(strip_tags($msg)))
		."--".$boundary."\nContent-Type: text/html; charset=UTF-8\nContent-Transfer-Encoding: base64\n\n"
		.chunk_split(base64_encode(str_replace("\n", '<br/>', $msg)));
		
		$return = mail($options['to'], $options['title'].' - '.config::$_SITENAME, '', $headers);
		ini_set('error_reporting', $error_reporting);
		return $return;
	}
	
	public static function pagination($start, $count, $per, $link, $options = array(
		'limit' => false,
	)){
		$start = Data::id($start, $per);
		if($count<=$per && !$start)
			return '';
		$out = '';
		$link = str_replace(array('&', '"'), array('&amp;', '&quot;'), str_replace('&amp;', '&', $link));
		if($count-$per>$start)
			$out .= '<div class="fright">
				<a href="'.str_replace('{start}', $start+$per, $link).'" class="go'.(config::$_BROWSER['platform']!='ie' ? '2' : ' bold fix').'">'.lang::$global['next'].'</a>
				'.($options['limit'] ? '
					<a href="'.str_replace('{start}', Data::id($count-1, $per), $link).'" class="go'.(config::$_BROWSER['platform']!='ie' ? '5' : '4 bold fix').'">'.lang::$global['last'].'</a>
				' : '').'
			</div>';
		
		if(floor($start/$per)>=1)
			$out .= '<div class="fleft">
				'.($options['limit'] ? '
					<a href="'.str_replace('{start}', 0, $link).'" class="bold go6 fix">'.lang::$global['first'].'</a>
				' : '').'
				<a href="'.str_replace('{start}', $start-$per, $link).'" class="bold go1 fix">'.lang::$global['previous'].'</a>
			</div>';
		
		script::set('Page.Pagination('.floor(!($count%$per) ? $count/$per-1 : $count/$per).','.floor($start/$per).','.$per.','.json_encode($link).');');
		return $out.'<div class="paginate">
				<div class="fleft area slider"><div class="knob fix"></div></div>
				'.lang::$global['page'].' <span class="slide b">'.(floor($start/$per)+1).'</span> '.lang::$global['of'].' '.ceil($count/$per).'
			</div>';
	}*/
}
?>