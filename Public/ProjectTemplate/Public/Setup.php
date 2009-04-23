<?php

class Setup {
	
	private static $messages = array(
		'guide' => '<span style="font-family: Calibri, Tahoma, sans-serif; font-size: 13px;">Need Help? <a href="http://styx.og5.net/docs/GettingStarted">GettingStarted</a></span><br/>',
		
		'nostyx' => '
			The Framework was not found. Please adjust your include statement in the index.php-File.
			<br/><br/>
			In order to load the Framework you have to include the File "Styx.php" from the
			Framework\'s Source-Folder.
		',
		
		'nodb' => 'There is no Database connection to either the system itself or the database "%s". You might
			just see some errors above this message that can help you figure out how to solve the problem.
			<br/><br/>
			<b>Your database configuration:</b><br/>
			%s
			<br/><br/>
			Please adjust your database settings in the Config/Configuration.php-File of your Project-Root.
		',
		
		'notable' => 'A database connection has been established, however there doesn\'t seem to be a users-Table.<br/><br/>
			<b>You may just forgot to import the database-skeleton.</b> Import the file application.sql.bz2 to your
			database.
		',
	
		'htaccess' => 'If you can read this you most probably forgot to rename the file my.htaccess to .htaccess or mod_rewrite in your
			Apache-Installation is disabled. Another source of problem can be that you did not set the "app.link"-Setting as an absolute
			path to the Public-Folder of your project (The folder where your index.php is in).
			<br/><br/>
			You will see a nicely designed template if you fix this and then you are ready to go ;)
			<br/><br/>
			Note: If you are using Linux and you are sure you have already configured your Apache to use mod_rewrite and put the .htaccess-File
			to the right directory but it still does not work you may forgot to set the chmod 777 to the "Cache" Folder inside the Framework\'s Source-Folder
		',
	
		'rights' => 'There was an error when rewriting the files. If you are operating on Linux you may
			not have the necessary rights (owner/mode). Make sure your files have the same owner
			as Apache (usually www-data) and the chmod rights 777.
			<br/><br/>
			Alternatively you can just remove the lines in the index.php-File and in the Templates/Page/html.php-File
			that start with an /**/. After you reload the page this message should be gone.
		',
		
		'secure' => 'There is one more step included before you can start
			creating your application: Please change the "secure"-String in the Configuration of your Application.
		',
		
		'password' => 'After changing the "secure"-String in your Configuration you have to reset your password. Note that you should
			not change the "secure"-String at any time as it would invalidate all passwords. Please never expose this string and keep it private.
			After a successful change of the password please try to login with the used password and the username "admin". You can change the username
			at any time right in the database. For a little UserManagementLayer see the Sample Application.
		',
		
		'mbstring' => 'Notice: The mbstring PHP-Extension was not found or was manually disabled. It is recommended to enable it if you are creating
			a multilingual website. Have a look at <a href="http://php.net/mbstring">php.net/mbstring</a>. Styx will fall back to use the internal
			php functions like strlen instead of mb_strlen.
		',
		
		'iconv' => 'Notice: The iconv PHP-Extension was not found or was manually disabled. It is recommended to enable it. Iconv is used in Styx
			to filter out bad UTF-8 sequences so your application will only contain valid characters.
			More information: <a href="http://php.net/iconv">php.net/iconv</a>.
		',
		
		'image' => 'Notice: The GD-Library was not found or misconfigured. If you want to use the Image-Class provided by Styx you should
			enable it. How-To: <a href="http://php.net/gd">http://php.net/gd</a>
		',
		
		'finished' => 'If you can read this, it seems like you have installed this ProjectTemplate just fine!
			<br/><br/>
			Make sure to remove the Setup.php, the PasswordLayer and the corresponding output in the project before pushing the application to the public.
			<br/><a href="%s">Automatically clean the Project-Files from Setup-related functions</a>
			<br/>
			If possible please link to <a href="%s">styx.og5.net</a> and add the Mini-Logo to your Application: <img src="%s/Images/Styxmini.png" alt="" />
			<br/>
			Thanks for giving Styx the opportunity to become your Framework of choice <img src="%s/Images/Smile.gif" alt="" />
		',
	);
	
	public static function getError($msg, $display = false){
		return '<div'.(!$display ? ' class="nodisplay"' : '').' style="font-family: Calibri, Tahoma, sans-serif; font-size: 12px; background: #FBE3E4; color: #8a1f11; margin: 1em 0; padding: .8em; border: 2px solid #FBC2C4;">
			'.self::getMessage($msg).'
		</div>';
	}
	
	public static function getMessage($msg){
		return self::$messages[$msg];
	}
	
	public static function handleSetup(){
		if(Layer::retrieve('Password')){
			$user = Database::select('users')->fetch(); // Select first user
			if(Core::retrieve('secure')=='-insecure-'){
				return '<div class="notice">'.Setup::getMessage('secure').'</div>';
			}elseif($user['pwd']=='8ce4625f597baf20d99917a52076d28070f6bb91'){ // Check if there is still the default password
				return '<div class="notice">'.Setup::getMessage('password').'</div>';
			}
		}
		
		$data = array();
		
		if(!Core::retrieve('feature.mbstring'))
			$data[] = '<div class="notice">'.Setup::getMessage('mbstring').'</div>';
		if(!Core::retrieve('feature.iconv'))
			$data[] = '<div class="notice">'.Setup::getMessage('iconv').'</div>';
		if(!function_exists('imagepng'))
			$data[] = '<div class="notice">'.Setup::getMessage('image').'</div>';
		
		$styx = Core::retrieve('styx.link');
		$data[] = '<div class="success">'.sprintf(Setup::getMessage('finished'), Response::link(array('cleanup' => null)), $styx, $styx, $styx).'</div>';
		
		return implode($data);
	}
	
	static function format($data){
		return str_replace(array("\t", "    "), '&nbsp;&nbsp;', nl2br(trim(print_r($data, true))));
	}
	
}

$html = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>Project Setup</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<link rel="shortcut icon" href="favicon.png" />
	</head>
	<body>
	${body}
	</body>
	</html>';

/* Has the Framework been loaded? */
if(!class_exists('Core', false)){
	echo str_replace('${body}', Setup::getError('nostyx'), $html);
	die;
}

/* We check if we have a Database connection */
$db = Database::getInstance();
$db->connect();
$db->selectDatabase();
if(!$db->isConnected()){
	$dbConfig = Core::retrieve('database');
	$dbConfig['password'] = str_repeat('*', String::length($dbConfig['password']));
	echo str_replace('${body}', Setup::getMessage('guide').sprintf(Setup::getError('nodb'), $dbConfig['db'], Setup::format($dbConfig)), $html);
	die;
}

/* Check if there is a users-Table */
$news = Database::select('users')->fetch();
if(empty($news['id'])){
	echo str_replace('${body}', Setup::getMessage('guide').Setup::getError('notable'), $html);
	die;
}

Core::store('setup', true);

// Remove the setup lines from the html.php-Template
$get = Request::retrieve('get');
if($get && array_key_exists('cleanup', $get)){
	Page::getInstance()->assign(array('layer' => 'Clean up successful, please delete Setup.php and PasswordLayer'));
	
	foreach(array(
		Core::retrieve('app.path').'/Templates/Page/html.php',
		Core::retrieve('app.public').'/index.php',
	) as $file){
		if(!file_exists($file))
			continue;
		
		$lines = file($file);
		foreach($lines as $k => $v){
			$lines[$k] = $v = trim($v, "\r\n");
			if(String::starts(ltrim($v), '/**/') || String::starts(ltrim($v), '<?php /**/'))
				unset($lines[$k]);
		}
		
		if(!chmod($file, 0777) || !file_put_contents($file, implode("\n", $lines))){
			Page::getInstance()->assign(array('layer' => Setup::getError('rights', true)));
			
			break;
		}
	}
}

unset($news, $html, $get, $db, $file, $lines, $dbConfig);