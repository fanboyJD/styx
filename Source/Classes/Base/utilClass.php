<?php
class Util {
	private static $regex = null,
		$once = false;
	
	public static function endsWith($string, $look){
		return strrpos($string, $look)===strlen($string)-strlen($look);
	}
	public static function startsWith($string, $look){
		return strpos($string, $look)===0;
	}
	public static function cleanWhitespaces($array, $whitespaces = false){
		if(is_array($array)){
			foreach($array as $key => $val){
				$array[$key] = self::cleanWhitespaces($val, $whitespaces);
				
				if(!$array[$key] && $array[$key]!==0) unset($array[$key]);
			}
		}else{
			$array = trim($array);
			if($whitespaces)
				$array = str_replace(array("\t", "\n", "\r"), array("", " ", ""), $array);
			
		}
		
		return $array;
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
		.chunk_split(base64_encode(db::striptags($msg)))
		."--".$boundary."\nContent-Type: text/html; charset=UTF-8\nContent-Transfer-Encoding: base64\n\n"
		.chunk_split(base64_encode(str_replace("\n", '<br/>', $msg)));
		
		$return = mail($options['to'], $options['title'].' - '.config::$_SITENAME, '', $headers);
		ini_set('error_reporting', $error_reporting);
		return $return;
	}
	public static function pagination($start, $count, $per, $link, $options = array(
		'limit' => false,
	)){
		$start = db::numeric($start, $per);
		if($count<=$per && !$start)
			return '';
		$out = '';
		$link = str_replace(array('&', '"'), array('&amp;', '&quot;'), str_replace('&amp;', '&', $link));
		if($count-$per>$start)
			$out .= '<div class="fright">
				<a href="'.str_replace('{start}', $start+$per, $link).'" class="go'.(config::$_BROWSER['platform']!='ie' ? '2' : ' bold fix').'">'.lang::$global['next'].'</a>
				'.($options['limit'] ? '
					<a href="'.str_replace('{start}', db::numeric($count-1, $per), $link).'" class="go'.(config::$_BROWSER['platform']!='ie' ? '5' : '4 bold fix').'">'.lang::$global['last'].'</a>
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
	public static function getTitle($title, $options = array(
		'editId' => null,
		'contents' => null,
	)){
		if(!self::$regex)
			self::$regex = array(
				explode(' ', 'Æ æ Œ œ ß Ü ü Ö ö Ä ä À Á Â Ã Ä Å &#260; &#258; Ç &#262; &#268; &#270; &#272; Ð È É Ê Ë &#280; &#282; &#286; Ì Í Î Ï &#304; &#321; &#317; &#313; Ñ &#323; &#327; Ò Ó Ô Õ Ö Ø &#336; &#340; &#344; Š &#346; &#350; &#356; &#354; Ù Ú Û Ü &#366; &#368; Ý Ž &#377; &#379; à á â ã ä å &#261; &#259; ç &#263; &#269; &#271; &#273; è é ê ë &#281; &#283; &#287; ì í î ï &#305; &#322; &#318; &#314; ñ &#324; &#328; ð ò ó ô õ ö ø &#337; &#341; &#345; &#347; š &#351; &#357; &#355; ù ú û ü &#367; &#369; ý ÿ ž &#378; &#380;'),
				explode(' ', 'Ae ae Oe oe ss Ue ue Oe oe Ae ae A A A A A A A A C C C D D D E E E E E E G I I I I I L L L N N N O O O O O O O R R S S S T T U U U U U U Y Z Z Z a a a a a a a a c c c d d e e e e e e g i i i i i l l l n n n o o o o o o o o r r s s s t t u u u u u u y y z z z'),
			);
		
		$title = trim(substr(preg_replace('/\_{2,}/i', '_', preg_replace('/[^A-Za-z0-9_]/i', '_', str_replace(self::$regex[0], self::$regex[1], $title))), 0, 64));
		if(db::numeric($title))
			$title = '_'.$title;
		
		if($options['contents'])
			$title = self::checkTitle($title, 0, $options);
		
		return $title;
	}
	public static function checkTitle($title, $i, $options = array(
		'contents' => null,
		'editId' => null,
	)){
		if(!is_array($options['contents']))
			return $title;
		foreach($options['contents'] as $content)
			if((!$options['editId'] || $options['editId']!=$content['id']) && strtolower($content['pagetitle'])==strtolower($title.(db::numeric($i) ? (!self::endsWith($title, '_') ? '_' : '').$i : '')))
				return self::checkTitle($title, ++$i, $options);
		return $title.(db::numeric($i) ? (!self::endsWith($title, '_') ? '_' : '').$i : '');
	}
}
?>