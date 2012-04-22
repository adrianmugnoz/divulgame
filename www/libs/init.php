<?php
// The source code packaged with this file is Free Software, Copyright (C) 2005-2010 by
// Ricardo Galli <gallir at uib dot es>.
// It's licensed under the AFFERO GENERAL PUBLIC LICENSE unless stated otherwise.
// You can get copies of the licenses here:
// 		http://www.affero.org/oagpl.html
// AFFERO GENERAL PUBLIC LICENSE is also included in the file called "COPYING".

include mnminclude.'utils.php';

// Basic initialization

mb_internal_encoding('UTF-8');
global $globals;

if (!empty($globals['static_server'])) {
	$globals['base_static'] = $globals['static_server'] . $globals['base_url'];
} else {
	$globals['base_static'] = 'http://'.get_server_name().$globals['base_url'];
}

// Use proxy and load balancer detection
if ($globals['check_behind_proxy']) {
	$globals['user_ip'] = check_ip_behind_proxy();
} elseif ($globals['behind_load_balancer']) {
	$globals['user_ip'] = check_ip_behind_load_balancer();
} else {
	$globals['user_ip'] = $_SERVER["REMOTE_ADDR"];
}

// Warn, we shoud printf "%u" because PHP on 32 bits systems fails with high unsigned numbers
$globals['user_ip_int'] = sprintf("%u", ip2long($globals['user_ip']));

$globals['now'] = time();
$globals['cache-control'] = Array();
$globals['uri'] = preg_replace('/[<>\r\n]/', '', urldecode($_SERVER['REQUEST_URI'])); // clean  it for future use
//echo "<!-- " . $globals['uri'] . "-->\n";


// For PHP < 5
if ( !function_exists('htmlspecialchars_decode') ) {
	function htmlspecialchars_decode($text) {
		return strtr($text, array_flip(get_html_translation_table(HTML_SPECIALCHARS)));
	}
}

if($_SERVER['HTTP_HOST']) {
	// Check bots
	if (preg_match('/(httpclient|bot|slurp|wget|libwww|\Wjava|\Wphp|wordpress)[\W\s0-9]/i', $_SERVER['HTTP_USER_AGENT'])) {
		$globals['bot'] = true;
	} else {
		$globals['bot'] = false;
	}

	// Check mobile/TV versions
	if (preg_match('/SymbianOS|BlackBerry|iPhone|Nintendo|Mobile|Opera Mini|\/MIDP|Portable|webOS|Kindle|Fennec/i', $_SERVER['HTTP_USER_AGENT'])
			&& ! preg_match('/ipad|tablet/i', $_SERVER['HTTP_USER_AGENT']) ) { // Don't treat iPad as mobiles
		$globals['mobile'] = 1;
	} else {
		$globals['mobile'] = 0;
	}

	// Check the user's referer.
	if( !empty($_SERVER['HTTP_REFERER'])) {
		if (preg_match('/http:\/\/'.preg_quote($_SERVER['HTTP_HOST']).'/', $_SERVER['HTTP_REFERER'])) {
			$globals['referer'] = 'local';
		} elseif (preg_match('/q=|search/', $_SERVER['HTTP_REFERER']) ) {
			$globals['referer'] = 'search';
		} else {
			$globals['referer'] = 'remote';
		}
	} else {
		$globals['referer'] = 'unknown';
	}

	// Fill server names
	// Alert, if does not work with port 443, in order to avoid standard HTTP connections to SSL port
	if($_SERVER['SERVER_PORT'] != '80' && $_SERVER['SERVER_PORT'] != 443) {
		$globals['server_name'] = strtolower($_SERVER['SERVER_NAME']) . ':' . $_SERVER['SERVER_PORT'];
	} else {
		$globals['server_name'] = strtolower($_SERVER['SERVER_NAME']);
	}
} else {
	if (!$globals['server_name']) $globals['server_name'] = 'meneame.net'; // Warn: did you put the right server name?
}

if (empty($globals['static_server_name'])) {
	if ($globals['static_server']) $globals['static_server_name'] = preg_replace('/^http:\/\//', '', $globals['static_server']);
	else $globals['static_server_name'] = $globals['server_name'];
}

// Votes' tags
$globals['negative_votes_values'] = Array ( -1 => _('irrelevante'), -2 => _('antigua'), -3 => _('cansina'), -4 => _('sensacionalista'), -5 => _('spam'), -6 => _('duplicada'), -7 => _('microblogging'), -8 => _('errónea'),  -9 => _('copia/plagio'));


// autoloaded clasess
// Should be defined after mnminclude
// and before de database
function __autoload($class) {
	static $classfiles = array(
				'Annotation' => 'annotation.php',
				'Log' => 'log.php',
				'db' => 'mysqli.php',
				'RGDB' => 'rgdb.php',
				'LCPBase' => 'LCPBase.php',
				'Link' => 'link.php',
				'LinkMobile' => 'linkmobile.php',
				'Comment' => 'comment.php',
				'CommentMobile' => 'blog.php',
				'Vote' => 'votes.php',
				'Annotation' => 'annotation.php',
				'Blog' => 'blog.php',
				'Post' => 'post.php',
				'PrivateMessage' => 'private.php',
				'UserAuth' => 'login.php',
				'User' => 'user.php',
				'BasicThumb' => 'webimages.php',
				'WebThumb' => 'webimages.php',
				'HtmlImages' => 'webimages.php',
				'Trackback' => 'trackback.php',
				'Upload' => 'upload.php',
				'Media' => 'media.php',
				'S3' => 'S3.php',
	);

	if (isset($classfiles[$class]) && file_exists(mnminclude.$classfiles[$class])) {
		require_once(mnminclude.$classfiles[$class]);
	} else {
		// Build the include for "standards" frameworks wich uses path1_path2_classnameclassName
		$filePath = str_replace('_', DIRECTORY_SEPARATOR, $class) . '.php';
		$includePaths = explode(PATH_SEPARATOR, get_include_path());
		foreach($includePaths as $includePath){
			if(file_exists($includePath . DIRECTORY_SEPARATOR . $filePath)){
				require_once($filePath);
				return;
			}
		}
		/* "try"  to include $class.php file if exists */
		@include_once($class.".php");
	}
}

function haanga_bootstrap()
{
	/* bootstrap function, load our custom tags/filter */
	require mnminclude.'haanga_mnm.php';
}

/* Load template engine here */
$config = array(
	'template_dir' => mnmpath.'/'.$globals['haanga_templates'],
	'autoload'	 => FALSE, /* Don't use Haanga's autoloader */
	'bootstrap'	=> 'haanga_bootstrap',
	'compiler' => array( /* opts for the tpl compiler */
		/* Avoid use if empty($var) */
		'if_empty' => FALSE,
		/* we're smart enought to know when escape :-) */
		'autoescape' => FALSE,
		/* let's save bandwidth */
		'strip_whitespace' => TRUE,
		/* call php functions from the template */
		'allow_exec'  => TRUE,
		/* global $global, $current_user for all templates */
		'global' => array('globals', 'current_user'),
	),
	'use_hash_filename' => FALSE, /* don't use hash filename for generated php */
);

// Allow full or relative pathname for the cache (i.e. /var/tmp or cache)
if ($globals['haanga_cache'][0] == '/') {
	$config['cache_dir'] =  $globals['haanga_cache'] .'/Haanga/'.$_SERVER['SERVER_NAME'];
} else {
	$config['cache_dir'] = mnmpath.'/'.$globals['haanga_cache'] .'/Haanga/'.$_SERVER['SERVER_NAME'];
}

/*** Disabled, it's a little faster checking filetime directly
if (is_callable('xcache_isset')) {
	// don't check for changes in the template for the next 15 seconds
	$config['check_ttl'] = 15;
	$config['check_get'] = 'xcache_get';
	$config['check_set'] = 'xcache_set';
}
*/

require mnminclude.'Haanga.php';

Haanga::configure($config);

// Allows a script to define to use the alternate server
if (isset($globals['alternate_db_server']) && !empty($globals['alternate_db_servers'][$globals['alternate_db_server']])) {
	$db = new RGDB($globals['db_user'], $globals['db_password'], $globals['db_name'], $globals['alternate_db_servers'][$globals['alternate_db_server']]);
} else {
	$db = new RGDB($globals['db_user'], $globals['db_password'], $globals['db_name'], $globals['db_server']);
	$db->persistent = $globals['mysql_persistent'];
}

?>
