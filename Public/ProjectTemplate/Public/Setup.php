<?php

class Setup {
	
	private static $errors = array(
		'nostyx' => '
			The Framework was not found. Please adjust your include statement in the index.php-File.
			<br/><br/>
			In order to load the Framework you have to include the File "Styx.php" from the
			Framework\'s Source-Folder.
			',
		'nodb' => 'There is no Database connection to either the systen itself or the database "%s". You might
			just see some errors above this message that can help you figure out how to solve the problem.
			<br/><br/>
			<b>Your database configuration:</b><br/>
			%s
			<br/><br/>
			Please adjust your database settings in the Config/Configuration.php-File of your Project-Root.
			',
		
		'notable' => 'A database connection has been established, however there doesn\'t seem to be a users-Table.<br/><br/>
			<b>You might just forgot to import the database-skeleton.</b> Import the file application.sql.bz2 to your
			database and delete the file afterwards.
			',
	
		'htaccess' => 'If you can read this you most probably forgot to rename the file my.htaccess to .htaccess or mod_rewrite in your Apache-Installation is disabled.
			<br/><br/>
			You will see a nicely designed template if you fix this and then you are ready to go ;)
			',
	);
	
	public static $notices = array(
		'secure' => 'If you can read this, it seems like you installed this ProjectTemplate just fine! There is one more step included before you can start
			creating your application: After changing the "secure"-String in your Configuration you should once reset your password. Note that you should
			not change the "secure"-String at any time as it would invalidate all passwords. Please never expose this string and keep it private.
			After a successful change of the password please try to login with the used password and the username "admin". You can change the username
			at any time right in the database. For a little UsermanagementLayer see the Sample Application.
			<br/><br/>
			Make sure to remove the PasswordLayer and the corresponding output in the html-Template before pushing the application to the public.
			<br/>
			If possible please link to <a href="%s">styx.og5.net</a> and add the Mini-Logo to your Application: <img src="%s/Images/Styxmini.png" alt="" />
			<br/>
			Thanks for giving Styx the opportunity to become your Framework of choice <img src="%s/Images/Smile.gif" alt="" />
		',
	);
	
	public static function getError($error){
		return '<div class="nodisplay" style="font-family: Calibri; font-size: 12px; background: #FBE3E4; color: #8a1f11; margin: 1em 0; padding: .8em; border: 2px solid #FBC2C4;">
			'.self::$errors[$error].'
		</div>';
	}
	
	public static function getNotice($notice){
		return self::$notices[$notice];
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
	echo str_replace('${body}', sprintf(Setup::getError('nodb'), $dbConfig['db'], Setup::format($dbConfig)), $html);
	die;
}

/* Check if there is a users-Table */
$news = Database::select('users')->fetch();
if(empty($news['id'])){
	echo str_replace('${body}', Setup::getError('notable'), $html);
	die;
}

Core::store('setup', true);